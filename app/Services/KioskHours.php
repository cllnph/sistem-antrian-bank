<?php

namespace App\Services;

use App\Models\Branch;

class KioskHours
{
    public static function isOpen(?Branch $branch = null): bool
    {
        if ($branch) {
            return app(BranchSchedule::class)->isOpen($branch);
        }
        if (app()->environment(['local', 'testing']) && config('kiosk.test_mode_enabled', false)) {
            return true;
        }

        $now = now('Asia/Jakarta');

        return $now->isWeekday() && $now->hour >= 4 && $now->hour < 18;
    }
}
