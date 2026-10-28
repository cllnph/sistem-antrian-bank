<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Str;

class QueueResetService
{
    public static function reset(User $actor, ?int $branchId = null, ?string $service = null, ?string $expectedPeriod = null, ?string $requestId = null): int
    {
        abort_unless($actor->is_active && $actor->hasRole('admin') && $actor->can('reset_queues'), 403);
        abort_unless($branchId !== null, 422, 'Pilih satu cabang untuk reset.');
        abort_if($service !== null, 422, 'Reset berlaku untuk seluruh layanan dalam satu periode cabang.');
        $branch = Branch::findOrFail($branchId);
        $periods = app(QueuePeriods::class);
        $state = $periods->ensure($branch);

        return $periods->reset($branch, $actor, $expectedPeriod ?? $state->period_id, $requestId ?? (string) Str::uuid());
    }
}
