<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with 10-module permission matrix and user counts.
     */
    public function index(): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        return redirect('/admin/roles');
    }

    /**
     * Store a newly created role via standard form submit.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $request->merge(['name' => is_string($request->input('name')) ? Str::slug(strtolower($request->input('name')), '_') : $request->input('name')]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'distinct', 'exists:permissions,name'],
        ], [
            'permissions.required' => 'Minimal harus memilih 1 hak akses otorisasi.',
            'permissions.min' => 'Minimal harus memilih 1 hak akses otorisasi.',
            'name.unique' => 'Nama role ini sudah terdaftar dalam sistem.',
        ]);

        $roleSlug = Str::slug(strtolower($validated['name']), '_');
        $roleModelClass = config('permission.models.role', Role::class);
        $role = $roleModelClass::create(['name' => $roleSlug, 'guard_name' => 'web']);

        if (! empty($validated['permissions'])) {
            $permModels = [];
            foreach ($validated['permissions'] as $permName) {
                $permModels[] = Permission::findByName($permName);
            }
            $role->syncPermissions($permModels);
        }

        Log::info("Banking Audit Trail: Admin created new Role '{$role->name}' with ".count($validated['permissions']).' permissions.');

        return back()->with('status', "Role baru '".ucfirst(str_replace('_', ' ', $validated['name']))."' berhasil disimpan ke database.");
    }

    /**
     * Store a newly created role via AJAX JSON API.
     */
    public function storeApi(Request $request): JsonResponse
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        try {
            $request->merge(['name' => is_string($request->input('name')) ? Str::slug(strtolower($request->input('name')), '_') : $request->input('name')]);
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
                'description' => ['nullable', 'string', 'max:500'],
                'permissions' => ['required', 'array', 'min:1'],
                'permissions.*' => ['required', 'string', 'distinct', 'exists:permissions,name'],
            ], [
                'permissions.required' => 'Minimal harus memilih 1 hak akses otorisasi.',
                'permissions.min' => 'Minimal harus memilih 1 hak akses otorisasi.',
                'name.unique' => 'Nama role ini sudah terdaftar dalam sistem.',
            ]);

            $roleSlug = Str::slug(strtolower($validated['name']), '_');
            $roleModelClass = config('permission.models.role', Role::class);
            $role = $roleModelClass::create(['name' => $roleSlug, 'guard_name' => 'web']);

            if (! empty($validated['permissions'])) {
                $permModels = [];
                foreach ($validated['permissions'] as $permName) {
                    $permModels[] = Permission::findByName($permName);
                }
                $role->syncPermissions($permModels);
            }

            Log::info("Banking Audit Trail: Admin created new Role '{$role->name}' via AJAX API.");

            return response()->json([
                'success' => true,
                'message' => "Role baru '".ucfirst(str_replace('_', ' ', $validated['name']))."' berhasil disimpan ke database.",
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => ucfirst(str_replace('_', ' ', $validated['name'])),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: '.implode(', ', Arr::flatten($e->errors())),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update permissions for a specified role.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $roleModelClass = config('permission.models.role', Role::class);
        $role = $roleModelClass::findOrFail($id);

        $validated = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'distinct', 'exists:permissions,name'],
        ], [
            'permissions.required' => 'Minimal harus memilih 1 hak akses otorisasi.',
            'permissions.min' => 'Minimal harus memilih 1 hak akses otorisasi.',
        ]);

        $permModels = [];
        foreach ($validated['permissions'] as $permName) {
            $permModels[] = Permission::findByName($permName);
        }

        $role->syncPermissions($permModels);

        Log::info("Banking Audit Trail: Admin updated permissions for Role '{$role->name}'.");

        return back()->with('status', "Hak akses untuk role '".ucfirst(str_replace('_', ' ', $role->name))."' berhasil diperbarui.");
    }

    /**
     * Remove the specified role with active user check (Only primary Admin is permanent).
     */
    public function destroy(int $id): RedirectResponse
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);
        $roleModelClass = config('permission.models.role', Role::class);
        $role = $roleModelClass::findOrFail($id);

        // Protect primary administrator role from deletion
        if (in_array(strtolower($role->name), ['admin', 'administrator', 'super_admin'])) {
            return back()->withErrors([
                'role_error' => 'Role Administrator Utama bersifat permanen dan tidak dapat dihapus dari sistem.',
            ]);
        }

        // Check if role is assigned to active users
        $userCount = User::where('role', $role->name)
            ->orWhereHas('roles', fn ($q) => $q->where('roles.id', $role->id))
            ->count();

        if ($userCount > 0) {
            return back()->withErrors([
                'role_error' => "Role '".ucfirst(str_replace('_', ' ', $role->name))."' tidak dapat dihapus karena sedang digunakan oleh {$userCount} pengguna aktif. Silakan ubah role pengguna tersebut terlebih dahulu.",
            ]);
        }

        $roleName = ucfirst(str_replace('_', ' ', $role->name));
        $role->delete();

        Log::warning("Banking Audit Trail: Admin deleted Role '{$roleName}'.");

        return back()->with('status', "Role '{$roleName}' berhasil dihapus dari sistem.");
    }
}
