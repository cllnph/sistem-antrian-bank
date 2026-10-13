<?php

namespace App\Services;

class KioskHours
{
    public static function isOpen(): bool
    {
        $now = now('Asia/Jakarta');

        return $now->isWeekday() && $now->hour >= 4 && $now->hour < 18;
    }
}
