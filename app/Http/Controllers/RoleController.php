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
use App\Models\Role;

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

        $request->merge(['display_name' => $request->input('display_name', $request->input('name')), 'name' => is_string($request->input('name')) ? Str::slug(strtolower($request->input('name')), '_') : $request->input('name')]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'distinct', \Illuminate\Validation\Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ], [
            'permissions.required' => 'Minimal harus memilih 1 hak akses otorisasi.',
            'permissions.min' => 'Minimal harus memilih 1 hak akses otorisasi.',
            'name.unique' => 'Nama role ini sudah terdaftar dalam sistem.',
        ]);

        $role = $this->createRole($validated);

        Log::info("Banking Audit Trail: Admin created new Role '{$role->name}' with ".count($validated['permissions']).' permissions.');

        return back()->with('status', "Role baru '".$role->display_name."' berhasil disimpan ke database.");
    }

    /**
     * Store a newly created role via AJAX JSON API.
     */
    public function storeApi(Request $request): JsonResponse
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        try {
            $request->merge(['display_name' => $request->input('display_name', $request->input('name')), 'name' => is_string($request->input('name')) ? Str::slug(strtolower($request->input('name')), '_') : $request->input('name')]);
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
                'display_name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:500'],
                'permissions' => ['required', 'array', 'min:1'],
                'permissions.*' => ['required', 'string', 'distinct', \Illuminate\Validation\Rule::exists('permissions', 'name')->where('guard_name', 'web')],
            ], [
                'permissions.required' => 'Minimal harus memilih 1 hak akses otorisasi.',
                'permissions.min' => 'Minimal harus memilih 1 hak akses otorisasi.',
                'name.unique' => 'Nama role ini sudah terdaftar dalam sistem.',
            ]);

            $role = $this->createRole($validated);

            Log::info("Banking Audit Trail: Admin created new Role '{$role->name}' via AJAX API.");

            return response()->json([
                'success' => true,
                'message' => "Role baru '".$role->display_name."' berhasil disimpan ke database.",
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: '.implode(', ', Arr::flatten($e->errors())),
            ], 422);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba kembali.',
            ], 500);
        }
    }

    private function createRole(array $validated): Role
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated): Role {
            $role = Role::create(['name' => $validated['name'], 'display_name' => $validated['display_name'], 'guard_name' => 'web']);
            $role->syncPermissions(array_map(fn ($name) => Permission::findByName($name, 'web'), $validated['permissions']));

            return $role;
        });
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
            'permissions.*' => ['required', 'string', 'distinct', \Illuminate\Validation\Rule::exists('permissions', 'name')->where('guard_name', 'web')],
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

        return back()->with('status', "Hak akses untuk role '".$role->display_name."' berhasil diperbarui.");
    }

    /**
     * Remove the specified role with active user check (Only primary Admin is permanent).
     */
    public function destroy(int $id): RedirectResponse
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);
        $roleModelClass = config('permission.models.role', Role::class);
        $role = $roleModelClass::findOrFail($id);

        \App\Services\IntegrityRules::assertDeletable($role);

        $roleName = $role->display_name;
        $role->delete();

        Log::warning("Banking Audit Trail: Admin deleted Role '{$roleName}'.");

        return back()->with('status', "Role '{$roleName}' berhasil dihapus dari sistem.");
    }
}
