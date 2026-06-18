@extends('layouts.guest')

@section('title', 'Accept Invitation — SaaSStarter')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4">
            <span class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;font-size:1.5rem">
                <i class="bi bi-envelope-open"></i>
            </span>
            <h2 class="fw-bold mb-1">You're invited!</h2>
            <p class="text-muted small">Create your account to join the workspace.</p>
        </div>

        <div class="card p-4">
            <div id="error-alert" class="alert alert-danger d-none"></div>
            <div id="success-alert" class="alert alert-success d-none"></div>

            <form id="accept-form" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Your Name</label>
                    <input type="text" name="name" class="form-control" placeholder="John Doe" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email</label>
                    <input type="email" name="email" class="form-control" id="invite-email" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Choose a password" required minlength="8">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold small">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="btn btn-success w-100" id="submit-btn">
                    <span id="btn-text"><i class="bi bi-check-circle me-1"></i>Accept &amp; Create Account</span>
                    <span id="btn-spinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span> Creating...
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const inviteToken = '{{ $token }}';

// Try to load invitation details (email hint)
window.addEventListener('DOMContentLoaded', async () => {
    // Email is not exposed publicly for security — just show a placeholder
    document.getElementById('invite-email').placeholder = 'Will be set from invitation';
});

document.getElementById('accept-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    setBusy(true);
    document.getElementById('error-alert').classList.add('d-none');

    const res = await fetch(`/api/invite/${encodeURIComponent(inviteToken)}/accept`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
            name:                  form.name.value.trim(),
            password:              form.password.value,
            password_confirmation: form.password_confirmation.value,
        }),
    });

    const json = await res.json();
    setBusy(false);

    if (res.ok && json.token) {
        localStorage.setItem('saas_token', json.token);
        showSuccess('Account created! Redirecting...');
        setTimeout(() => window.location.href = '/dashboard', 1200);
    } else {
        const el = document.getElementById('error-alert');
        el.textContent = json.message ?? 'Could not accept invitation. It may have expired.';
        el.classList.remove('d-none');
    }
});

function setBusy(busy) {
    document.getElementById('btn-text').classList.toggle('d-none', busy);
    document.getElementById('btn-spinner').classList.toggle('d-none', !busy);
    document.getElementById('submit-btn').disabled = busy;
}

function showSuccess(msg) {
    const el = document.getElementById('success-alert');
    el.textContent = msg;
    el.classList.remove('d-none');
}
</script>
@endpush
