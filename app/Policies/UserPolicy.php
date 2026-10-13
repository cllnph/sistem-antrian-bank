<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('view_any_user') && $this->canManage($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_user');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('edit_user') && $this->canManage($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete_user') && $this->canManage($user, $model);
    }

    private function canManage(User $user, User $model): bool
    {
        return ! $user->hasRole('admin_cabang') || (
            $user->branch_id && $user->branch_id === $model->branch_id
            && $model->hasAnyRole(['cs', 'teller'])
            && ! $model->roles()->whereNotIn('name', ['cs', 'teller'])->exists()
        );
    }
}
