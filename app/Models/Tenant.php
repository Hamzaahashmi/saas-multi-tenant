<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Laravel\Sanctum\HasApiTokens;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasApiTokens;

    /**
     * Extra columns stored in the tenants table.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'plan',
            'stripe_customer_id',
            'stripe_subscription_id',
            'subscription_status',
            'trial_ends_at',
            'plan_ends_at',
        ];
    }

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'plan_ends_at'  => 'datetime',
    ];

    /**
     * Check if the tenant has an active subscription.
     */
    public function isSubscribed(): bool
    {
        return in_array($this->subscription_status, ['active', 'trialing']);
    }

    /**
     * Check if the tenant is on a specific plan.
     */
    public function onPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    /**
     * Check if the tenant is on trial.
     */
    public function onTrial(): bool
    {
        return $this->subscription_status === 'trialing'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }
}
