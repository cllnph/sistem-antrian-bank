<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string { return __('app.user_management'); }
    public static function getModelLabel(): string { return __('app.user'); }
    public static function getPluralModelLabel(): string { return __('app.user_management'); }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_user') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_user') ?? false; }
    public static function canEdit(Model $record): bool { return auth()->user()?->can('edit_user') ?? false; }
    public static function canDelete(Model $record): bool { return auth()->user()?->can('delete_user') ?? false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(fn () => __('app.full_name'))->placeholder('Budi Santoso')->required()->maxLength(255),
            TextInput::make('email')->label(fn () => __('app.username_email'))->email()->placeholder('budi.cs@bprdptaspen.co.id')->required()->unique(ignoreRecord: true),
            TextInput::make('password')
                ->label(fn () => __('app.password'))
                ->password()
                ->autocomplete('new-password')
                ->revealable()
                ->minLength(8)
                ->placeholder(fn (string $operation) => $operation === 'edit' ? __('app.password_placeholder_edit') : '••••••••')
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state)),
            TextInput::make('password_confirmation')
                ->label(fn () => __('app.confirm_password'))
                ->password()
                ->autocomplete('new-password')
                ->revealable()
                ->minLength(8)
                ->placeholder('••••••••')
                ->required(fn (string $operation) => $operation === 'create')
                ->same('password')
                ->visible(fn (string $operation, $get) => $operation === 'create' || filled($get('password')))
                ->dehydrated(false),
            Select::make('roles')->label(fn () => __('app.role'))
                ->relationship('roles', 'name')
                ->preload()
                ->searchable()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Nama Peran Baru')
                        ->placeholder('Contoh: supervisor, auditor, head_teller')
                        ->helperText('Format: huruf kecil tanpa spasi (menggunakan underscore _). Contoh: head_teller')
                        ->required()
                        ->dehydrateStateUsing(fn (?string $state) => \Illuminate\Support\Str::slug(strtolower((string) $state), '_'))
                        ->unique(table: config('permission.table_names.roles', 'roles'), column: 'name'),

                    \Filament\Schemas\Components\Section::make('🔑 1. Panel & Akses Sistem')
                        ->description('Kontrol hak masuk ke panel admin dan konsol operasional petugas.')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('perm_system')
                                ->hiddenLabel()
                                ->options([
                                    'access_admin_panel' => '🔑 Akses Panel Admin',
                                    'access_teller_console' => '🖥️ Akses Konsol Teller',
                                    'access_cs_console' => '🖥️ Akses Konsol Layanan Pelanggan',
                                ])
                                ->columns(2),
                        ]),

                    \Filament\Schemas\Components\Section::make('👥 2. Manajemen Pengguna')
                        ->description('Hak pengelolaan data dan pembuatan akun pengguna.')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('perm_user')
                                ->hiddenLabel()
                                ->options([
                                    'view_any_user' => '👥 Lihat Data Pengguna',
                                    'create_user' => '👤 Buat Pengguna Baru',
                                    'edit_user' => '✏️ Edit Pengguna',
                                    'delete_user' => '🗑️ Hapus Pengguna',
                                ])
                                ->columns(2),
                        ]),

                    \Filament\Schemas\Components\Section::make('📋 3. Manajemen Antrean')
                        ->description('Hak pemanggilan, pembuatan, dan pengeditan tiket antrean.')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('perm_queue')
                                ->hiddenLabel()
                                ->options([
                                    'view_any_queue' => '📋 Lihat Data Antrean',
                                    'create_queue' => '➕ Buat Tiket Antrean',
                                    'edit_queue' => '✏️ Edit / Panggil Antrean',
                                    'delete_queue' => '🗑️ Hapus Antrean',
                                    'reset_queues' => '🔄 Reset Status Antrean',
                                ])
                                ->columns(2),
                        ]),

                    \Filament\Schemas\Components\Section::make('⚙️ 4. Pengaturan Loket & Counter')
                        ->description('Pengaturan status buka/tutup dan nama counter perbankan.')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('perm_counter')
                                ->hiddenLabel()
                                ->options([
                                    'view_any_service_setting' => '⚙️ Lihat Pengaturan Loket',
                                    'edit_service_setting' => '🔧 Edit Loket Layanan',
                                ])
                                ->columns(2),
                        ]),

                    \Filament\Schemas\Components\Section::make('📊 5. Kontrol & Laporan Operasional')
                        ->description('Akses ke dashboard pusat kendali dan laporan aktivitas.')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('perm_report')
                                ->hiddenLabel()
                                ->options([
                                    'view_control_center' => '📊 Lihat Pusat Kendali',
                                ])
                                ->columns(2),
                        ]),
                ])
                ->createOptionUsing(function (array $data) {
                    $rawName = $data['name'] ?? '';
                    $roleName = \Illuminate\Support\Str::slug(strtolower((string) $rawName), '_');
                    if (blank($roleName)) {
                        $roleName = 'role_' . time();
                    }
                    $role = \Spatie\Permission\Models\Role::findOrCreate($roleName);

                    $allPermNames = array_values(array_filter(array_merge(
                        (array) ($data['perm_system'] ?? ['access_admin_panel']),
                        (array) ($data['perm_user'] ?? []),
                        (array) ($data['perm_queue'] ?? ['view_any_queue']),
                        (array) ($data['perm_counter'] ?? []),
                        (array) ($data['perm_report'] ?? [])
                    )));

                    $permModels = [];
                    foreach ($allPermNames as $permName) {
                        $permModels[] = \Spatie\Permission\Models\Permission::findOrCreate($permName);
                    }

                    $role->syncPermissions($permModels);
                    return $role->id;
                })
                ->createOptionModalHeading('Tambah Role / Aktor Baru & Atur Hak Akses')
                ->getOptionLabelFromRecordUsing(fn ($record) => match ($record->name) {
                    'cs' => 'Customer Service',
                    'teller' => 'Teller',
                    'admin' => 'Administrator',
                    default => ucfirst(str_replace('_', ' ', $record->name)),
                })
                ->required(),

            Select::make('service_setting_id')
                ->label('Loket / Counter Tugas (Assignment Data)')
                ->placeholder('Pilih Loket Tugas...')
                ->options(fn () => \App\Models\ServiceSetting::all()->pluck('counter_name', 'id'))
                ->searchable()
                ->preload()
                ->helperText('Loket tugas spesifik untuk Teller / CS. Administrator mengelola seluruh loket.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(fn () => __('app.name'))
                    ->description(fn (User $record) => $record->email)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label(fn () => __('app.role'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cs' => 'success',
                        'teller' => 'info',
                        'admin' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'cs' => 'Customer Service',
                        'teller' => 'Teller',
                        'admin' => 'Administrator',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),

                TextColumn::make('serviceSetting.counter_name')
                    ->label(fn () => __('app.assigned_counter'))
                    ->badge()
                    ->color('warning')
                    ->placeholder(fn () => __('app.unassigned')),

                \Filament\Tables\Columns\ToggleColumn::make('is_active')
                    ->label(fn () => __('app.status')),

                TextColumn::make('last_login_at')
                    ->label(fn () => __('app.last_login'))
                    ->dateTime('d M Y, H:i')
                    ->placeholder(fn () => __('app.never_logged_in'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(fn () => __('app.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label(fn () => __('app.status'))
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                EditAction::make(), 
                DeleteAction::make()
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['roles', 'serviceSetting']);
    }

    public static function getPages(): array { return ['index' => ManageUsers::route('/')]; }
}
