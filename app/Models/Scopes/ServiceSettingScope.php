<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ServiceSettingScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $user = auth()->user();

            if (! $user->hasRole('admin')) {
                $serviceSetting = $user->serviceSetting;
                $serviceKey = $serviceSetting?->service;

                if (! $serviceKey) {
                    $serviceKey = match (true) {
                        $user->hasRole('cs') => 'cs',
                        $user->hasRole('teller') => 'teller',
                        default => null,
                    };
                }

                if ($serviceKey) {
                    $settingIds = \App\Models\ServiceSetting::where('service', $serviceKey)->pluck('id')->toArray();
                    $builder->whereIn($model->getTable() . '.service_setting_id', $settingIds);
                } elseif ($user->service_setting_id) {
                    $builder->where($model->getTable() . '.service_setting_id', $user->service_setting_id);
                }
            }
        }
    }
}
