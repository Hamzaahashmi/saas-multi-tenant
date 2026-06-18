@extends('layouts.guest')

@section('title', 'SaaSStarter — Multi-tenant SaaS Boilerplate')

@section('nav-right')
    <div class="d-flex gap-2">
        <a href="#login-section" class="btn btn-outline-secondary btn-sm" id="go-login-btn">Sign In</a>
        <a href="/register" class="btn btn-primary btn-sm">Get Started Free</a>
    </div>
@endsection

@section('content')
{{-- Hero --}}
<div class="row justify-content-center text-center mb-5">
    <div class="col-lg-8">
        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold mb-3 px-3 py-2" style="border-radius:2rem">
            <i class="bi bi-lightning-charge-fill me-1"></i> Laravel 12 + Multi-tenancy
        </span>
        <h1 class="display-5 fw-bold text-dark mb-3">
            The SaaS Backend<br>You Don't Have to Build
        </h1>
        <p class="lead text-muted mb-4">
            Full multi-tenant architecture. Per-company database isolation.
            Roles, billing, invitations, activity logs — all wired up.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="/register" class="btn btn-primary btn-lg px-4">
                <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial
            </a>
            <a href="#features" class="btn btn-outline-secondary btn-lg px-4">Learn More</a>
        </div>
        <p class="text-muted small mt-3">14-day free trial &bull; No credit card required</p>
    </div>
</div>

{{-- Stats bar --}}
<div class="row g-3 justify-content-center mb-5">
    @foreach([
        ['icon'=>'database-fill','label'=>'Database per tenant','color'=>'text-primary'],
        ['icon'=>'shield-lock-fill','label'=>'Role-based access','color'=>'text-success'],
        ['icon'=>'credit-card-fill','label'=>'Stripe billing ready','color'=>'text-warning'],
        ['icon'=>'activity','label'=>'Full audit logging','color'=>'text-info'],
    ] as $s)
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <i class="bi bi-{{ $s['icon'] }} fs-3 {{ $s['color'] }} mb-2"></i>
            <div class="small fw-semibold">{{ $s['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Features --}}
<div id="features" class="row g-4 mb-5">
    @foreach([
        ['icon'=>'layers-fill','title'=>'Multi-tenant Architecture','desc'=>'Every company gets their own isolated MySQL database. Complete data separation out of the box using stancl/tenancy.','color'=>'primary'],
        ['icon'=>'people-fill','title'=>'Team Management','desc'=>'Invite team members by email, assign roles (admin/manager/member), manage permissions with Spatie Permission.','color'=>'success'],
        ['icon'=>'credit-card-2-front-fill','title'=>'Stripe Billing','desc'=>'Checkout sessions, customer portal, subscription management and webhook handling ready to configure.','color'=>'warning'],
        ['icon'=>'bell-fill','title'=>'Activity Log','desc'=>'Every team action is recorded — settings changes, invitations, logins. Full audit trail per tenant.','color'=>'info'],
        ['icon'=>'shield-fill-check','title'=>'Sanctum Auth','desc'=>'Token-based API authentication per tenant. Custom middleware handles multi-tenant token resolution.','color'=>'danger'],
        ['icon'=>'gear-wide-connected','title'=>'Tenant Settings','desc'=>'Per-tenant timezone, notifications, company info — stored in a flexible JSON data column.','color'=>'secondary'],
    ] as $f)
    <div class="col-md-4">
        <div class="card h-100 p-4">
            <div class="mb-3">
                <span class="bg-{{ $f['color'] }} bg-opacity-10 text-{{ $f['color'] }} rounded-circle d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px">
                    <i class="bi bi-{{ $f['icon'] }}"></i>
                </span>
            </div>
            <h5 class="fw-semibold mb-2">{{ $f['title'] }}</h5>
            <p class="text-muted small mb-0">{{ $f['desc'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Pricing --}}
<div class="text-center mb-4">
    <h2 class="fw-bold">Simple Pricing</h2>
    <p class="text-muted">Start free, upgrade as you grow.</p>
</div>
<div class="row g-4 justify-content-center mb-5">
    @foreach([
        ['name'=>'Starter','price'=>'$29','per'=>'/month','color'=>'','members'=>5,'highlight'=>false,'features'=>['5 team members','10 GB storage','Email support','All core features']],
        ['name'=>'Pro','price'=>'$79','per'=>'/month','color'=>'primary','members'=>25,'highlight'=>true,'features'=>['25 team members','100 GB storage','Priority support','Advanced analytics','Custom domain']],
        ['name'=>'Enterprise','price'=>'$199','per'=>'/month','color'=>'','members'=>'Unlimited','highlight'=>false,'features'=>['Unlimited members','1 TB storage','Dedicated support','SLA guarantee','SSO / SAML']],
    ] as $plan)
    <div class="col-md-4">
        <div class="card h-100 p-4 {{ $plan['highlight'] ? 'border-primary border-2' : '' }}">
            @if($plan['highlight'])
            <div class="text-center mb-2"><span class="badge bg-primary px-3">Most Popular</span></div>
            @endif
            <h5 class="fw-bold mb-1">{{ $plan['name'] }}</h5>
            <div class="mb-3">
                <span class="fs-2 fw-bold text-{{ $plan['color'] ?: 'dark' }}">{{ $plan['price'] }}</span>
                <span class="text-muted small">{{ $plan['per'] }}</span>
            </div>
            <ul class="list-unstyled text-muted small mb-4">
                @foreach($plan['features'] as $feat)
                <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>{{ $feat }}</li>
                @endforeach
            </ul>
            <a href="/register" class="btn {{ $plan['highlight'] ? 'btn-primary' : 'btn-outline-secondary' }} w-100 mt-auto">
                Start Free Trial
            </a>
        </div>
    </div>
    @endforeach
</div>

{{-- Login section --}}
<div id="login-section" class="row justify-content-center">
    <div class="col-md-5">
        <div class="card p-4">
            <h5 class="fw-bold mb-1">Sign in to your workspace</h5>
            <p class="text-muted small mb-3">Enter your company's subdomain to continue.</p>
            <div class="input-group">
                <input type="text" id="subdomain-input" class="form-control" placeholder="your-company">
                <span class="input-group-text text-muted">.localhost</span>
            </div>
            <button class="btn btn-primary w-100 mt-3" onclick="goToWorkspace()">
                <i class="bi bi-arrow-right-circle me-1"></i> Go to workspace
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function goToWorkspace() {
    const sub = document.getElementById('subdomain-input').value.trim().toLowerCase();
    if (!sub) return;
    window.location.href = `http://${sub}.localhost:8001/login`;
}
document.getElementById('subdomain-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') goToWorkspace();
});
document.getElementById('go-login-btn').addEventListener('click', e => {
    e.preventDefault();
    document.getElementById('login-section').scrollIntoView({ behavior: 'smooth' });
});
</script>
@endpush
