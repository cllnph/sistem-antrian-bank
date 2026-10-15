<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Granular Permissions
        $permissions = [
            'access_admin_panel',
            'access_teller_console',
            'access_cs_console',
            'view_any_queue',
            'create_queue',
            'edit_queue',
            'delete_queue',
            'view_any_user',
            'create_user',
            'edit_user',
            'delete_user',
            'view_any_service_setting',
            'edit_service_setting',
            'view_control_center',
            'reset_queues',
        ];

        foreach ($permissions as $permName) {
            Permission::findOrCreate($permName);
        }

        // 2. Create Roles and Assign Permissions
        $adminRole = Role::findOrCreate('admin');
        $adminRole->givePermissionTo(Permission::all());

        $csRole = Role::findOrCreate('cs');
        $csRole->syncPermissions([
            'access_cs_console',
            'view_any_queue',
            'create_queue',
            'edit_queue',
        ]);

        $tellerRole = Role::findOrCreate('teller');
        $tellerRole->syncPermissions([
            'access_teller_console',
            'view_any_queue',
            'create_queue',
            'edit_queue',
        ]);

        // 3. Sync Existing Users in DB to Spatie Pivot Table (model_has_roles)
        \App\Services\BranchAdminRole::ensure();

        $users = User::all();
        foreach ($users as $user) {
            $userRole = $user->getAttribute('role');
            if (filled($userRole)) {
                $targetRole = Role::findOrCreate($userRole);
                if (!$user->hasRole($targetRole)) {
                    $user->assignRole($targetRole);
                }
            }
        }
    }
}
