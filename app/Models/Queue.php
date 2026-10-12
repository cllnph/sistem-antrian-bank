<?php

namespace App\Models;

use App\Enums\QueueStatus;
use App\Models\Scopes\ServiceSettingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ServiceSettingScope());
    }

    protected $fillable = [
        'branch_id',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function serviceSetting(): BelongsTo
    {
        return $this->belongsTo(ServiceSetting::class, 'service_setting_id');
    }

    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(QueueLog::class, 'queue_id');
    }
}
