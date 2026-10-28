<?php

namespace App\Http\Controllers;

use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Services\Printing\BranchAgentPrinting;
use App\Services\Printing\BrowserPairing;
use App\Services\Printing\PrintAgentManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintAgentController extends Controller
{
    public function preflight(Request $request)
    {
        abort_if(! app()->environment(['local', 'testing']) && ! $request->isSecure(), 403, 'HTTPS wajib digunakan.');
        $data = $request->validate(['agent_id' => 'required|uuid', 'enrollment_key' => 'required|string|size:64']);
        $agent = PrintAgent::find($data['agent_id']);
        abort_unless($agent && $agent->branch?->is_active && $agent->enrollment_hash && $agent->enrollment_expires_at?->isFuture()
            && hash_equals($agent->enrollment_hash, hash('sha256', $data['enrollment_key'])), 401, 'Paket sudah kedaluwarsa atau diganti. Unduh installer baru.');

        return response()->json(['ok' => true, 'application' => 'bank-dp-taspen-print-agent', 'branch_code' => $agent->branch->code])->header('Cache-Control', 'no-store');
    }

    public function enroll(Request $request)
    {
        abort_if(! app()->environment(['local', 'testing']) && ! $request->isSecure(), 403, 'HTTPS wajib digunakan.');
        $data = $request->validate(['agent_id' => 'required|uuid', 'enrollment_key' => 'required|string|size:64', 'device_id' => 'required|uuid', 'printer_name' => 'required|string|max:255',
            'machine_name' => 'sometimes|string|max:100', 'paper_profile' => 'sometimes|in:a4,thermal58,thermal80']);

        return DB::transaction(function () use ($data) {
            $agent = PrintAgent::whereKey($data['agent_id'])->lockForUpdate()->first();
            abort_unless($agent && $agent->enrollment_hash && $agent->enrollment_expires_at?->isFuture()
                && $agent->branch?->is_active && hash_equals($agent->enrollment_hash, hash('sha256', $data['enrollment_key'])), 401);
            abort_if(preg_match('/[\x00-\x1f"]|PDF|XPS|OneNote|Fax/i', $data['printer_name']), 422);
            $token = bin2hex(random_bytes(32));
            $agent->update(['token_hash' => hash('sha256', $token), 'device_id' => $data['device_id'], 'printer_name' => $data['printer_name'],
                'paper_profile' => $data['paper_profile'] ?? $agent->paper_profile,
                'auto_enable_pending' => $agent->enrollment_auto_enable, 'enrollment_auto_enable' => false,
                'revoked' => false, 'enrollment_hash' => null, 'enrollment_expires_at' => null, 'printer_ready' => false, 'heartbeat_at' => null, 'installed_printers' => null]);
            PrintJob::where('print_agent_id', $agent->id)->where('status', 'queued')->update(['status' => 'expired', 'finished_at' => now(), 'failure_code' => 'agent_reenrolled']);
            PrintJob::where('print_agent_id', $agent->id)->where('status', 'processing')->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => 'agent_reenrolled']);

            return response()->json(['token' => $token, 'branch_code' => $agent->branch->code])->header('Cache-Control', 'no-store');
        });
    }

    public function prepare(Request $request)
    {
        abort_if(! app()->environment(['local', 'testing']) && ! $request->isSecure(), 403);
        $data = $request->validate(['agent_id' => 'required|uuid', 'enrollment_key' => 'required|string|size:64', 'device_id' => 'required|uuid',
            'printer_name' => ['required', 'string', 'max:255', 'not_regex:/[\x00-\x1f"]|PDF|XPS|OneNote|Fax/i'],
            'machine_name' => 'sometimes|string|max:100', 'paper_profile' => 'sometimes|in:a4,thermal58,thermal80']);

        return DB::transaction(function () use ($data) {
            $agent = PrintAgent::whereKey($data['agent_id'])->lockForUpdate()->first();
            abort_unless($agent && $agent->branch?->is_active && $agent->enrollment_hash && $agent->enrollment_expires_at?->isFuture()
                && hash_equals($agent->enrollment_hash, hash('sha256', $data['enrollment_key'])), 401);
            $token = bin2hex(random_bytes(32));
            DB::table('print_agent_enrollments')->updateOrInsert(['print_agent_id' => $agent->id], [
                'enrollment_hash' => $agent->enrollment_hash, 'token_hash' => hash('sha256', $token), 'device_id' => $data['device_id'],
                'printer_name' => $data['printer_name'], 'expires_at' => $agent->enrollment_expires_at,
                'paper_profile' => $data['paper_profile'] ?? $agent->paper_profile,
                'machine_name' => $data['machine_name'] ?? null, 'auto_enable' => $agent->enrollment_auto_enable,
            ]);

            return response()->json(['token' => $token])->header('Cache-Control', 'no-store');
        });
    }

    public function activateEnrollment(Request $request)
    {
        abort_if(! app()->environment(['local', 'testing']) && ! $request->isSecure(), 403);
        $data = $request->validate(['agent_id' => 'required|uuid', 'device_id' => 'required|uuid', 'token' => 'required|string|size:64|regex:/^[a-f0-9]+$/']);

        return DB::transaction(function () use ($data) {
            $agent = PrintAgent::whereKey($data['agent_id'])->lockForUpdate()->first();
            abort_unless($agent && $agent->branch?->is_active, 401);
            $hash = hash('sha256', $data['token']);
            // Activation may be replayed after its HTTP response was lost.
            if (! $agent->revoked && $agent->token_hash && hash_equals($agent->token_hash, $hash) && $agent->device_id === $data['device_id']) {
                return response()->json(['ok' => true])->header('Cache-Control', 'no-store');
            }
            $pending = DB::table('print_agent_enrollments')->where('print_agent_id', $agent->id)->first();
            abort_unless($pending && $pending->expires_at > now()->format('Y-m-d H:i:s') && $agent->enrollment_hash
                && hash_equals($pending->enrollment_hash, $agent->enrollment_hash) && hash_equals($pending->token_hash, $hash)
                && $pending->device_id === $data['device_id'], 401);
            $agent->update(['token_hash' => $hash, 'device_id' => $pending->device_id, 'printer_name' => $pending->printer_name,
                'paper_profile' => $pending->paper_profile, 'machine_name' => $pending->machine_name,
                'auto_enable_pending' => (bool) $pending->auto_enable && $agent->enrollment_auto_enable, 'enrollment_auto_enable' => false,
                'revoked' => false, 'enrollment_hash' => null, 'enrollment_expires_at' => null, 'printer_ready' => false, 'heartbeat_at' => null, 'installed_printers' => null]);
            PrintJob::where('print_agent_id', $agent->id)->where('status', 'queued')->update(['status' => 'expired', 'finished_at' => now(), 'failure_code' => 'agent_reenrolled']);
            PrintJob::where('print_agent_id', $agent->id)->where('status', 'processing')->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => 'agent_reenrolled']);
            DB::table('print_agent_enrollments')->where('print_agent_id', $agent->id)->delete();

            return response()->json(['ok' => true])->header('Cache-Control', 'no-store');
        });
    }

    public function heartbeat(Request $request)
    {
        $data = $request->validate(['printer_ready' => 'required|boolean', 'printer_name' => 'required|string|max:255', 'machine_name' => 'required|string|max:100', 'version' => 'required|string|max:30', 'error' => 'nullable|string|max:255',
            'paper_profile' => 'sometimes|in:a4,thermal58,thermal80', 'inventory_status' => 'sometimes|in:ok,stale,unavailable',
            'installed_printers' => 'sometimes|array|max:100', 'installed_printers.*' => ['required', 'string', 'max:255', 'not_regex:/[\x00-\x1f"]|PDF|XPS|OneNote|Fax/i'],
        ]);

        return DB::transaction(function () use ($request, $data) {
            $authenticated = $request->attributes->get('print_agent');
            $agent = PrintAgent::whereKey($authenticated->id)->lockForUpdate()->first();
            abort_unless($agent && ! $agent->revoked && $agent->token_hash === $authenticated->token_hash && $agent->device_id === $authenticated->device_id, 401);
            $matches = $agent->printer_name === $data['printer_name'] && (! isset($data['paper_profile']) || $agent->paper_profile === $data['paper_profile']);
            // Old clients cannot synchronize a new destination. They must be upgraded once.
            abort_if(! $matches && ! array_key_exists('installed_printers', $data) && ! isset($data['inventory_status']), 409, 'Printer berubah. Pasang penghubung versi terbaru untuk sinkronisasi.');
            $agent->fill(['heartbeat_at' => now(), 'printer_ready' => $matches && $data['printer_ready'],
                'last_error' => $matches ? ($data['error'] ?? null) : 'Menunggu konfirmasi printer baru dari PC penghubung.',
                'machine_name' => $data['machine_name'], 'version' => $data['version']]);
            if (array_key_exists('installed_printers', $data) && ($data['inventory_status'] ?? 'ok') === 'ok') {
                $agent->installed_printers = array_values(array_unique($data['installed_printers']));
            }
            if (isset($data['inventory_status'])) {
                $agent->inventory_status = $data['inventory_status'];
            }
            if ($agent->auto_name) {
                $agent->name = $data['machine_name'];
            }
            if ($matches && $data['printer_ready'] && $agent->auto_enable_pending && $agent->branch?->is_active) {
                $agent->enabled = true;
                $agent->auto_enable_pending = false;
            }
            $agent->save();

            return response()->json(['ok' => true, 'enabled' => $agent->enabled, 'printer_name' => $agent->printer_name, 'paper_profile' => $agent->paper_profile])->header('Cache-Control', 'no-store');
        });
    }

    public function browserPairings(Request $request, BrowserPairing $pairing)
    {
        return response()->json($pairing->issue($request->attributes->get('print_agent')))->header('Cache-Control', 'no-store');
    }

    public function printerSelection(Request $request, PrintAgentManagement $management)
    {
        $data = $request->validate([
            'printer_name' => ['required', 'string', 'max:255', 'not_regex:/[\x00-\x1f"]|PDF|XPS|OneNote|Fax/i'],
            'paper_profile' => 'required|in:a4,thermal58,thermal80',
            'installed_printers' => 'required|array|min:1|max:100',
            'installed_printers.*' => ['required', 'string', 'max:255', 'not_regex:/[\x00-\x1f"]|PDF|XPS|OneNote|Fax/i'],
        ]);
        $management->selectLocally($request->attributes->get('print_agent'), $data);

        return response()->json(['ok' => true])->header('Cache-Control', 'no-store');
    }

    public function claim(Request $request, BranchAgentPrinting $printing)
    {
        return response()->json(['job' => $printing->claim($request->attributes->get('print_agent'))])->header('Cache-Control', 'no-store');
    }

    private function authorizeJob(Request $request, PrintJob $printJob, BranchAgentPrinting $printing): void
    {
        $printing->authorizeClaim($request->attributes->get('print_agent'), $printJob, $request->header('X-Print-Claim'));
    }

    public function pdf(Request $request, PrintJob $printJob, BranchAgentPrinting $printing)
    {
        $this->authorizeJob($request, $printJob, $printing);
        try {
            $path = $printing->pdf($printJob);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'PDF tiket gagal disiapkan.'], 422);
        }

        return response()->file($path, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'no-store', 'X-Pdf-Sha256' => hash_file('sha256', $path)]);
    }

    public function start(Request $request, PrintJob $printJob, BranchAgentPrinting $printing)
    {
        $this->authorizeJob($request, $printJob, $printing);
        $data = $request->validate(['pdf_sha256' => 'required|string|size:64']);
        $printing->start($request->attributes->get('print_agent'), $printJob, $data['pdf_sha256']);

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request, PrintJob $printJob, BranchAgentPrinting $printing)
    {
        $this->authorizeJob($request, $printJob, $printing);
        $data = $request->validate(['status' => 'required|in:submitted,failed,unknown', 'failure_code' => 'nullable|string|max:100|regex:/^[a-z0-9_]+$/']);
        $printing->complete($printJob, $data['status'], $data['failure_code'] ?? null);

        return response()->json(['ok' => true]);
    }
}
