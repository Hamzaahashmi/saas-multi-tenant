<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

// Replaces auth:sanctum — the standard guard doesn't re-resolve after
// InitializeTenancyByDomain switches the DB connection mid-pipeline.
class AuthenticateTenantUser
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.', ['sanctum'], null);
        }

        auth()->shouldUse('sanctum');

        return $next($request);
    }
}
