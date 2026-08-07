<?php

namespace App\Filament\Resources\QueueResource\Pages;

use App\Filament\Resources\QueueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageQueues extends ManageRecords
{
    protected static string $resource = QueueResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Antrean')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Riwayat')
                ->icon('heroicon-o-list-bullet'),
            'waiting' => Tab::make('Sedang Menunggu')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'waiting')),
            'called' => Tab::make('Sedang Dipanggil')
                ->icon('heroicon-o-megaphone')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'called')),
            'completed' => Tab::make('Selesai Dilayani')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'skipped' => Tab::make('Dilewati')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'skipped')),
        ];
    }
}
