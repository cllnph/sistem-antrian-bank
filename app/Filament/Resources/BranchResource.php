<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages\ManageBranches;
use App\Models\Branch;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_group_org');
    }

    public static function getNavigationLabel(): string
    {
        return 'Master Kantor Cabang';
    }

    public static function getModelLabel(): string
    {
        return 'Kantor Cabang';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Master Kantor Cabang';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Kantor Cabang')
                ->description('Profil kantor cabang bank, tipe unit kerja, dan konfigurasi jam operasional.')
                ->schema([
                    TextInput::make('code')
                        ->label('Kode Cabang')
                        ->placeholder('Contoh: 001, 002')
                        ->required()
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')
                        ->label('Nama Kantor Cabang')
                        ->placeholder('Contoh: Kantor Cabang Bogor')
                        ->required(),
                    TextInput::make('slug')
                        ->label('Slug URL')
                        ->placeholder('Contoh: bogor, pusat, depok')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Select::make('type')
                        ->label('Tipe Kantor')
                        ->options([
                            'Kantor Pusat' => 'Kantor Pusat',
                            'Kantor Cabang' => 'Kantor Cabang',
                            'Kantor Kas' => 'Kantor Kas',
                        ])
                        ->default('Kantor Cabang')
                        ->required(),
                    TextInput::make('phone')
                        ->label('Nomor Telepon')
                        ->placeholder('Contoh: 0251-8321098'),
                    TextInput::make('operating_hours')
                        ->label('Jam Layanan')
                        ->default('Senin – Jumat (08.00 – 16.00 WIB)')
                        ->required(),
                    Textarea::make('address')
                        ->label('Alamat Lengkap (Muncul di Struk & TV Display)')
                        ->placeholder('Alamat lengkap kantor cabang...')
                        ->rows(3)
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Status Operasional Aktif')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->badge()->color('primary')->sortable(),
                TextColumn::make('name')->label('Nama Kantor')->weight('bold')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipe')->badge()->color('info'),
                TextColumn::make('phone')->label('Telepon')->placeholder('-'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                Action::make('kiosk_link')
                    ->label('Kiosk')
                    ->icon(Heroicon::Ticket)
                    ->color('success')
                    ->url(fn (Branch $record) => url('/ambil-nomor/'.$record->code))
                    ->openUrlInNewTab(),
                Action::make('display_link')
                    ->label('Display TV')
                    ->icon(Heroicon::Tv)
                    ->color('warning')
                    ->url(fn (Branch $record) => url('/display/'.$record->code))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBranches::route('/')];
    }
}
