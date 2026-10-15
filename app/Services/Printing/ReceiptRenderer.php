<?php

namespace App\Services\Printing;

use Dompdf\Dompdf;
use Dompdf\Options;

class ReceiptRenderer
{
    public function render(array $receipt): string
    {
        $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'defaultFont' => 'Helvetica', 'chroot' => public_path('images')]);
        $pdf = new Dompdf($options);
        $paper = $receipt['paper'];
        $pdf->setPaper([0, 0, $paper['width_mm'] * 72 / 25.4, $paper['height_mm'] * 72 / 25.4]);
        $pdf->loadHtml(view('queue.receipt-pdf', ['receipt' => $receipt])->render());
        $pdf->render();
        if ($pdf->getCanvas()->get_page_count() !== 1) {
            throw new \RuntimeException('Isi tiket melebihi satu halaman.');
        }

        return $pdf->output();
    }
}
