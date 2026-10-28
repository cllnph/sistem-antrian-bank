<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrintAgent extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['token_hash', 'enrollment_hash', 'enrollment_auto_enable', 'setup_request_hash'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'revoked' => 'boolean', 'printer_ready' => 'boolean', 'auto_name' => 'boolean', 'enrollment_auto_enable' => 'boolean', 'auto_enable_pending' => 'boolean', 'heartbeat_at' => 'datetime', 'enrollment_expires_at' => 'datetime', 'installed_printers' => 'array'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isReady(): bool
    {
        return ! $this->trashed() && ! $this->revoked && filled($this->printer_name) && $this->printer_ready && $this->heartbeat_at?->gt(now()->subSeconds(config('printing.agent_ttl')));
    }
}
