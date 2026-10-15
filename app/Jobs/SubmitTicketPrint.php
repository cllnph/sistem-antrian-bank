<?php

namespace App\Jobs;

use App\Models\PrintJob;
use App\Services\Printing\PrinterGateway;
use App\Services\Printing\PrintOutcomeUnknown;
use App\Services\Printing\PrintStorage;
use App\Services\Printing\ReceiptRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SubmitTicketPrint implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public bool $failOnTimeout = true;

    public function __construct(public string $printJobId) {}

    public function handle(ReceiptRenderer $renderer, PrinterGateway $gateway): void
    {
        $job = PrintJob::find($this->printJobId);
        if (! $job || $job->status !== 'queued') {
            return;
        }
        if ($job->created_at->lte(now()->subSeconds(config('printing.job_ttl')))) {
            PrintJob::whereKey($job->id)->where('status', 'queued')->update(['status' => 'expired', 'finished_at' => now(), 'failure_code' => 'queue_expired']);

            return;
        }
        // At most one automatic native submission, including queue redelivery after a crash.
        if (! PrintJob::whereKey($job->id)->where('status', 'queued')->update(['status' => 'processing', 'started_at' => now()])) {
            return;
        }
        $path = PrintStorage::directory().'/'.$job->id.'.pdf';
        $sending = false;
        try {
            $gateway->assertAvailable($job->printer_name);
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0700, true);
            }
            $pdf = $renderer->render($job->snapshot);
            if (file_put_contents($path, $pdf, LOCK_EX) === false) {
                throw new \RuntimeException('PDF tidak dapat disimpan.');
            }
            if (! PrintJob::whereKey($job->id)->where('status', 'processing')->update(['submission_started_at' => now()])) {
                return;
            }
            $sending = true;
            $gateway->submit($path, $job->printer_name);
            $job->update(['status' => 'submitted', 'finished_at' => now()]);
        } catch (\Throwable $e) {
            $job->update(['status' => ($sending || $e instanceof PrintOutcomeUnknown) ? 'unknown' : 'failed', 'finished_at' => now(), 'failure_code' => $sending ? 'submission_uncertain' : 'pre_submission_failed', 'failure_detail' => mb_substr($e->getMessage(), 0, 2000)]);
            report($e);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function failed(?\Throwable $exception): void
    {
        PrintJob::whereKey($this->printJobId)->whereIn('status', ['queued', 'processing'])->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => 'worker_failed', 'failure_detail' => mb_substr($exception?->getMessage() ?? 'Worker terputus.', 0, 2000)]);
    }
}
