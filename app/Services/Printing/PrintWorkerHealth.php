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
        DB::table('print_agent_browser_pairings')->where('expires_at', '<=', now())->delete();
        app(BranchAgentPrinting::class)->sweep();
        PrintJob::whereIn('transport', ['server', 'agent'])->where('status', 'queued')->where('created_at', '<=', now()->subSeconds(config('printing.job_ttl')))->update(['status' => 'expired', 'finished_at' => now(), 'failure_code' => 'queue_expired']);
        // A killed worker cannot run finally. Remove abandoned PDFs after the native 20s deadline.
        foreach (glob(PrintStorage::directory().'/*.pdf') ?: [] as $pdf) {
            $id = pathinfo($pdf, PATHINFO_FILENAME);
            if (filemtime($pdf) < time() - 120 && ! PrintJob::whereKey($id)->where('status', 'processing')->exists()) {
                @unlink($pdf);
            }
        }
        foreach (glob(PrintStorage::directory().'/*.lock') ?: [] as $lock) {
            if (basename($lock) !== 'worker.lock' && @filemtime($lock) < time() - 120
                && ! PrintJob::whereKey(pathinfo($lock, PATHINFO_FILENAME))->where('status', 'processing')->exists()) {
                @unlink($lock);
            }
        }
        foreach (glob(storage_path('app/private/agent-installers/*.zip')) ?: [] as $installer) {
            if (@filemtime($installer) < time() - 900) {
                @unlink($installer);
            }
        }
    }

    /** Called only after acquiring the exclusive worker lock. Never resubmit interrupted work. */
    public function recoverInterrupted(): void
    {
        PrintJob::where('transport', 'server')->where('status', 'processing')->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => 'worker_interrupted']);
        $this->expireWaiting();
        foreach (glob(PrintStorage::directory().'/*.pdf') ?: [] as $pdf) {
            if (! PrintJob::whereKey(pathinfo($pdf, PATHINFO_FILENAME))->whereNotNull('print_agent_id')->where('status', 'processing')->exists()) {
                @unlink($pdf);
            }
        }
    }
}
