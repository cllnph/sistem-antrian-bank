<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrintAgentResource\Pages\ManagePrintAgents;
use App\Filament\Resources\PrintAgentResource\Pages\SelectPrintBranch;
use App\Models\Branch;
use App\Models\PrintAgent;
use App\Services\Printing\AgentPackageBuilder;
use App\Services\Printing\PrintAgentManagement;
use App\Services\Printing\ReceiptPaper;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PrintAgentResource extends Resource
{
    protected static ?string $model = PrintAgent::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_group_org');
    }

    public static function getNavigationLabel(): string
    {
        return 'Penghubung Cetak Cabang';
    }

    public static function getModelLabel(): string
    {
        return 'Penghubung Cetak';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Penghubung Cetak Cabang';
    }

    public static function canViewAny(): bool
    {
        return (bool) (auth()->user()?->is_active && auth()->user()?->hasRole('admin'));
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_id')->label('Kantor Cabang')->relationship('branch', 'name', fn ($query) => $query->where('is_active', true))
                ->required()->searchable()->preload()->helperText('Satu kantor boleh memiliki beberapa PC dan printer.'),
            TextInput::make('name')->label('Nama Perangkat Penghubung')->required()->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->poll(fn ($livewire) => filled($livewire->mountedActions) ? null : '10s')
            ->recordTitleAttribute('name')->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')->label('Perangkat')->weight('bold')->searchable()->wrap()
                            ->description(fn (PrintAgent $record) => $record->machine_name
                                ? (strcasecmp($record->name, $record->machine_name) === 0 ? null : 'PC: '.$record->machine_name)
                                : 'Menunggu pemasangan pada PC'),
                        TextColumn::make('printer_name')->label('Printer')->prefix('Printer: ')->wrap()->placeholder('Pilih printer saat pemasangan'),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('paper_profile')->label('Kertas')->formatStateUsing(fn ($state) => ReceiptPaper::LABELS[$state] ?? '80 mm (lama)'),
                        TextColumn::make('connection')->label('Status')->state(fn (PrintAgent $record) => $record->revoked ? 'Akses dicabut' : ($record->isReady() ? ($record->enabled ? 'Siap ? aktif' : 'Siap ? nonaktif') : 'Belum siap'))
                            ->badge()->color(fn (PrintAgent $record) => $record->isReady() && $record->enabled ? 'success' : 'warning'),
                    ])->space(2)->grow(false),
                ])->from('lg'),
            ])->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Detail')->authorize(fn () => static::canViewAny())
                        ->schema([
                            TextEntry::make('name')->label('Label perangkat'),
                            TextEntry::make('machine_name')->label('Nama PC')->placeholder('Belum terpasang'),
                            TextEntry::make('printer_name')->label('Printer')->placeholder('Belum dipilih'),
                            TextEntry::make('paper_profile')->label('Kertas')->formatStateUsing(fn ($state) => ReceiptPaper::LABELS[$state] ?? '80 mm (lama)'),
                            TextEntry::make('version')->label('Versi')->placeholder('Belum terpasang'),
                            TextEntry::make('inventory_status')->label('Inventaris')->placeholder('Belum terbaca'),
                            TextEntry::make('heartbeat_at')->label('Terakhir terhubung')->dateTime('d M Y H:i:s')->placeholder('Belum terhubung'),
                            TextEntry::make('last_error')->label('Diagnosis')->placeholder('Tidak ada pesan kesalahan'),
                        ]),
                    EditAction::make()->label('Ubah label perangkat')->authorize(fn () => static::canViewAny())
                        ->using(fn (PrintAgent $record, array $data) => app(PrintAgentManagement::class)->update($record, [...$data, 'branch_id' => $record->branch_id])),
                    Action::make('changePrinter')->label('Ganti printer & kertas')->icon(Heroicon::OutlinedPrinter)
                        ->authorize(fn () => static::canViewAny())->disabled(fn (PrintAgent $record) => ! $record->device_id || $record->revoked)
                        ->schema([
                            Select::make('printer_name')->label('Printer pada PC ini')->required()
                                ->options(fn (PrintAgent $record) => array_combine($record->installed_printers ?? [], $record->installed_printers ?? []))
                                ->default(fn (PrintAgent $record) => $record->printer_name)
                                ->helperText('Daftar berasal dari PC penghubung. Jika printer baru belum muncul, tambahkan melalui Windows pada PC tersebut dan tunggu pembaruan daftar.'),
                            Select::make('paper_profile')->label('Kertas')->options(ReceiptPaper::LABELS)->required()
                                ->default(fn (PrintAgent $record) => $record->paper_profile ?? 'thermal80'),
                        ])->modalDescription('Perubahan berlaku untuk perangkat ini. Tunggu cetakan yang sedang diproses selesai. Tugas yang masih menunggu dibatalkan; tiket tetap tersedia sampai reset periode cabang. Printer baru harus dikonfirmasi siap oleh PC penghubung.')
                        ->action(function (PrintAgent $record, array $data, Schema $schema) {
                            try {
                                app(PrintAgentManagement::class)->selectFromAdmin($record, $data);
                            } catch (ValidationException $error) {
                                throw ValidationException::withMessages(collect($error->errors())
                                    ->mapWithKeys(fn ($messages, $field) => [$schema->getStatePath().'.'.$field => $messages])->all());
                            }
                            Notification::make()->title('Pilihan disimpan. Menunggu konfirmasi PC penghubung.')->success()->send();
                        }),
                    Action::make('useHere')->label('Cara membuka kiosk otomatis')->icon(Heroicon::OutlinedComputerDesktop)
                        ->authorize(fn () => static::canViewAny())
                        ->modalDescription('Pada PC printer, buka shortcut Buka Kiosk dari installer 1.3.0. Pilih browser yang diinginkan. Browser tersebut dipasangkan otomatis tanpa perlu login admin. Aktivasi dari PC admin pusat tidak mengubah tujuan printer PC admin.')
                        ->modalSubmitAction(false)->modalCancelActionLabel('Tutup'),
                    Action::make('installer')->label('Unduh Installer')->icon(Heroicon::OutlinedArrowDownTray)
                        ->modalSubmitActionLabel('Unduh Installer')
                        ->authorize(fn () => static::canViewAny())->schema([
                            TextInput::make('server_url')->label('Alamat Aplikasi Pusat')->url()->required()->maxLength(255)
                                ->default(fn () => url('/'))->helperText('Gunakan alamat dasar aplikasi lengkap, termasuk subfolder jika ada. Gunakan HTTPS saat aplikasi sudah online.'),
                        ])->modalDescription('Berlaku 15 menit. Klik kanan ZIP terbaru > Extract All / Ekstrak Semua, lalu buka Install-Agent.cmd dari folder hasil ekstraksi. Pilih printer USB, Wi-Fi/LAN, atau sharing pada jendela pemasangan; tersedia Cari / tambah printer walaupun laptop belum memiliki printer. Jangan jalankan langsung dari dalam ZIP. Pemasangan ulang mengganti akses penghubung lama.')
                        ->action(function (PrintAgent $record, array $data) {
                            abort_unless(static::canViewAny(), 403);
                            try {
                                $path = app(AgentPackageBuilder::class)->build($record, $data['server_url'], auth()->user());
                            } catch (\RuntimeException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();

                                return null;
                            }

                            $code = preg_replace('/[^a-zA-Z0-9_-]/', '_', $record->branch->code);
                            $version = app(AgentPackageBuilder::class)->windowsInstallerVersion();
                            $filename = 'Penghubung-Cetak-'.$code.'-v'.$version.'-'.now()->format('Ymd-His').'.zip';

                            return response()->download($path, $filename, ['Cache-Control' => 'private, no-store'])->deleteFileAfterSend(true);
                        }),
                    Action::make('enable')->label('Aktifkan')->color('success')->requiresConfirmation()
                        ->modalSubmitActionLabel('Aktifkan')
                        ->authorize(fn () => static::canViewAny())->visible(fn (PrintAgent $record) => ! $record->enabled)
                        ->modalDescription('Penghubung kembali menerima tugas. Buka kiosk melalui shortcut Buka Kiosk pada PC printer. Aktivasi ini tidak memasangkan browser admin pusat.')
                        ->action(function (PrintAgent $record) {
                            app(PrintAgentManagement::class)->enable($record);
                        }),
                    Action::make('disable')->label('Nonaktifkan')->color('warning')->requiresConfirmation()
                        ->authorize(fn () => static::canViewAny())->visible(fn (PrintAgent $record) => $record->enabled)
                        ->action(fn (PrintAgent $record) => app(PrintAgentManagement::class)->disable($record)),
                    Action::make('revoke')->label('Cabut Akses')->color('danger')->requiresConfirmation()
                        ->modalSubmitActionLabel('Cabut Akses')
                        ->authorize(fn () => static::canViewAny())
                        ->modalDescription('Perangkat ini tidak dapat mengambil tugas lagi. Job yang belum pasti tidak akan dicetak ulang otomatis.')
                        ->action(function (PrintAgent $record) {
                            app(PrintAgentManagement::class)->disable($record, revoke: true);
                        }),
                    DeleteAction::make()->label('Hapus')->authorize(fn () => static::canViewAny())
                        ->modalDescription('Hapus penghubung dari daftar dan cabut aksesnya. Tiket tetap tersedia sampai reset periode cabang. Cetakan yang sudah terkirim tidak dapat ditarik kembali.')
                        ->using(fn (PrintAgent $record) => app(PrintAgentManagement::class)->disable($record, delete: true)),
                ])->label('Kelola')->button()->color('gray')->dropdownPlacement('bottom-start')->dropdownWidth('xs'),
            ], position: RecordActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => SelectPrintBranch::route('/'),
            'branch' => ManagePrintAgents::route('/{branch}/cetak'),
        ];
    }

    public static function forBranchUrl(Branch $branch): string
    {
        return static::getUrl('branch', ['branch' => $branch->id]);
    }
}
