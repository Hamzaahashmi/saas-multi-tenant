@extends('layouts.app')

@section('title', 'Team')
@section('page-title', 'Team')

@section('content')
<div class="row g-3">

    {{-- Members list --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Members</h6>
                    {{-- Invite button: admin and manager only --}}
                    <button id="invite-btn" class="btn btn-primary btn-sm d-none"
                            data-bs-toggle="modal" data-bs-target="#inviteModal">
                        <i class="bi bi-person-plus me-1"></i>Invite
                    </button>
                </div>
                <div id="members-list">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending invitations --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Pending Invitations</h6>
                <div id="invitations-list">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
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
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="colleague@company.com">
                    </div>
                    <div>
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

{{-- Edit role modal --}}
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-semibold">Change Role</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="edit-role-form">
                <input type="hidden" name="user_id">
                <div class="modal-body pt-0">
                    <select name="role" class="form-select">
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="member">Member</option>
                    </select>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentUserId = null;

async function pageInit() {
    await new Promise(r => setTimeout(r, 50));
    const role = window.currentUserRole;

    // Show invite button for admin and manager
    if (role === 'admin' || role === 'manager') {
        document.getElementById('invite-btn').classList.remove('d-none');
    }

    // Hide invitations panel for members
    if (role === 'member') {
        document.querySelector('.col-lg-4')?.classList.add('d-none');
    }

    await Promise.all([loadMembers(), loadInvitations()]);
}

async function loadMembers() {
    const data = await api('GET', '/team');
    const el = document.getElementById('members-list');
    if (!data?.members?.length) {
        el.innerHTML = '<p class="text-muted small mb-0">No members yet.</p>';
        return;
    }
    el.innerHTML = data.members.map(m => `
        <div class="d-flex align-items-center gap-3 py-2 border-bottom" id="member-${m.id}">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                 style="width:38px;height:38px;font-size:.9rem">
                ${m.name.charAt(0).toUpperCase()}
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold small">${escHtml(m.name)}</div>
                <div class="text-muted" style="font-size:.75rem">${escHtml(m.email)}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                ${m.roles.map(r => `<span class="badge bg-${roleBadge(r)} bg-opacity-10 text-${roleBadge(r)} small">${r}</span>`).join('')}
                ${(window.currentUserRole === 'admin' && !m.is_tenant_admin) ? `
                <div class="dropdown">
                    <button class="btn btn-link btn-sm text-muted p-0" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 small">
                        <li><a class="dropdown-item" href="#" onclick="openEditRole(${m.id}, '${m.roles[0]??'member'}')">
                            <i class="bi bi-pencil me-2"></i>Change role</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="removeMember(${m.id}, '${escHtml(m.name)}')">
                            <i class="bi bi-person-x me-2"></i>Remove</a></li>
                    </ul>
                </div>` : ''}
            </div>
        </div>
    `).join('');
}

async function loadInvitations() {
    const data = await api('GET', '/team/invitations');
    const el = document.getElementById('invitations-list');
    if (!data?.invitations?.length) {
        el.innerHTML = '<p class="text-muted small mb-0">No pending invitations.</p>';
        return;
    }
    el.innerHTML = data.invitations.map(inv => `
        <div class="d-flex align-items-start gap-2 mb-2 pb-2 border-bottom" id="inv-${inv.id}">
            <div class="flex-grow-1">
                <div class="small fw-semibold">${escHtml(inv.email)}</div>
                <div class="text-muted" style="font-size:.72rem">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary">${inv.role}</span>
                    Expires ${new Date(inv.expires_at).toLocaleDateString()}
                </div>
            </div>
            <button class="btn btn-link btn-sm text-danger p-0" onclick="cancelInvite(${inv.id})" title="Cancel">
                <i class="bi bi-x-circle"></i>
            </button>
        </div>
    `).join('');
}

function openEditRole(userId, currentRole) {
    currentUserId = userId;
    const form = document.getElementById('edit-role-form');
    form.user_id.value = userId;
    form.role.value = currentRole;
    new bootstrap.Modal(document.getElementById('editRoleModal')).show();
}

document.getElementById('edit-role-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const res = await api('PUT', `/team/${form.user_id.value}`, { role: form.role.value });
    if (res?.message) {
        bootstrap.Modal.getInstance(document.getElementById('editRoleModal')).hide();
        showToast('Role updated.');
        await loadMembers();
    } else {
        showToast(res?.message ?? 'Failed to update role.', 'error');
    }
});

async function removeMember(id, name) {
    if (!confirm(`Remove ${name} from the team?`)) return;
    const res = await api('DELETE', `/team/${id}`);
    if (res?.message) {
        showToast(name + ' removed.');
        document.getElementById(`member-${id}`)?.remove();
    } else {
        showToast(res?.message ?? 'Failed to remove member.', 'error');
    }
}

async function cancelInvite(id) {
    const res = await api('DELETE', `/team/invitations/${id}`);
    if (res?.message) {
        showToast('Invitation cancelled.');
        document.getElementById(`inv-${id}`)?.remove();
    }
}

document.getElementById('invite-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    document.getElementById('invite-error').classList.add('d-none');
    const res = await api('POST', '/team/invite', {
        email: form.email.value.trim(),
        role:  form.role.value,
    });
    if (res?.invitation) {
        bootstrap.Modal.getInstance(document.getElementById('inviteModal')).hide();
        form.reset();
        showToast('Invitation sent!');
        await loadInvitations();
    } else {
        const el = document.getElementById('invite-error');
        el.textContent = res?.message ?? 'Could not send invitation.';
        el.classList.remove('d-none');
    }
});

function roleBadge(role) {
    return { admin: 'danger', manager: 'warning', member: 'primary' }[role] ?? 'secondary';
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
