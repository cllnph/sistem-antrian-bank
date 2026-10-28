<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo json_encode([
    'transport' => config('printing.transport'),
    'enabled_agents' => App\Models\PrintAgent::where('enabled', true)->count(),
    'recent_jobs' => App\Models\PrintJob::latest()->limit(8)->get(['id', 'print_agent_id', 'printer_name', 'status'])->map(fn ($job) => [
        'route' => $job->print_agent_id ? 'branch_agent' : 'server', 'printer' => $job->printer_name, 'status' => $job->status,
    ]),
    'original_jobs' => App\Models\PrintJob::whereNull('original_job_id')->count(),
    'created_logs_with_metadata' => App\Models\QueueLog::where('action', 'created')->whereNotNull('payload')->count(),
], JSON_PRETTY_PRINT).PHP_EOL;
