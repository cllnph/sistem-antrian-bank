<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$expected = realpath(__DIR__.'/device-history-audit/private/app.sqlite');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(config('database.connections.sqlite.database')) !== $expected
    || ! app(App\Services\Printing\PrinterGateway::class) instanceof App\Services\Printing\FakePrinterGateway) {
    throw new RuntimeException('Isolated fake-printer audit environment required.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
echo 'Isolated device-history browser fixture ready. No physical printing.'.PHP_EOL;
