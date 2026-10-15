<?php

namespace App\Filament\Pages;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Filament\Resources\BranchResource;
use App\Filament\Resources\ServiceSettingResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class BranchOperations extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = -20;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Operasional Seluruh Cabang';

    protected static ?string $slug = 'operasional-cabang';

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('admin') ? 'Operasional Cabang' : 'Operasional Cabang Saya';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->is_active && ($user->hasRole('admin') ||
            ($user->branch_id && $user->hasAnyRole(['admin_cabang', 'cs', 'teller'])));
    }

    public function content(Schema $schema): Schema
    {
        ['branches' => $branches, 'isAdmin' => $isAdmin] = $this->getViewData();
        $canManage = $isAdmin || auth()->user()?->hasRole('admin_cabang');
        $branchSections = [];

        foreach ($branches as $branch) {
            $branchSections[] = Section::make($branch->code.' - '.$branch->name)
                ->description($branch->type.' - '.$branch->operating_hours)
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->schema([
                    IconEntry::make('branch_'.$branch->id.'_active')
                        ->label('Status operasional')->state($branch->is_active)->boolean(),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Stat::make('Menunggu', $branch->waiting_count)->color('warning'),
                        Stat::make('Dilayani', $branch->called_count)->color('info'),
                        Stat::make('Selesai hari ini', $branch->completed_count)->color('success'),
                    ]),
                    Actions::make([
                        Action::make('kiosk_'.$branch->id)->label('Buka Kiosk')
                            ->icon(Heroicon::OutlinedTicket)
                            ->url(url('/ambil-nomor/'.$branch->code))->openUrlInNewTab(),
                        Action::make('display_'.$branch->id)->label('Buka Layar')
                            ->icon(Heroicon::OutlinedTv)->color('gray')
                            ->url(url('/display/'.$branch->code))->openUrlInNewTab(),
                    ])->visible($branch->is_active),
                    RepeatableEntry::make('branch_'.$branch->id.'_counters')
                        ->label('Loket layanan')->state($branch->serviceSettings)
                        ->placeholder('Belum ada loket layanan.')
                        ->schema([
                            TextEntry::make('counter_name')->label('Nama loket'),
                            IconEntry::make('is_active')->label('Buka')->boolean(),
                        ])->columns(2),
                    RepeatableEntry::make('branch_'.$branch->id.'_staff')
                        ->label('Akun petugas')->state($branch->users)
                        ->placeholder('Belum ada akun petugas.')
                        ->schema([
                            TextEntry::make('name')->label('Nama'),
                            TextEntry::make('role')->label('Peran')->badge()
                                ->state(fn (User $record): string => $record->roles->pluck('display_name')->implode(', ')),
                            TextEntry::make('email')->label('Email')->columnSpanFull(),
                            TextEntry::make('serviceSetting.counter_name')->label('Loket')
                                ->placeholder('Belum ditugaskan ke loket'),
                            IconEntry::make('is_active')->label('Aktif')->boolean(),
                        ])->columns(2),
                ]);
        }

        return $schema->extraAttributes(['wire:poll.10s' => '$refresh'])->components([
            Section::make($isAdmin ? 'Seluruh cabang, satu pusat kendali' : 'Layanan cabang Anda')
                ->description($isAdmin ? 'Pantau antrean, loket, dan akun petugas setiap cabang.' : 'Pantau antrean dan status loket di kantor Anda.')
                ->schema([
                    Actions::make([
                        Action::make('manage_users')->label('Kelola Akun')->color('gray')
                            ->icon(Heroicon::OutlinedUsers)->url(UserResource::getUrl())->visible($canManage),
                        Action::make('manage_counters')->label('Kelola Loket')->color('gray')
                            ->url(ServiceSettingResource::getUrl())->visible($canManage),
                        Action::make('manage_branches')->label('Kelola Cabang')->color('gray')
                            ->url(BranchResource::getUrl())->visible($isAdmin),
                        Action::make('queues')->label($isAdmin ? 'Riwayat Antrean' : 'Konsol Petugas')
                            ->icon(Heroicon::OutlinedQueueList)->url(url('/admin/queues')),
                    ]),
                ]),
            Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                Stat::make('Cabang aktif', $branches->where('is_active', true)->count())->icon(Heroicon::OutlinedBuildingOffice2),
                Stat::make('Antrean menunggu', $branches->sum('waiting_count'))->icon(Heroicon::OutlinedClock),
                Stat::make('Sedang dilayani', $branches->sum('called_count'))->icon(Heroicon::OutlinedMegaphone),
                Stat::make('Selesai hari ini', $branches->sum('completed_count'))->icon(Heroicon::OutlinedCheckCircle),
            ]),
            Grid::make(['default' => 1, 'xl' => 2])->schema($branchSections),
            TextEntry::make('refresh_notice')->hiddenLabel()
                ->state('Antrean hari ini - Diperbarui otomatis setiap 10 detik'),
            TextEntry::make('empty_branches')->hiddenLabel()->visible($branches->isEmpty())
                ->state('Belum ada cabang. Tambahkan kantor melalui Kelola Cabang.'),
        ]);
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $dailyQueues = fn (Builder $query) => $query->withoutGlobalScopes()
            ->where('created_at', '>=', $today)->where('created_at', '<', $tomorrow);

        $branches = Branch::query()
            ->when(! $user->hasRole('admin'), fn (Builder $query) => $query->whereKey($user->branch_id))
            ->with([
                'serviceSettings' => fn ($query) => $query->orderBy('service')->orderBy('id'),
                'users' => fn ($query) => $query->withRoleNames(['cs', 'teller'])
                    ->with(['roles', 'serviceSetting'])->orderBy('name'),
            ])
            ->withCount([
                'queues as waiting_count' => fn (Builder $query) => $dailyQueues($query)->where('status', QueueStatus::WAITING),
                'queues as called_count' => fn (Builder $query) => $dailyQueues($query)->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING]),
                'queues as completed_count' => fn (Builder $query) => $dailyQueues($query)->where('status', QueueStatus::COMPLETED),
            ])
            ->orderBy('code')->get();

        return ['branches' => $branches, 'isAdmin' => $user->hasRole('admin')];
    }
}
