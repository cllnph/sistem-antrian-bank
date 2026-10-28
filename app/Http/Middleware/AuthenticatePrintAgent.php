<?php

namespace App\Http\Middleware;

use App\Models\PrintAgent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthenticatePrintAgent
{
    public function handle(Request $request, Closure $next)
    {
        abort_if(! app()->environment(['local', 'testing']) && ! $request->isSecure(), 403, 'HTTPS wajib digunakan.');
        $id = $request->header('X-Print-Agent', '');
        $token = $request->bearerToken();
        abort_unless(Str::isUuid($id) && is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token), 401);
        $agent = PrintAgent::find($id);
        abort_unless($agent && ! $agent->revoked && $agent->token_hash && hash_equals($agent->token_hash, hash('sha256', $token))
            && $agent->device_id && hash_equals($agent->device_id, $request->header('X-Print-Device', '')), 401);
        $request->attributes->set('print_agent', $agent);

        return $next($request);
    }
}
