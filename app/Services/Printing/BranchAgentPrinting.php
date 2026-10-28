<?php

namespace App\Services\Printing;

use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Models\Queue;
use App\Services\QueuePeriods;
use Illuminate\Support\Facades\DB;

class BranchAgentPrinting
{
    public function forBranch(Branch $branch, ?string $agentId): array
    {
        $agent = $agentId ? PrintAgent::whereKey($agentId)->where('branch_id', $branch->id)->first() : null;
        if (! $agent || ! $agent->enabled || ! $branch->is_active || ! $agent->isReady()) {
            throw new \RuntimeException('Printer atau penghubung yang dipasangkan ke PC ini belum siap, dinonaktifkan, atau dihapus. Periksa penghubung atau pilih Printer PC ini.');
        }

        return ['agent_id' => $agent->id, 'device_id' => $agent->device_id, 'printer' => $agent->printer_name];
    }

    public function sweep(): void
    {
        PrintJob::whereNotNull('print_agent_id')->where('status', 'processing')->where('lease_expires_at', '<=', now())
            ->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => 'agent_lease_expired']);
    }

    public function claim(PrintAgent $agent): ?array
    {
        abort_unless($agent->enabled && $agent->isReady() && $agent->branch?->is_active, 409, 'Penghubung atau kantor belum siap.');
        app(QueuePeriods::class)->ensure($agent->branch);
        $this->sweep();
        app(PrintWorkerHealth::class)->expireWaiting();

        return DB::transaction(function () use ($agent) {
            // Recheck under the same lock used by edit, revoke and delete.
            $branch = Branch::whereKey($agent->branch_id)->lockForUpdate()->firstOrFail();
            $state = app(QueuePeriods::class)->lockedState($branch);
            abort_unless($state->next_reset_at->isFuture(), 409, 'Reset cabang sedang berlangsung.');
            $authenticated = $agent;
            $agent = PrintAgent::whereKey($agent->id)->lockForUpdate()->first();
            abort_unless($agent && $agent->enabled && $agent->isReady() && $agent->branch?->is_active
                && $agent->token_hash === $authenticated->token_hash && $agent->device_id === $authenticated->device_id, 409);
            if (PrintJob::where('print_agent_id', $agent->id)->where('status', 'processing')->exists()) {
                return null;
            }
            $job = PrintJob::where('print_agent_id', $agent->id)->where('status', 'queued')
                ->whereIn('queue_id', Queue::withoutGlobalScopes()->currentPeriod()->select('id'))
                ->where('created_at', '>', now()->subSeconds(config('printing.job_ttl')))->oldest()->lockForUpdate()->first();
            if (! $job) {
                return null;
            }
            $claim = bin2hex(random_bytes(32));
            $job->update(['status' => 'processing', 'started_at' => now(), 'lease_expires_at' => now()->addSeconds(config('printing.agent_lease')), 'claim_hash' => hash('sha256', $claim)]);

            return ['id' => $job->id, 'ticket' => $job->snapshot['ticket'], 'printer_name' => $job->printer_name,
                'claim_token' => $claim, 'pdf_url' => route('agent.pdf', $job->id),
                'lease_expires_at' => $job->lease_expires_at->toIso8601String()];
        });
    }

    public function authorizeClaim(PrintAgent $agent, PrintJob $job, ?string $claim): void
    {
        abort_unless($job->print_agent_id === $agent->id && is_string($claim) && strlen($claim) === 64
            && $job->claim_hash && hash_equals($job->claim_hash, hash('sha256', $claim)), 403);
    }

    public function pdf(PrintJob $job): string
    {
        abort_unless($job->status === 'processing' && ! $job->submission_started_at && $job->lease_expires_at->isFuture(), 409);
        $directory = PrintStorage::directory();
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $path = $directory.'/'.$job->id.'.pdf';
        $lock = fopen($directory.'/'.$job->id.'.lock', 'c');
        try {
            flock($lock, LOCK_EX);
            if (! is_file($path)) {
                $bytes = app(ReceiptRenderer::class)->render($job->snapshot);
                if (file_put_contents($path, $bytes, LOCK_EX) === false) {
                    throw new \RuntimeException('PDF tiket tidak dapat disimpan.');
                }
            }
            $hash = hash_file('sha256', $path);
            if ($job->pdf_sha256 && ! hash_equals($job->pdf_sha256, $hash)) {
                throw new \RuntimeException('Isi PDF tiket berubah.');
            }
            $job->update(['pdf_sha256' => $hash]);

            return $path;
        } catch (\Throwable $e) {
            PrintJob::whereKey($job->id)->whereNull('submission_started_at')->where('status', 'processing')
                ->update(['status' => 'failed', 'finished_at' => now(), 'failure_code' => 'agent_render_failed', 'failure_detail' => mb_substr($e->getMessage(), 0, 2000)]);
            @unlink($path);
            throw $e;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function start(PrintAgent $agent, PrintJob $job, string $hash): void
    {
        app(QueuePeriods::class)->ensure($agent->branch);
        DB::transaction(function () use ($agent, $job, $hash) {
            $branch = Branch::whereKey($agent->branch_id)->lockForUpdate()->firstOrFail();
            $state = app(QueuePeriods::class)->lockedState($branch);
            abort_unless($state->next_reset_at->isFuture(), 409, 'Reset cabang sedang berlangsung.');
            $authenticated = $agent;
            $agent = PrintAgent::whereKey($agent->id)->lockForUpdate()->first();
            $job = PrintJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
            abort_unless($agent && $agent->enabled && ! $agent->revoked && $agent->branch?->is_active
                && $agent->token_hash === $authenticated->token_hash && $agent->device_id === $authenticated->device_id
                && $job->print_agent_id === $agent->id && $job->printer_name === $agent->printer_name
                && (int) $job->snapshot['branch_id'] === (int) $agent->branch_id, 409);
            abort_unless(Queue::withoutGlobalScopes()->currentPeriod()->whereKey($job->queue_id)->exists(), 410, 'Periode tiket telah berakhir.');
            abort_unless($job->pdf_sha256 && hash_equals($job->pdf_sha256, $hash), 409, 'PDF tidak cocok.');
            // An ambiguous start response must NOT be retried as permission to print.
            abort_unless(PrintJob::whereKey($job->id)->where('status', 'processing')->whereNull('submission_started_at')
                ->where('lease_expires_at', '>', now())->update(['submission_started_at' => now()]), 409, 'Pengiriman sudah dimulai atau kedaluwarsa.');
        });
    }

    public function complete(PrintJob $job, string $status, ?string $code): void
    {
        DB::transaction(function () use ($job, $status, $code) {
            $locked = PrintJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, ['submitted', 'failed', 'unknown', 'expired'], true)) {
                abort_unless($locked->status === $status, 409);

                return;
            }
            abort_unless($locked->status === 'processing', 409);
            abort_if($status === 'submitted' && ! $locked->submission_started_at, 409);
            // Once native submission might have begun, failure means unknown.
            abort_if($status === 'failed' && $locked->submission_started_at, 409);
            $locked->update(['status' => $status, 'finished_at' => now(), 'failure_code' => $code]);
        });
        @unlink(PrintStorage::directory().'/'.$job->id.'.pdf');
        @unlink(PrintStorage::directory().'/'.$job->id.'.lock');
    }
}
