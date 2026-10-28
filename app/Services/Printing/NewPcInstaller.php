<?php

namespace App\Services\Printing;

use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewPcInstaller
{
    public function build(Branch $branch, User $actor, string $intent, string $serverUrl): string
    {
        abort_unless($actor->is_active && $actor->hasRole('admin') && Str::isUuid($intent), 403);
        $hash = hash('sha256', $branch->id.':'.$actor->id.':'.$intent);
        $path = storage_path('app/private/agent-installers/new-'.$hash.'.zip');
        $generated = null;
        $created = false;
        try {
            return DB::transaction(function () use ($branch, $actor, $hash, $serverUrl, $path, &$generated, &$created) {
                $locked = Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->is_active, 422, 'Cabang sedang nonaktif.');
                $agent = PrintAgent::withTrashed()->where('setup_request_hash', $hash)->first();
                if ($agent) {
                    abort_unless(! $agent->trashed() && ! $agent->device_id && $agent->enrollment_expires_at?->isFuture() && is_file($path), 409,
                        'Paket pemasangan ini sudah digunakan atau kedaluwarsa. Buka kembali Pasang PC baru untuk PC lain; gunakan Unduh Installer pada perangkat yang sudah terdaftar untuk pemasangan ulang.');

                    return $path;
                }
                $created = true;
                $agent = PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => $branch->id,
                    'setup_request_hash' => $hash, 'name' => 'PC baru — menunggu pemasangan', 'auto_name' => true,
                    'enabled' => false, 'revoked' => false, 'paper_profile' => 'a4']);
                $generated = app(AgentPackageBuilder::class)->build($agent, $serverUrl, $actor);
                if (! rename($generated, $path)) {
                    throw new \RuntimeException('Paket installer tidak dapat disimpan.');
                }

                return $path;
            });
        } catch (\Throwable $error) {
            if ($generated) {
                @unlink($generated);
            }
            if ($created) {
                @unlink($path);
            }
            throw $error;
        }
    }
}
