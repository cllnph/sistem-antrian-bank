<?php

namespace App\Filament\Resources\QueueResource\Pages;

use App\Filament\Resources\QueueResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ManageQueues extends ManageRecords
{
    protected static string $resource = QueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleCounterStatus')
                ->label(fn () => auth()->user()?->serviceSetting?->is_active ? 'Tutup Loket' : 'Buka Loket')
                ->color(fn () => auth()->user()?->serviceSetting?->is_active ? 'success' : 'danger')
                ->icon(fn () => auth()->user()?->serviceSetting?->is_active ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedLockClosed)
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
                            ->body('Status loket telah diperbarui di kiosk dan layar antrean.')
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
                ->icon(Heroicon::OutlinedListBullet),
            'waiting' => Tab::make('Sedang Menunggu')
                ->icon(Heroicon::OutlinedClock)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'waiting')),
            'called' => Tab::make('Sedang Dipanggil')
                ->icon(Heroicon::OutlinedMegaphone)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'called')),
            'completed' => Tab::make('Selesai Dilayani')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'skipped' => Tab::make('Dilewati')
                ->icon(Heroicon::OutlinedXCircle)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'skipped')),
        ];
    }
}
