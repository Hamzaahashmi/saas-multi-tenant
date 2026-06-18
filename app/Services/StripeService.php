<?php

namespace App\Services;

use App\Models\Tenant;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected array $plans;

    public function __construct(protected StripeClient $stripe)
    {
        $this->plans = [
            'starter'    => env('STRIPE_PLAN_STARTER'),
            'pro'        => env('STRIPE_PLAN_PRO'),
            'enterprise' => env('STRIPE_PLAN_ENTERPRISE'),
        ];
    }

    /**
     * Create a Stripe Checkout Session for a plan upgrade.
     * The tenant is redirected to Stripe's hosted checkout page.
     */
    public function createCheckoutSession(Tenant $tenant, string $plan): string
    {
        $priceId = $this->plans[$plan] ?? throw new \InvalidArgumentException("Unknown plan: $plan");

        $session = $this->stripe->checkout->sessions->create([
            'customer'            => $tenant->stripe_customer_id,
            'payment_method_types' => ['card'],
            'line_items'          => [[
                'price'    => $priceId,
                'quantity' => 1,
            ]],
            'mode'                => 'subscription',
            'success_url'         => 'https://' . $tenant->domains->first()->domain . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'          => 'https://' . $tenant->domains->first()->domain . '/billing/cancel',
            'subscription_data'   => [
                'metadata' => ['tenant_id' => $tenant->id],
            ],
        ]);

        return $session->url;
    }

    /**
     * Create a billing portal session so the tenant can manage their subscription.
     */
    public function createBillingPortalSession(Tenant $tenant): string
    {
        $session = $this->stripe->billingPortal->sessions->create([
            'customer'   => $tenant->stripe_customer_id,
            'return_url' => 'https://' . $tenant->domains->first()->domain . '/dashboard',
        ]);

        return $session->url;
    }

    /**
     * Cancel the tenant's current subscription at period end.
     */
    public function cancelSubscription(Tenant $tenant): void
    {
        if (!$tenant->stripe_subscription_id) {
            return;
        }

        $this->stripe->subscriptions->update($tenant->stripe_subscription_id, [
            'cancel_at_period_end' => true,
        ]);

        $tenant->update(['subscription_status' => 'canceling']);
    }

    /**
     * Handle Stripe webhook events.
     * Called from the WebhookController after signature verification.
     */
    public function handleWebhook(string $type, array $data): void
    {
        match ($type) {
            'customer.subscription.created' => $this->onSubscriptionCreated($data),
            'customer.subscription.updated' => $this->onSubscriptionUpdated($data),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($data),
            'invoice.payment_failed'        => $this->onPaymentFailed($data),
            default                         => Log::info("Unhandled Stripe event: $type"),
        };
    }

    protected function onSubscriptionCreated(array $data): void
    {
        $tenant = $this->getTenantByCustomerId($data['customer']);
        if (!$tenant) return;

        $tenant->update([
            'stripe_subscription_id' => $data['id'],
            'subscription_status'    => $data['status'],
            'plan'                   => $this->getPlanFromPriceId($data['items']['data'][0]['price']['id']),
            'plan_ends_at'           => null,
        ]);
    }

    protected function onSubscriptionUpdated(array $data): void
    {
        $tenant = $this->getTenantByCustomerId($data['customer']);
        if (!$tenant) return;

        $tenant->update([
            'subscription_status' => $data['status'],
            'plan'                => $this->getPlanFromPriceId($data['items']['data'][0]['price']['id']),
            'plan_ends_at'        => $data['cancel_at'] ? \Carbon\Carbon::createFromTimestamp($data['cancel_at']) : null,
        ]);
    }

    protected function onSubscriptionDeleted(array $data): void
    {
        $tenant = $this->getTenantByCustomerId($data['customer']);
        if (!$tenant) return;

        $tenant->update([
            'subscription_status'    => 'canceled',
            'stripe_subscription_id' => null,
            'plan'                   => 'free',
        ]);
    }

    protected function onPaymentFailed(array $data): void
    {
        $tenant = $this->getTenantByCustomerId($data['customer']);
        if (!$tenant) return;

        $tenant->update(['subscription_status' => 'past_due']);

        // TODO: send payment failed email via Mail::to($tenant->email)->send(...)
        Log::warning("Payment failed for tenant: {$tenant->id}");
    }

    protected function getTenantByCustomerId(string $customerId): ?Tenant
    {
        return Tenant::where('stripe_customer_id', $customerId)->first();
    }

    protected function getPlanFromPriceId(string $priceId): string
    {
        return array_search($priceId, $this->plans) ?: 'starter';
    }
}
