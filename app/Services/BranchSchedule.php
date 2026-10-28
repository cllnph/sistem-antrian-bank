<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchSchedule
{
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public static function defaults(): array
    {
        $days = [];
        foreach (self::DAYS as $day => $label) {
            $days[$day] = ['enabled' => $day <= 5, 'opens' => '04:00', 'closes' => '18:00'];
        }

        return $days;
    }

    public function isOpen(Branch $branch, ?CarbonInterface $time = null): bool
    {
        if (! $branch->is_active) {
            return false;
        }
        if (app()->environment(['local', 'testing']) && config('kiosk.test_mode_enabled', false)) {
            return true;
        }
        $time = ($time ?? now())->copy()->timezone('Asia/Jakarta');
        $day = ($branch->weekly_schedule ?? self::defaults())[$time->isoWeekday()];

        return (bool) $day['enabled'] && $time->format('H:i') >= $day['opens'] && $time->format('H:i') < $day['closes'];
    }

    public function nextReset(string $resetTime, ?CarbonInterface $time = null): CarbonInterface
    {
        $time = ($time ?? now())->copy()->timezone('Asia/Jakarta');
        $next = $time->copy()->setTimeFromTimeString($resetTime.':00');

        return $next->lte($time) ? $next->addDay() : $next;
    }

    public function label(Branch $branch): string
    {
        $schedule = $branch->weekly_schedule ?? self::defaults();
        if ($schedule === self::defaults()) {
            return 'Senin–Jumat 04.00–18.00 WIB';
        }
        $parts = [];
        foreach (self::DAYS as $day => $label) {
            $hours = $schedule[$day];
            if ($hours['enabled']) {
                $parts[] = $label.' '.str_replace(':', '.', $hours['opens']).'–'.str_replace(':', '.', $hours['closes']);
            }
        }

        return $parts ? implode('; ', $parts).' WIB' : 'Tutup setiap hari';
    }

    public function save(Branch $branch, User $actor, array $weekly, string $resetTime): void
    {
        abort_unless($actor->is_active && $actor->hasRole('admin'), 403);
        $validTime = fn ($value) => is_string($value) && preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $value);
        if (! $validTime($resetTime)) {
            throw ValidationException::withMessages(['reset_time' => 'Gunakan jam reset dalam format HH:MM.']);
        }
        $normalized = [];
        foreach (self::DAYS as $day => $label) {
            $hours = $weekly[$day] ?? [];
            $enabled = (bool) ($hours['enabled'] ?? false);
            $opens = $hours['opens'] ?? '04:00';
            $closes = $hours['closes'] ?? '18:00';
            if (! $validTime($opens) || ! $validTime($closes) || ($enabled && $opens >= $closes)) {
                throw ValidationException::withMessages(['weekly_schedule.'.$day.'.opens' => $label.': jam buka harus lebih awal dari jam tutup pada hari yang sama.']);
            }
            if ($enabled && $resetTime >= $opens && $resetTime < $closes) {
                throw ValidationException::withMessages(['reset_time' => 'Jam reset harus berada di luar jam layanan '.$label.'.']);
            }
            $normalized[$day] = ['enabled' => $enabled, 'opens' => $opens, 'closes' => $closes];
        }
        // Process an already due boundary under the old schedule before changing future boundaries.
        app(QueuePeriods::class)->ensure($branch);
        DB::transaction(function () use ($branch, $normalized, $resetTime) {
            $locked = Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $resetChanged = ($locked->reset_time ?? '00:00') !== $resetTime;
            $locked->forceFill(['weekly_schedule' => $normalized, 'reset_time' => $resetTime]);
            $locked->operating_hours = $this->label($locked);
            $locked->save();
            if ($resetChanged) {
                DB::table('branch_queue_states')->where('branch_id', $branch->id)->update(['next_reset_at' => $this->nextReset($resetTime)]);
            }
        }, 3);
    }
}
