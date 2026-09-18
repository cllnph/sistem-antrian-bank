<?php

namespace App\Observers;

use App\Models\User;
use App\Services\NotificationService;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if (auth()->check()) {
            NotificationService::sendUserAccountNotification($user, 'created', auth()->user());
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if (auth()->check()) {
            NotificationService::sendUserAccountNotification($user, 'updated', auth()->user());
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        if (auth()->check()) {
            NotificationService::sendUserAccountNotification($user, 'deleted', auth()->user());
        }
    }
}
