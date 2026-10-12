<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\Action;
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
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string { return __('app.nav_group_user'); }
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
            \Filament\Schemas\Components\Section::make('Informasi Akun & Penempatan Tugas')
                ->description('Kelola profil pengguna, unit kerja kantor cabang, peran, dan kredensial akses.')
                ->schema([
                    TextInput::make('name')
                        ->label(fn () => __('app.full_name'))
                        ->placeholder('Budi Santoso')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label(fn () => __('app.username_email'))
                        ->email()
                        ->placeholder('budi.cs@bprdptaspen.co.id')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Select::make('branch_id')
                        ->label('Kantor Cabang / Unit Kerja')
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('roles')
                        ->label(fn () => __('app.role'))
                        ->relationship('roles', 'name')
                        ->preload()
                        ->searchable()
                        ->getOptionLabelFromRecordUsing(fn ($record) => match ($record->name) {
                            'cs' => 'Customer Service',
                            'teller' => 'Teller',
                            'admin' => 'Administrator',
                            default => ucfirst(str_replace('_', ' ', $record->name)),
                        })
                        ->required(),
                    Select::make('service_setting_id')
                        ->label('Loket / Counter Tugas')
                        ->placeholder('Pilih Loket Tugas...')
                        ->options(fn () => \App\Models\ServiceSetting::all()->pluck('counter_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->helperText('Spesifik untuk Teller / CS. Administrator mengelola seluruh loket.'),
                    TextInput::make('password')
                        ->label(fn () => __('app.password'))
                        ->password()
                        ->autocomplete('new-password')
                        ->revealable()
                        ->minLength(8)
                        ->placeholder(fn (string $operation) => $operation === 'edit' ? __('app.password_placeholder_edit') : '••••••••')
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (?string $state) => filled($state)),
                ])
                ->columns(2),
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
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('roles.name')
                    ->label(fn () => __('app.role'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cs' => 'success',
                        'teller' => 'info',
                        'admin' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'cs' => 'Customer Service',
                        'teller' => 'Teller',
                        'admin' => 'Administrator',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),

                TextColumn::make('branch.name')
                    ->label('Kantor Cabang')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('serviceSetting.counter_name')
                    ->label(fn () => __('app.assigned_counter'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filter Cabang')
                    ->relationship('branch', 'name'),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label(fn () => __('app.status'))
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                Impersonate::make()
                    ->redirectTo(fn () => route('filament.admin.resources.queues.index'))
                    ->backTo(fn () => route('filament.admin.resources.users.index')),
                EditAction::make(), 
                DeleteAction::make()
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['roles', 'serviceSetting', 'branch']);
    }

    public static function getPages(): array { return ['index' => ManageUsers::route('/')]; }
}
