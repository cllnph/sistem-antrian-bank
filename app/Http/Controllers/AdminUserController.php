<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the users with search, role, status, and date filtering.
     */
    public function index(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('view_any_user'), 403);

        return redirect('/admin/users');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->can('create_user'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'service_setting_id' => ['nullable', 'exists:service_settings,id'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'service_setting_id' => $validated['service_setting_id'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $roleModel = Role::findOrCreate($validated['role']);
        $user->syncRoles([$roleModel]);

        Log::info("Banking Audit Trail: Admin created new User #{$user->id} ({$user->email}) with role {$validated['role']}.");

        return back()->with('status', "Pengguna baru '{$user->name}' berhasil ditambahkan.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if(! auth()->user()->can('edit_user'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'service_setting_id' => ['nullable', 'exists:service_settings,id'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'service_setting_id' => $validated['service_setting_id'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        $roleModel = Role::findOrCreate($validated['role']);
        $user->syncRoles([$roleModel]);

        Log::info("Banking Audit Trail: Admin updated User #{$user->id} ({$user->email}).");

        return back()->with('status', "Data pengguna '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Toggle the active status of a user.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        abort_if(! auth()->user()->can('edit_user'), 403);

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusLabel = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        Log::info("Banking Audit Trail: Admin {$statusLabel} User #{$user->id} ({$user->email}).");

        return back()->with('status', "Status pengguna '{$user->name}' berhasil {$statusLabel}.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_if(! auth()->user()->can('delete_user'), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $user->delete();

        Log::warning("Banking Security Alert: Admin deleted User #{$user->id} ({$userEmail}).");

        return back()->with('status', "Pengguna '{$userName}' berhasil dihapus.");
    }
}
