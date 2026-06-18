<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\StripeService;
use App\Services\TenantRegistrationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient(config('services.stripe.secret'));
        });

        $this->app->singleton(StripeService::class);
        $this->app->singleton(TenantRegistrationService::class);
        $this->app->singleton(ActivityLogService::class);
    }

    public function boot(): void
    {
        // Team management gate — admin or manager role required
        Gate::define('manage-team', function (User $user) {
            return $user->is_tenant_admin || $user->hasRole(['admin', 'manager']);
        });

        // Settings gate — admin only
        Gate::define('manage-settings', function (User $user) {
            return $user->is_tenant_admin || $user->hasRole('admin');
        });

        // Activity log gate — admin only
        Gate::define('view-activity-log', function (User $user) {
            return $user->is_tenant_admin || $user->hasRole('admin');
        });
    }
}
