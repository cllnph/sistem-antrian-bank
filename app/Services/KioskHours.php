<?php

namespace App\Services;

class KioskHours
{
    public static function isOpen(): bool
    {
        if (app()->environment(['local', 'testing']) && config('kiosk.test_mode_enabled', false)) {
            return true;
        }

        $now = now('Asia/Jakarta');

        return $now->isWeekday() && $now->hour >= 4 && $now->hour < 18;
    }
}
