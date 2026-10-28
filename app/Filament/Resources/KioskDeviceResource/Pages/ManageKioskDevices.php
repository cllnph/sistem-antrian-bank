<?php

namespace App\Filament\Resources\KioskDeviceResource\Pages;

use App\Filament\Pages\BranchOperations;
use App\Filament\Resources\KioskDeviceEventResource;
use App\Filament\Resources\KioskDeviceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageKioskDevices extends ManageRecords
{
    protected static string $resource = KioskDeviceResource::class;

    public function getSubheading(): ?string
    {
        return 'Tercatat otomatis saat membuka kiosk atau mengambil tiket. Identitas mengikuti browser/cookie; jenis perangkat diperkirakan dari browser. Pencatatan tidak mendaftarkan penghubung printer.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('agents')->label('Pilih cabang untuk cetak')->url(BranchOperations::getUrl()),
            Action::make('events')->label('Semua Aktivitas')->url(KioskDeviceEventResource::getUrl('index')),
        ];
    }
}
