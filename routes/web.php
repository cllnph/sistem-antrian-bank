<?php

use App\Http\Controllers\DisplayController;
use App\Http\Controllers\LoketController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\RoleController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use STS\FilamentImpersonate\Facades\Impersonation;

Route::get('/', function () {
    return redirect('/ambil-nomor');
});

// Auth Login Redirects
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::post('/admin/logout-custom', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/admin/login');
})->name('custom.admin.logout');

Route::post('/logout', function () {
    Auth::logout();
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

    // Impersonation Routes using official STS Impersonation
    Route::get('/admin/impersonate/{user}', function (User $user) {
        $currentUser = auth()->user();
        if (! $currentUser?->hasRole('admin') && ! Impersonation::isImpersonating()) {
            abort(403, 'Hanya Administrator yang dapat melakukan impersonate.');
        }

        session()->put('impersonate.back_to', route('filament.admin.pages.admin-control-center'));
        Impersonation::enter($currentUser, $user);

        return redirect()->route('filament.admin.resources.queues.index');
    })->name('admin.impersonate');

    Route::get('/admin/impersonate/leave', function () {
        return redirect()->route('filament-impersonate.leave');
    })->name('admin.impersonate.leave');

    // Reset Queues Route
    Route::middleware(['can:reset_queues'])->group(function () {
        Route::post('/admin/queues/reset', [QueueController::class, 'resetQueues'])->name('admin.queues.reset');
    });
});

// Public Kiosk & Display Screens (Supports multi-branch via slug/code or query parameter)
Route::get('/display/{branch?}', [DisplayController::class, 'index'])->name('display');
Route::get('/api/display/data/{branch?}', [DisplayController::class, 'getDisplayData'])->name('api.display.data');
Route::get('/ambil-nomor/{branch?}', [QueueController::class, 'takeNumber'])->name('queue.take-number');
Route::post('/ambil-nomor/{branch?}', [QueueController::class, 'storeNumber'])->name('queue.store-number');
Route::get('/verifikasi/{queueId}', [QueueController::class, 'verifyTicket'])->whereNumber('queueId')->name('queue.verify');

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
