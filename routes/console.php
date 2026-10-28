<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('branches:process-operations')->everyMinute()->withoutOverlapping();

Schedule::call(fn () => app(\App\Services\Printing\PrintWorkerHealth::class)->expireWaiting())
    ->name('print-agent-housekeeping')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
