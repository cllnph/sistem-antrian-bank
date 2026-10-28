<?php

namespace App\Services\Printing;

use App\Jobs\SubmitTicketPrint;
use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\User;
use App\Services\KioskDeviceHistory;
use App\Services\QueuePeriods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue as QueueBus;
use Illuminate\Support\Str;

class TicketPrinting
{
    public function __construct(private PrinterConfiguration $configuration, private PrinterGateway $gateway, private PrintWorkerHealth $health) {}

    public function ready(Branch $branch, ?Request $request = null): array
    {
        $selection = app(KioskPrintRouting::class)->selection($branch, $request ?? request());
        if ($selection['transport'] === 'browser') {
            return ['paper' => ReceiptPaper::dimensions($selection['paper_profile']), 'printer' => 'Printer PC ini (dialog browser)', 'transport' => 'browser'];
        }
        if ($selection['transport'] === 'agent') {
            if (! ($selection['verified'] ?? false)) {
                throw new \RuntimeException('Pemasangan PC berubah. Jalankan Buka Kiosk dari installer pada PC printer, atau minta admin memilih Cetak lokal PC ini pada menu Cetak & Printer cabang menggunakan browser ini.');
            }
            $agent = app(BranchAgentPrinting::class)->forBranch($branch, $selection['agent_id']);
            if ($agent['device_id'] !== $selection['proof_device_id']) {
                throw new \RuntimeException('Pemasangan penghubung berubah. Buka kembali shortcut Buka Kiosk pada PC printer.');
            }

            return ['paper' => ReceiptPaper::dimensions($selection['paper_profile']), 'paper_profile' => $selection['paper_profile'], ...$agent, 'transport' => 'agent'];
        }
        abort_unless($selection['transport'] === 'server', 409, 'Pilihan cetak tidak valid.');
        $configuration = $this->configuration->forBranch($branch);
        $this->health->assertHealthy();
        $this->gateway->assertAvailable($configuration['printer']);

        return [...$configuration, 'transport' => 'server'];
    }

    public function owner(Request $request): string
    {
        $device = app(KioskDeviceHistory::class)->device($request);

        return hash_hmac('sha256', 'kiosk-device:'.$device->id, config('app.key'));
    }

    public function isOwner(PrintJob $job, Request $request): bool
    {
        $owner = match ($job->owner_type) {
            'device' => $this->owner($request),
            'session' => hash_hmac('sha256', $request->session()->getId(), config('app.key')),
            default => '',
        };

        return $owner !== '' && hash_equals($job->owner_hash, $owner);
    }

    public function assertReplay(PrintJob $job, Request $request, int $branchId, string $service): void
    {
        abort_unless($this->isOwner($job, $request), 403, 'Permintaan tiket ini berasal dari browser atau sesi lain. Nomor baru tidak dibuat otomatis.');
        app(QueuePeriods::class)->assertCurrent($job);
        abort_unless((int) $job->snapshot['branch_id'] === $branchId && $job->snapshot['service'] === $service && ! $job->original_job_id, 409, 'Permintaan tiket tidak cocok.');
    }

    public function snapshot(Queue $ticket, array $configuration): array
    {
        $branch = $ticket->branch;
        $service = $ticket->serviceSetting;

        return [
            'ticket' => $ticket->number, 'id' => $ticket->id, 'period_id' => $ticket->period_id,
            'branch_id' => $branch->id, 'branch_name' => $branch->name,
            'branch_address' => $branch->address, 'branch_phone' => $branch->type === 'Kantor Pusat' ? $branch->phone : null,
            'service' => $service->service, 'service_name' => $service->service === 'teller' ? 'TELLER' : 'CUSTOMER SERVICE',
            'counter_name' => $service->counter_name,
            'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
            'date_formatted' => $ticket->created_at->timezone('Asia/Jakarta')->translatedFormat('l, d F Y').' · '.$ticket->created_at->format('H:i').' WIB',
            'paper' => $configuration['paper'],
            'logo' => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/bank-dp-taspen-logo.png'))),
        ];
    }

    /** Called inside the SAME database transaction as the ticket; queue insertion is atomic. */
    public function enqueue(Queue $ticket, string $requestId, string $owner, array $configuration, ?array $snapshot = null, ?PrintJob $original = null, ?User $actor = null): PrintJob
    {
        $transport = $configuration['transport'];
        if ($agentId = $configuration['agent_id'] ?? null) {
            $agent = PrintAgent::whereKey($agentId)->where('branch_id', $ticket->branch_id)->lockForUpdate()->first();
            abort_unless($agent && $agent->enabled && $agent->isReady() && $agent->printer_name === $configuration['printer']
                && $agent->device_id === ($configuration['device_id'] ?? null)
                && ($agent->paper_profile ?? 'thermal80') === ($configuration['paper_profile'] ?? null), 409, 'Penghubung atau kertas berubah saat mengambil tiket. Periksa tujuan, lalu tekan layanan yang sama.');
        }
        $job = PrintJob::create([
            'id' => $requestId, 'queue_id' => $ticket->id, 'owner_hash' => $owner, 'owner_type' => $actor ? 'staff' : 'device',
            'snapshot' => $snapshot ?? $this->snapshot($ticket, $configuration),
            'printer_name' => $configuration['printer'], 'status' => $transport === 'browser' ? 'browser_ready' : 'queued', 'transport' => $transport,
            'original_job_id' => $original?->id, 'actor_id' => $actor?->id,
            'print_agent_id' => $agentId,
        ]);
        if ($transport === 'server') {
            QueueBus::connection('printing')->push(new SubmitTicketPrint($job->id), '', 'ticket-printing');
        }

        return $job;
    }

    public function reprint(Queue $ticket, User $actor, ?Request $request = null): PrintJob
    {
        Gate::forUser($actor)->authorize('print', $ticket);
        app(QueuePeriods::class)->ensure($ticket->branch);
        $configuration = $this->ready($ticket->branch, $request);

        return DB::transaction(function () use ($ticket, $actor, $configuration) {
            Branch::whereKey($ticket->branch_id)->lockForUpdate()->firstOrFail();
            abort_unless(Queue::withoutGlobalScopes()->currentPeriod()->whereKey($ticket->id)->lockForUpdate()->first(), 410, 'Periode tiket telah berakhir.');
            abort_if(PrintJob::where('queue_id', $ticket->id)->whereIn('status', ['queued', 'processing'])->exists(), 409, 'Tiket ini masih dalam proses cetak.');
            $original = PrintJob::where('queue_id', $ticket->id)->whereNull('original_job_id')->oldest()->first();
            $job = $this->enqueue($ticket, (string) Str::uuid(), 'staff:'.$actor->id, $configuration, $original?->snapshot, $original, $actor);
            QueueLog::create(['queue_id' => $ticket->id, 'branch_id' => $ticket->branch_id, 'actor_id' => $actor->id, 'action' => 'print_requested', 'payload' => ['print_job_id' => $job->id, 'original_job_id' => $original?->id]]);

            return $job;
        });
    }

    public function response(PrintJob $job): array
    {
        $receipt = $job->snapshot;
        unset($receipt['logo'], $receipt['paper']);

        return ['success' => true, ...$receipt,
            'verification_url' => route('queue.verify', ['queueId' => $job->queue_id]),
            'waiting' => Queue::withoutGlobalScopes()->where('branch_id', $receipt['branch_id'])->whereHas('serviceSetting', fn ($query) => $query->where('service', $receipt['service']))->currentPeriod()->where('status', 'waiting')->count(),
            'request_id' => $job->id, 'print_job' => $job->publicStatus(),
        ];
    }
}
