<?php

namespace App\Observers;

use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Validation\ValidationException;

class UserObserver
{
    public function saving(User $user): void
    {
        $actor = auth()->user();
        if ($actor?->hasRole('admin_cabang')) {
            abort_unless($actor->branch_id && (int) $user->branch_id === (int) $actor->branch_id, 403);
            if ($user->exists) {
                abort_unless($actor->can('update', $user), 403);
                abort_unless((int) $user->getOriginal('branch_id') === (int) $actor->branch_id, 403);
            }
        }

        if ($user->branch_id && $user->service_setting_id) {
            $matches = ServiceSetting::whereKey($user->service_setting_id)
                ->where('branch_id', $user->branch_id)->exists();

            if (! $matches) {
                throw ValidationException::withMessages([
                    'service_setting_id' => 'Loket tugas harus berada di cabang yang sama dengan akun petugas.',
                ]);
            }
        }
    }

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
