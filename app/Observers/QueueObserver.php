<?php

namespace App\Observers;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Services\IntegrityRules;
use Illuminate\Validation\ValidationException;

class QueueObserver
{
    public function saving(Queue $queue): void
    {
        $setting = $queue->service_setting_id ? ServiceSetting::find($queue->service_setting_id) : null;
        if (! $setting || (int) $setting->branch_id !== (int) $queue->branch_id) {
            throw ValidationException::withMessages(['service_setting_id' => 'Loket antrean harus tersedia dan berada di kantor yang sama.']);
        }
        if (! $queue->exists) {
            if (! $setting->is_active) {
                throw ValidationException::withMessages(['service_setting_id' => 'Loket sedang nonaktif.']);
            }
            if ($queue->branch_id) {
                $branch = Branch::whereKey($queue->branch_id)->lockForUpdate()->firstOrFail();
                if (! $branch->is_active) {
                    throw ValidationException::withMessages(['branch_id' => 'Kantor sedang nonaktif.']);
                }
            }
            $prefix = match ($setting->service) { 'cs' => 'CS', 'teller' => 'T', default => 'U' };
            if (! preg_match('/^'.$prefix.'-[0-9]{3,}$/', (string) $queue->number)) {
                throw ValidationException::withMessages(['number' => 'Format nomor tiket harus sesuai layanan, misalnya '.$prefix.'-001.']);
            }
            if (Queue::withoutGlobalScopes()->where('branch_id', $queue->branch_id)
                ->where('number', $queue->number)->whereDate('created_at', ($queue->created_at ?? now())->toDateString())->exists()) {
                throw ValidationException::withMessages(['number' => 'Nomor tiket sudah digunakan di kantor ini pada tanggal tersebut.']);
            }

            return;
        }
        if ($queue->isDirty(['number', 'branch_id'])) {
            throw ValidationException::withMessages(['number' => 'Nomor dan kantor tiket yang sudah diterbitkan tidak dapat diubah.']);
        }
        if ($queue->isDirty('service_setting_id')) {
            $oldSetting = ServiceSetting::find($queue->getRawOriginal('service_setting_id'));
            if (! $oldSetting || $oldSetting->service !== $setting->service) {
                throw ValidationException::withMessages(['service_setting_id' => 'Tiket tidak dapat dipindahkan ke jenis layanan lain.']);
            }
        }
        if ($queue->isDirty('status')) {
            $allowed = match ($queue->getRawOriginal('status')) {
                'waiting' => [QueueStatus::CALLED, QueueStatus::SKIPPED],
                'called' => [QueueStatus::SERVING, QueueStatus::COMPLETED, QueueStatus::SKIPPED],
                'serving' => [QueueStatus::COMPLETED, QueueStatus::SKIPPED],
                default => [],
            };
            if (! in_array($queue->status, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status antrean tidak valid. Tiket selesai atau dilewati tidak dapat dibuka kembali.']);
            }
        }
    }

    public function deleting(Queue $queue): void
    {
        IntegrityRules::assertDeletable($queue);
    }
}
