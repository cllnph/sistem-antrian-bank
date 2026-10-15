<?php

namespace App\Policies;

use App\Models\Queue;
use App\Models\User;

class QueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_queue');
    }

    public function view(User $user, Queue $queue): bool
    {
        return $user->can('view_any_queue') && $this->sameService($user, $queue);
    }

    public function create(User $user): bool
    {
        return $user->can('create_queue');
    }

    public function update(User $user, Queue $queue): bool
    {
        return $user->can('edit_queue') && $this->sameService($user, $queue)
            && $user->serviceSetting?->is_active && $user->branch?->is_active
            && (! in_array($queue->status, [\App\Enums\QueueStatus::CALLED, \App\Enums\QueueStatus::SERVING], true)
                || (int) $queue->served_by === (int) $user->id);
    }

    public function delete(User $user, Queue $queue): bool
    {
        return false;
    }

    public function print(User $user, Queue $queue): bool
    {
        return $user->is_active && $user->can('edit_queue') && $this->sameService($user, $queue)
            && $user->branch?->is_active && $user->serviceSetting?->is_active;
    }

    private function sameService(User $user, Queue $queue): bool
    {
        return $user->branch_id && (int) $user->branch_id === (int) $queue->branch_id
            && $user->serviceSetting && $queue->serviceSetting
            && (int) $user->serviceSetting->branch_id === (int) $user->branch_id
            && $user->serviceSetting->service === $queue->serviceSetting->service;
    }
}
