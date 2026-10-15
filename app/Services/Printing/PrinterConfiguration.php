<?php

namespace App\Services\Printing;

use App\Models\Branch;

class PrinterConfiguration
{
    public function forBranch(Branch $branch): array
    {
        try {
            $data = json_decode(file_get_contents(config('printing.config_path')), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Konfigurasi printer server tidak dapat dibaca.', previous: $e);
        }
        $printer = $data['branch_printers'][$branch->code] ?? null;
        if (! $printer && app()->environment(['local', 'testing'])) {
            $printer = $data['printer_name'] ?? null;
        }
        if (! is_string($printer) || trim($printer) === '' || preg_match('/[\x00-\x1f"\\\\]|PDF|XPS|OneNote|Fax/i', $printer)) {
            throw new \RuntimeException('Printer fisik untuk kantor ini belum dikonfigurasi. Hubungi petugas.');
        }
        $width = $data['paper']['width_mm'] ?? 80;
        $height = $data['paper']['height_mm'] ?? 150;
        if (! is_numeric($width) || ! is_numeric($height) || $width < 58 || $width > 210 || $height < 100 || $height > 500) {
            throw new \RuntimeException('Ukuran kertas tiket tidak valid.');
        }

        return ['printer' => $printer, 'paper' => ['width_mm' => (float) $width, 'height_mm' => (float) $height]];
    }
}
