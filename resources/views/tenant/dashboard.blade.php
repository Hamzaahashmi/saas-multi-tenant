@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Trial banner (shown via JS) --}}
<div id="trial-banner" class="alert border-0 mb-4 d-none" role="alert"
     style="background:#fef3c7;border-left:4px solid #f59e0b !important;">
    <i class="bi bi-clock-history me-2 text-warning"></i>
    <span id="trial-banner-text"></span>
    <a href="/billing" class="btn btn-sm btn-warning ms-3">Upgrade now</a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-num" id="stat-members">—</div>
                <div class="stat-label"><i class="bi bi-people me-1"></i>Team Members</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-num" id="stat-invitations">—</div>
                <div class="stat-label"><i class="bi bi-envelope me-1"></i>Pending Invites</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-num" id="stat-plan" style="font-size:1.4rem">—</div>
                <div class="stat-label"><i class="bi bi-star me-1"></i>Current Plan</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-num" id="stat-status" style="font-size:1.4rem">—</div>
                <div class="stat-label"><i class="bi bi-circle-fill me-1 text-success" style="font-size:.5rem"></i>Status</div>
            </div>
        </div>
    </div>
</div>

{{-- Two column --}}
<div class="row g-3">
    {{-- Quick actions --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Quick Actions</h6>
                <div class="d-grid gap-2" id="quick-actions">
                    {{-- Shown for admin + manager --}}
                    <button id="qa-invite" class="btn btn-outline-primary btn-sm text-start d-none"
                            data-bs-toggle="modal" data-bs-target="#inviteModal">
                        <i class="bi bi-person-plus me-2"></i>Invite team member
                    </button>
                    {{-- Admin only --}}
                    <a id="qa-settings" href="/settings" class="btn btn-outline-secondary btn-sm text-start d-none">
                        <i class="bi bi-gear me-2"></i>Configure settings
                    </a>
                    {{-- Admin + manager --}}
                    <a id="qa-activity" href="/activity" class="btn btn-outline-secondary btn-sm text-start d-none">
                        <i class="bi bi-activity me-2"></i>View activity log
                    </a>
                    {{-- Admin only --}}
                    <a id="qa-billing" href="/billing" class="btn btn-outline-secondary btn-sm text-start d-none">
                        <i class="bi bi-credit-card me-2"></i>Manage billing
                    </a>
                    {{-- Always visible --}}
                    <a href="/team" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-people me-2"></i>View team
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Recent Activity</h6>
                    <a href="/activity" class="text-primary small text-decoration-none">View all →</a>
                </div>
                <div id="activity-list">
                    <div class="text-muted small text-center py-3">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Invite modal --}}
<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-semibold">Invite Team Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="invite-form">
                <div class="modal-body pt-0">
                    <div id="invite-error" class="alert alert-danger d-none small"></div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="colleague@company.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role</label>
                        <select name="role" class="form-select">
                            <option value="member">Member</option>
                            <option value="manager">Manager</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function pageInit() {
    const [dash, activity] = await Promise.all([
        api('GET', '/dashboard'),
        api('GET', '/activity'),
    ]);

    if (dash) {
        const t = dash.tenant, s = dash.stats;
        document.getElementById('stat-members').textContent     = s.team_members;
        document.getElementById('stat-invitations').textContent = s.pending_invitations;
        document.getElementById('stat-plan').textContent        = t.plan.charAt(0).toUpperCase() + t.plan.slice(1);
        document.getElementById('stat-status').textContent      = t.on_trial ? 'Trial' : t.status;

        if (t.on_trial && t.trial_ends_at) {
            const days = Math.ceil((new Date(t.trial_ends_at) - new Date()) / 86400000);
            document.getElementById('trial-banner-text').textContent =
                `Your free trial ends in ${days} day${days !== 1 ? 's' : ''}. Upgrade to keep access.`;
            document.getElementById('trial-banner').classList.remove('d-none');
        }
    }

    // Show quick actions based on role
    const role = window.currentUserRole;
    if (role === 'admin' || role === 'manager') {
        document.getElementById('qa-invite')?.classList.remove('d-none');
        document.getElementById('qa-activity')?.classList.remove('d-none');
    }
    if (role === 'admin') {
        document.getElementById('qa-settings')?.classList.remove('d-none');
        document.getElementById('qa-billing')?.classList.remove('d-none');
    }

    if (activity) {
        const list = document.getElementById('activity-list');
        const rows = activity.data?.slice(0, 5) ?? [];
        if (!rows.length) {
            list.innerHTML = '<p class="text-muted small mb-0">No activity yet.</p>';
        } else {
            list.innerHTML = rows.map(a => `
                <div class="d-flex gap-2 align-items-start mb-2 pb-2 border-bottom">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;font-size:.8rem">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div>
                        <div class="small fw-semibold">${formatAction(a.action)}</div>
                        <div class="text-muted" style="font-size:.75rem">${a.user_name} &bull; ${formatDate(a.created_at)}</div>
                    </div>
                </div>
            `).join('');
        }
    }
}

document.getElementById('invite-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const res = await api('POST', '/team/invite', {
        email: form.email.value.trim(),
        role:  form.role.value,
    });
    if (res?.invitation) {
        bootstrap.Modal.getInstance(document.getElementById('inviteModal')).hide();
        form.reset();
        showToast('Invitation sent to ' + res.invitation.email);
        document.getElementById('stat-invitations').textContent =
            parseInt(document.getElementById('stat-invitations').textContent || 0) + 1;
    } else {
        const el = document.getElementById('invite-error');
        el.textContent = res?.message ?? 'Could not send invitation.';
        el.classList.remove('d-none');
    }
});

function formatAction(action) {
    return action.replace(/\./g, ' › ').replace(/_/g, ' ');
}

function formatDate(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
}
</script>
@endpush
