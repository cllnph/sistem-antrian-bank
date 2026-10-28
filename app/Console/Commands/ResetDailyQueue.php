<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\QueuePeriods;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetDailyQueue extends Command
{
    protected $signature = 'queue:reset {--branch= : Kode cabang yang akan direset}';

    protected $description = 'Reset satu periode cabang secara eksplisit (CS-001 dan T-001).';

    public function handle(QueuePeriods $periods): int
    {
        if (! $this->option('branch')) {
            $this->error('Wajib menentukan --branch=KODE. Reset global tidak tersedia.');

            return self::FAILURE;
        }
        $branch = Branch::where('code', $this->option('branch'))->firstOrFail();
        $state = $periods->ensure($branch);
        $count = $periods->reset($branch, null, $state->period_id, (string) Str::uuid());
        $this->info('Periode cabang '.$branch->code.' direset; '.$count.' antrean ditutup.');

        return self::SUCCESS;
    }
}
