<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
    private const LIMITS = [
        'starter'    => 5,
        'pro'        => 25,
        'enterprise' => PHP_INT_MAX,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenancy()->tenant;

        if (!$tenant) {
            return $next($request);
        }

        $limit = self::LIMITS[$tenant->plan] ?? 5;
        $current = User::count();

        if ($current >= $limit) {
            return response()->json([
                'message' => "Your {$tenant->plan} plan allows up to {$limit} team members. Please upgrade to add more.",
                'limit'   => $limit,
                'current' => $current,
                'upgrade_url' => '/api/billing/checkout',
            ], 422);
        }

        return $next($request);
    }
}
