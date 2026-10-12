<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages\ManageRoles;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string { return __('app.nav_group_user'); }
    public static function getNavigationLabel(): string { return __('app.role_management'); }
    public static function getModelLabel(): string { return __('app.role'); }
    public static function getPluralModelLabel(): string { return __('app.role_management'); }

    public static function shouldRegisterNavigation(): bool { return true; }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_user') ?? false; }
    public static function canCreate(): bool { return static::canViewAny(); }
    public static function canEdit(Model $record): bool { return static::canViewAny(); }
    public static function canDelete(Model $record): bool { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama Peran')
                ->placeholder('misal: supervisor, auditor, head_teller')
                ->required()
                ->unique(ignoreRecord: true),
            CheckboxList::make('permissions')->label('Hak Akses (Permissions)')
                ->relationship('permissions', 'name')
                ->columns(2)
                ->getOptionLabelFromRecordUsing(fn ($record) => match ($record->name) {
                    'access_admin_panel' => '🔑 Akses Panel Admin',
                    'access_teller_console' => '🖥️ Akses Konsol Teller',
                    'access_cs_console' => '🖥️ Akses Konsol Layanan Pelanggan',
                    'view_any_queue' => '📋 Lihat Data Antrean',
                    'create_queue' => '➕ Buat Tiket Antrean',
                    'edit_queue' => '✏️ Edit / Panggil Antrean',
                    'delete_queue' => '🗑️ Hapus Antrean',
                    'view_any_user' => '👥 Lihat Data Pengguna',
                    'create_user' => '👤 Buat Pengguna Baru',
                    'edit_user' => '✏️ Edit Pengguna',
                    'delete_user' => '🗑️ Hapus Pengguna',
                    'view_any_service_setting' => '⚙️ Lihat Pengaturan Loket',
                    'edit_service_setting' => '🔧 Edit Loket Layanan',
                    'view_control_center' => '📊 Lihat Pusat Kendali',
                    'reset_queues' => '🔄 Reset Antrean',
                    default => $record->name,
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(fn () => __('app.role'))->searchable()->sortable()
                ->formatStateUsing(fn (string $state) => match ($state) {
                    'cs' => __('app.role_cs'),
                    'teller' => __('app.role_teller'),
                    'admin' => __('app.role_admin'),
                    'supervisor' => __('app.role_supervisor'),
                    default => ucfirst($state),
                }),
            TextColumn::make('permissions_count')->label('Jumlah Hak Akses')->counts('permissions')->badge()->color('info'),
            TextColumn::make('created_at')->label(fn () => __('app.created_at'))->dateTime('d M Y H:i'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array { return ['index' => ManageRoles::route('/')]; }
}
