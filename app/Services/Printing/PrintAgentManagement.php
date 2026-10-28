<?php

namespace App\Services\Printing;

use App\Models\PrintAgent;
use App\Models\PrintJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrintAgentManagement
{
    public function update(PrintAgent $record, array $data): PrintAgent
    {
        $this->authorize();

        return DB::transaction(function () use ($record, $data) {
            $agent = PrintAgent::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_if(isset($data['branch_id']) && (int) $data['branch_id'] !== (int) $agent->branch_id, 403, 'Cabang perangkat tidak dapat diubah.');
            abort_if(array_key_exists('printer_name', $data) && $data['printer_name'] !== $agent->printer_name, 422, 'Gunakan Ganti printer & kertas untuk memilih inventaris PC.');
            $validated = validator($data, ['name' => ['required', 'string', 'max:100']])->validate();
            $agent->fill(['name' => $validated['name'], 'auto_name' => false])->save();

            return $agent;
        });
    }

    public function enable(PrintAgent $record): void
    {
        $this->authorize();
        DB::transaction(function () use ($record) {
            $agent = PrintAgent::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if (! $agent->isReady() || ! $agent->branch?->is_active) {
                throw ValidationException::withMessages(['agent' => 'Pasang dan jalankan penghubung sampai status Siap.']);
            }
            $agent->update(['enabled' => true]);
        });
    }

    public function disable(PrintAgent $record, bool $revoke = false, bool $delete = false): bool
    {
        $this->authorize();

        return DB::transaction(function () use ($record, $revoke, $delete) {
            $agent = PrintAgent::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $agent->enabled = false;
            $agent->auto_enable_pending = false;
            $agent->enrollment_auto_enable = false;
            DB::table('print_agent_enrollments')->where('print_agent_id', $agent->id)->update(['auto_enable' => false]);
            DB::table('print_agent_browser_pairings')->where('print_agent_id', $agent->id)->delete();
            if ($revoke || $delete) {
                $agent->fill(['revoked' => true, 'token_hash' => null, 'enrollment_hash' => null, 'enrollment_expires_at' => null, 'printer_ready' => false]);
            }
            $agent->save();
            $this->cancelJobs($agent, $delete ? 'agent_deleted' : ($revoke ? 'agent_revoked' : 'agent_disabled'));

            // Keep the binding so the browser displays an error instead of changing destination.
            return $delete ? (bool) $agent->delete() : true;
        });
    }

    private function cancelJobs(PrintAgent $agent, string $code): void
    {
        PrintJob::where('print_agent_id', $agent->id)->where('status', 'queued')
            ->update(['status' => 'expired', 'finished_at' => now(), 'failure_code' => $code]);
        PrintJob::where('print_agent_id', $agent->id)->where('status', 'processing')
            ->update(['status' => 'unknown', 'finished_at' => now(), 'failure_code' => $code]);
    }

    public function selectLocally(PrintAgent $authenticated, array $data): void
    {
        app(BranchAgentPrinting::class)->sweep();
        DB::transaction(function () use ($authenticated, $data) {
            $agent = PrintAgent::whereKey($authenticated->id)->lockForUpdate()->first();
            abort_unless($agent && ! $agent->revoked && $agent->branch?->is_active
                && $agent->token_hash === $authenticated->token_hash && $agent->device_id === $authenticated->device_id, 401);
            abort_unless(in_array($data['printer_name'], $data['installed_printers'], true), 422, 'Printer tidak ditemukan pada PC ini.');
            if ($agent->printer_name === $data['printer_name'] && $agent->paper_profile === $data['paper_profile']) {
                return;
            }
            abort_if(PrintJob::where('print_agent_id', $agent->id)->where('status', 'processing')->exists(), 409, 'Tunggu cetakan yang sedang diproses selesai sebelum mengganti printer.');
            $this->cancelJobs($agent, 'local_printer_changed');
            $agent->update(['printer_name' => $data['printer_name'], 'paper_profile' => $data['paper_profile'],
                'installed_printers' => array_values(array_unique($data['installed_printers'])),
                'printer_ready' => false, 'last_error' => 'Menunggu konfirmasi printer dan kertas baru dari PC penghubung.']);
        });
    }

    public function selectFromAdmin(PrintAgent $record, array $data): void
    {
        $this->authorize();
        ReceiptPaper::dimensions($data['paper_profile']);
        app(BranchAgentPrinting::class)->sweep();

        DB::transaction(function () use ($record, $data) {
            $agent = PrintAgent::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_unless($agent->device_id && ! $agent->revoked && $agent->branch?->is_active, 409, 'PC penghubung belum terpasang atau tidak aktif.');
            if (! in_array($data['printer_name'], $agent->installed_printers ?? [], true)
                || preg_match('/[\x00-\x1f"]|PDF|XPS|OneNote|Fax/i', $data['printer_name'])) {
                throw ValidationException::withMessages(['printer_name' => 'Pilih printer fisik yang dilaporkan PC penghubung.']);
            }
            if ($agent->printer_name === $data['printer_name'] && $agent->paper_profile === $data['paper_profile']) {
                return;
            }
            if (PrintJob::where('print_agent_id', $agent->id)->where('status', 'processing')->exists()) {
                throw ValidationException::withMessages(['printer_name' => 'Tunggu cetakan yang sedang diproses selesai sebelum mengganti printer.']);
            }
            $this->cancelJobs($agent, 'admin_printer_changed');
            $agent->update(['printer_name' => $data['printer_name'], 'paper_profile' => $data['paper_profile'],
                'printer_ready' => false, 'last_error' => 'Menunggu konfirmasi printer dan kertas baru dari PC penghubung.']);
        });
    }

    private function authorize(): void
    {
        abort_unless(auth()->user()?->is_active && auth()->user()?->hasRole('admin'), 403);
    }
}
