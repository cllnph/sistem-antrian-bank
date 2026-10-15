<?php

namespace App\Services\Printing;

/** Explicit test double. Never selected in local/production runtime. */
class FakePrinterGateway implements PrinterGateway
{
    public array $submissions = [];

    public ?\Throwable $availabilityFailure = null;

    public ?\Throwable $submissionFailure = null;

    public function assertAvailable(string $printer): void
    {
        if ($this->availabilityFailure) {
            throw $this->availabilityFailure;
        }
    }

    public function submit(string $pdf, string $printer): void
    {
        $this->submissions[] = ['printer' => $printer, 'pdf' => file_get_contents($pdf)];
        if ($this->submissionFailure) {
            throw $this->submissionFailure;
        }
    }
}
