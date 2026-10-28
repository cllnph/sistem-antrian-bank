<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KioskDeviceResource\Pages\ManageKioskDevices;
use App\Models\Branch;
use App\Models\KioskDevice;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KioskDeviceResource extends Resource
{
    protected static ?string $model = KioskDevice::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return 'Perangkat Kiosk';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Riwayat Perangkat Kiosk';
    }

    public static function canViewAny(): bool
    {
        return (bool) (auth()->user()?->is_active && auth()->user()?->hasRole('admin'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('lastBranch')->withCount([
            'events as visits_count' => fn (Builder $query) => $query->where('event', 'opened'),
            'events as tickets_count' => fn (Builder $query) => $query->where('event', 'ticket_issued'),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Nama Perangkat')->maxLength(100)
                ->helperText('Opsional, misalnya Laptop Rina atau HP Petugas. Nama ini hanya penanda riwayat.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('last_seen_at', 'desc')->poll('10s')->columns([
            TextColumn::make('label')->label('Perangkat')->formatStateUsing(fn ($state, KioskDevice $record) => $record->displayName())
                ->placeholder(fn (KioskDevice $record) => $record->displayName())->searchable(['label', 'platform', 'browser']),
            TextColumn::make('device_type')->label('Jenis')->badge(),
            TextColumn::make('platform')->label('Sistem'),
            TextColumn::make('browser')->label('Browser'),
            TextColumn::make('last_ip')->label('IP Terakhir')->searchable()->placeholder('-'),
            TextColumn::make('lastBranch.name')->label('Cabang Terakhir')->wrap(),
            TextColumn::make('visits_count')->label('Buka Kiosk')->numeric(),
            TextColumn::make('tickets_count')->label('Tiket Diambil')->numeric(),
            TextColumn::make('identity_source')->label('Pengenalan')->badge()
                ->formatStateUsing(fn ($state) => $state === 'legacy_session' ? 'Sesi lama' : 'Browser'),
            TextColumn::make('first_seen_at')->label('Pertama Tercatat')->dateTime('d M Y H:i:s')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('last_seen_at')->label('Terakhir Tercatat')->dateTime('d M Y H:i:s')->sortable(),
        ])->filters([
            SelectFilter::make('branch_id')->label('Pernah Membuka Cabang')->options(fn () => Branch::orderBy('name')->pluck('name', 'id'))
                ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null,
                    fn (Builder $query, $id) => $query->whereHas('events', fn (Builder $events) => $events->where('branch_id', $id)))),
        ])->recordActions([
            Action::make('history')->label('Riwayat Aktivitas')->url(fn (KioskDevice $record) => KioskDeviceEventResource::getUrl('index', [
                'filters' => ['kiosk_device_id' => ['value' => $record->id]],
            ])),
            EditAction::make()->label('Beri Nama')->authorize(fn () => static::canViewAny()),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageKioskDevices::route('/')];
    }
}
