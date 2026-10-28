<?php

namespace App\Filament\Resources\QueueResource\Pages;

use App\Enums\QueueStatus;
use App\Filament\Resources\QueueResource;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Services\KioskHours;
use App\Services\QueuePeriods;
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
                        abort_unless($user->can('update', $user->serviceSetting), 403);
                        $newStatus = ! $user->serviceSetting->is_active;
                        $user->serviceSetting->update(['is_active' => $newStatus]);
                        Notification::make()
                            ->title($newStatus ? 'Loket Berhasil Dibuka' : 'Loket Ditutup / Istirahat')
                            ->body('Status loket telah diperbarui di kiosk dan layar antrean.')
                            ->color($newStatus ? 'success' : 'warning')
                            ->send();
                    }
                })
                ->visible(fn () => ! auth()->user()?->hasRole('admin') && auth()->user()?->serviceSetting !== null
                    && auth()->user()->can('update', auth()->user()->serviceSetting)),

            CreateAction::make()
                ->label('Tambah Antrean Manual')
                ->mutateFormDataUsing(function (array $data): array {
                    abort_unless(auth()->user()?->is_active && auth()->user()?->hasRole('admin'), 403);
                    $branch = Branch::findOrFail($data['branch_id']);
                    app(QueuePeriods::class)->ensure($branch);
                    abort_unless(KioskHours::isOpen($branch), 422, 'Cabang di luar jam layanan.');
                    unset($data['number'], $data['period_id']);

                    return array_replace($data, ['status' => QueueStatus::WAITING]);
                })
                ->after(fn (Queue $record) => QueueLog::create([
                    'queue_id' => $record->id, 'branch_id' => $record->branch_id,
                    'actor_id' => auth()->id(), 'action' => 'created', 'payload' => ['source' => 'manual'],
                ]))
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
            'all' => Tab::make('Periode Berjalan')
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
