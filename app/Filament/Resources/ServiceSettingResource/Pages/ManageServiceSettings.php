<?php

namespace App\Filament\Resources\ServiceSettingResource\Pages;

use App\Filament\Resources\ServiceSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceSettings extends ManageRecords
{
    protected static string $resource = ServiceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah counter')];
    }
}
