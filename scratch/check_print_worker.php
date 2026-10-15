<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$workers=Illuminate\Support\Facades\DB::table('printer_workers')->get();
echo json_encode(['environment'=>app()->environment(),'queue_default'=>config('queue.default'),'printing_connection'=>config('queue.connections.printing'),'workers'=>$workers,'print_jobs'=>App\Models\PrintJob::count(),'native_gateway'=>get_class(app(App\Services\Printing\PrinterGateway::class))],JSON_PRETTY_PRINT);
