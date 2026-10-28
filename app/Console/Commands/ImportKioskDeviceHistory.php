<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\KioskDeviceHistory;
use Illuminate\Console\Command;

class ImportKioskDeviceHistory extends Command
{
    protected $signature = 'kiosk:import-device-history';

    protected $description = 'Pulihkan riwayat perangkat dari sesi permintaan dan log tiket yang tersimpan';

    public function handle(KioskDeviceHistory $history): int
    {
        $imported = 0;
        PrintJob::whereNull('original_job_id')->orderBy('id')->chunkById(100, function ($jobs) use ($history, &$imported) {
            foreach ($jobs as $job) {
                $imported += (int) $history->recover($job);
            }
        });
        $this->info("Riwayat {$imported} tiket dipulihkan. Data lama dikelompokkan per sesi browser, bukan berdasarkan IP.");

        return self::SUCCESS;
    }
}
