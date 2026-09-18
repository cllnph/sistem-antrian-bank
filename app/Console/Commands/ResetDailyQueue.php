<?php

namespace App\Console\Commands;

use App\Models\Queue;
use Illuminate\Console\Command;

class ResetDailyQueue extends Command
{
    protected $signature = 'queue:reset';
    protected $description = 'Reset seluruh antrean uji coba untuk memulai hari baru (dimulai dari CS-001 & T-001)';

    public function handle()
    {
        Queue::truncate();
        $this->info('✅ Seluruh antrean berhasil di-reset! Nomor antrean Kiosk kembali ke CS-001 & T-001.');
        return 0;
    }
}
