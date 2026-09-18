<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! app()->runningInConsole()) {
            @ini_set('max_execution_time', '120');
        }

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event) {
            if ($event->user instanceof \App\Models\User && $event->user->is_active) {
                $event->user->timestamps = false;
                $event->user->update(['last_login_at' => now()]);
            }
        });

        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\ServiceSetting::observe(\App\Observers\ServiceSettingObserver::class);
    }
}
