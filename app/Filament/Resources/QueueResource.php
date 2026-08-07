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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    public static function getNavigationLabel(): string { return __('app.queue_management'); }
    public static function getModelLabel(): string { return __('app.ticket_number'); }
    public static function getPluralModelLabel(): string { return __('app.queue_management'); }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_queue') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_queue') ?? false; }
    public static function canEdit(Model $record): bool { return auth()->user()?->can('edit_queue') ?? false; }
    public static function canDelete(Model $record): bool { return auth()->user()?->can('delete_queue') ?? false; }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['serviceSetting', 'servedBy']);
        $user = auth()->user();

        if ($user && !$user->hasRole('admin') && $user->service_setting_id) {
            return $query->where('service_setting_id', $user->service_setting_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $assignedServiceId = $user?->service_setting_id;

        return $schema->components([
            TextInput::make('number')->label(fn () => __('app.queue_number'))->required()->unique(ignoreRecord: true),
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
        return $table
            ->poll('3s')
            ->columns([
                TextColumn::make('number')->label(fn () => __('app.queue_number'))->searchable()->sortable(),
                TextColumn::make('serviceSetting.counter_name')->label(fn () => __('app.service'))->sortable()->placeholder('-'),
                TextColumn::make('status')
                    ->label(fn () => __('app.status'))
                    ->badge()
                    ->color(fn (QueueStatus $state) => $state->getColor())
                    ->formatStateUsing(fn (QueueStatus $state) => $state->getLabel()),
                TextColumn::make('called_at')->label(fn () => __('app.call_time'))->dateTime('d M Y H:i')->placeholder('-'),
                TextColumn::make('servedBy.name')->label(fn () => __('app.active_staff'))->placeholder('-'),
            ])->filters([
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
                        $hasActiveQueue = Queue::query()
                            ->where('service_setting_id', $record->service_setting_id)
                            ->where('served_by', auth()->id())
                            ->where('status', QueueStatus::CALLED)
                            ->exists();

                        if ($hasActiveQueue) {
                            Notification::make()
                                ->title(__('app.status_called'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => QueueStatus::CALLED,
                            'called_at' => now(),
                            'served_by' => auth()->id(),
                        ]);
                    }),
                Action::make('complete')
                    ->label(fn () => __('app.complete'))
                    ->color('success')
                    ->visible(fn (Queue $record) => in_array($record->status, [QueueStatus::CALLED, QueueStatus::SERVING], true))
                    ->action(fn (Queue $record) => $record->update(['status' => QueueStatus::COMPLETED])),
                Action::make('skip')
                    ->label(fn () => __('app.skip'))
                    ->color('gray')
                    ->visible(fn (Queue $record) => in_array($record->status, [QueueStatus::WAITING, QueueStatus::CALLED, QueueStatus::SERVING], true))
                    ->action(fn (Queue $record) => $record->update(['status' => QueueStatus::SKIPPED])),
                EditAction::make(),
                DeleteAction::make(),
            ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageQueues::route('/')];
    }
}
