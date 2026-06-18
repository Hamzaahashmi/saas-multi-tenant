<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(protected StripeService $stripeService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        return response()->json([
            'billing' => [
                'plan'                   => $tenant->plan,
                'subscription_status'    => $tenant->subscription_status,
                'stripe_customer_id'     => $tenant->stripe_customer_id,
                'stripe_subscription_id' => $tenant->stripe_subscription_id,
                'trial_ends_at'          => $tenant->trial_ends_at,
                'plan_ends_at'           => $tenant->plan_ends_at,
                'is_subscribed'          => $tenant->isSubscribed(),
                'on_trial'               => $tenant->onTrial(),
            ],
            'plans' => $this->getPlans(),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::in(['starter', 'pro', 'enterprise'])],
        ]);

        $tenant = tenancy()->tenant;
        $checkoutUrl = $this->stripeService->createCheckoutSession($tenant, $validated['plan']);

        return response()->json([
            'checkout_url' => $checkoutUrl,
        ]);
    }

    public function portal(): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $portalUrl = $this->stripeService->createBillingPortalSession($tenant);

        return response()->json([
            'portal_url' => $portalUrl,
        ]);
    }

    public function cancel(): JsonResponse
    {
        $tenant = tenancy()->tenant;

        if (!$tenant->stripe_subscription_id) {
            return response()->json(['message' => 'No active subscription to cancel.'], 422);
        }

        $this->stripeService->cancelSubscription($tenant);

        return response()->json([
            'message' => 'Subscription will be canceled at the end of the current period.',
            'plan_ends_at' => $tenant->fresh()->plan_ends_at,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $this->stripeService->handleWebhook($event->type, $event->data->object->toArray());

        return response()->json(['message' => 'Webhook received.']);
    }

    private function getPlans(): array
    {
        return [
            [
                'key'        => 'starter',
                'name'       => 'Starter',
                'price'      => 29,
                'currency'   => 'usd',
                'interval'   => 'month',
                'features'   => ['Up to 5 team members', '10 GB storage', 'Email support', 'API access'],
            ],
            [
                'key'        => 'pro',
                'name'       => 'Pro',
                'price'      => 79,
                'currency'   => 'usd',
                'interval'   => 'month',
                'features'   => ['Up to 25 team members', '100 GB storage', 'Priority support', 'API access', 'Custom domain'],
            ],
            [
                'key'        => 'enterprise',
                'name'       => 'Enterprise',
                'price'      => 299,
                'currency'   => 'usd',
                'interval'   => 'month',
                'features'   => ['Unlimited team members', 'Unlimited storage', 'Dedicated support', 'SLA', 'Custom integrations'],
            ],
        ];
    }
}
