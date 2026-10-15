<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || config('database.default') !== 'sqlite' || realpath(config('database.connections.sqlite.database')) !== realpath(base_path('scratch/filament-native-ui.sqlite'))) throw new RuntimeException('Isolated fixture required.');
$job=App\Models\PrintJob::whereHas('ticket',fn($q)=>$q->where('number','like','CS-%'))->latest()->firstOrFail();
$path=base_path('scratch/server-print-audit/receipt-cs.pdf');
file_put_contents($path,app(App\Services\Printing\ReceiptRenderer::class)->render($job->snapshot));
echo json_encode(['file'=>$path,'job'=>$job->id,'status'=>$job->status]);
