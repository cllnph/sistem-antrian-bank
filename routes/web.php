<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LoketController;

Route::get('/', function () {
    return redirect('/ambil-nomor');
});

// Auth Login Redirects
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::any('/admin/logout-custom', function () {
    \Illuminate\Support\Facades\Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/admin/login');
})->name('custom.admin.logout');

Route::any('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/admin/login');
})->name('logout');

// Aliases for Loket and legacy paths -> Filament Panel resources
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/lokets', function () {
        return redirect('/admin/service-settings');
    })->name('admin.lokets.index');

    Route::get('/admin/lokets-alias', function () {
        return redirect('/admin/service-settings');
    })->name('filament.admin.resources.lokets.index');

    Route::post('/admin/api/roles', [RoleController::class, 'storeApi'])->name('admin.api.roles.store');
    Route::post('/admin/api/lokets', [LoketController::class, 'storeApi'])->name('admin.api.lokets.store');

    // Reset Queues Route
    Route::middleware(['can:reset_queues'])->group(function () {
        Route::post('/admin/queues/reset', [QueueController::class, 'resetQueues'])->name('admin.queues.reset');
    });
});

// Public Kiosk & Display Screens
Route::get('/display', [DisplayController::class, 'index'])->name('display');
Route::get('/api/display/data', [DisplayController::class, 'getDisplayData'])->name('api.display.data');
Route::get('/ambil-nomor', [QueueController::class, 'takeNumber'])->name('queue.take-number');
Route::post('/ambil-nomor', [QueueController::class, 'storeNumber'])->name('queue.store-number');
Route::get('/verifikasi/{number}', [QueueController::class, 'verifyTicket'])->name('queue.verify');

// Officer Consoles -> Filament Queue Resource
Route::middleware(['auth', 'role:cs'])->group(function () {
    Route::get('/dashboard/cs', function () {
        return redirect('/admin/queues');
    })->name('cs.dashboard');
    Route::post('/queue/next/cs', [QueueController::class, 'nextCs'])->name('queue.next.cs');
    Route::post('/queue/complete/cs', [QueueController::class, 'completeCs'])->name('queue.complete.cs');
    Route::post('/queue/skip/cs', [QueueController::class, 'skipCs'])->name('queue.skip.cs');
    Route::post('/queue/recall/cs', [QueueController::class, 'recallCs'])->name('queue.recall.cs');
});

Route::middleware(['auth', 'role:teller'])->group(function () {
    Route::get('/dashboard/teller', function () {
        return redirect('/admin/queues');
    })->name('teller.dashboard');
    Route::post('/queue/next/teller', [QueueController::class, 'nextTeller'])->name('queue.next.teller');
    Route::post('/queue/complete/teller', [QueueController::class, 'completeTeller'])->name('queue.complete.teller');
    Route::post('/queue/skip/teller', [QueueController::class, 'skipTeller'])->name('queue.skip.teller');
    Route::post('/queue/recall/teller', [QueueController::class, 'recallTeller'])->name('queue.recall.teller');
});
