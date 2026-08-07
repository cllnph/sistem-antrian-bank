<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the unified operational dashboard hub.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('teller')) {
            return redirect()->route('teller.dashboard');
        }

        if ($user->hasRole('cs')) {
            return redirect()->route('cs.dashboard');
        }

        $userCount = User::count();
        $loketCount = ServiceSetting::count();
        $todayQueueCount = Queue::whereDate('created_at', today())->count();

        return view('admin.dashboard', compact('userCount', 'loketCount', 'todayQueueCount'));
    }
}
