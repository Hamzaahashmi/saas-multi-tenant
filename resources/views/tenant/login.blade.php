@extends('layouts.guest')

@section('title', 'Sign In — SaaSStarter')

@section('nav-right')
    <a href="/" class="text-muted small text-decoration-none">← All workspaces</a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4">
            <div class="mb-3">
                <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px;font-size:1.5rem">
                    <i class="bi bi-layers-fill"></i>
                </span>
            </div>
            <h2 class="fw-bold mb-1">Welcome back</h2>
            <p class="text-muted small">Sign in to <strong id="workspace-name">your workspace</strong></p>
        </div>

        <div class="card p-4">
            <div id="error-alert" class="alert alert-danger d-none"></div>

            <form id="login-form" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="john@acme.com" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold small">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Your password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="submit-btn">
                    <span id="btn-text"><i class="bi bi-box-arrow-in-right me-1"></i>Sign In</span>
                    <span id="btn-spinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span> Signing in...
                    </span>
                </button>
            </form>
        </div>

        <p class="text-center text-muted small mt-3">
            Don't have an account? <a href="{{ config('app.url') }}/register">Register your company</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Show current subdomain in UI
const subdomain = window.location.hostname.split('.')[0];
if (subdomain && subdomain !== 'localhost') {
    document.getElementById('workspace-name').textContent = subdomain + '.localhost';
}

// If already logged in, redirect to dashboard
if (localStorage.getItem('saas_token')) {
    window.location.href = '/dashboard';
}

document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    document.getElementById('error-alert').classList.add('d-none');

    const form = e.target;
    setBusy(true);

    const res = await fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email: form.email.value.trim(), password: form.password.value }),
    });

    const json = await res.json();
    setBusy(false);

    if (res.ok) {
        localStorage.setItem('saas_token', json.token);
        window.location.href = '/dashboard';
    } else {
        const el = document.getElementById('error-alert');
        el.textContent = json.message ?? 'Invalid credentials.';
        el.classList.remove('d-none');
    }
});

function setBusy(busy) {
    document.getElementById('btn-text').classList.toggle('d-none', busy);
    document.getElementById('btn-spinner').classList.toggle('d-none', !busy);
    document.getElementById('submit-btn').disabled = busy;
}
</script>
@endpush
