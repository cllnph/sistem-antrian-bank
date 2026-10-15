<?php

namespace App\Services\Printing;

use App\Jobs\SubmitTicketPrint;
use App\Models\Branch;
use App\Models\PrintJob;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue as QueueBus;
use Illuminate\Support\Str;

class TicketPrinting
{
    public function __construct(private PrinterConfiguration $configuration, private PrinterGateway $gateway, private PrintWorkerHealth $health) {}

    public function ready(Branch $branch): array
    {
        $configuration = $this->configuration->forBranch($branch);
        $this->health->assertHealthy();
        $this->gateway->assertAvailable($configuration['printer']);

        return $configuration;
    }

    public function owner(Request $request): string
    {
        return hash_hmac('sha256', $request->session()->getId(), config('app.key'));
    }

    public function assertReplay(PrintJob $job, Request $request, int $branchId, string $service): void
    {
        abort_unless(hash_equals($job->owner_hash, $this->owner($request)), 403);
        abort_unless((int) $job->snapshot['branch_id'] === $branchId && $job->snapshot['service'] === $service && ! $job->original_job_id, 409, 'Permintaan tiket tidak cocok.');
    }

    public function snapshot(Queue $ticket, array $configuration): array
    {
        $branch = $ticket->branch;
        $service = $ticket->serviceSetting;

        return [
            'ticket' => $ticket->number, 'id' => $ticket->id,
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
        $job = PrintJob::create([
            'id' => $requestId, 'queue_id' => $ticket->id, 'owner_hash' => $owner,
            'snapshot' => $snapshot ?? $this->snapshot($ticket, $configuration),
            'printer_name' => $configuration['printer'], 'status' => 'queued',
            'original_job_id' => $original?->id, 'actor_id' => $actor?->id,
        ]);
        QueueBus::connection('printing')->push(new SubmitTicketPrint($job->id), '', 'ticket-printing');

        return $job;
    }

    public function reprint(Queue $ticket, User $actor): PrintJob
    {
        Gate::forUser($actor)->authorize('print', $ticket);
        $configuration = $this->ready($ticket->branch);

        return DB::transaction(function () use ($ticket, $actor, $configuration) {
            Queue::withoutGlobalScopes()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
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
            'waiting' => Queue::withoutGlobalScopes()->where('branch_id', $receipt['branch_id'])->whereHas('serviceSetting', fn ($query) => $query->where('service', $receipt['service']))->whereDate('created_at', today())->where('status', 'waiting')->count(),
            'request_id' => $job->id, 'print_job' => $job->publicStatus(),
        ];
    }
}
