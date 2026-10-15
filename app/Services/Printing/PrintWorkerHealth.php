<?php

namespace App\Services\Printing;

use App\Models\PrintJob;
use Illuminate\Support\Facades\DB;

class PrintWorkerHealth
{
    public function beat(): void
    {
        DB::table('printer_workers')->updateOrInsert(['id' => 'ticket-printing'], ['process_id' => getmypid(), 'heartbeat_at' => now()]);
    }

    public function assertHealthy(): void
    {
        if (config('printing.require_worker') && ! DB::table('printer_workers')->where('id', 'ticket-printing')->where('heartbeat_at', '>=', now()->subSeconds(config('printing.worker_ttl')))->exists()) {
            throw new \RuntimeException('Layanan cetak belum siap. Hubungi petugas sebelum mengambil tiket.');
        }
    }

    public function expireWaiting(): void
    {
        PrintJob::where('status', 'queued')->where('created_at', '<=', now()->subSeconds(config('printing.job_ttl')))->update(['status' => 'expired', 'finished_at' => now(), 'failure_code' => 'queue_expired']);
        // A killed worker cannot run finally. Remove abandoned PDFs after the native 20s deadline.
        foreach (glob(PrintStorage::directory().'/*.pdf') ?: [] as $pdf) {
            if (filemtime($pdf) < time() - 60) {
                @unlink($pdf);
            }
        }
    }

    /** Called only after acquiring the exclusive worker lock. Never resubmit interrupted work. */
    public function recoverInterrupted(): void
    {
        PrintJob::where('status', 'processing')->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => 'worker_interrupted']);
        $this->expireWaiting();
        foreach (glob(PrintStorage::directory().'/*.pdf') ?: [] as $pdf) {
            @unlink($pdf);
        }
    }
}
