<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchQueueState extends Model
{
    protected $primaryKey = 'branch_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['period_started_at' => 'datetime', 'next_reset_at' => 'datetime', 'last_reset_at' => 'datetime'];
    }
}
