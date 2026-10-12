<?php

namespace App\Filament\Resources\QueueResource\Pages;

use App\Filament\Resources\QueueResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageQueues extends ManageRecords
{
    protected static string $resource = QueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleCounterStatus')
                ->label(fn () => auth()->user()?->serviceSetting?->is_active ? '🟢 Loket Aktif (Klik untuk Istirahat)' : '🔴 Loket Tutup (Klik untuk Buka Loket)')
                ->color(fn () => auth()->user()?->serviceSetting?->is_active ? 'success' : 'danger')
                ->icon(fn () => auth()->user()?->serviceSetting?->is_active ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->requiresConfirmation()
                ->modalHeading(fn () => auth()->user()?->serviceSetting?->is_active ? 'Tutup Loket Sementara?' : 'Buka Loket Layanan?')
                ->modalDescription(fn () => auth()->user()?->serviceSetting?->is_active ? 'Layanan loket Anda di Kiosk akan dinonaktifkan sementara (misal jam istirahat siang).' : 'Layanan loket Anda di Kiosk akan diaktifkan untuk melayani nasabah.')
                ->action(function () {
                    $user = auth()->user();
                    if ($user?->serviceSetting) {
                        $newStatus = ! $user->serviceSetting->is_active;
                        $user->serviceSetting->update(['is_active' => $newStatus]);
                        Notification::make()
                            ->title($newStatus ? 'Loket Berhasil Dibuka' : 'Loket Ditutup / Istirahat')
                            ->body('Status loket telah diperbarui secara real-time di Kiosk dan TV Display.')
                            ->color($newStatus ? 'success' : 'warning')
                            ->send();
                    }
                })
                ->visible(fn () => ! auth()->user()?->hasRole('admin') && auth()->user()?->serviceSetting !== null),

            CreateAction::make()
                ->label('Tambah Antrean Manual')
                ->visible(fn () => auth()->user()?->hasRole('admin') ?? false),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('admin')) {
            return [];
        }

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
