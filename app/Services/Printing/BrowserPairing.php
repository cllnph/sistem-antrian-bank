<?php

namespace App\Services\Printing;

use App\Models\PrintAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrowserPairing
{
    public function issue(PrintAgent $authenticated): array
    {
        return DB::transaction(function () use ($authenticated) {
            $agent = PrintAgent::whereKey($authenticated->id)->lockForUpdate()->first();
            abort_unless($agent && ! $agent->revoked && $agent->enabled && $agent->branch?->is_active
                && $agent->token_hash === $authenticated->token_hash && $agent->device_id === $authenticated->device_id, 409, 'Penghubung belum aktif atau akses berubah.');
            $token = bin2hex(random_bytes(32));
            DB::table('print_agent_browser_pairings')->insert([
                'token_hash' => hash('sha256', $token), 'print_agent_id' => $agent->id,
                'device_id' => $agent->device_id, 'credential_hash' => $agent->token_hash, 'expires_at' => now()->addMinutes(2),
            ]);

            return ['connect_url' => route('kiosk-connect').'#'.$token, 'expires_at' => now()->addMinutes(2)->toIso8601String()];
        });
    }

    public function consume(Request $request, string $token): string
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/D', $token), 422, 'Kode pemasangan tidak valid.');
        $hash = hash('sha256', $token);
        $candidate = DB::table('print_agent_browser_pairings')->where('token_hash', $hash)->first();
        abort_unless($candidate, 409, 'Kode telah dipakai atau kedaluwarsa. Buka kembali shortcut Buka Kiosk.');

        return DB::transaction(function () use ($request, $hash, $candidate) {
            // Lock agent first, matching enrollment, revoke and token issuance.
            $agent = PrintAgent::whereKey($candidate->print_agent_id)->lockForUpdate()->first();
            $proof = DB::table('print_agent_browser_pairings')->where('token_hash', $hash)->lockForUpdate()->first();
            abort_unless($proof && $proof->expires_at > now()->format('Y-m-d H:i:s') && $agent && ! $agent->revoked
                && $agent->enabled && $agent->branch?->is_active && $agent->device_id === $proof->device_id
                && hash_equals((string) $agent->token_hash, $proof->credential_hash), 409, 'Pemasangan tidak berlaku. Buka kembali shortcut Buka Kiosk.');
            app(KioskPrintRouting::class)->bindVerifiedAgent($request, $agent);
            DB::table('print_agent_browser_pairings')->where('token_hash', $hash)->delete();

            return route('queue.take-number', ['branch' => $agent->branch->code], false);
        });
    }
}
