<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ServiceSetting;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Run Roles & Permissions Seeder
        $this->call(RoleAndPermissionSeeder::class);

        // 2. Ensure Service Settings Exist
        $csService = ServiceSetting::query()->updateOrCreate(
            ['service' => 'cs'],
            ['label' => 'Customer Service & Kredit Pensiun', 'counter_name' => 'Loket CS - Kredit & Pensiun', 'is_active' => true]
        );

        $tellerService = ServiceSetting::query()->updateOrCreate(
            ['service' => 'teller'],
            ['label' => 'Teller Tabungan & Pensiun TASPEN', 'counter_name' => 'Loket Teller - Kas & Pensiun', 'is_active' => true]
        );

        $adminRole = Role::findOrCreate('admin');
        $csRole = Role::findOrCreate('cs');
        $tellerRole = Role::findOrCreate('teller');

        // 3. Admin Account (admin@example.com)
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator BPR DP TASPEN',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $admin->is_active = true;
        $admin->password = 'password';
        $admin->save();
        $admin->syncRoles([$adminRole]);

        // 4. Admin Gmail Account (admin@gmail.com)
        $adminGmail = User::query()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator BPR DP TASPEN',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $adminGmail->is_active = true;
        $adminGmail->password = 'password';
        $adminGmail->save();
        $adminGmail->syncRoles([$adminRole]);

        // 5. Teller Account (teller@example.com)
        $teller = User::query()->updateOrCreate(
            ['email' => 'teller@example.com'],
            [
                'name' => 'Petugas Teller BPR DP TASPEN',
                'service_setting_id' => $tellerService->id,
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $teller->is_active = true;
        $teller->service_setting_id = $tellerService->id;
        $teller->password = 'password';
        $teller->save();
        $teller->syncRoles([$tellerRole]);

        // 6. CS Account (cs@example.com)
        $cs = User::query()->updateOrCreate(
            ['email' => 'cs@example.com'],
            [
                'name' => 'Petugas CS BPR DP TASPEN',
                'service_setting_id' => $csService->id,
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $cs->is_active = true;
        $cs->service_setting_id = $csService->id;
        $cs->password = 'password';
        $cs->save();
        $cs->syncRoles([$csRole]);
    }
}
