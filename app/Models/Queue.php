<?php

namespace App\Models;

use App\Enums\QueueStatus;
use App\Models\Scopes\ServiceSettingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ServiceSettingScope());
    }

    protected $fillable = [
        'number',
        'service_setting_id',
        'status',
        'called_at',
        'served_by',
    ];

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'status' => QueueStatus::class,
        ];
    }

    public function serviceSetting(): BelongsTo
    {
        return $this->belongsTo(ServiceSetting::class, 'service_setting_id');
    }

    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }
}
