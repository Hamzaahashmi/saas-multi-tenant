<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresActiveSubscription
{
    /**
     * Block access to protected routes if tenant has no active subscription
     * and their trial has expired.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenancy()->tenant;

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        if ($tenant->isSubscribed()) {
            return $next($request);
        }

        // Still on trial?
        if ($tenant->onTrial()) {
            return $next($request);
        }

        return response()->json([
            'message'      => 'Your subscription has expired. Please upgrade to continue.',
            'billing_url'  => '/api/billing/checkout',
            'subscription_status' => $tenant->subscription_status,
        ], 402); // 402 Payment Required
    }
}
