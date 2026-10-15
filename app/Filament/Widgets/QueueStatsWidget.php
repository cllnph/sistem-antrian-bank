<?php

namespace App\Filament\Widgets;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QueueStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '3s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        $todayTotal = Queue::query()->where('created_at', '>=', $today)->count();
        $waitingTotal = Queue::query()->where('created_at', '>=', $today)->where('status', QueueStatus::WAITING)->count();
        $completedTotal = Queue::query()->where('created_at', '>=', $today)->where('status', QueueStatus::COMPLETED)->count();

        $branchId = auth()->user()?->hasRole('admin') ? null : (auth()->user()?->branch_id ?? 0);
        $staff = User::query()->where('is_active', true)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId));
        $csCount = (clone $staff)->withRoleNames(['cs'])->count();
        $tellerCount = (clone $staff)->withRoleNames(['teller'])->count();
        $activeStaff = (clone $staff)->withRoleNames(['cs', 'teller'])->count();

        return [
            Stat::make('Antrean Hari Ini', $todayTotal)
                ->description('Total tiket antrean hari ini')
                ->descriptionIcon(Heroicon::Ticket)
                ->color('primary'),

            Stat::make('Sedang Menunggu', $waitingTotal)
                ->description('Dalam daftar antrean menunggu')
                ->descriptionIcon(Heroicon::Clock)
                ->color('warning'),

            Stat::make('Selesai Dilayani', $completedTotal)
                ->description('Berhasil dilayani hari ini')
                ->descriptionIcon(Heroicon::CheckCircle)
                ->color('success'),

            Stat::make('Petugas Operasional', $activeStaff)
                ->description("{$csCount} CS • {$tellerCount} Teller bertugas")
                ->descriptionIcon(Heroicon::UserGroup)
                ->color('info'),
        ];
    }
}
