<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchOperatingHoursSeeder extends Seeder
{
    public const HOURS = 'Senin – Jumat (08.00 – 15.00 WIB)';

    public function run(): void
    {
        Branch::query()->update(['operating_hours' => self::HOURS]);
    }
}
