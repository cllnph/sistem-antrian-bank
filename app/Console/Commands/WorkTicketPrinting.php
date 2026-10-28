<?php

namespace App\Console\Commands;

use App\Services\Printing\PrintStorage;
use App\Services\Printing\PrintWorkerHealth;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
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
            $recovering = true;
            $backoff = 2;
            do {
                try {
                    if ($recovering) {
                        $health->recoverInterrupted();
                        $recovering = false;
                    }
                    $health->beat();
                    $health->expireWaiting();
                    $this->callSilent('queue:work', ['connection' => 'printing', '--queue' => 'ticket-printing', '--once' => true, '--tries' => 1, '--timeout' => 30, '--sleep' => 0]);
                    $health->beat();
                    $backoff = 2;
                } catch (QueryException $error) {
                    // DB outages are temporary; never replay a possibly submitted job.
                    $recovering = true;
                    if ($this->option('once')) {
                        throw $error;
                    }
                    sleep($backoff);
                    $backoff = min(30, $backoff * 2);
                }
                if (! $this->option('once')) {
                    usleep(500000);
                }
            } while (! $this->option('once'));
        } finally {
            try {
                DB::table('printer_workers')->where('id', 'ticket-printing')->where('process_id', getmypid())->delete();
            } catch (\Throwable) {
                // An unavailable DB must not mask the original failure or prevent releasing the lock.
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return self::SUCCESS;
    }
}
