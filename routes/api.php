<?php

use App\Http\Controllers\PrintAgentController;
use App\Http\Middleware\AuthenticatePrintAgent;
use Illuminate\Support\Facades\Route;

Route::post('/print-agent/enroll', [PrintAgentController::class, 'enroll'])->middleware('throttle:20,1');
Route::post('/print-agent/preflight', [PrintAgentController::class, 'preflight'])->middleware('throttle:20,1');
Route::post('/print-agent/prepare', [PrintAgentController::class, 'prepare'])->middleware('throttle:20,1');
Route::post('/print-agent/activate-enrollment', [PrintAgentController::class, 'activateEnrollment'])->middleware('throttle:20,1');
Route::prefix('print-agent')->middleware(['throttle:3000,1', AuthenticatePrintAgent::class, 'throttle:print-agent'])->group(function () {
    Route::post('/heartbeat', [PrintAgentController::class, 'heartbeat']);
    Route::post('/browser-pairings', [PrintAgentController::class, 'browserPairings']);
    Route::post('/printer-selection', [PrintAgentController::class, 'printerSelection']);
    Route::post('/claim', [PrintAgentController::class, 'claim']);
    Route::get('/jobs/{printJob}/pdf', [PrintAgentController::class, 'pdf'])->whereUuid('printJob')->name('agent.pdf');
    Route::post('/jobs/{printJob}/start', [PrintAgentController::class, 'start'])->whereUuid('printJob');
    Route::post('/jobs/{printJob}/complete', [PrintAgentController::class, 'complete'])->whereUuid('printJob');
});
