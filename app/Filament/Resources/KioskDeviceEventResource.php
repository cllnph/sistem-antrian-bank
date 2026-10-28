<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KioskDeviceEventResource\Pages\ManageKioskDeviceEvents;
use App\Models\KioskDevice;
use App\Models\KioskDeviceEvent;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KioskDeviceEventResource extends Resource
{
    protected static ?string $model = KioskDeviceEvent::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getPluralModelLabel(): string
    {
        return 'Aktivitas Perangkat Kiosk';
    }

    public static function canViewAny(): bool
    {
        return KioskDeviceResource::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['device', 'branch', 'printJob']);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('observed_at', 'desc')->columns([
            TextColumn::make('observed_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
            TextColumn::make('device.label')->label('Perangkat')->state(fn (KioskDeviceEvent $record) => $record->device->displayName())->wrap(),
            TextColumn::make('branch.name')->label('Cabang')->wrap(),
            TextColumn::make('event')->label('Aktivitas')->badge()->formatStateUsing(fn ($state) => $state === 'opened' ? 'Buka Kiosk' : 'Ambil Tiket'),
            TextColumn::make('ticket_number')->label('Nomor Tiket')->placeholder('-')->searchable(),
            TextColumn::make('ip_address')->label('IP')->placeholder('-')->searchable(),
            TextColumn::make('printJob.printer_name')->label('Tujuan Printer')->placeholder('-')->wrap(),
            TextColumn::make('route')->label('Jalur Cetak')->state(fn (KioskDeviceEvent $record) => $record->printJob
                ? match ($record->printJob->transport) {
                    'browser' => 'Printer PC ini (browser)', 'agent' => 'Penghubung cabang', default => 'Server'
                } : '-'),
            TextColumn::make('printJob.status')->label('Status Cetak')->badge()->placeholder('-'),
            TextColumn::make('source')->label('Sumber')->formatStateUsing(fn ($state) => $state === 'legacy' ? 'Log tiket lama' : 'Pencatatan otomatis'),
            TextColumn::make('user_agent')->label('Informasi Browser')->limit(70)->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('kiosk_device_id')->label('Perangkat')->relationship('device', 'label')
                ->getOptionLabelFromRecordUsing(fn (KioskDevice $record) => $record->displayName())->searchable()->preload(),
            SelectFilter::make('branch_id')->label('Cabang')->relationship('branch', 'name')->searchable()->preload(),
            SelectFilter::make('event')->label('Aktivitas')->options(['opened' => 'Buka Kiosk', 'ticket_issued' => 'Ambil Tiket']),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageKioskDeviceEvents::route('/')];
    }
}
