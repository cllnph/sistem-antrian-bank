<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $app->make('config')->set('database.default', 'sqlite');
        $app->make('config')->set('database.connections.sqlite.database', ':memory:');
        $app->instance(\App\Services\Printing\PrinterGateway::class, new \App\Services\Printing\FakePrinterGateway);
        $app->make('config')->set('printing.require_worker', false);

        return $app;
    }

    protected function connectionsToTransact()
    {
        return ['sqlite'];
    }
}
