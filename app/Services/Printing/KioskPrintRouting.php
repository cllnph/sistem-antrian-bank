<?php

namespace App\Services\Printing;

use App\Models\Branch;
use App\Models\PrintAgent;
use App\Services\KioskDeviceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskPrintRouting
{
    public function selection(Branch $branch, Request $request): array
    {
        $local = ['transport' => 'browser', 'agent_id' => null, 'paper_profile' => 'a4', 'label' => 'Printer PC ini (pilih pada dialog cetak)', 'notice' => null];
        if (! $request->hasSession()) {
            return $local;
        }
        $device = app(KioskDeviceHistory::class)->device($request);
        $binding = DB::table('kiosk_print_bindings')->where('kiosk_device_id', $device->id)->where('branch_id', $branch->id)->first();
        if (! $binding) {
            return $local;
        }
        $local['paper_profile'] = $binding->paper_profile ?? 'a4';
        if ($binding->transport === 'browser') {
            return $local;
        }
        if (! $binding->verified_at) {
            return [...$local, 'notice' => 'Pasangan lama belum diverifikasi pada PC ini. Cetak lokal tersedia; gunakan shortcut Buka Kiosk untuk mode otomatis.'];
        }
        if ($binding->transport === 'server' && $binding->binding_source === 'admin_server') {
            return [...$local, 'transport' => 'server', 'label' => 'Printer server (pilihan lanjutan admin)'];
        }
        $agent = $binding->print_agent_id ? PrintAgent::withTrashed()->find($binding->print_agent_id) : null;
        $validProof = $binding->binding_source === 'local_launcher' && $agent && $binding->proof_device_id === $agent->device_id;

        // A verified but unavailable destination stays visible, never redirected silently.
        return [...$local, 'transport' => 'agent', 'agent_id' => $binding->print_agent_id,
            'proof_device_id' => $binding->proof_device_id,
            'verified' => $validProof && (int) $agent->branch_id === (int) $branch->id,
            'paper_profile' => $agent?->paper_profile ?? 'thermal80',
            'label' => ($agent?->machine_name ?: $agent?->name ?: 'Penghubung dihapus').' · '.($agent?->printer_name ?? 'Belum dipilih'),
        ];
    }

    public function bindAgent(Request $request, PrintAgent $agent): void
    {
        $this->authorizeAdmin($request);
        abort(409, 'Buka shortcut Buka Kiosk pada PC printer untuk memverifikasi pemasangan. Aktivasi dari PC admin tidak memasangkan browser admin.');
    }

    /** Called after BrowserPairing has verified a one-time agent credential proof. */
    public function bindVerifiedAgent(Request $request, PrintAgent $agent): void
    {
        $this->save($request, $agent->branch_id, 'agent', $agent->id, $agent->device_id, 'local_launcher', $agent->paper_profile ?? 'thermal80');
    }

    public function bindServer(Request $request, Branch $branch): void
    {
        $this->authorizeAdmin($request);
        abort_unless($branch->is_active, 409);
        app(PrinterConfiguration::class)->forBranch($branch);
        $this->save($request, $branch->id, 'server', null, null, 'admin_server');
    }

    public function browser(Request $request, Branch $branch): void
    {
        $selection = $this->selection($branch, $request);
        $this->save($request, $branch->id, 'browser', null, null, 'browser', $selection['paper_profile']);
    }

    public function paper(Request $request, Branch $branch, string $profile): void
    {
        ReceiptPaper::dimensions($profile);
        $selection = $this->selection($branch, $request);
        abort_unless($selection['transport'] === 'browser', 409, 'Untuk cetak otomatis, ubah kertas melalui Ganti printer pada PC penghubung.');
        $this->save($request, $branch->id, 'browser', null, null, 'browser', $profile);
    }

    private function save(Request $request, int $branchId, string $transport, ?string $agentId, ?string $proof = null, string $source = 'browser', ?string $paper = null): void
    {
        $device = app(KioskDeviceHistory::class)->device($request);
        DB::table('kiosk_print_bindings')->updateOrInsert(['kiosk_device_id' => $device->id, 'branch_id' => $branchId], [
            'transport' => $transport, 'print_agent_id' => $agentId, 'created_at' => now(), 'updated_at' => now(),
            'verified_at' => now(), 'proof_device_id' => $proof, 'binding_source' => $source, 'paper_profile' => $paper,
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()?->hasRole('admin'), 403);
    }
}
