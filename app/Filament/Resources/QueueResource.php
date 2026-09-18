<?php

namespace App\Filament\Resources;

use App\Enums\QueueStatus;
use App\Filament\Resources\QueueResource\Pages\ManageQueues;
use App\Models\Queue;
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
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';
    protected static ?int $navigationSort = -10;

    public static function getNavigationLabel(): string { return __('app.queue_management'); }
    public static function getModelLabel(): string { return __('app.ticket_number'); }
    public static function getPluralModelLabel(): string { return __('app.queue_management'); }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_queue') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_queue') ?? false; }
    public static function canEdit(Model $record): bool { return auth()->user()?->can('edit_queue') ?? false; }
    public static function canDelete(Model $record): bool { return auth()->user()?->can('delete_queue') ?? false; }

    protected static array $memoizedResults = [];

    public static function getEloquentQuery(): Builder
    {
        // Auto-expire leftover WAITING/CALLED tickets from past days so yesterday's unserved tickets do not stay waiting
        Queue::query()
            ->whereDate('created_at', '<', now()->toDateString())
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->update(['status' => QueueStatus::SKIPPED]);

        $query = parent::getEloquentQuery()->with(['serviceSetting', 'servedBy']);
        $user = auth()->user();

        if ($user && ! $user->hasRole('admin')) {
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
                ? ServiceSetting::query()->where('service', $serviceKey)->pluck('id')->toArray()
                : ($user->service_setting_id ? [$user->service_setting_id] : []);

            $baseQuery = Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class);

            if (! empty($matchingSettingIds)) {
                $baseQuery->whereIn('service_setting_id', $matchingSettingIds);
            }

            // 1. Priority 1: Ticket currently CALLED/SERVING by THIS OFFICER or at THIS COUNTER
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

            // 2. Priority 2: Single next WAITING ticket (oldest / FIFO) in this service category
            $nextWaitingId = (clone $baseQuery)
                ->where('status', QueueStatus::WAITING)
                ->whereDate('created_at', now()->toDateString())
                ->oldest('id')
                ->value('id');

            if ($nextWaitingId) {
                return $query->where('id', $nextWaitingId);
            }

            // 3. No active or waiting ticket -> empty result set (0 rows)
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
            Select::make('service_setting_id')
                ->label(fn () => __('app.service'))
                ->options(fn () => ServiceSetting::all()->pluck('counter_name', 'id'))
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
                    ->searchable($isAdmin)
                    ->sortable($isAdmin)
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('serviceSetting.counter_name')->label(fn () => __('app.service'))->sortable($isAdmin)->placeholder('-'),
                TextColumn::make('status')
                    ->label(fn () => __('app.status'))
                    ->badge()
                    ->color(fn (QueueStatus $state) => $state->getColor())
                    ->formatStateUsing(fn (QueueStatus $state) => $state->getLabel()),
                TextColumn::make('called_at')->label(fn () => __('app.call_time'))->dateTime('d M Y H:i')->placeholder('-'),
                TextColumn::make('servedBy.name')->label(fn () => __('app.active_staff'))->placeholder('-'),
            ])->filters($isAdmin ? [
                Filter::make('today')
                    ->label('📅 Antrean Hari Ini (Default)')
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', now()->toDateString())),
                SelectFilter::make('status')->label(fn () => __('app.status'))->options([
                    QueueStatus::WAITING->value => QueueStatus::WAITING->getLabel(),
                    QueueStatus::CALLED->value => QueueStatus::CALLED->getLabel(),
                    QueueStatus::SERVING->value => QueueStatus::SERVING->getLabel(),
                    QueueStatus::COMPLETED->value => QueueStatus::COMPLETED->getLabel(),
                    QueueStatus::SKIPPED->value => QueueStatus::SKIPPED->getLabel(),
                ]),
            ] : [])->recordActions([
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

                            // 1. Check if officer's own counter currently has an active CALLED/SERVING ticket
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
                        });
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
