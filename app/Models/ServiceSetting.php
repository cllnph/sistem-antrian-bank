<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceSetting extends Model
{
    protected $fillable = ['branch_id', 'service', 'label', 'counter_name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'service_setting_id');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class, 'service_setting_id');
    }
}
