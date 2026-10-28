<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\QueuePeriods;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBranchOperations extends Command
{
    protected $signature = 'branches:process-operations';

    protected $description = 'Proses jadwal reset cabang dan pembersihan data periode lama.';

    public function handle(QueuePeriods $periods): int
    {
        $failed = 0;
        foreach (Branch::cursor() as $branch) {
            try {
                $periods->ensure($branch);
                $deadline = microtime(true) + 10;
                while ($periods->purge($branch->id) === 250 && microtime(true) < $deadline) {
                    // Bound work per branch; the next minute resumes any remaining cleanup.
                }
            } catch (\Throwable $error) {
                $failed++;
                Log::error('Branch operations failed', ['branch_id' => $branch->id, 'error_type' => $error::class]);
                DB::table('branch_queue_states')->where('branch_id', $branch->id)->update(['last_error' => 'Pemrosesan gagal; periksa koneksi database dan log server.']);
            }
        }
        DB::table('ticket_request_tombstones')->where('expires_at', '<=', now())->delete();
        DB::table('operation_scheduler_status')->updateOrInsert(['id' => 1], ['heartbeat_at' => now(),
            'status' => $failed ? 'error' : 'ok', 'last_error' => $failed ? $failed.' cabang gagal diproses.' : null]);
        $this->info($failed ? 'Terdapat kegagalan pemrosesan cabang.' : 'Jadwal dan pembersihan cabang selesai.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
