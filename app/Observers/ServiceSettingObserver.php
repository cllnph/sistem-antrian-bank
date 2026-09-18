<?php

namespace App\Observers;

use App\Models\ServiceSetting;
use App\Services\NotificationService;

class ServiceSettingObserver
{
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
