<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\ServiceSetting;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_group_user');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.user_management');
    }

    public static function getModelLabel(): string
    {
        return __('app.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.user_management');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_user') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_user') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete', $record) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Akun & Penempatan Tugas')
                ->columnSpanFull()
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
                        ->relationship('branch', 'name', modifyQueryUsing: fn (Builder $query) => $query->when(auth()->user()?->hasRole('admin_cabang'), fn (Builder $query) => $query->whereKey(auth()->user()->branch_id)))
                        ->default(fn () => auth()->user()?->branch_id)
                        ->rules(fn () => auth()->user()?->hasRole('admin_cabang') ? [Rule::in([auth()->user()->branch_id])] : [])
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('service_setting_id', null))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('roles')
                        ->label(fn () => __('app.role'))
                        ->relationship('roles', 'display_name', modifyQueryUsing: fn (Builder $query) => $query->when(auth()->user()?->hasRole('admin_cabang'), fn (Builder $query) => $query->whereIn('name', ['cs', 'teller'])))
                        ->saveRelationshipsUsing(function (Select $component, User $record, $state): void {
                            try {
                                $record->syncRoles(\App\Models\Role::findOrFail($state));
                            } catch (\Illuminate\Validation\ValidationException $exception) {
                                $prefix = (string) str($component->getStatePath())->beforeLast('.');
                                throw \Illuminate\Validation\ValidationException::withMessages(
                                    collect($exception->errors())->mapWithKeys(fn ($messages, $field) => [$prefix.'.'.$field => $messages])->all()
                                );
                            }
                        })
                        ->rules(fn () => auth()->user()?->hasRole('admin_cabang') ? [Rule::exists('roles', 'id')->whereIn('name', ['cs', 'teller'])] : [])
                        ->preload()
                        ->searchable(['display_name', 'name'])
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                        ->required(),
                    Select::make('service_setting_id')
                        ->label('Loket / Counter Tugas')
                        ->placeholder('Pilih Loket Tugas...')
                        ->options(fn (Get $get) => ServiceSetting::where('branch_id', $get('branch_id'))->pluck('counter_name', 'id'))
                        ->rules(fn (Get $get) => [Rule::exists('service_settings', 'id')->where('branch_id', $get('branch_id'))])
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
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('roles.display_name')
                    ->label(fn () => __('app.role'))->badge(),

                TextColumn::make('branch.name')
                    ->label('Kantor Cabang')
                    ->wrap()
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('serviceSetting.counter_name')
                    ->label(fn () => __('app.assigned_counter'))
                    ->wrap()
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                ToggleColumn::make('is_active')
                    ->label(fn () => __('app.status')),

                TextColumn::make('last_login_at')
                    ->label(fn () => __('app.last_login'))
                    ->dateTime('d M Y, H:i')
                    ->placeholder(fn () => __('app.never_logged_in'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(fn () => __('app.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Filter Cabang')
                    ->relationship('branch', 'name'),
                TernaryFilter::make('is_active')
                    ->label(fn () => __('app.status'))
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Impersonate::make()->label('Masuk sebagai petugas')
                        ->redirectTo(fn () => route('filament.admin.resources.queues.index'))
                        ->backTo(fn () => route('filament.admin.resources.users.index')),
                    EditAction::make(),
                    DeleteAction::make()
                        ->disabled(fn ($record) => \App\Services\IntegrityRules::deletionReason($record) !== null)
                        ->tooltip(fn ($record) => \App\Services\IntegrityRules::deletionReason($record))
                        ->before(fn ($record, DeleteAction $action) => \App\Services\IntegrityRules::beforeDelete($record, $action)),
                ])->label('Tindakan'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['roles', 'serviceSetting', 'branch'])
            ->when(auth()->user()?->hasRole('admin_cabang'), fn (Builder $query) => $query
                ->where('branch_id', auth()->user()->branch_id)
                ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', ['cs', 'teller']))
                ->whereDoesntHave('roles', fn (Builder $query) => $query->whereNotIn('name', ['cs', 'teller'])));
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
