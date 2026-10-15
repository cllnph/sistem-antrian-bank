<?php

namespace App\Services\Printing;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class WindowsPrinterGateway implements PrinterGateway
{
    public function assertAvailable(string $printer): void
    {
        if (PHP_OS_FAMILY !== 'Windows' || ! is_file(config('printing.executable'))) {
            throw new \RuntimeException('Layanan cetak server belum terpasang. Hubungi petugas.');
        }
        $checker = config('printing.check_executable', base_path('tools/TicketPrinter/CheckPrinter.exe'));
        if (! is_file($checker)) {
            throw new \RuntimeException('Pemeriksa printer server belum terpasang. Hubungi petugas.');
        }
        $process = new Process([$checker, $printer]);
        $process->setTimeout(10);
        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            throw new \RuntimeException('Printer belum merespons. Periksa koneksi printer ke server.', previous: $e);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Pemeriksaan printer server gagal. Hubungi petugas.', previous: $e);
        }
        if (! $process->isSuccessful()) {
            throw new \RuntimeException(match ($process->getExitCode()) {
                2 => 'Printer tiket tidak ditemukan atau bukan printer fisik. Hubungi petugas.',
                3 => 'Printer tiket sedang offline. Periksa koneksi dan daya printer.',
                4 => 'Printer tiket belum siap. Periksa kertas, penutup, dan antrean printer.',
                default => 'Status printer belum dapat diperiksa. Hubungi petugas.',
            });
        }
    }

    public function submit(string $pdf, string $printer): void
    {
        $process = new Process(['powershell.exe', '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts/Print-TicketPdf.ps1'), '-Executable', config('printing.executable'), '-PdfPath', $pdf, '-PrinterName', $printer]);
        $process->setTimeout(25);
        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            throw new PrintOutcomeUnknown('Proses pengiriman cetak melewati batas waktu.', previous: $e);
        }
        if ($process->getExitCode() === 124) {
            throw new PrintOutcomeUnknown('Printer tidak menyelesaikan pengiriman dalam 20 detik.');
        }
        if (! $process->isSuccessful()) {
            // A nonzero native exit does not prove that no page was spooled.
            throw new PrintOutcomeUnknown('Pengiriman cetak terhenti (kode '.($process->getExitCode() ?? 'unknown').').');
        }
    }
}
