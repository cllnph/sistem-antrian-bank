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
        return $user->can('view_any_queue');
    }

    public function create(User $user): bool
    {
        return $user->can('create_queue');
    }

    public function update(User $user, Queue $queue): bool
    {
        return $user->can('edit_queue');
    }

    public function delete(User $user, Queue $queue): bool
    {
        return $user->can('delete_queue');
    }
}
