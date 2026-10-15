<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueResetService
{
    public static function reset(User $actor, ?int $branchId = null, ?string $service = null): int
    {
        abort_unless($actor->is_active && $actor->can('reset_queues'), 403);
        if (! $actor->hasRole('admin')) {
            abort_unless($actor->branch_id && ($branchId === null || $branchId === (int) $actor->branch_id), 403);
            $branchId = (int) $actor->branch_id;
        }
        if ($branchId !== null) {
            Branch::findOrFail($branchId);
        }
        if ($service !== null && ! in_array($service, ['cs', 'teller', 'umum'], true)) {
            throw ValidationException::withMessages(['service' => 'Jenis layanan tidak valid.']);
        }

        return DB::transaction(function () use ($actor, $branchId, $service): int {
            $queues = Queue::withoutGlobalScopes()
                ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
                ->when($service !== null, fn ($query) => $query->whereHas('serviceSetting', fn ($settings) => $settings->where('service', $service)))
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED, QueueStatus::SERVING])
                ->lockForUpdate()->get();

            foreach ($queues as $queue) {
                $previous = $queue->status->value;
                $queue->update(['status' => QueueStatus::SKIPPED]);
                QueueLog::create([
                    'queue_id' => $queue->id, 'branch_id' => $queue->branch_id,
                    'actor_id' => $actor->id, 'action' => 'reset',
                    'payload' => ['previous_status' => $previous],
                ]);
            }

            return $queues->count();
        });
    }
}
