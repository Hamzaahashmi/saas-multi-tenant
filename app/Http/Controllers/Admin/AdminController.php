<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::with('domains')->latest();

        if ($request->filled('plan')) {
            $query->where('plan', $request->input('plan'));
        }

        if ($request->filled('status')) {
            $query->where('subscription_status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('email', 'like', '%' . $request->input('search') . '%');
            });
        }

        $tenants = $query->paginate($request->integer('per_page', 20));

        $tenants->getCollection()->transform(fn($t) => $this->formatTenant($t));

        return response()->json($tenants);
    }

    public function show(string $id): JsonResponse
    {
        $tenant = Tenant::with('domains')->findOrFail($id);

        return response()->json([
            'tenant' => $this->formatTenant($tenant, detailed: true),
        ]);
    }

    public function suspend(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        if ($tenant->subscription_status === 'suspended') {
            return response()->json(['message' => 'Tenant is already suspended.'], 422);
        }

        $tenant->update([
            'subscription_status' => 'suspended',
            'data' => array_merge($tenant->data ?? [], [
                'suspended_at'     => now()->toISOString(),
                'suspended_reason' => $request->input('reason', 'Administrative action'),
                'previous_status'  => $tenant->subscription_status,
            ]),
        ]);

        return response()->json(['message' => 'Tenant suspended successfully.', 'tenant_id' => $id]);
    }

    public function unsuspend(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        if ($tenant->subscription_status !== 'suspended') {
            return response()->json(['message' => 'Tenant is not suspended.'], 422);
        }

        $data = $tenant->data ?? [];
        $previousStatus = $data['previous_status'] ?? 'active';
        unset($data['suspended_at'], $data['suspended_reason'], $data['previous_status']);

        $tenant->update([
            'subscription_status' => $previousStatus,
            'data' => $data,
        ]);

        return response()->json(['message' => 'Tenant restored successfully.', 'status' => $previousStatus]);
    }

    public function stats(): JsonResponse
    {
        $tenants = Tenant::all();

        return response()->json([
            'stats' => [
                'total_tenants'    => $tenants->count(),
                'active'           => $tenants->where('subscription_status', 'active')->count(),
                'trialing'         => $tenants->where('subscription_status', 'trialing')->count(),
                'canceled'         => $tenants->where('subscription_status', 'canceled')->count(),
                'suspended'        => $tenants->where('subscription_status', 'suspended')->count(),
                'by_plan' => [
                    'starter'    => $tenants->where('plan', 'starter')->count(),
                    'pro'        => $tenants->where('plan', 'pro')->count(),
                    'enterprise' => $tenants->where('plan', 'enterprise')->count(),
                ],
            ],
        ]);
    }

    private function formatTenant(Tenant $tenant, bool $detailed = false): array
    {
        $base = [
            'id'                  => $tenant->id,
            'name'                => $tenant->name,
            'email'               => $tenant->email,
            'plan'                => $tenant->plan,
            'subscription_status' => $tenant->subscription_status,
            'trial_ends_at'       => $tenant->trial_ends_at,
            'plan_ends_at'        => $tenant->plan_ends_at,
            'domain'              => $tenant->domains->first()?->domain,
            'created_at'          => $tenant->created_at,
        ];

        if ($detailed) {
            $base['stripe_customer_id']     = $tenant->stripe_customer_id;
            $base['stripe_subscription_id'] = $tenant->stripe_subscription_id;
            $base['all_domains']            = $tenant->domains->pluck('domain');
        }

        return $base;
    }
}
