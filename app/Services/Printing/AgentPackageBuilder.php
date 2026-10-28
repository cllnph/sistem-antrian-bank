<?php

namespace App\Services\Printing;

use App\Models\PrintAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AgentPackageBuilder
{
    public function windowsInstallerVersion(): string
    {
        $path = base_path('agent/VERSION.txt');
        if (! is_readable($path)) {
            throw new \RuntimeException('Versi installer Windows belum tersedia pada server.');
        }
        $version = trim(file_get_contents($path));
        if (! preg_match('/^\d+\.\d+\.\d+$/D', $version)) {
            throw new \RuntimeException('Versi installer Windows tidak valid.');
        }

        return $version;
    }

    public function build(PrintAgent $agent, string $serverUrl, User $actor, string $platform = 'windows'): string
    {
        abort_unless($actor->is_active && $actor->hasRole('admin'), 403);
        if (! in_array($platform, ['windows', 'android'], true)) {
            throw new \RuntimeException('Platform penghubung tidak didukung.');
        }
        $parts = parse_url($serverUrl);
        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new \RuntimeException('Alamat aplikasi pusat tidak valid.');
        }
        if (! app()->environment(['local', 'testing']) && $parts['scheme'] !== 'https') {
            throw new \RuntimeException('Alamat aplikasi pusat wajib HTTPS pada produksi.');
        }
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi ZIP pada server belum tersedia.');
        }
        $sumatra = base_path('tools/SumatraPDF/SumatraPDF.exe');
        if ($platform === 'windows' && (! is_readable($sumatra)
            || strtoupper(hash_file('sha256', $sumatra)) !== '719F689B34F47BE8CA105CE8484948474DAFDE0E106BAB599E4A89326070C3D0')) {
            throw new \RuntimeException('Komponen SumatraPDF resmi belum tersedia atau tidak cocok pada server. Hubungi pengelola server.');
        }
        $directory = storage_path('app/private/agent-installers');
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $path = $directory.'/'.bin2hex(random_bytes(16)).'.zip';
        try {
            DB::transaction(function () use ($agent, $serverUrl, $path, $platform, $sumatra) {
                $agent = PrintAgent::whereKey($agent->id)->lockForUpdate()->firstOrFail();
                $key = bin2hex(random_bytes(32));
                $agent->update(['enrollment_hash' => hash('sha256', $key), 'enrollment_expires_at' => now()->addMinutes(15), 'enrollment_auto_enable' => true]);
                $zip = new \ZipArchive;
                if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    throw new \RuntimeException('Paket penghubung gagal dibuat.');
                }
                try {
                    $files = $platform === 'windows'
                        ? ['Install-Agent.cmd', 'Install-Agent.ps1', 'Start-Agent.ps1', 'Watch-Agent.ps1', 'Open-Kiosk.ps1', 'Printer.Inventory.ps1', 'Agent.Common.ps1', 'Printer.Setup.ps1', 'README.txt', 'VERSION.txt']
                        : [];
                    foreach ($files as $file) {
                        if (! $zip->addFile(base_path('agent/'.$file), $file)) {
                            throw new \RuntimeException('Berkas installer tidak lengkap.');
                        }
                    }
                    foreach ($platform === 'windows' ? ['Print-TicketPdf.ps1', 'Keep-PrinterDefault.ps1'] : [] as $file) {
                        if (! $zip->addFile(base_path('scripts/'.$file), $file)) {
                            throw new \RuntimeException('Helper cetak tidak tersedia.');
                        }
                    }
                    if ($platform === 'windows') {
                        $zip->addFile(base_path('scripts/native/CheckPrinter.cs'), 'CheckPrinter.cs');
                        $zip->addFile($sumatra, 'SumatraPDF.exe');
                        $zip->addFile(base_path('tools/SumatraPDF/README.md'), 'SUMATRA-NOTICE.txt');
                    } else {
                        $zip->addFile(base_path('android-print-agent/README.txt'), 'README.txt');
                    }
                    $setup = ['server_url' => rtrim($serverUrl, '/'), 'agent_id' => $agent->id,
                        'branch_code' => $agent->branch->code, 'printer_name' => $agent->printer_name,
                        'enrollment_key' => $key, 'paper_profile' => $agent->paper_profile ?? 'a4', 'platform' => $platform, 'allow_http' => app()->environment(['local', 'testing'])];
                    if ($platform === 'windows') {
                        $setup['installer_version'] = $this->windowsInstallerVersion();
                        $setup['enrollment_expires_at'] = $agent->enrollment_expires_at->toIso8601String();
                    }
                    $zip->addFromString('setup.json', json_encode($setup, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
                } finally {
                    $zip->close();
                }
            });

            return $path;
        } catch (\Throwable $e) {
            @unlink($path);
            throw $e;
        }
    }
}
