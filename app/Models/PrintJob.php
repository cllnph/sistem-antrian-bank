<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJob extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'started_at' => 'datetime', 'submission_started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Queue::class, 'queue_id')->withoutGlobalScopes();
    }

    public function publicStatus(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_url' => route('print-jobs.show', $this->id),
            'message' => match ($this->status) {
                'submitted' => 'Tiket sudah dikirim ke printer. Silakan ambil tiket Anda.',
                'failed' => 'Cetak gagal. Hubungi petugas untuk mencetak ulang nomor ini.',
                'unknown' => 'Hasil cetak belum dapat dipastikan. Periksa printer bersama petugas sebelum mencetak ulang.',
                'expired' => 'Batas waktu cetak terlewati. Hubungi petugas untuk mencetak ulang nomor ini.',
                default => 'Tiket sedang diproses untuk dicetak.',
            },
        ];
    }
}
