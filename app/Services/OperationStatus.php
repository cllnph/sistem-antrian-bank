<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchQueueState;

class OperationStatus
{
    public function forBranch(Branch $branch): array
    {
        $state = BranchQueueState::findOrFail($branch->id);

        return ['is_open' => app(BranchSchedule::class)->isOpen($branch),
            'label' => app(BranchSchedule::class)->label($branch), 'period_id' => $state->period_id,
            'server_time' => now()->toIso8601String(), 'reset_time' => $branch->reset_time ?? '00:00',
            'next_reset_at' => $state->next_reset_at->toIso8601String(),
            'last_reset_at' => $state->last_reset_at?->toIso8601String()];
    }
}
