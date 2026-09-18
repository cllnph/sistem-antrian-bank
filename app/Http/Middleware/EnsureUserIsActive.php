<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            $userId = Auth::id();
            $userEmail = Auth::user()?->email;

            Auth::logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            \Illuminate\Support\Facades\Log::warning("Banking Security Alert: Inactive User #{$userId} ({$userEmail}) was blocked and logged out by EnsureUserIsActive middleware.");

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun Anda telah dinonaktifkan.'], 403);
            }

            return redirect('/admin/login')->withErrors([
                'login_error' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.',
            ]);
        }

        return $next($request);
    }
}
