<?php

namespace App\Filament\Resources\KioskDeviceEventResource\Pages;

use App\Filament\Resources\KioskDeviceEventResource;
use App\Filament\Resources\KioskDeviceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageKioskDeviceEvents extends ManageRecords
{
    protected static string $resource = KioskDeviceEventResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('devices')->label('Daftar Perangkat')->url(KioskDeviceResource::getUrl('index'))];
    }
}
