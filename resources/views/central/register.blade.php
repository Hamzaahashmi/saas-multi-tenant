@extends('layouts.guest')

@section('title', 'Register — SaaSStarter')

@section('nav-right')
    <a href="/" class="btn btn-outline-secondary btn-sm">← Back to home</a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Create your workspace</h2>
            <p class="text-muted">Start your 14-day free trial. No credit card required.</p>
        </div>

        <div class="card p-4">
            <div id="error-alert" class="alert alert-danger d-none"></div>
            <div id="success-alert" class="alert alert-success d-none"></div>

            <form id="register-form" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Company Name</label>
                    <input type="text" name="company_name" class="form-control" placeholder="Acme Corp" required>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Subdomain</label>
                    <div class="input-group">
                        <input type="text" name="subdomain" class="form-control" placeholder="acme" required
                            pattern="[a-z0-9\-]+" oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9-]/g,'')">
                        <span class="input-group-text text-muted">.localhost</span>
                    </div>
                    <div class="form-text text-muted">Lowercase letters, numbers and hyphens only.</div>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Your Name</label>
                    <input type="text" name="owner_name" class="form-control" placeholder="John Doe" required>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john@acme.com" required>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required minlength="8">
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password" required>
                    <div class="invalid-feedback"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="submit-btn">
                    <span id="btn-text"><i class="bi bi-rocket-takeoff me-2"></i>Create Workspace</span>
                    <span id="btn-spinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span> Creating...
                    </span>
                </button>
            </form>
        </div>

        <p class="text-center text-muted small mt-3">
            Already have an account? Enter your subdomain on the <a href="/#login-section">homepage</a>.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('register-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    clearErrors();

    const form = e.target;
    const data = {
        company_name:          form.company_name.value.trim(),
        subdomain:             form.subdomain.value.trim(),
        owner_name:            form.owner_name.value.trim(),
        email:                 form.email.value.trim(),
        password:              form.password.value,
        password_confirmation: form.password_confirmation.value,
    };

    setBusy(true);

    const res = await fetch('/api/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data),
    });

    const json = await res.json();
    setBusy(false);

    if (res.ok) {
        const token  = json.token;
        const domain = json.tenant.domain; // e.g. acme.localhost

        showSuccess('Workspace created! Redirecting you now...');
        // Pass token via URL param; dashboard will pick it up and store in localStorage
        setTimeout(() => {
            const port = window.location.port ? `:${window.location.port}` : '';
            window.location.href = `http://${domain}${port}/dashboard?token=${encodeURIComponent(token)}`;
        }, 1200);
    } else if (res.status === 422) {
        showFieldErrors(json.errors ?? {});
        if (json.message && !json.errors) showError(json.message);
    } else {
        showError(json.message ?? 'Something went wrong. Please try again.');
    }
});

function setBusy(busy) {
    document.getElementById('btn-text').classList.toggle('d-none', busy);
    document.getElementById('btn-spinner').classList.toggle('d-none', !busy);
    document.getElementById('submit-btn').disabled = busy;
}

function clearErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.getElementById('error-alert').classList.add('d-none');
    document.getElementById('success-alert').classList.add('d-none');
}

function showError(msg) {
    const el = document.getElementById('error-alert');
    el.textContent = msg;
    el.classList.remove('d-none');
}

function showSuccess(msg) {
    const el = document.getElementById('success-alert');
    el.textContent = msg;
    el.classList.remove('d-none');
}

function showFieldErrors(errors) {
    Object.entries(errors).forEach(([field, msgs]) => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const fb = input.closest('.mb-3').querySelector('.invalid-feedback');
            if (fb) fb.textContent = msgs[0];
        }
    });
    if (Object.keys(errors).length) showError('Please fix the errors below.');
}
</script>
@endpush
