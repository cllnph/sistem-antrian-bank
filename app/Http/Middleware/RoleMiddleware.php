<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $hasAccess = $user->hasAnyRole($roles) || $user->hasRole('admin');

        if (! $hasAccess) {
            foreach ($roles as $role) {
                if ($user->can('access_' . $role . '_console')) {
                    $hasAccess = true;
                    break;
                }
            }
        }

        if (! $hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
