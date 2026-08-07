<?php

namespace App\Filament\Widgets;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\User;
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
        $waitingTotal = Queue::query()->where('status', QueueStatus::WAITING)->count();
        $completedTotal = Queue::query()->where('created_at', '>=', $today)->where('status', QueueStatus::COMPLETED)->count();

        $csCount = User::role('cs')->count();
        $tellerCount = User::role('teller')->count();
        $activeStaff = $csCount + $tellerCount;

        return [
            Stat::make('Antrean Hari Ini', $todayTotal . ' Tiket')
                ->description('Total tiket antrean hari ini')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),

            Stat::make('Sedang Menunggu', $waitingTotal . ' Nasabah')
                ->description('Dalam daftar antrean menunggu')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Selesai Dilayani', $completedTotal . ' Selesai')
                ->description('Berhasil dilayani hari ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Petugas Operasional', $activeStaff . ' Petugas Aktif')
                ->description("{$csCount} CS • {$tellerCount} Teller bertugas")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }
}
