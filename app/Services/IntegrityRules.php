<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Models\KioskDeviceEvent;
use App\Models\PrintAgent;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\ServiceSetting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class IntegrityRules
{
    public const SYSTEM_ROLES = ['admin', 'admin_cabang', 'cs', 'teller'];

    public static function isSystemRole(?Role $role): bool
    {
        return $role && ($role->getRawOriginal('guard_name') ?? $role->guard_name) === 'web'
            && in_array($role->getRawOriginal('name') ?? $role->name, self::SYSTEM_ROLES, true);
    }

    public static function activeWork(User $user): bool
    {
        return Queue::withoutGlobalScopes()->currentPeriod()->where('served_by', $user->id)
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])->exists();
    }

    public static function lastActiveAdmin(User $user): bool
    {
        return $user->exists && (bool) $user->getRawOriginal('is_active')
            && $user->roles()->where('name', 'admin')->where('guard_name', 'web')->exists()
            && ! User::whereKeyNot($user->id)->where('is_active', true)
                ->withRoleNames(['admin'])->exists();
    }

    public static function deletionReason(Model $record): ?string
    {
        if ($record instanceof Branch && \Illuminate\Support\Facades\DB::table('ticket_request_tombstones')->where('branch_id', $record->id)->exists()) {
            return 'Bukti teknis permintaan masih disimpan selama tujuh hari. Nonaktifkan cabang terlebih dahulu.';
        }
        if ($record instanceof Role) {
            if (self::isSystemRole($record)) {
                return 'Peran sistem admin, admin_cabang, cs, dan teller tidak dapat dihapus.';
            }
            if (User::whereHas('roles', fn ($query) => $query->whereKey($record->id))->exists()) {
                return 'Peran masih digunakan oleh akun pengguna, termasuk akun nonaktif. Pindahkan perannya terlebih dahulu.';
            }
        }
        if ($record instanceof Branch && ($record->serviceSettings()->exists()
            || $record->users()->exists() || $record->queues()->withoutGlobalScopes()->exists()
            || $record->queueLogs()->exists())) {
            return 'Kantor masih memiliki loket, akun, atau riwayat antrean. Kantor tidak dapat dihapus.';
        }
        if ($record instanceof Branch && PrintAgent::withTrashed()->where('branch_id', $record->id)->exists()) {
            return 'Kantor memiliki penghubung cetak yang tercatat. Nonaktifkan kantor untuk mempertahankan riwayat.';
        }
        if ($record instanceof Branch && KioskDeviceEvent::where('branch_id', $record->id)->exists()) {
            return 'Kantor memiliki riwayat perangkat kiosk. Nonaktifkan kantor untuk mempertahankan riwayat.';
        }
        if ($record instanceof ServiceSetting && ($record->users()->exists()
            || $record->queues()->withoutGlobalScopes()->exists())) {
            return 'Loket masih digunakan oleh akun petugas atau riwayat antrean. Loket tidak dapat dihapus.';
        }
        if ($record instanceof User) {
            if ((int) auth()->id() === (int) $record->id || self::lastActiveAdmin($record)) {
                return 'Akun sendiri dan administrator aktif terakhir tidak dapat dihapus.';
            }
            if (Queue::withoutGlobalScopes()->where('served_by', $record->id)->exists()
                || QueueLog::where('actor_id', $record->id)->exists()) {
                return 'Akun memiliki riwayat pelayanan atau aktivitas. Nonaktifkan akun setelah pelayanan selesai.';
            }
        }
        if ($record instanceof Queue) {
            return 'Tiket yang sudah diterbitkan harus tetap tersimpan. Gunakan Lewati atau Reset untuk menutup antrean.';
        }

        return null;
    }

    public static function assertDeletable(Model $record): void
    {
        if ($reason = self::deletionReason($record)) {
            throw ValidationException::withMessages(['delete' => $reason]);
        }
    }

    public static function beforeDelete(Model $record, Action $action): void
    {
        if ($reason = self::deletionReason($record)) {
            Notification::make()->title('Data tidak dapat dihapus')->body($reason)->danger()->send();
            $action->halt();
        }
    }

    public static function assertRoleChange(User $user, array $roleIds): void
    {
        $names = Role::whereIn('id', $roleIds)->where('guard_name', 'web')->pluck('name')->all();
        $currentlyAdmin = $user->exists && $user->roles()->where('name', 'admin')->where('guard_name', 'web')->exists();
        if ($currentlyAdmin && ! in_array('admin', $names, true)
            && ((int) auth()->id() === (int) $user->id || self::lastActiveAdmin($user))) {
            throw ValidationException::withMessages(['roles' => 'Peran administrator akun sendiri atau administrator aktif terakhir tidak dapat dicabut.']);
        }

        $currentIds = $user->exists ? $user->roles()->pluck('roles.id')->sort()->values()->all() : [];
        $nextIds = collect($roleIds)->sort()->values()->all();
        if ($currentIds !== $nextIds && $user->exists && self::activeWork($user)) {
            throw ValidationException::withMessages(['roles' => 'Selesaikan pelayanan aktif sebelum mengubah peran petugas.']);
        }

        $setting = $user->service_setting_id ? ServiceSetting::find($user->service_setting_id) : null;
        $operationalNames = array_intersect($names, ['cs', 'teller']);
        if ($setting && $operationalNames && ! in_array($setting->service, $operationalNames, true)
            && ! in_array('admin', $names, true)) {
            throw ValidationException::withMessages(['service_setting_id' => 'Jenis loket harus sesuai dengan peran petugas.']);
        }
    }
}
