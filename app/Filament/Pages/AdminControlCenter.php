<?php

namespace App\Filament\Pages;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\Scopes\ServiceSettingScope;
use App\Models\ServiceSetting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class AdminControlCenter extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.admin-control-center';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public ?string $selectedBranchId = null;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('admin') && $user->branch_id) {
            $this->selectedBranchId = (string) $user->branch_id;
        }
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_group_queue');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.control_center');
    }

    public function getTitle(): string|Htmlable
    {
        return __('app.control_center');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_control_center') ?? false;
    }

    public function branchFilter(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('selectedBranchId')
                ->label('Pantau cabang')
                ->placeholder('Semua cabang')
                ->options(Branch::where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn ($branch) => [$branch->id => $branch->code.' - '.$branch->name]))
                ->searchable()->live()->visible(fn () => auth()->user()?->hasRole('admin')),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return collect(['cs' => 'Customer Service', 'teller' => 'Teller'])->map(fn ($label, $service) => Action::make('reset_'.$service)
            ->label('Reset '.$label)
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('danger')->requiresConfirmation()
            ->modalHeading('Tutup antrean aktif '.$label.'?')
            ->modalDescription('Antrean aktif pada cakupan cabang yang dipilih akan ditandai dilewati. Riwayat tetap disimpan.')
            ->action(fn () => $this->resetService($service))
        )->values()->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ringkasan Antrean per Cabang')
            ->records(fn () => collect($this->getViewData()['branchStats'])->keyBy('id'))
            ->paginated(false)
            ->columns([
                TextColumn::make('code')->label('Kode'),
                TextColumn::make('name')->label('Nama Kantor')->wrap(),
                TextColumn::make('type')->label('Tipe')->badge()->color('gray'),
                TextColumn::make('waiting')->label('Menunggu')->badge()->color('warning'),
                TextColumn::make('called')->label('Dilayani')->badge()->color('info'),
                TextColumn::make('completed')->label('Selesai')->badge()->color('success'),
                TextColumn::make('total')->label('Total Tiket'),
            ])
            ->recordActions([
                Action::make('kiosk')->label('Kiosk')
                    ->icon(Heroicon::OutlinedTicket)
                    ->url(fn (array $record) => url('/ambil-nomor/'.$record['code']))->openUrlInNewTab(),
                Action::make('display')->label('Layar')
                    ->icon(Heroicon::OutlinedTv)
                    ->url(fn (array $record) => url('/display/'.$record['code']))->openUrlInNewTab(),
            ]);
    }

    public function resetService(string $serviceKey): void
    {
        if (! in_array($serviceKey, ['cs', 'teller'], true)) {
            return;
        }

        $branchId = $this->selectedBranchId ? (int) $this->selectedBranchId : null;

        $settingQuery = ServiceSetting::query()->where('service', $serviceKey);
        if ($branchId) {
            $settingQuery->where('branch_id', $branchId);
        }
        $settingIds = $settingQuery->pluck('id')->toArray();

        if (empty($settingIds)) {
            return;
        }

        $count = Queue::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereIn('service_setting_id', $settingIds)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->update(['status' => QueueStatus::SKIPPED]);

        Notification::make()
            ->title("Antrean {$serviceKey} direset")
            ->body("{$count} antrean aktif ditutup sebagai dilewati; riwayat tetap tersimpan.")
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $today = now()->startOfDay();
        $branchId = $this->selectedBranchId ? (int) $this->selectedBranchId : null;

        $summary = [];
        $settingsQuery = ServiceSetting::query()->whereIn('service', ['cs', 'teller']);
        if ($branchId) {
            $settingsQuery->where('branch_id', $branchId);
        }
        $settings = $settingsQuery->get();

        foreach (['cs' => __('app.role_cs'), 'teller' => __('app.role_teller')] as $serviceKey => $label) {
            $serviceSettingIds = $settings->where('service', $serviceKey)->pluck('id')->toArray();

            $queueQuery = fn () => Queue::query()
                ->withoutGlobalScope(ServiceSettingScope::class)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereIn('service_setting_id', $serviceSettingIds)
                ->where('created_at', '>=', $today);

            $summary[$serviceKey] = [
                'label' => $label,
                'waiting' => empty($serviceSettingIds) ? 0 : $queueQuery()->where('status', QueueStatus::WAITING)->count(),
                'called' => empty($serviceSettingIds) ? 0 : $queueQuery()->where('status', QueueStatus::CALLED)->count(),
                'completed' => empty($serviceSettingIds) ? 0 : $queueQuery()->where('status', QueueStatus::COMPLETED)->count(),
                'skipped' => empty($serviceSettingIds) ? 0 : $queueQuery()->where('status', QueueStatus::SKIPPED)->count(),
            ];
        }

        // 9 Branches Aggregated Matrix
        $allBranches = Branch::where('is_active', true)->orderBy('code')->get();
        $branchStats = [];
        foreach ($allBranches as $b) {
            $bQueues = Queue::withoutGlobalScope(ServiceSettingScope::class)
                ->where('branch_id', $b->id)
                ->where('created_at', '>=', $today)
                ->get();

            $branchStats[] = [
                'id' => $b->id,
                'code' => $b->code,
                'name' => $b->name,
                'type' => $b->type,
                'waiting' => $bQueues->where('status', QueueStatus::WAITING)->count(),
                'called' => $bQueues->where('status', QueueStatus::CALLED)->count(),
                'completed' => $bQueues->where('status', QueueStatus::COMPLETED)->count(),
                'total' => $bQueues->count(),
            ];
        }

        $staffQuery = User::role(['cs', 'teller'])->with(['roles', 'branch']);
        if ($branchId) {
            $staffQuery->where('branch_id', $branchId);
        }

        $recentQueuesQuery = Queue::withoutGlobalScope(ServiceSettingScope::class)
            ->with(['serviceSetting', 'branch', 'servedBy'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('id')
            ->limit(12);

        return [
            'summary' => $summary,
            'branchStats' => $branchStats,
            'branches' => $allBranches,
            'selectedBranch' => $branchId ? Branch::find($branchId) : null,
            'staff' => $staffQuery->get(),
            'recentQueues' => $recentQueuesQuery->get(),
        ];
    }
}
