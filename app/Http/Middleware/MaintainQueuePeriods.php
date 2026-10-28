<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Services\QueuePeriods;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaintainQueuePeriods
{
    public function handle(Request $request, Closure $next)
    {
        if (Schema::hasTable('branch_queue_states')) {
            $ids = DB::table('branch_queue_states')->where('next_reset_at', '<=', now())->pluck('branch_id');
            foreach (Branch::whereIn('id', $ids)->get() as $branch) {
                app(QueuePeriods::class)->ensure($branch);
            }
        }

        return $next($request);
    }
}
