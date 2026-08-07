<?php

namespace App\Policies;

use App\Models\ServiceSetting;
use App\Models\User;

class ServiceSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_service_setting');
    }

    public function view(User $user, ServiceSetting $setting): bool
    {
        if (!$user->can('view_any_service_setting')) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->service_setting_id === $setting->id;
    }

    public function create(User $user): bool
    {
        return $user->can('edit_service_setting');
    }

    public function update(User $user, ServiceSetting $setting): bool
    {
        if (!$user->can('edit_service_setting')) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->service_setting_id === $setting->id;
    }

    public function delete(User $user, ServiceSetting $setting): bool
    {
        if (!$user->can('edit_service_setting')) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->service_setting_id === $setting->id;
    }
}
