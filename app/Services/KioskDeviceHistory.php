<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\KioskDevice;
use App\Models\KioskDeviceEvent;
use App\Models\PrintJob;
use App\Models\QueueLog;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KioskDeviceHistory
{
    public const COOKIE = 'bank_kiosk_device';

    public function device(Request $request): KioskDevice
    {
        // Filament/Livewire can evaluate an action through a subrequest; use its active web session.
        if (! $request->hasSession()) {
            $request->setLaravelSession(app('session')->driver());
        }
        if ($device = $request->attributes->get('kiosk_device')) {
            return $device;
        }
        // Session bookkeeping must not depend on ticket/device ownership.
        $owner = hash_hmac('sha256', $request->session()->getId(), config('app.key'));
        $device = DB::transaction(fn () => $this->identify($owner, $request->cookie(self::COOKIE), $request->userAgent(), 'browser'), 3);
        $request->attributes->set('kiosk_device', $device);
        Cookie::queue(Cookie::make(self::COOKIE, $device->id, 525600, '/', null, $request->isSecure(), true, false, 'lax'));

        return $device;
    }

    public function opened(Request $request, Branch $branch): void
    {
        $this->capture($request, $branch->id, null);
    }

    public function issued(Request $request, PrintJob $job): void
    {
        $this->capture($request, (int) $job->snapshot['branch_id'], $job);
    }

    private function capture(Request $request, int $branchId, ?PrintJob $job): void
    {
        try {
            $device = $this->device($request);
            DB::transaction(function () use ($request, $branchId, $job, $device) {
                $this->event($device, $branchId, $request->ip(), $request->userAgent(), now(), $job, 'live');
            }, 3);
        } catch (\Throwable $error) {
            // Passive history must not interrupt ticket issuance/printing.
            Log::warning('Riwayat perangkat kiosk belum dapat disimpan.', ['error_type' => $error::class]);
        }
    }

    private function identify(string $owner, mixed $cookie, ?string $userAgent, string $source): KioskDevice
    {
        $sessionDeviceId = DB::table('kiosk_device_sessions')->where('id', $owner)->value('kiosk_device_id');
        $device = is_string($cookie) && Str::isUuid($cookie) ? KioskDevice::find($cookie) : null;
        $device ??= $sessionDeviceId ? KioskDevice::find($sessionDeviceId) : null;
        if (! $device) {
            $device = KioskDevice::create([
                'id' => (string) Str::uuid(), ...$this->describe($userAgent), 'identity_source' => $source,
                'first_seen_at' => now(), 'last_seen_at' => now(),
            ]);
        }
        DB::table('kiosk_device_sessions')->updateOrInsert(['id' => $owner], ['kiosk_device_id' => $device->id]);

        return $device;
    }

    private function event(KioskDevice $device, int $branchId, ?string $ip, ?string $userAgent, CarbonInterface $time, ?PrintJob $job, string $source): void
    {
        $metadata = [
            'kiosk_device_id' => $device->id, 'branch_id' => $branchId, 'event' => $job ? 'ticket_issued' : 'opened',
            'source' => $source, 'ip_address' => filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 1024) : null,
            'ticket_number' => $job?->snapshot['ticket'], 'observed_at' => $time,
        ];
        $event = $job
            ? KioskDeviceEvent::firstOrCreate(['print_job_id' => $job->id], $metadata)
            : KioskDeviceEvent::create($metadata);
        if (! $event->wasRecentlyCreated) {
            return;
        }
        $device = KioskDevice::whereKey($device->id)->lockForUpdate()->firstOrFail();
        $changes = [];
        if ($time->lt($device->first_seen_at)) {
            $changes['first_seen_at'] = $time;
        }
        if ($time->gte($device->last_seen_at) || ! $device->events()->where('id', '!=', $event->id)->exists()) {
            $changes = [...$changes, 'last_seen_at' => $time, 'last_branch_id' => $branchId,
                'last_ip' => $metadata['ip_address'], 'last_user_agent' => $metadata['user_agent'], ...$this->describe($userAgent)];
        }
        if ($source === 'live') {
            $changes['identity_source'] = 'browser';
        }
        $device->update($changes);
    }

    public function recover(PrintJob $job): bool
    {
        if ($job->owner_type !== 'session' || $job->original_job_id || ! preg_match('/^[a-f0-9]{64}$/', $job->owner_hash)
            || KioskDeviceEvent::where('print_job_id', $job->id)->exists()) {
            return false;
        }
        $log = QueueLog::where('queue_id', $job->queue_id)->where('action', 'created')->oldest()->first();
        $payload = $log?->payload;
        if (! $log || ! is_array($payload) || ! is_string($payload['user_agent'] ?? null)) {
            return false;
        }

        return DB::transaction(function () use ($job, $log, $payload) {
            $device = $this->identify($job->owner_hash, null, $payload['user_agent'], 'legacy_session');
            $this->event($device, $log->branch_id, is_string($payload['ip'] ?? null) ? $payload['ip'] : null,
                $payload['user_agent'], $log->created_at, $job, 'legacy');

            return true;
        }, 3);
    }

    private function describe(?string $userAgent): array
    {
        $ua = $userAgent ?? '';
        $platform = match (true) {
            str_contains($ua, 'Android') => 'Android',
            preg_match('/iPhone|iPad|iPod/', $ua) === 1 => 'iOS/iPadOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Tidak diketahui',
        };
        $type = match (true) {
            str_contains($ua, 'iPad') || ($platform === 'Android' && ! str_contains($ua, 'Mobile')) => 'Tablet',
            preg_match('/Mobile|iPhone|iPod/', $ua) === 1 => 'HP',
            in_array($platform, ['Windows', 'macOS', 'Linux'], true) => 'Laptop/PC',
            default => 'Perangkat',
        };
        $browser = match (true) {
            preg_match('/Edg(?:A|iOS)?\//', $ua) === 1 => 'Edge',
            str_contains($ua, 'SamsungBrowser/') => 'Samsung Internet',
            str_contains($ua, 'OPR/') => 'Opera',
            preg_match('/Firefox\/|FxiOS\//', $ua) === 1 => 'Firefox',
            preg_match('/Chrome\/|CriOS\//', $ua) === 1 => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Tidak diketahui',
        };

        return ['device_type' => $type, 'platform' => $platform, 'browser' => $browser];
    }
}
