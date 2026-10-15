<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ServiceSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Run Roles & Permissions Seeder
        $this->call(RoleAndPermissionSeeder::class);

        $this->call(BranchSeeder::class);
        $pusat = Branch::where('code', '001')->firstOrFail();
        $csService = ServiceSetting::where('branch_id', $pusat->id)->where('service', 'cs')->firstOrFail();
        $tellerService = ServiceSetting::where('branch_id', $pusat->id)->where('service', 'teller')->firstOrFail();

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $adminRole = Role::findOrCreate('admin');
        $csRole = Role::findOrCreate('cs');
        $tellerRole = Role::findOrCreate('teller');

        // 3. Admin Account (admin@example.com)
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator BPR DP TASPEN',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        if ($admin->wasRecentlyCreated) {
            $admin->syncRoles([$adminRole]);
        }

        // 4. Admin Gmail Account (admin@gmail.com)
        $adminGmail = User::query()->firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator BPR DP TASPEN',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        if ($adminGmail->wasRecentlyCreated) {
            $adminGmail->syncRoles([$adminRole]);
        }

        // 5. Teller Account (teller@example.com)
        $teller = User::query()->firstOrCreate(
            ['email' => 'teller@example.com'],
            [
                'name' => 'Petugas Teller BPR DP TASPEN',
                'branch_id' => $pusat->id,
                'service_setting_id' => $tellerService->id,
                'password' => 'password',
                'is_active' => true,
            ]
        );
        if ($teller->wasRecentlyCreated) {
            $teller->syncRoles([$tellerRole]);
        }

        // 6. CS Account (cs@example.com)
        $cs = User::query()->firstOrCreate(
            ['email' => 'cs@example.com'],
            [
                'name' => 'Petugas CS BPR DP TASPEN',
                'branch_id' => $pusat->id,
                'service_setting_id' => $csService->id,
                'password' => 'password',
                'is_active' => true,
            ]
        );
        if ($cs->wasRecentlyCreated) {
            $cs->syncRoles([$csRole]);
        }
    }
}
