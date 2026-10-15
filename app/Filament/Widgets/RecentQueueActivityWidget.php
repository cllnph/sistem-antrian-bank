<?php

namespace App\Filament\Widgets;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\Scopes\ServiceSettingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentQueueActivityWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    public ?string $selectedBranchId = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('view_control_center') ?? false;
    }

    public function table(Table $table): Table
    {
        $branchId = auth()->user()?->hasRole('admin')
            ? $this->selectedBranchId
            : (auth()->user()?->branch_id ?? 0);

        return $table
            ->heading(__('app.queue_management').' Terkini')
            ->query(Queue::withoutGlobalScope(ServiceSettingScope::class)
                ->with(['serviceSetting', 'branch'])
                ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
                ->latest('id')->limit(12))
            ->poll('10s')
            ->paginated(false)
            ->columns([
                TextColumn::make('number')->label('Nomor antrean')->weight('bold'),
                TextColumn::make('serviceSetting.counter_name')->label('Loket')->wrap()->placeholder('-'),
                TextColumn::make('branch.name')->label('Kantor')->wrap()->placeholder('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (QueueStatus $state): string => $state->getLabel())
                    ->color(fn (QueueStatus $state): string => $state->getColor()),
                TextColumn::make('created_at')->label('Dibuat')->since(),
            ])
            ->emptyStateHeading('Belum ada aktivitas antrean.');
    }
}
