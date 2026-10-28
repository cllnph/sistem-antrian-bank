<?php

return [
    // A managed transport is selected per browser/branch. Unpaired browsers print locally.
    'transport' => env('TICKET_PRINT_TRANSPORT', 'browser'),
    'agent_ttl' => 20,
    'agent_lease' => 90,
    'config_path' => base_path('kiosk.config.json'),
    'executable' => base_path('tools/SumatraPDF/SumatraPDF.exe'),
    'check_executable' => base_path('tools/TicketPrinter/CheckPrinter.exe'),
    'require_worker' => true,
    'worker_ttl' => 45,
    'job_ttl' => 60,
];
