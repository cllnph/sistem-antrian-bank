<?php

namespace App\Services\Printing;

interface PrinterGateway
{
    /** Throw before issuance when the configured physical destination is unavailable. */
    public function assertAvailable(string $printer): void;

    /** Return only after submission; this does not establish that paper has emerged. */
    public function submit(string $pdf, string $printer): void;
}
