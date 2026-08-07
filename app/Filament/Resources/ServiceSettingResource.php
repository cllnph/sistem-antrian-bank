<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceSettingResource\Pages\ManageServiceSettings;
use App\Models\ServiceSetting;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceSettingResource extends Resource
{
    protected static ?string $model = ServiceSetting::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $recordTitleAttribute = 'counter_name';

    public static function getNavigationLabel(): string { return __('app.service_settings'); }
    public static function getModelLabel(): string { return __('app.counter'); }
    public static function getPluralModelLabel(): string { return __('app.service_settings'); }

    public static function getGloballySearchableAttributes(): array
    {
        return ['counter_name', 'label', 'service'];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_service_setting') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('edit_service_setting') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('edit_service_setting') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('edit_service_setting') ?? false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('service')->label(fn () => __('app.service_type'))->options(['cs' => __('app.cs_service'), 'teller' => __('app.teller_service')])->required(),
            TextInput::make('label')->label(fn () => __('app.service'))->required(),
            TextInput::make('counter_name')->label(fn () => __('app.counter_name'))->required()->unique(ignoreRecord: true),
            Toggle::make('is_active')->label(fn () => __('app.operational_status'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label')->label(fn () => __('app.service')),
            TextColumn::make('counter_name')->label(fn () => __('app.counter_name')),
            IconColumn::make('is_active')->label(fn () => __('app.operational_status'))->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user || $user->hasRole('admin')) {
            return $query;
        }

        if ($user->service_setting_id) {
            return $query->where('id', $user->service_setting_id);
        }

        return $query;
    }

    public static function getPages(): array { return ['index' => ManageServiceSettings::route('/')]; }
}
