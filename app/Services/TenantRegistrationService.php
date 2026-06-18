<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class TenantRegistrationService
{
    public function __construct(protected StripeClient $stripe) {}

    public function register(array $data): array
    {
        $stripeCustomerId = null;
        try {
            $customer = $this->stripe->customers->create([
                'email'    => $data['email'],
                'name'     => $data['company_name'],
                'metadata' => ['subdomain' => $data['subdomain']],
            ]);
            $stripeCustomerId = $customer->id;
        } catch (\Stripe\Exception\AuthenticationException $e) {
            if (!app()->isProduction()) {
                Log::warning('Stripe skipped in dev: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }

        $tenant = Tenant::create([
            'id'                  => Str::uuid(),
            'name'                => $data['company_name'],
            'email'               => $data['email'],
            'plan'                => 'starter',
            'stripe_customer_id'  => $stripeCustomerId,
            'subscription_status' => 'trialing',
            'trial_ends_at'       => now()->addDays(14),
        ]);

        $tenant->domains()->create([
            'domain' => $data['subdomain'] . '.' . config('tenancy.central_domains')[0],
        ]);

        [$user, $plainTextToken] = $this->seedTenantAdminWithToken($tenant, $data);

        return [
            'tenant' => $tenant->fresh('domains'),
            'user'   => $user,
            'token'  => $plainTextToken,
        ];
    }

    protected function seedTenantAdminWithToken(Tenant $tenant, array $data): array
    {
        $tenantDbName = config('tenancy.database.prefix', 'tenant_') . $tenant->getTenantKey();

        $tenantConfig = array_merge(
            config('database.connections.mysql'),
            ['database' => $tenantDbName]
        );

        config(['database.connections.tenant' => $tenantConfig]);
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            foreach (['admin', 'manager', 'member'] as $role) {
                \Spatie\Permission\Models\Role::create(['name' => $role, 'guard_name' => 'web']);
            }

            /** @var \App\Models\User $user */
            $user = \App\Models\User::create([
                'name'            => $data['owner_name'],
                'email'           => $data['email'],
                'password'        => bcrypt($data['password']),
                'is_tenant_admin' => true,
            ]);

            $user->assignRole('admin');

            // Load roles and create token before restoring the connection
            $user->load('roles');
            $plainTextToken = $user->createToken('auth_token')->plainTextToken;
            $user->setConnection(null);

            return [$user, $plainTextToken];
        } finally {
            DB::setDefaultConnection('mysql');
            DB::purge('tenant');
            config(['database.connections.tenant' => null]);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
