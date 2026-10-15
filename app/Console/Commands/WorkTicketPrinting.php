<?php

namespace App\Console\Commands;

use App\Services\Printing\PrintStorage;
use App\Services\Printing\PrintWorkerHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WorkTicketPrinting extends Command
{
    protected $signature = 'tickets:work {--once : Process at most one job (for diagnostics)}';

    protected $description = 'Run the dedicated ticket-printing database worker without automatic resubmission';

    public function handle(PrintWorkerHealth $health): int
    {
        $directory = PrintStorage::directory();
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $lock = fopen($directory.'/worker.lock', 'c');
        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            $this->error('Worker cetak sudah berjalan.');

            return self::FAILURE;
        }
        try {
            $health->recoverInterrupted();
            do {
                $health->beat();
                $health->expireWaiting();
                $this->callSilent('queue:work', ['connection' => 'printing', '--queue' => 'ticket-printing', '--once' => true, '--tries' => 1, '--timeout' => 30, '--sleep' => 0]);
                $health->beat();
                if (! $this->option('once')) {
                    usleep(500000);
                }
            } while (! $this->option('once'));
        } finally {
            DB::table('printer_workers')->where('id', 'ticket-printing')->where('process_id', getmypid())->delete();
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return self::SUCCESS;
    }
}
