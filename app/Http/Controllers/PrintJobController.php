<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Services\Printing\PrintWorkerHealth;
use App\Services\Printing\TicketPrinting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintJobController extends Controller
{
    public function show(Request $request, PrintJob $printJob, TicketPrinting $printing, PrintWorkerHealth $health): JsonResponse
    {
        abort_unless(hash_equals($printJob->owner_hash, $printing->owner($request)) || $request->user()?->can('print', $printJob->ticket), 403);
        $health->expireWaiting();
        // Status can become uncertain while the supervisor is restarting.
        if ($printJob->status === 'processing' && $printJob->started_at?->lt(now()->subSeconds(45))) {
            PrintJob::whereKey($printJob->id)->where('status', 'processing')->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => 'worker_unresponsive']);
        }

        return response()->json(['ticket' => $printJob->snapshot['ticket'], 'print_job' => $printJob->fresh()->publicStatus()])->header('Cache-Control', 'no-store');
    }
}
