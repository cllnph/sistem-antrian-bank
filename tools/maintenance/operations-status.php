<?php

use App\Models\Queue;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
echo json_encode([
    'test_mode_enabled' => config('kiosk.test_mode_enabled'),
    'branches' => DB::table('branches')->count(), 'states' => DB::table('branch_queue_states')->count(),
    'active_queues' => Queue::withoutGlobalScopes()->currentPeriod()->count(),
    'legacy_queues' => DB::table('queues')->whereNull('period_id')->count(),
    'branchless_queues' => DB::table('queues')->whereNull('branch_id')->count(),
    'inflight_server' => DB::table('print_jobs')->where('transport', 'server')->whereIn('status', ['queued', 'processing'])->count(),
    'agents' => DB::table('print_agents')->count(), 'bindings' => DB::table('kiosk_print_bindings')->count(),
    'scheduler' => DB::table('operation_scheduler_status')->first(),
    'resets' => DB::table('branch_queue_states')->join('branches', 'branches.id', '=', 'branch_queue_states.branch_id')
        ->get(['branches.code', 'branch_queue_states.next_reset_at', 'branch_queue_states.last_reset_at']),
], JSON_PRETTY_PRINT)."\n";
