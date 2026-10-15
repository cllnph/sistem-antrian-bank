<?php

namespace App\Observers;

use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\IntegrityRules;
use Illuminate\Validation\ValidationException;

class UserObserver
{
    public function saving(User $user): void
    {
        if ($user->exists && $user->isDirty('is_active') && ! $user->is_active
            && (IntegrityRules::lastActiveAdmin($user) || (int) auth()->id() === (int) $user->id)) {
            throw ValidationException::withMessages(['is_active' => 'Akun sendiri dan administrator aktif terakhir tidak dapat dinonaktifkan.']);
        }
        if ($user->exists && $user->isDirty(['is_active', 'branch_id', 'service_setting_id']) && IntegrityRules::activeWork($user)) {
            throw ValidationException::withMessages(['service_setting_id' => 'Selesaikan pelayanan aktif sebelum menonaktifkan atau memindahkan petugas.']);
        }
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

    public function deleting(User $user): void
    {
        IntegrityRules::assertDeletable($user);
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
