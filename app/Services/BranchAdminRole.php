<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BranchAdminRole
{
    public static function ensure(): Role
    {
        $permissions = [
            'access_admin_panel', 'view_any_user', 'create_user', 'edit_user', 'delete_user',
            'view_any_service_setting', 'edit_service_setting', 'view_any_queue',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $role = Role::findOrCreate('admin_cabang', 'web');
        $role->syncPermissions($permissions);

        return $role;
    }
}
