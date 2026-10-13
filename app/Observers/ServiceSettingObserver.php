<?php

namespace App\Observers;

use App\Models\ServiceSetting;
use App\Services\NotificationService;

class ServiceSettingObserver
{
    public function saving(ServiceSetting $setting): void
    {
        $actor = auth()->user();
        if ($actor?->hasRole('admin_cabang')) {
            abort_unless($actor->branch_id && (int) $setting->branch_id === (int) $actor->branch_id, 403);
            if ($setting->exists) {
                abort_unless((int) $setting->getOriginal('branch_id') === (int) $actor->branch_id, 403);
            }
        }
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
