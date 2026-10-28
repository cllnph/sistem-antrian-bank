<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchQueueState;
use App\Models\PrintJob;
use App\Models\User;
use App\Services\Printing\PrintStorage;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueuePeriods
{
    public function ensure(Branch $branch): BranchQueueState
    {
        $state = DB::transaction(function () use ($branch) {
            $locked = Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $state = $this->lockedState($locked);
            if ($state->next_reset_at->lte(now())) {
                $boundary = app(BranchSchedule::class)->nextReset($locked->reset_time ?? '00:00')->subDay();
                $this->rotate($locked, $state, $boundary);
            }

            return $state;
        }, 3);
        if ($state->last_reset_at) {
            $this->purge($branch->id);
        }

        return $state;
    }

    /** Caller holds the branch row lock. All allocation/reset/start paths lock branch first. */
    public function lockedState(Branch $branch): BranchQueueState
    {
        $state = BranchQueueState::whereKey($branch->id)->lockForUpdate()->first();
        if ($state) {
            return $state;
        }
        $state = BranchQueueState::create(['branch_id' => $branch->id, 'period_id' => (string) Str::uuid(),
            'period_started_at' => today(), 'next_reset_at' => app(BranchSchedule::class)->nextReset($branch->reset_time ?? '00:00')]);
        DB::table('queues')->where('branch_id', $branch->id)->whereNull('period_id')->whereDate('created_at', today())->update(['period_id' => $state->period_id]);
        foreach (['cs', 'teller', 'umum'] as $service) {
            $this->counter($branch->id, $state->period_id, $service);
        }

        return $state;
    }

    private function counter(int $branchId, string $period, string $service): object
    {
        $counter = DB::table('queue_counters')->where('branch_id', $branchId)->where('service', $service)->lockForUpdate()->first();
        if (! $counter) {
            $prefix = $this->prefix($service);
            $max = 0;
            foreach (DB::table('queues')->where('branch_id', $branchId)->where('period_id', $period)->where('number', 'like', $prefix.'-%')->pluck('number') as $number) {
                $max = max($max, (int) substr($number, strlen($prefix) + 1));
            }
            DB::table('queue_counters')->insert(['branch_id' => $branchId, 'service' => $service, 'period_id' => $period, 'sequence' => $max]);
            $counter = (object) ['period_id' => $period, 'sequence' => $max];
        }

        return $counter;
    }

    public function allocate(Branch $branch, string $service): array
    {
        $state = $this->lockedState($branch);
        abort_if($state->next_reset_at->lte(now()), 410, 'Periode antrean telah berakhir. Muat ulang untuk memulai permintaan baru.');
        $counter = $this->counter($branch->id, $state->period_id, $service);
        $number = $counter->period_id === $state->period_id ? (int) $counter->sequence + 1 : 1;
        DB::table('queue_counters')->where('branch_id', $branch->id)->where('service', $service)
            ->update(['period_id' => $state->period_id, 'sequence' => $number]);

        return ['number' => sprintf('%s-%03d', $this->prefix($service), $number), 'period_id' => $state->period_id];
    }

    public function nextNumber(Branch $branch, string $service): string
    {
        $state = BranchQueueState::findOrFail($branch->id);
        $sequence = DB::table('queue_counters')->where('branch_id', $branch->id)->where('service', $service)->where('period_id', $state->period_id)->value('sequence') ?? 0;

        return sprintf('%s-%03d', $this->prefix($service), $sequence + 1);
    }

    public function prefix(string $service): string
    {
        return match ($service) {
            'cs' => 'CS', 'teller' => 'T', 'umum' => 'U', default => throw new \InvalidArgumentException('Jenis layanan tidak valid.')
        };
    }

    public function context(Request $request, Branch $branch): string
    {
        $state = $this->ensure($branch);

        return Crypt::encryptString(json_encode(['v' => 1, 'branch_id' => $branch->id, 'period_id' => $state->period_id,
            'device_id' => app(KioskDeviceHistory::class)->device($request)->id], JSON_THROW_ON_ERROR));
    }

    public function assertContext(Request $request, Branch $branch, string $context): void
    {
        try {
            $data = json_decode(Crypt::decryptString($context), true, 8, JSON_THROW_ON_ERROR);
        } catch (\Throwable $error) {
            abort(409, 'Konteks halaman tidak berlaku. Muat ulang halaman; permintaan sebelumnya tetap diperiksa.');
        }
        abort_unless(($data['v'] ?? null) === 1 && (int) ($data['branch_id'] ?? 0) === (int) $branch->id
            && ($data['device_id'] ?? '') === app(KioskDeviceHistory::class)->device($request)->id, 403, 'Konteks tiket berasal dari browser atau cabang lain.');
        $state = $this->lockedState($branch);
        abort_unless(($data['period_id'] ?? '') === $state->period_id && $state->next_reset_at->isFuture(), 410,
            'Periode permintaan ini sudah berakhir. Nomor baru tidak dibuat otomatis.');
    }

    public function assertCurrent(PrintJob $job): void
    {
        $branch = Branch::findOrFail($job->snapshot['branch_id']);
        $state = $this->ensure($branch);
        $ticket = DB::table('queues')->where('id', $job->queue_id)->first(['period_id']);
        $legacyBeforeFirstReset = $ticket && $ticket->period_id === null && ! $state->last_reset_at;
        abort_unless($ticket && ($ticket->period_id === $state->period_id || $legacyBeforeFirstReset), 410, 'Periode tiket sudah berakhir. Struk tidak lagi tersedia.');
    }

    public function expiredRequest(string $id, Request $request, ?int $branchId = null, ?string $service = null): void
    {
        $proof = DB::table('ticket_request_tombstones')->where('request_hash', hash('sha256', $id))->where('expires_at', '>', now())->first();
        if (! $proof) {
            return;
        }
        $owner = match ($proof->owner_type) {
            'device' => hash_hmac('sha256', 'kiosk-device:'.app(KioskDeviceHistory::class)->device($request)->id, config('app.key')),
            'session' => hash_hmac('sha256', $request->session()->getId(), config('app.key')),
            'staff' => 'staff:'.$request->user()?->id,
            default => '',
        };
        $staff = $request->user();
        $canInspect = $staff?->is_active && $staff->can('edit_queue') && ($staff->hasRole('admin') ||
            ((int) $staff->branch_id === (int) $proof->branch_id && $staff->serviceSetting?->service === $proof->service));
        abort_unless(($owner !== '' && hash_equals($proof->owner_hash, $owner)) || $canInspect, 403, 'Tiket ini milik browser lain.');
        abort_if(($branchId !== null && (int) $proof->branch_id !== $branchId) || ($service !== null && $proof->service !== $service), 409, 'Permintaan tiket tidak cocok.');
        abort(410, 'Periode tiket sudah berakhir. Data struk telah dibersihkan; nomor baru tidak dibuat otomatis.');
    }

    public function reset(Branch $branch, ?User $actor, string $expectedPeriod, string $requestId): int
    {
        if ($actor) {
            abort_unless($actor->is_active && $actor->hasRole('admin') && $actor->can('reset_queues'), 403);
        }
        $this->ensure($branch);
        $count = DB::transaction(function () use ($branch, $expectedPeriod, $requestId) {
            $locked = Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $state = $this->lockedState($locked);
            if ($state->last_reset_request_id === $requestId) {
                return 0;
            }
            abort_unless($state->period_id === $expectedPeriod, 409, 'Periode cabang sudah berubah. Periksa keadaan terbaru sebelum reset.');
            $count = DB::table('queues')->where('branch_id', $branch->id)->where('period_id', $state->period_id)->count();
            $this->rotate($locked, $state, now(), $requestId);

            return $count;
        }, 3);
        $this->purge($branch->id);

        return $count;
    }

    private function rotate(Branch $branch, BranchQueueState $state, CarbonInterface $boundary, ?string $requestId = null): void
    {
        $newPeriod = (string) Str::uuid();
        $oldQueueIds = DB::table('queues')->where('branch_id', $branch->id)->select('id');
        // Claimed jobs without a start permission may be cancelled; authorized submissions finish once.
        PrintJob::whereIn('queue_id', $oldQueueIds)->where(function ($query) {
            $query->where('status', 'queued')->orWhere(fn ($query) => $query->where('status', 'processing')->whereNull('submission_started_at'));
        })->update(['status' => 'expired', 'finished_at' => now(), 'failure_code' => 'period_reset']);
        DB::table('queue_counters')->where('branch_id', $branch->id)->update(['period_id' => $newPeriod, 'sequence' => 0]);
        $state->fill(['period_id' => $newPeriod, 'period_started_at' => $boundary, 'last_reset_at' => now(),
            'next_reset_at' => app(BranchSchedule::class)->nextReset($branch->reset_time ?? '00:00'),
            'last_reset_request_id' => $requestId, 'last_error' => null])->save();
    }

    public function purge(int $branchId): int
    {
        $files = [];
        $count = DB::transaction(function () use ($branchId, &$files) {
            Branch::whereKey($branchId)->lockForUpdate()->firstOrFail();
            $state = BranchQueueState::findOrFail($branchId);
            if (! $state->last_reset_at) {
                return 0;
            }
            $old = fn () => DB::table('queues')->where('branch_id', $branchId)
                ->where(fn ($query) => $query->whereNull('period_id')->orWhere('period_id', '!=', $state->period_id));
            PrintJob::whereIn('queue_id', $old()->select('id'))->where('status', 'processing')
                ->where(function ($query) {
                    $query->where('lease_expires_at', '<=', now())
                        ->orWhere(fn ($query) => $query->where('transport', 'server')->where('started_at', '<=', now()->subSeconds(45)))
                        ->orWhere(fn ($query) => $query->whereNull('lease_expires_at')->whereNull('started_at')->where('created_at', '<=', now()->subSeconds(90)));
                })->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => 'reset_submission_uncertain']);
            $ids = $old()->whereNotExists(fn ($query) => $query->selectRaw('1')->from('print_jobs')
                ->whereColumn('print_jobs.queue_id', 'queues.id')->where('status', 'processing'))->limit(250)->pluck('id');
            // Legacy imports may have a ticket number without a print-job relation.
            DB::table('kiosk_device_events')->where('branch_id', $branchId)->whereNotNull('ticket_number')
                ->whereNull('print_job_id')->where('observed_at', '<', $state->period_started_at)->delete();
            if ($ids->isEmpty()) {
                return 0;
            }
            $jobs = PrintJob::whereIn('queue_id', $ids)->get();
            foreach ($jobs as $job) {
                DB::table('ticket_request_tombstones')->updateOrInsert(['request_hash' => hash('sha256', $job->id)], [
                    'owner_hash' => $job->owner_hash, 'owner_type' => $job->owner_type,
                    'branch_id' => $branchId, 'service' => $job->snapshot['service'] ?? 'umum', 'expires_at' => now()->addDays(7),
                ]);
                if (Str::isUuid($job->id)) {
                    $files[] = $job->id;
                }
            }
            DB::table('kiosk_device_events')->whereIn('print_job_id', $jobs->pluck('id'))->delete();
            $requestIds = $jobs->pluck('id')->filter(fn ($id) => Str::isUuid($id));
            if ($requestIds->isNotEmpty()) {
                foreach (['jobs', 'failed_jobs'] as $table) {
                    DB::table($table)->where('queue', 'ticket-printing')->where('payload', 'like', '%SubmitTicketPrint%')
                        ->where(function ($query) use ($requestIds) {
                            foreach ($requestIds as $id) {
                                $query->orWhere('payload', 'like', '%'.$id.'%');
                            }
                        })->delete();
                }
            }
            PrintJob::whereIn('queue_id', $ids)->whereNotNull('original_job_id')->delete();
            PrintJob::whereIn('queue_id', $ids)->delete();
            DB::table('queue_logs')->whereIn('queue_id', $ids)->delete();

            return DB::table('queues')->whereIn('id', $ids)->delete();
        }, 3);
        if ($files) {
            DB::afterCommit(function () use ($files) {
                $directory = PrintStorage::directory();
                foreach ($files as $id) {
                    foreach (['pdf', 'lock'] as $extension) {
                        @unlink($directory.'/'.$id.'.'.$extension);
                    }
                }
            });
        }

        return $count;
    }
}
