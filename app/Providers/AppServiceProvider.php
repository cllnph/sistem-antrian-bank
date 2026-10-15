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
        $this->app->singleton(\App\Services\Printing\PrinterGateway::class, function () {
            // Only a deliberately isolated testing server may replace the native printer.
            if (app()->environment('testing') && env('PRINT_TEST_FAKE', false) === true && config('database.default') === 'sqlite') {
                return new \App\Services\Printing\FakePrinterGateway;
            }
            return new \App\Services\Printing\WindowsPrinterGateway;
        });
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
            if (! $user->is_active) {
                return false;
            }
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event) {
            if ($event->user instanceof \App\Models\User && $event->user->is_active) {
                // Login metadata is a system update, not a user-management action.
                \App\Models\User::withoutTimestamps(fn () => $event->user
                    ->forceFill(['last_login_at' => now()])->saveQuietly());
            }
        });

        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\ServiceSetting::observe(\App\Observers\ServiceSettingObserver::class);
        \App\Models\Branch::observe(\App\Observers\BranchObserver::class);
        \App\Models\Queue::observe(\App\Observers\QueueObserver::class);
    }
}
