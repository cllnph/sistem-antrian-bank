<?php

namespace App\Observers;

use App\Models\ServiceSetting;
use App\Services\NotificationService;
use App\Services\IntegrityRules;
use App\Enums\QueueStatus;
use Illuminate\Validation\ValidationException;

class ServiceSettingObserver
{
    public function saving(ServiceSetting $setting): void
    {
        if (! in_array($setting->service, ['cs', 'teller', 'umum'], true)) {
            throw ValidationException::withMessages(['service' => 'Jenis layanan harus CS, teller, atau umum.']);
        }
        if ($setting->exists && $setting->isDirty(['branch_id', 'service']) && IntegrityRules::deletionReason($setting)) {
            throw ValidationException::withMessages(['service' => 'Kantor dan jenis layanan loket yang sudah digunakan tidak dapat diubah.']);
        }
        if ($setting->exists && $setting->isDirty('is_active') && ! $setting->is_active
            && $setting->queues()->withoutGlobalScopes()->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])->exists()) {
            throw ValidationException::withMessages(['is_active' => 'Selesaikan pelayanan aktif sebelum menutup loket.']);
        }
        $actor = auth()->user();
        if ($actor?->hasRole('admin_cabang')) {
            abort_unless($actor->branch_id && (int) $setting->branch_id === (int) $actor->branch_id, 403);
            if ($setting->exists) {
                abort_unless((int) $setting->getOriginal('branch_id') === (int) $actor->branch_id, 403);
            }
        }
    }

    public function deleting(ServiceSetting $setting): void
    {
        IntegrityRules::assertDeletable($setting);
    }

    /**
     * Handle the ServiceSetting "created" event.
     */
    public function created(ServiceSetting $setting): void
    {
        if (auth()->check()) {
            NotificationService::sendCounterStatusNotification($setting->counter_name, (bool) $setting->is_active, auth()->user(), 'created');
        }
    }

    /**
     * Handle the ServiceSetting "updated" event.
     */
    public function updated(ServiceSetting $setting): void
    {
        if (auth()->check()) {
            NotificationService::sendCounterStatusNotification($setting->counter_name, (bool) $setting->is_active, auth()->user(), 'updated');
        }
    }

    /**
     * Handle the ServiceSetting "deleted" event.
     */
    public function deleted(ServiceSetting $setting): void
    {
        if (auth()->check()) {
            NotificationService::sendCounterStatusNotification($setting->counter_name, false, auth()->user(), 'deleted');
        }
    }
}
