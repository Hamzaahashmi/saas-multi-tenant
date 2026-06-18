<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/central.php',  // Central routes — no tenancy middleware
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Tenant routes — InitializeTenancyByDomain + PreventAccessFromCentralDomains
            // so these only work on registered tenant subdomains, never on the central domain
            Route::middleware([
                'api',
                \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/tenant.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.auth'         => \App\Http\Middleware\AuthenticateTenantUser::class,
            'subscription.active' => \App\Http\Middleware\RequiresActiveSubscription::class,
            'plan.limits'         => \App\Http\Middleware\EnforcePlanLimits::class,
            'super.admin'         => \App\Http\Middleware\SuperAdminAuth::class,
        ]);
    })
    ->withProviders([
        \App\Providers\TenancyServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
