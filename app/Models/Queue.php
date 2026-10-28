<?php

namespace App\Models;

use App\Enums\QueueStatus;
use App\Models\Scopes\ServiceSettingScope;
use App\Services\QueuePeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Queue extends Model
{
    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            if ($this->exists) {
                $branch = Branch::whereKey($this->branch_id)->lockForUpdate()->firstOrFail();
                $state = app(QueuePeriods::class)->lockedState($branch);
                abort_unless($this->period_id === $state->period_id && $state->next_reset_at->isFuture(), 410, 'Periode antrean telah berakhir.');
            }

            return parent::save($options);
        });
    }

    protected $attributes = ['status' => 'waiting'];

    protected static function booted(): void
    {
        static::addGlobalScope(new ServiceSettingScope);
    }

    protected $fillable = [
        'branch_id',
        'number',
        'service_setting_id',
        'status',
        'called_at',
        'served_by',
        'period_id',
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

    public function scopeCurrentPeriod($query)
    {
        return $query->whereExists(fn ($state) => $state->selectRaw('1')->from('branch_queue_states')
            ->whereColumn('branch_queue_states.branch_id', 'queues.branch_id')
            ->whereColumn('branch_queue_states.period_id', 'queues.period_id'));
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
