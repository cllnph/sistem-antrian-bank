<?php

namespace App\Services\Printing;

class PrintStorage
{
    public static function directory(): string
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        return storage_path('app/private/ticket-printing/'.substr(hash('sha256', $connection.'|'.$database), 0, 16));
    }
}
