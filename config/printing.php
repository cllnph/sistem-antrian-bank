<?php

return [
    'config_path' => base_path('kiosk.config.json'),
    'executable' => base_path('tools/SumatraPDF/SumatraPDF.exe'),
    'check_executable' => base_path('tools/TicketPrinter/CheckPrinter.exe'),
    'require_worker' => true,
    'worker_ttl' => 45,
    'job_ttl' => 60,
];
