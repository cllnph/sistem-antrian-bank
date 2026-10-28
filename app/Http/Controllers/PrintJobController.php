<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PrintJob;
use App\Services\Printing\PrintWorkerHealth;
use App\Services\Printing\TicketPrinting;
use App\Services\QueuePeriods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintJobController extends Controller
{
    private function resolve(Request $request, string $id, TicketPrinting $printing): PrintJob
    {
        app(QueuePeriods::class)->expiredRequest($id, $request);
        $job = PrintJob::find($id);
        if (! $job) {
            // A reset may have committed between the proof lookup and the job lookup.
            app(QueuePeriods::class)->expiredRequest($id, $request);
            abort(404);
        }
        $ticket = $job->ticket;
        abort_unless($printing->isOwner($job, $request) || ($ticket && $request->user()?->can('print', $ticket)), 403,
            'Tiket hanya dapat dibuka dari browser pembuat atau petugas yang berwenang.');

        return $job;
    }

    public function receipt(Request $request, string $printJob, TicketPrinting $printing)
    {
        $job = $this->resolve($request, $printJob, $printing);

        return DB::transaction(function () use ($request, $job) {
            Branch::whereKey($job->snapshot['branch_id'])->lockForUpdate()->firstOrFail();
            app(QueuePeriods::class)->assertCurrent($job);
            abort_unless($job->transport === 'browser', 404);

            return response()->view('queue.receipt-browser', ['receipt' => $job->snapshot,
                'controls' => ! $request->boolean('embedded'), 'autoPrint' => $request->boolean('autoprint')])
                ->header('Cache-Control', 'private, no-store')->header('Content-Security-Policy', "frame-ancestors 'self'")
                ->header('X-Frame-Options', 'SAMEORIGIN');
        });
    }

    public function show(Request $request, string $printJob, TicketPrinting $printing, PrintWorkerHealth $health): JsonResponse
    {
        $job = $this->resolve($request, $printJob, $printing);
        $health->expireWaiting();

        return DB::transaction(function () use ($job) {
            Branch::whereKey($job->snapshot['branch_id'])->lockForUpdate()->firstOrFail();
            app(QueuePeriods::class)->assertCurrent($job);
            if ($job->transport === 'server' && $job->status === 'processing' && $job->started_at?->lt(now()->subSeconds(45))) {
                PrintJob::whereKey($job->id)->where('status', 'processing')->update(['status' => 'unknown',
                    'finished_at' => now(), 'failure_code' => 'worker_unresponsive']);
            }

            return response()->json(['ticket' => $job->snapshot['ticket'], 'print_job' => $job->fresh()->publicStatus()])->header('Cache-Control', 'no-store');
        });
    }
}
