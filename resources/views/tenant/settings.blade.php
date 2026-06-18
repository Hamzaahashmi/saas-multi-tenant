@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="row g-3">

    {{-- Tenant settings --}}
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Workspace Settings</h6>
                <div id="settings-loading" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>
                <form id="settings-form" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Timezone</label>
                            <select name="timezone" class="form-select form-select-sm" id="timezone-select">
                                @foreach(\DateTimeZone::listIdentifiers() as $tz)
                                    <option value="{{ $tz }}">{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Date Format</label>
                            <select name="date_format" class="form-select form-select-sm" id="date-format-select">
                                <option value="Y-m-d">2026-06-19 (ISO)</option>
                                <option value="d/m/Y">19/06/2026</option>
                                <option value="m/d/Y">06/19/2026</option>
                                <option value="d M Y">19 Jun 2026</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Company Description</label>
                            <textarea name="company_description" class="form-control form-control-sm" rows="2"
                                id="company-description" placeholder="Brief description of your company"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Website</label>
                            <input type="url" name="company_website" class="form-control form-control-sm"
                                id="company-website" placeholder="https://acme.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Support Email</label>
                            <input type="email" name="support_email" class="form-control form-control-sm"
                                id="support-email" placeholder="support@acme.com">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notifications_enabled"
                                    id="notifications-toggle" role="switch">
                                <label class="form-check-label small" for="notifications-toggle">
                                    Email notifications enabled
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-check2 me-1"></i>Save Settings
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="pageInit()">
                            Discard
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Profile --}}
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Your Profile</h6>
                <form id="profile-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" id="profile-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" id="profile-email" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-3">
                        <i class="bi bi-check2 me-1"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Change password --}}
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Change Password</h6>
                <form id="password-form">
                    <div id="pw-error" class="alert alert-danger d-none small"></div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control form-control-sm" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm" required>
                    </div>
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-lock me-1"></i>Change Password
                    </button>
                </form>
            </div>
        </div>

        {{-- Danger zone --}}
        <div class="card border-danger-subtle">
            <div class="card-body">
                <h6 class="fw-semibold text-danger mb-2">Danger Zone</h6>
                <p class="text-muted small mb-3">These actions are irreversible.</p>
                <button class="btn btn-outline-danger btn-sm" onclick="confirmLogoutAll()">
                    <i class="bi bi-box-arrow-right me-1"></i>Log out all sessions
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function pageInit() {
    // Only admins can access settings
    await new Promise(r => setTimeout(r, 50)); // wait for role to load
    if (window.currentUserRole && window.currentUserRole !== 'admin') {
        document.querySelector('.page-body').innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-lock-fill text-muted" style="font-size:3rem"></i>
                <h5 class="mt-3 text-muted">Access Restricted</h5>
                <p class="text-muted small">Only admins can manage workspace settings.</p>
                <a href="/dashboard" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
            </div>`;
        return;
    }

    const [settingsData, meData] = await Promise.all([
        api('GET', '/settings'),
        api('GET', '/me'),
    ]);

    if (settingsData?.settings) {
        const s = settingsData.settings;
        document.getElementById('timezone-select').value       = s.timezone ?? 'UTC';
        document.getElementById('date-format-select').value    = s.date_format ?? 'Y-m-d';
        document.getElementById('company-description').value   = s.company_description ?? '';
        document.getElementById('company-website').value       = s.company_website ?? '';
        document.getElementById('support-email').value         = s.support_email ?? '';
        document.getElementById('notifications-toggle').checked = !!s.notifications_enabled;
        document.getElementById('settings-loading').classList.add('d-none');
        document.getElementById('settings-form').classList.remove('d-none');
    }

    if (meData?.user) {
        document.getElementById('profile-name').value  = meData.user.name;
        document.getElementById('profile-email').value = meData.user.email;
    }
}

document.getElementById('settings-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const res = await api('PUT', '/settings', {
        timezone:              form.timezone.value,
        date_format:           form.date_format.value,
        company_description:   form.company_description.value,
        company_website:       form.company_website.value,
        support_email:         form.support_email.value,
        notifications_enabled: form.notifications_enabled.checked,
    });
    if (res?.settings) {
        showToast('Settings saved.');
    } else {
        showToast(res?.message ?? 'Failed to save settings.', 'error');
    }
});

document.getElementById('profile-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const res = await api('PUT', '/me', {
        name:  form.name.value.trim(),
        email: form.email.value.trim(),
    });
    if (res?.user) {
        showToast('Profile updated.');
        document.getElementById('sidebar-user-name').textContent = res.user.name;
    } else {
        showToast(res?.message ?? 'Failed to update profile.', 'error');
    }
});

document.getElementById('password-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    document.getElementById('pw-error').classList.add('d-none');
    const res = await api('PUT', '/me/password', {
        current_password:      form.current_password.value,
        password:              form.password.value,
        password_confirmation: form.password_confirmation.value,
    });
    if (res?.message && !res.errors) {
        showToast('Password changed.');
        form.reset();
    } else {
        const el = document.getElementById('pw-error');
        el.textContent = res?.message ?? 'Failed to change password.';
        el.classList.remove('d-none');
    }
});

async function confirmLogoutAll() {
    if (!confirm('This will sign you out everywhere. Continue?')) return;
    await logout();
}
</script>
@endpush
