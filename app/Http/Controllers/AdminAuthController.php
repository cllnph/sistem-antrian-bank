<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Display the admin login view.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/admin');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle an incoming admin authentication request with Rate Limiting & Audit Trail.
     */
        $email = $request->input('email') 
            ?? $request->input('data.email') 
            ?? data_get($request->all(), 'data.email')
            ?? data_get($request->all(), 'email');

        $password = $request->input('password') 
            ?? $request->input('data.password') 
            ?? data_get($request->all(), 'data.password')
            ?? data_get($request->all(), 'password');

        $throttleKey = Str::transliterate(Str::lower((string) $email) . '|' . $request->ip());

        // Check if user is rate limited (Max 5 attempts per 60 seconds)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning("Banking Security Alert: Brute force attempt blocked for email '{$email}' from IP {$request->ip()}. Locked for {$seconds}s.");

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'login_error' => "Terlalu banyak percobaan login gagal. Akun dikunci sementara. Silakan coba lagi dalam {$seconds} detik.",
                ]);
        }

        $credentials = [
            'email' => $email,
            'password' => $password,
        ];

        $remember = $request->boolean('remember');

        // Attempt authentication using web guard
        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Log::warning("Banking Security Alert: Inactive user attempt login for User #{$user->id} ({$user->email}).");

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors([
                        'login_error' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.',
                    ]);
            }

            // Authentication passed -> Clear rate limiter
            RateLimiter::clear($throttleKey);

            // Regenerate session to prevent session fixation attacks
            $request->session()->regenerate();

            $user->update(['last_login_at' => now()]);
            Log::info("Banking Audit Trail: Successful admin authentication for User #{$user->id} ({$user->email}) from IP {$request->ip()}.");

            if ($user->hasRole('teller') || $user->role === 'teller') {
                return redirect()->route('teller.dashboard');
            }

            if ($user->hasRole('cs') || $user->role === 'cs') {
                return redirect()->route('cs.dashboard');
            }

            return redirect()->intended('/admin');
        }

        // Authentication failed -> Increment rate limiter
        RateLimiter::hit($throttleKey, 60);

        Log::warning("Banking Audit Trail: Failed admin authentication attempt for email '{$request->input('email')}' from IP {$request->ip()}.");

        // Generic error message for security (does not leak email existence)
        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'login_error' => 'Email atau kata sandi yang Anda masukkan tidak valid.',
            ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        $userEmail = Auth::user()?->email;

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info("Banking Audit Trail: User #{$userId} ({$userEmail}) logged out successfully.");

        return redirect('/admin/login')->with('status', 'Anda telah berhasil keluar dari sistem perbankan.');
    }
}
