<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Branch extends Model
{
    protected $fillable = [
        'code',
        'slug',
        'name',
        'type',
        'address',
        'phone',
        'operating_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weekly_schedule' => 'array',
        ];
    }

    public function serviceSettings(): HasMany
    {
        return $this->hasMany(ServiceSetting::class, 'branch_id');
    }

    public function printAgents(): HasMany
    {
        return $this->hasMany(PrintAgent::class);
    }

    public function queueState(): HasOne
    {
        return $this->hasOne(BranchQueueState::class, 'branch_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class, 'branch_id');
    }

    public function queueLogs(): HasMany
    {
        return $this->hasMany(QueueLog::class, 'branch_id');
    }
}
