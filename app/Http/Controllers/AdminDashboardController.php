<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class AdminDashboardController extends Controller
{
    /**
     * Display the unified operational dashboard hub.
     */
    public function index(): RedirectResponse
    {
        abort_unless(auth()->check(), 403);

        return redirect('/admin');
    }
}
