@extends('layouts.app')

@section('title', 'Billing')
@section('page-title', 'Billing')

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Current Subscription</h6>
                <div id="billing-loading" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>
                <div id="billing-info" class="d-none">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Plan</span>
                        <span class="fw-semibold small" id="b-plan">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Status</span>
                        <span id="b-status">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3" id="b-trial-row">
                        <span class="text-muted small">Trial ends</span>
                        <span class="fw-semibold small" id="b-trial-date">—</span>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-sm" onclick="openPortal()">
                            <i class="bi bi-credit-card me-1"></i>Manage subscription
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="cancelSub()">
                            <i class="bi bi-x-circle me-1"></i>Cancel subscription
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Upgrade Plan</h6>
                <div class="d-grid gap-2">
                    @foreach(['Starter' => 'starter', 'Pro' => 'pro', 'Enterprise' => 'enterprise'] as $label => $plan)
                    <button class="btn btn-outline-primary btn-sm" onclick="checkout('{{ $plan }}')">
                        <i class="bi bi-arrow-up-circle me-1"></i>Upgrade to {{ $label }}
                    </button>
                    @endforeach
                </div>
                <p class="text-muted small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Powered by Stripe. Requires STRIPE_KEY configuration.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function pageInit() {
    await new Promise(r => setTimeout(r, 50));
    if (window.currentUserRole && window.currentUserRole !== 'admin') {
        document.querySelector('.page-body').innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-lock-fill text-muted" style="font-size:3rem"></i>
                <h5 class="mt-3 text-muted">Access Restricted</h5>
                <p class="text-muted small">Billing is managed by the workspace admin.</p>
                <a href="/dashboard" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
            </div>`;
        return;
    }
    const data = await api('GET', '/billing');
    document.getElementById('billing-loading').classList.add('d-none');
    document.getElementById('billing-info').classList.remove('d-none');

    if (data?.tenant) {
        const t = data.tenant;
        document.getElementById('b-plan').textContent = t.plan ?? '—';
        document.getElementById('b-status').innerHTML = t.subscription_status === 'trialing'
            ? '<span class="badge-trial">Trial</span>'
            : `<span class="badge-active">${t.subscription_status ?? '—'}</span>`;
        if (t.trial_ends_at) {
            document.getElementById('b-trial-date').textContent = new Date(t.trial_ends_at).toLocaleDateString();
        } else {
            document.getElementById('b-trial-row').classList.add('d-none');
        }
    } else {
        document.getElementById('b-plan').textContent = 'N/A';
    }
}

async function checkout(plan) {
    showSpinner();
    const res = await api('POST', '/billing/checkout', { plan });
    hideSpinner();
    if (res?.url) {
        window.location.href = res.url;
    } else {
        showToast(res?.message ?? 'Stripe not configured. Add STRIPE_SECRET to .env', 'error');
    }
}

async function openPortal() {
    showSpinner();
    const res = await api('POST', '/billing/portal');
    hideSpinner();
    if (res?.url) {
        window.location.href = res.url;
    } else {
        showToast(res?.message ?? 'Could not open billing portal.', 'error');
    }
}

async function cancelSub() {
    if (!confirm('Are you sure you want to cancel your subscription?')) return;
    const res = await api('DELETE', '/billing/subscription');
    if (res?.message) {
        showToast('Subscription cancelled.');
        pageInit();
    } else {
        showToast(res?.message ?? 'Failed to cancel subscription.', 'error');
    }
}
</script>
@endpush
