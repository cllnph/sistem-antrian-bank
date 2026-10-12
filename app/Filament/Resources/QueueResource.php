<?php

namespace App\Filament\Resources;

use App\Enums\QueueStatus;
use App\Filament\Resources\QueueResource\Pages\ManageQueues;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\ServiceSetting;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string { return __('app.nav_group_queue'); }
    public static function getNavigationLabel(): string { return __('app.queue_management'); }
    public static function getModelLabel(): string { return __('app.ticket_number'); }
    public static function getPluralModelLabel(): string { return __('app.queue_management'); }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_queue') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_queue') ?? false; }
    public static function canEdit(Model $record): bool { return auth()->user()?->can('edit_queue') ?? false; }
    public static function canDelete(Model $record): bool { return auth()->user()?->can('delete_queue') ?? false; }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // Auto-expire leftover WAITING/CALLED tickets from past days
        $expireQuery = Queue::query()
            ->whereDate('created_at', '<', now()->toDateString())
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED]);
        if ($user && ! $user->hasRole('admin') && $user->branch_id) {
            $expireQuery->where('branch_id', $user->branch_id);
        }
        $expireQuery->update(['status' => QueueStatus::SKIPPED]);

        $query = parent::getEloquentQuery()->with(['serviceSetting', 'servedBy', 'branch']);

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
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class);

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
                ->whereDate('created_at', now()->toDateString())
                ->latest('called_at')
                ->value('id');

            if ($activeQueueId) {
                return $query->where('id', $activeQueueId);
            }

            // 2. Prioritas 2: Tepat 1 antrean MENUNGGU berikutnya (paling awal / FIFO) pada layanan loket ini
            $nextWaitingId = (clone $baseQuery)
                ->where('status', QueueStatus::WAITING)
                ->whereDate('created_at', now()->toDateString())
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
            TextInput::make('number')->label(fn () => __('app.queue_number'))->required(),
            Select::make('branch_id')
                ->label('Cabang')
                ->relationship('branch', 'name')
                ->default($user?->branch_id)
                ->required(),
            Select::make('service_setting_id')
                ->label(fn () => __('app.service'))
                ->options(function () use ($user) {
                    $query = ServiceSetting::query();
                    if ($user && ! $user->hasRole('admin') && $user->branch_id) {
                        $query->where('branch_id', $user->branch_id);
                    }
                    return $query->pluck('counter_name', 'id');
                })
                ->default($assignedServiceId)
                ->disabled(!empty($assignedServiceId) && !$user->hasRole('admin'))
                ->dehydrated()
                ->required(),
            Select::make('status')
                ->label(fn () => __('app.status'))
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
            ->emptyStateIcon('heroicon-o-check-circle')
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
                TextColumn::make('serviceSetting.counter_name')->label(fn () => __('app.service'))->sortable()->placeholder('-'),
                TextColumn::make('status')
                    ->label(fn () => __('app.status'))
                    ->badge()
                    ->color(fn (QueueStatus $state) => $state->getColor())
                    ->formatStateUsing(fn (QueueStatus $state) => $state->getLabel()),
                TextColumn::make('called_at')->label(fn () => __('app.call_time'))->dateTime('d M Y H:i')->placeholder('-'),
                TextColumn::make('servedBy.name')->label(fn () => __('app.active_staff'))->placeholder('-'),
            ])->filters([
                Filter::make('today')
                    ->label('📅 Antrean Hari Ini (Default)')
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', now()->toDateString())),
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
                Action::make('call')
                    ->label(fn () => __('app.call_next'))
                    ->color('info')
                    ->visible(fn (Queue $record) => $record->status === QueueStatus::WAITING)
                    ->action(function (Queue $record): void {
                        DB::transaction(function () use ($record): void {
                            $lockedRecord = Queue::query()->where('id', $record->id)->lockForUpdate()->first();
                            if (! $lockedRecord || $lockedRecord->status !== QueueStatus::WAITING) {
                                return;
                            }

                            $targetSettingId = auth()->user()?->service_setting_id ?? $lockedRecord->service_setting_id;

                            // Check if officer's own counter currently has an active CALLED/SERVING ticket
                            $myCounterHasActiveQueue = false;
                            if ($targetSettingId) {
                                $myCounterHasActiveQueue = Queue::query()
                                    ->where('service_setting_id', $targetSettingId)
                                    ->whereDate('created_at', now()->toDateString())
                                    ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
                                    ->lockForUpdate()
                                    ->exists();
                            } else {
                                $myCounterHasActiveQueue = Queue::query()
                                    ->where('served_by', auth()->id())
                                    ->whereDate('created_at', now()->toDateString())
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
                Action::make('recall')
                    ->label('Panggil Ulang')
                    ->color('warning')
                    ->icon('heroicon-m-speaker-wave')
                    ->visible(fn (Queue $record) => in_array($record->status, [QueueStatus::CALLED, QueueStatus::SERVING], true))
                    ->action(function (Queue $record): void {
                        DB::transaction(function () use ($record): void {
                            $lockedRecord = Queue::query()->where('id', $record->id)->lockForUpdate()->first();
                            if ($lockedRecord && in_array($lockedRecord->status, [QueueStatus::CALLED, QueueStatus::SERVING], true)) {
                                $targetSettingId = auth()->user()?->service_setting_id ?? $lockedRecord->service_setting_id;
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
                Action::make('complete')
                    ->label(fn () => __('app.complete'))
                    ->color('success')
                    ->visible(fn (Queue $record) => in_array($record->status, [QueueStatus::CALLED, QueueStatus::SERVING], true))
                    ->action(function (Queue $record): void {
                        DB::transaction(function () use ($record): void {
                            $lockedRecord = Queue::query()->where('id', $record->id)->lockForUpdate()->first();
                            if ($lockedRecord && in_array($lockedRecord->status, [QueueStatus::CALLED, QueueStatus::SERVING], true)) {
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
                Action::make('skip')
                    ->label(fn () => __('app.skip'))
                    ->color('gray')
                    ->visible(fn (Queue $record) => in_array($record->status, [QueueStatus::WAITING, QueueStatus::CALLED, QueueStatus::SERVING], true))
                    ->action(function (Queue $record): void {
                        DB::transaction(function () use ($record): void {
                            $lockedRecord = Queue::query()->where('id', $record->id)->lockForUpdate()->first();
                            if ($lockedRecord && in_array($lockedRecord->status, [QueueStatus::WAITING, QueueStatus::CALLED, QueueStatus::SERVING], true)) {
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
                EditAction::make()->visible(fn () => auth()->user()?->hasRole('admin') ?? false),
                DeleteAction::make()->visible(fn () => auth()->user()?->hasRole('admin') ?? false),
            ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageQueues::route('/')];
    }
}
