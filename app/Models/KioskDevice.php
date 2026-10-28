<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KioskDevice extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    public function events(): HasMany
    {
        return $this->hasMany(KioskDeviceEvent::class);
    }

    public function lastBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'last_branch_id');
    }

    public function displayName(): string
    {
        return $this->label ?: $this->device_type.' '.$this->platform.' · '.substr($this->id, 0, 8);
    }
}
