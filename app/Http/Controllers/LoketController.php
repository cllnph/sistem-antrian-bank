<?php

namespace App\Http\Controllers;

use App\Models\ServiceSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoketController extends Controller
{
    /**
     * Display a listing of service counter desks.
     */
    public function index(): RedirectResponse
    {
        abort_unless(auth()->user()?->can('view_any_service_setting'), 403);

        return redirect('/admin/service-settings');
    }

    /**
     * Store a newly created counter desk via standard form submit.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->can('edit_service_setting'), 403);

        $request->merge(['branch_id' => $request->input('branch_id') ?? auth()->user()->branch_id]);
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'counter_name' => ['required', 'string', 'max:255', 'unique:service_settings,counter_name'],
            'service' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        abort_if(! auth()->user()->hasRole('admin') && (int) $validated['branch_id'] !== (int) auth()->user()->branch_id, 403);
        $loket = ServiceSetting::create([
            'branch_id' => $validated['branch_id'],
            'counter_name' => $validated['counter_name'],
            'service' => $validated['service'],
            'label' => $validated['label'] ?? ($validated['service'] === 'cs' ? 'Layanan CS' : 'Layanan Teller'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Log::info("Banking Audit Trail: Admin created new Loket '{$loket->counter_name}'.");

        return back()->with('status', "Loket baru '{$loket->counter_name}' berhasil ditambahkan.");
    }

    /**
     * Store a newly created counter desk via AJAX JSON API.
     */
    public function storeApi(Request $request): JsonResponse
    {
        abort_if(! auth()->user()->can('edit_service_setting'), 403);

        try {
            $request->merge(['branch_id' => $request->input('branch_id') ?? auth()->user()->branch_id]);
            $validated = $request->validate([
                'branch_id' => ['required', 'integer', 'exists:branches,id'],
                'counter_name' => ['required', 'string', 'max:255', 'unique:service_settings,counter_name'],
                'service' => ['required', 'string', 'max:255'],
                'label' => ['nullable', 'string', 'max:255'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $label = $validated['label'] ?? ($validated['service'] === 'cs' ? 'Layanan Customer Service' : 'Layanan Teller');

            abort_if(! auth()->user()->hasRole('admin') && (int) $validated['branch_id'] !== (int) auth()->user()->branch_id, 403);
            $loket = ServiceSetting::create([
                'branch_id' => $validated['branch_id'],
                'counter_name' => $validated['counter_name'],
                'service' => $validated['service'],
                'label' => $label,
                'is_active' => $request->boolean('is_active', true),
            ]);

            Log::info("Banking Audit Trail: Admin created new Loket '{$loket->counter_name}' via AJAX API.");

            return response()->json([
                'success' => true,
                'message' => "Loket baru '{$loket->counter_name}' berhasil disimpan.",
                'loket' => [
                    'id' => $loket->id,
                    'counter_name' => $loket->counter_name,
                    'service_name' => "{$label} ({$loket->counter_name})",
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: '.implode(', ', Arr::flatten($e->errors())),
            ], 422);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified counter desk in storage.
     */
    public function update(Request $request, ServiceSetting $loket): RedirectResponse
    {
        $user = auth()->user();
        abort_if(! $user->can('edit_service_setting'), 403);
        if (! $user->hasRole('admin') && $loket->id !== $user->service_setting_id) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah loket yang bukan milik Anda.');
        }

        $validated = $request->validate([
            'counter_name' => ['required', 'string', 'max:255', Rule::unique('service_settings')->ignore($loket->id)],
            'service' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $loket->update($validated);

        Log::info("Banking Audit Trail: User #{$user->id} updated Loket #{$loket->id} ({$loket->counter_name}).");

        return back()->with('status', "Data loket '{$loket->counter_name}' berhasil diperbarui.");
    }

    /**
     * Toggle active status for the counter desk.
     */
    public function toggleStatus(ServiceSetting $loket): RedirectResponse
    {
        $user = auth()->user();
        abort_if(! $user->can('edit_service_setting'), 403);
        if (! $user->hasRole('admin') && $loket->id !== $user->service_setting_id) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah status loket yang bukan milik Anda.');
        }

        $loket->is_active = ! $loket->is_active;
        $loket->save();

        $statusText = $loket->is_active ? 'diaktifkan' : 'dinonaktifkan';
        Log::info("Banking Audit Trail: Loket #{$loket->id} ({$loket->counter_name}) has been {$statusText}.");

        return back()->with('status', "Status loket '{$loket->counter_name}' berhasil {$statusText}.");
    }

    /**
     * Remove the specified counter desk from storage.
     */
    public function destroy(ServiceSetting $loket): RedirectResponse
    {
        $user = auth()->user();
        abort_if(! $user->can('edit_service_setting'), 403);
        if (! $user->hasRole('admin') && $loket->id !== $user->service_setting_id) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus loket yang bukan milik Anda.');
        }

        $counterName = $loket->counter_name;

        // Unassign users from this counter desk before deleting
        User::where('service_setting_id', $loket->id)->update(['service_setting_id' => null]);

        $loket->delete();

        Log::warning("Banking Audit Trail: User #{$user->id} deleted Loket '{$counterName}'.");

        return back()->with('status', "Loket '{$counterName}' berhasil dihapus dari sistem.");
    }
}
