<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Services\BranchDashboard;
use App\Services\BranchSchedule;
use App\Services\QueuePeriods;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OperatingHours extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Waktu Operasional';

    protected static ?string $title = 'Waktu Operasional';

    protected static ?string $slug = 'waktu-operasional';

    protected static ?int $navigationSort = -10;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->is_active && ($user->hasRole('admin') || ($user->hasRole('admin_cabang') && $user->branch_id));
    }

    public function mount(): void
    {
        foreach (app(BranchDashboard::class)->branches(auth()->user())->get() as $branch) {
            app(QueuePeriods::class)->ensure($branch);
        }
    }

    public function content(Schema $schema): Schema
    {
        $health = DB::table('operation_scheduler_status')->find(1);
        $healthy = $health?->status === 'ok' && $health?->heartbeat_at && now()->diffInSeconds($health->heartbeat_at, true) < 180;

        return $schema->components([
            Section::make('Jadwal cabang dan reset antrean')
                ->description('Semua waktu menggunakan WIB. Antrean tersisa dapat diselesaikan hingga reset. Perubahan jam reset berlaku pada waktu berikutnya di masa depan.')
                ->schema([
                    TextEntry::make('scheduler')->label('Scheduler')->state($healthy ? 'Berjalan' : 'Perlu diperiksa')->badge()->color($healthy ? 'success' : 'warning'),
                    TextEntry::make('last_run')->label('Eksekusi terakhir')->state($health?->heartbeat_at ? Carbon::parse($health->heartbeat_at)->format('d M Y H:i:s').' WIB' : 'Belum tercatat'),
                    TextEntry::make('scheduler_error')->label('Keterangan')->state($health?->last_error ?? ($healthy ? 'Tidak ada kegagalan.' : 'Periksa tugas Windows BankDPTaspen-Operations. Pemeriksaan reset juga dijalankan sebelum operasi antrean.')),
                ])->columns(['default' => 1, 'lg' => 3]),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        $days = [];
        foreach (BranchSchedule::DAYS as $day => $label) {
            $days[] = Section::make($label)->schema([
                Toggle::make('weekly_schedule.'.$day.'.enabled')->label('Layanan buka')->live(),
                TimePicker::make('weekly_schedule.'.$day.'.opens')->label('Buka (WIB)')->seconds(false)->format('H:i')->required(),
                TimePicker::make('weekly_schedule.'.$day.'.closes')->label('Tutup (WIB)')->seconds(false)->format('H:i')->required(),
            ])->columns(['default' => 1, 'sm' => 3]);
        }

        return $table->query(app(BranchDashboard::class)->branches(auth()->user())->with('queueState'))->defaultSort('code')->paginated([10, 25])
            ->columns([Split::make([
                Stack::make([
                    TextColumn::make('name')->label('Cabang')->weight('bold')->searchable(['name', 'code'])->description(fn (Branch $record) => $record->code),
                    TextColumn::make('schedule')->label('Jadwal')->state(fn (Branch $record) => app(BranchSchedule::class)->label($record))->wrap(),
                ])->space(2),
                Stack::make([
                    TextColumn::make('service_open')->label('Status')->state(fn (Branch $record) => app(BranchSchedule::class)->isOpen($record) ? 'Buka' : 'Tutup')->badge(),
                    TextColumn::make('reset_time')->label('Reset harian')->formatStateUsing(fn ($state) => 'Reset '.$state.' WIB'),
                    TextColumn::make('last_reset')->label('Reset terakhir')->state(fn (Branch $record) => $record->queueState?->last_reset_at ? $record->queueState->last_reset_at->format('d M Y H:i').' WIB' : 'Belum pernah reset')->prefix('Terakhir: '),
                    TextColumn::make('next_reset')->label('Reset berikutnya')->state(fn (Branch $record) => $record->queueState?->next_reset_at ? $record->queueState->next_reset_at->format('d M Y H:i').' WIB' : 'Belum dijadwalkan')->prefix('Berikutnya: '),
                    TextColumn::make('reset_error')->label('Kegagalan')->state(fn (Branch $record) => $record->queueState?->last_error)->color('danger')->wrap(),
                ])->space(1),
            ])->from('lg')])->recordActions([
                Action::make('detail')->label('Detail Cabang')->url(fn (Branch $record) => BranchDetail::getUrl(['branch' => $record->id])),
                Action::make('editSchedule')->label('Atur Jadwal')->icon('heroicon-o-clock')
                    ->authorize(fn () => auth()->user()->hasRole('admin'))
                    ->visible(fn () => auth()->user()->hasRole('admin'))
                    ->fillForm(fn (Branch $record) => ['weekly_schedule' => $record->weekly_schedule ?? BranchSchedule::defaults(), 'reset_time' => $record->reset_time ?? '00:00'])
                    ->schema([...$days, TimePicker::make('reset_time')->label('Reset antrean harian (WIB)')->seconds(false)->format('H:i')->required()
                        ->helperText('Harus di luar seluruh jam layanan. Jadwal reset baru selalu dipilih di masa depan.')])
                    ->action(function (Branch $record, array $data, Schema $schema) {
                        try {
                            app(BranchSchedule::class)->save($record, auth()->user(), $data['weekly_schedule'], $data['reset_time']);
                        } catch (ValidationException $error) {
                            throw ValidationException::withMessages(collect($error->errors())->mapWithKeys(fn ($messages, $field) => [$schema->getStatePath().'.'.$field => $messages])->all());
                        }
                        Notification::make()->title('Jadwal cabang disimpan')->success()->send();
                    }),
                Action::make('resetNow')->label('Reset sekarang')->color('danger')
                    ->authorize(fn () => auth()->user()->hasRole('admin') && auth()->user()->can('reset_queues'))
                    ->visible(fn () => auth()->user()->hasRole('admin') && auth()->user()->can('reset_queues'))
                    ->requiresConfirmation()->modalDescription('Seluruh layanan pada cabang ini memulai periode baru. Antrean lama dan struk dibersihkan. Cetakan yang sudah berjalan tidak dikirim ulang.')
                    ->schema([Hidden::make('proof')->required()])
                    ->fillForm(fn (Branch $record) => ['proof' => Crypt::encryptString(json_encode(['branch_id' => $record->id,
                        'period_id' => app(QueuePeriods::class)->ensure($record)->period_id, 'request_id' => (string) Str::uuid()]))])
                    ->action(function (Branch $record, array $data) {
                        $proof = json_decode(Crypt::decryptString($data['proof']), true, 8, JSON_THROW_ON_ERROR);
                        abort_unless((int) $proof['branch_id'] === (int) $record->id, 403);
                        $count = app(QueuePeriods::class)->reset($record, auth()->user(), $proof['period_id'], $proof['request_id']);
                        Notification::make()->title('Periode baru dimulai')->body($count.' antrean ditutup.')->success()->send();
                    }),
            ]);
    }
}
