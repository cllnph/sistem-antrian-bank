<?php

namespace App\Services;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Get all admin users to receive system-wide notifications.
     */
    public static function getAdminUsers(): Collection
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();
    }

    /**
     * Send Queue Reset Notification.
     */
    public static function sendQueueResetNotification(User $actor): void
    {
        $recipients = User::where('is_active', true)->get();

        Notification::make()
            ->title('🔄 Antrean Hari Ini Telah Di-reset')
            ->body("Seluruh nomor antrean berhasil di-reset oleh {$actor->name}.")
            ->warning()
            ->actions([
                Action::make('view_queues')
                    ->label('📊 Lihat Daftar Antrean')
                    ->button()
                    ->url('/admin/queues'),
            ])
            ->sendToDatabase($recipients);
    }

    /**
     * Send High Queue Volume / Bottleneck Warning Notification.
     */
    public static function sendHighQueueVolumeNotification(string $serviceLabel, int $waitingCount): void
    {
        $recipients = User::where('is_active', true)->get();

        Notification::make()
            ->title("⚠️ Antrean Menumpuk di Layanan {$serviceLabel}")
            ->body("Terdapat {$waitingCount} nasabah sedang menunggu giliran pelayanan.")
            ->danger()
            ->actions([
                Action::make('open_console')
                    ->label('🖥️ Buka Konsol Antrean')
                    ->button()
                    ->url('/admin/queues'),
            ])
            ->sendToDatabase($recipients);
    }

    /**
     * Send User Account Created/Updated/Deleted Notification.
     */
    public static function sendUserAccountNotification(User $user, string $actionType, ?User $actor = null): void
    {
        $recipients = User::where('is_active', true)->get();
        if ($recipients->isEmpty()) {
            return;
        }

        $actorName = $actor?->name ?? 'System Admin';
        $actionText = match ($actionType) {
            'created' => 'ditambahkan',
            'deleted' => 'dihapus',
            default => 'diperbarui',
        };

        Notification::make()
            ->title("👤 Akun Petugas {$actionText}")
            ->body("Data pengguna '{$user->name}' ({$user->email}) telah {$actionText} oleh {$actorName}.")
            ->info()
            ->actions([
                Action::make('view_user')
                    ->label('👥 Kelola Data User')
                    ->button()
                    ->url('/admin/users'),
            ])
            ->sendToDatabase($recipients);
    }

    /**
     * Send Counter / Service Setting Status Changed Notification.
     */
    public static function sendCounterStatusNotification(string $counterName, bool $isActive, ?User $actor = null, string $actionType = 'updated'): void
    {
        $recipients = User::where('is_active', true)->get();
        if ($recipients->isEmpty()) {
            return;
        }

        $actorName = $actor?->name ?? 'System Admin';
        $actionText = match ($actionType) {
            'created' => 'ditambahkan',
            'deleted' => 'dihapus',
            default => $isActive ? 'Diaktifkan (BUKA)' : 'Dinonaktifkan (TUTUP/ISTIRAHAT)',
        };

        Notification::make()
            ->title("⚙️ Perubahan Data Loket Layanan")
            ->body("Loket '{$counterName}' telah {$actionText} oleh {$actorName}.")
            ->warning()
            ->actions([
                Action::make('view_settings')
                    ->label('🔧 Pengaturan Loket')
                    ->button()
                    ->url('/admin/service-settings'),
            ])
            ->sendToDatabase($recipients);
    }

    /**
     * Send Security Brute Force Alert Notification.
     */
    public static function sendSecurityAlertNotification(string $email, string $ipAddress, int $secondsLocked): void
    {
        $recipients = self::getAdminUsers();

        Notification::make()
            ->title('🛡️ Peringatan Keamanan System (Brute-Force)')
            ->body("Terdeteksi 5x percobaan login gagal berturut-turut untuk email '{$email}' dari IP {$ipAddress}. Dikunci selama {$secondsLocked} detik.")
            ->danger()
            ->actions([
                Action::make('manage_users')
                    ->label('🔒 Cek Manajemen User')
                    ->button()
                    ->url('/admin/users'),
            ])
            ->sendToDatabase($recipients);
    }
}
