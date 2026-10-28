<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\MaintainQueuePeriods;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: array_values(array_filter(array_map('trim', explode(',', $trustedProxies)))));
        }
        $middleware->web(append: [
            SetLocale::class,
            EnsureUserIsActive::class,
            MaintainQueuePeriods::class,
        ]);
        $middleware->api(append: [MaintainQueuePeriods::class]);
        $middleware->redirectTo(
            guests: '/admin/login',
        );
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 403 && ($request->is('admin/logout*') || $request->is('logout*'))) {
                Auth::logout();
                if (session()->isStarted()) {
                    session()->invalidate();
                    session()->regenerateToken();
                }

                return redirect('/admin/login');
            }
            if (in_array($e->getStatusCode(), [403, 419], true) && ($request->is('ambil-nomor*') || $request->is('print-jobs/*') || $request->is('kiosk-connect'))) {
                $reference = substr((string) Str::uuid(), 0, 8);
                Log::warning('Permintaan tiket ditolak.', [
                    'reference' => $reference, 'status' => $e->getStatusCode(), 'route' => $request->route()?->getName(),
                ]);
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'code' => $e->getStatusCode() === 419 ? 'session_expired' : 'access_denied',
                        'message' => ($e->getMessage() ?: 'Akses tiket tidak berlaku untuk browser ini.').' Kode kejadian: '.$reference,
                    ], $e->getStatusCode());
                }
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
