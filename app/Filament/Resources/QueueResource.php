<?php

namespace App\Filament\Resources;

use App\Enums\QueueStatus;
use App\Filament\Resources\QueueResource\Pages\ManageQueues;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\Scopes\ServiceSettingScope;
use App\Models\ServiceSetting;
use App\Services\IntegrityRules;
use App\Services\Printing\TicketPrinting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_group_queue');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.queue_management');
    }

    public static function getModelLabel(): string
    {
        return __('app.ticket_number');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.queue_management');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_queue') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_queue') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete_queue') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()->currentPeriod()->with(['serviceSetting', 'servedBy', 'branch']);

        if ($user?->hasRole('admin_cabang')) {
            return $query->where('branch_id', $user->branch_id ?? 0);
        }

        if ($user && ! $user->hasRole('admin')) {
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }

            $userSetting = $user->serviceSetting;
            $serviceKey = $userSetting?->service;

            if (! $serviceKey) {
                $serviceKey = match (true) {
                    $user->hasRole('cs') => 'cs',
                    $user->hasRole('teller') => 'teller',
                    default => null,
                };
            }

            $matchingSettingIds = $serviceKey
                ? ServiceSetting::query()
                    ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('service', $serviceKey)
                    ->pluck('id')
                    ->toArray()
                : ($user->service_setting_id ? [$user->service_setting_id] : []);

            $baseQuery = Queue::query()
                ->withoutGlobalScope(ServiceSettingScope::class);

            if ($user->branch_id) {
                $baseQuery->where('branch_id', $user->branch_id);
            }

            if (! empty($matchingSettingIds)) {
                $baseQuery->whereIn('service_setting_id', $matchingSettingIds);
            }

            // 1. Prioritas 1: Antrean yang SEDANG DIPANGGIL / DILAYANI oleh petugas ini atau di loket ini
            $activeQueueId = (clone $baseQuery)
                ->where(function ($q) use ($user) {
                    if ($user->service_setting_id) {
                        $q->where('service_setting_id', $user->service_setting_id);
                    } else {
                        $q->where('served_by', $user->id);
                    }
                })
                ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
                ->currentPeriod()
                ->latest('called_at')
                ->value('id');

            if ($activeQueueId) {
                return $query->where('id', $activeQueueId);
            }

            // 2. Prioritas 2: Tepat 1 antrean MENUNGGU berikutnya (paling awal / FIFO) pada layanan loket ini
            $nextWaitingId = (clone $baseQuery)
                ->where('status', QueueStatus::WAITING)
                ->currentPeriod()
                ->oldest('id')
                ->value('id');

            if ($nextWaitingId) {
                return $query->where('id', $nextWaitingId);
            }

            // 3. Jika tidak ada antrean aktif dan tidak ada yang menunggu -> tampilkan 0 baris (kosong)
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $assignedServiceId = $user?->service_setting_id;

        return $schema->components([
            TextInput::make('number')->label(fn () => __('app.queue_number'))->disabled()->dehydrated(false)->placeholder('Otomatis sesuai periode cabang'),
            Select::make('branch_id')
                ->label('Cabang')->disabledOn('edit')->live()
                ->afterStateUpdated(fn ($set) => $set('service_setting_id', null))
                ->relationship('branch', 'name')
                ->default($user?->branch_id)
                ->required(),
            Select::make('service_setting_id')
                ->label(fn () => __('app.service'))
                ->options(function (Get $get) use ($user) {
                    $query = ServiceSetting::query()->where('branch_id', $get('branch_id'))->where('is_active', true);
                    if ($user && ! $user->hasRole('admin') && $user->branch_id) {
                        $query->where('branch_id', $user->branch_id);
                    }

                    return $query->pluck('counter_name', 'id');
                })
                ->rules(fn (Get $get) => [Rule::exists('service_settings', 'id')->where('branch_id', $get('branch_id'))->where('is_active', true)])
                ->disabledOn('edit')
                ->default($assignedServiceId)
                ->disabled(fn (string $operation) => $operation === 'edit' || (! empty($assignedServiceId) && ! $user->hasRole('admin')))
                ->dehydrated()
                ->required(),
            Select::make('status')
                ->label(fn () => __('app.status'))->disabled()->dehydrated()
                ->options([
                    QueueStatus::WAITING->value => QueueStatus::WAITING->getLabel(),
                    QueueStatus::CALLED->value => QueueStatus::CALLED->getLabel(),
                    QueueStatus::SERVING->value => QueueStatus::SERVING->getLabel(),
                    QueueStatus::COMPLETED->value => QueueStatus::COMPLETED->getLabel(),
                    QueueStatus::SKIPPED->value => QueueStatus::SKIPPED->getLabel(),
                ])
                ->default(QueueStatus::WAITING->value)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = auth()->user()?->hasRole('admin') ?? false;

        return $table
            ->poll('3s')
            ->paginated($isAdmin ? [10, 25, 50] : false)
            ->emptyStateHeading('Tidak Ada Antrean')
            ->emptyStateDescription('Tidak ada antrean menunggu saat ini.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->columns([
                TextColumn::make('number')
                    ->label(fn () => __('app.queue_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->visible($isAdmin)
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('serviceSetting.counter_name')->label(fn () => __('app.service'))->sortable()->wrap()->visible($isAdmin)->placeholder('-'),
                TextColumn::make('status')
                    ->label(fn () => __('app.status'))
                    ->badge()
                    ->color(fn (QueueStatus $state) => $state->getColor())
                    ->formatStateUsing(fn (QueueStatus $state) => $state->getLabel()),
                TextColumn::make('called_at')->label(fn () => __('app.call_time'))->dateTime($isAdmin ? 'd M Y H:i' : 'H:i')->placeholder('-'),
                TextColumn::make('servedBy.name')->label(fn () => __('app.active_staff'))->wrap()->visible($isAdmin)->toggleable(isToggledHiddenByDefault: true)->placeholder('-'),
            ])->filters([
                SelectFilter::make('branch_id')
                    ->label('Filter Cabang')
                    ->relationship('branch', 'name')
                    ->visible($isAdmin),
                SelectFilter::make('status')->label(fn () => __('app.status'))->options([
                    QueueStatus::WAITING->value => QueueStatus::WAITING->getLabel(),
                    QueueStatus::CALLED->value => QueueStatus::CALLED->getLabel(),
                    QueueStatus::SERVING->value => QueueStatus::SERVING->getLabel(),
                    QueueStatus::COMPLETED->value => QueueStatus::COMPLETED->getLabel(),
                    QueueStatus::SKIPPED->value => QueueStatus::SKIPPED->getLabel(),
                ]),
            ])->recordActions([
                Action::make('reprint')
                    ->label('Cetak Ulang')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->authorize(fn (Queue $record) => auth()->user()?->can('print', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription('Cetak nomor tiket yang sama. Periksa kertas dan printer terlebih dahulu agar tidak menghasilkan salinan ganda.')
                    ->action(function (Queue $record) {
                        abort_unless(auth()->user()?->can('print', $record), 403);
                        try {
                            $job = app(TicketPrinting::class)->reprint($record, auth()->user(), request());
                            if ($job->transport === 'browser') {
                                return redirect()->route('print-jobs.receipt', ['printJob' => $job->id, 'autoprint' => 1]);
                            }
                            Notification::make()->title('Cetak ulang '.$record->number.' dijadwalkan')->body('Nomor antrean tetap sama. Periksa hasil pada printer.')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Cetak ulang belum dapat diproses')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('call')->button()
                    ->authorize(fn (Queue $record) => auth()->user()?->can('update', $record) ?? false)
                    ->label(fn () => __('app.call_next'))
                    ->color('info')
                    ->visible(fn (Queue $record) => $record->status === QueueStatus::WAITING)
                    ->action(function (Queue $record): void {
                        abort_unless(auth()->user()?->can('update', $record), 403);
                        DB::transaction(function () use ($record): void {
                            Branch::whereKey($record->branch_id)->lockForUpdate()->firstOrFail();
                            $targetSettingId = auth()->user()?->service_setting_id ?? $record->service_setting_id;
                            ServiceSetting::whereKey($targetSettingId)->lockForUpdate()->firstOrFail();
                            $lockedRecord = Queue::query()->currentPeriod()->where('id', $record->id)->lockForUpdate()->first();
                            if (! $lockedRecord || $lockedRecord->status !== QueueStatus::WAITING) {
                                return;
                            }

                            $targetSettingId = auth()->user()?->service_setting_id ?? $lockedRecord->service_setting_id;

                            // Check if officer's own counter currently has an active CALLED/SERVING ticket
                            $myCounterHasActiveQueue = false;
                            if ($targetSettingId) {
                                $myCounterHasActiveQueue = Queue::query()
                                    ->where('service_setting_id', $targetSettingId)
                                    ->currentPeriod()
                                    ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
                                    ->lockForUpdate()
                                    ->exists();
                            } else {
                                $myCounterHasActiveQueue = Queue::query()
                                    ->where('served_by', auth()->id())
                                    ->currentPeriod()
                                    ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
                                    ->lockForUpdate()
                                    ->exists();
                            }

                            if ($myCounterHasActiveQueue) {
                                $counterName = auth()->user()?->serviceSetting?->counter_name ?? 'Loket Anda';
                                Notification::make()
                                    ->title('Peringatan: Loket Anda Masih Memiliki Antrean Aktif!')
                                    ->body("{$counterName} sedang memanggil/melayani nasabah lain. Selesaikan transaksi di loket Anda terlebih dahulu sebelum memanggil antrean baru.")
                                    ->danger()
                                    ->send();

                                return;
                            }

                            abort_unless(auth()->user()?->can('update', $lockedRecord), 403);

                            // Update record to CALLED status
                            $lockedRecord->update([
                                'status' => QueueStatus::CALLED,
                                'called_at' => now(),
                                'served_by' => auth()->id(),
                                'service_setting_id' => $targetSettingId,
                            ]);

                            QueueLog::create([
                                'queue_id' => $lockedRecord->id,
                                'branch_id' => $lockedRecord->branch_id,
                                'actor_id' => auth()->id(),
                                'action' => 'called',
                            ]);
                        });
                    }),
                Action::make('recall')->button()
                    ->authorize(fn (Queue $record) => auth()->user()?->can('update', $record) ?? false)
                    ->label('Panggil Ulang')
                    ->color('warning')
                    ->icon(Heroicon::SpeakerWave)
                    ->visible(fn (Queue $record) => in_array($record->status, [QueueStatus::CALLED, QueueStatus::SERVING], true))
                    ->action(function (Queue $record): void {
                        abort_unless(auth()->user()?->can('update', $record), 403);
                        DB::transaction(function () use ($record): void {
                            Branch::whereKey($record->branch_id)->lockForUpdate()->firstOrFail();
                            $lockedRecord = Queue::query()->currentPeriod()->where('id', $record->id)->lockForUpdate()->first();
                            if ($lockedRecord && in_array($lockedRecord->status, [QueueStatus::CALLED, QueueStatus::SERVING], true)) {
                                $targetSettingId = auth()->user()?->service_setting_id ?? $lockedRecord->service_setting_id;
                                abort_unless(auth()->user()?->can('update', $lockedRecord), 403);
                                $lockedRecord->update([
                                    'called_at' => now(),
                                    'served_by' => auth()->id(),
                                    'service_setting_id' => $targetSettingId,
                                ]);

                                QueueLog::create([
                                    'queue_id' => $lockedRecord->id,
                                    'branch_id' => $lockedRecord->branch_id,
                                    'actor_id' => auth()->id(),
                                    'action' => 'recalled',
                                ]);
                            }
                        });

                        Notification::make()
                            ->title("Antrean {$record->number} dipanggil ulang")
                            ->body('Panggilan ulang berhasil dikirim ke layar display.')
                            ->warning()
                            ->send();
                    }),
                Action::make('complete')->button()
                    ->authorize(fn (Queue $record) => auth()->user()?->can('update', $record) ?? false)
                    ->label(fn () => __('app.complete'))
                    ->color('success')
                    ->visible(fn (Queue $record) => in_array($record->status, [QueueStatus::CALLED, QueueStatus::SERVING], true))
                    ->action(function (Queue $record): void {
                        abort_unless(auth()->user()?->can('update', $record), 403);
                        DB::transaction(function () use ($record): void {
                            Branch::whereKey($record->branch_id)->lockForUpdate()->firstOrFail();
                            $lockedRecord = Queue::query()->currentPeriod()->where('id', $record->id)->lockForUpdate()->first();
                            if ($lockedRecord && in_array($lockedRecord->status, [QueueStatus::CALLED, QueueStatus::SERVING], true)) {
                                abort_unless(auth()->user()?->can('update', $lockedRecord), 403);
                                $lockedRecord->update(['status' => QueueStatus::COMPLETED]);

                                QueueLog::create([
                                    'queue_id' => $lockedRecord->id,
                                    'branch_id' => $lockedRecord->branch_id,
                                    'actor_id' => auth()->id(),
                                    'action' => 'completed',
                                ]);
                            }
                        });
                    }),
                Action::make('skip')->button()
                    ->authorize(fn (Queue $record) => auth()->user()?->can('update', $record) ?? false)
                    ->label(fn () => __('app.skip'))
                    ->color('gray')
                    ->visible(fn (Queue $record) => in_array($record->status, [QueueStatus::WAITING, QueueStatus::CALLED, QueueStatus::SERVING], true))
                    ->action(function (Queue $record): void {
                        abort_unless(auth()->user()?->can('update', $record), 403);
                        DB::transaction(function () use ($record): void {
                            Branch::whereKey($record->branch_id)->lockForUpdate()->firstOrFail();
                            $lockedRecord = Queue::query()->currentPeriod()->where('id', $record->id)->lockForUpdate()->first();
                            if ($lockedRecord && in_array($lockedRecord->status, [QueueStatus::WAITING, QueueStatus::CALLED, QueueStatus::SERVING], true)) {
                                abort_unless(auth()->user()?->can('update', $lockedRecord), 403);
                                $lockedRecord->update(['status' => QueueStatus::SKIPPED]);

                                QueueLog::create([
                                    'queue_id' => $lockedRecord->id,
                                    'branch_id' => $lockedRecord->branch_id,
                                    'actor_id' => auth()->id(),
                                    'action' => 'skipped',
                                ]);
                            }
                        });
                    }),
                ActionGroup::make([
                    DeleteAction::make()
                        ->disabled(fn ($record) => IntegrityRules::deletionReason($record) !== null)
                        ->tooltip(fn ($record) => IntegrityRules::deletionReason($record))
                        ->before(fn ($record, DeleteAction $action) => IntegrityRules::beforeDelete($record, $action)),
                ])->visible(fn () => auth()->user()?->hasRole('admin') ?? false),
            ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageQueues::route('/')];
    }
}
