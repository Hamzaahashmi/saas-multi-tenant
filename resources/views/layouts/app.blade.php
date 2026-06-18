<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — SaaSStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-w: 240px; --sidebar-bg: #1e1b4b; --sidebar-text: #c7d2fe; --sidebar-active: #4f46e5; --primary: #4f46e5; }
        body { background: #f8f9ff; }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-w); min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 100;
            display: flex; flex-direction: column;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem 1rem;
            font-size: 1.25rem; font-weight: 700; color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.1);
            text-decoration: none;
        }
        .sidebar-brand small { display: block; font-size: .7rem; font-weight: 400; color: var(--sidebar-text); margin-top: 2px; }
        .sidebar-nav { flex: 1; padding: 1rem 0; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: .6rem;
            padding: .6rem 1.25rem; color: var(--sidebar-text);
            text-decoration: none; font-size: .875rem; border-radius: 0;
            transition: background .15s;
        }
        .sidebar-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar-nav a.active { background: var(--sidebar-active); color: #fff; }
        .sidebar-nav .nav-label {
            font-size: .65rem; font-weight: 600; letter-spacing: .08em;
            text-transform: uppercase; color: rgba(199,210,254,.5);
            padding: 1rem 1.25rem .25rem;
        }
        .sidebar-footer {
            padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,.1);
            font-size: .8rem; color: var(--sidebar-text);
        }
        .sidebar-footer strong { display: block; color: #fff; }

        /* Main */
        #main { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }
        .topbar {
            background: #fff; border-bottom: 1px solid #e5e7eb;
            padding: .75rem 1.5rem; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 50;
        }
        .topbar h1 { font-size: 1.1rem; font-weight: 600; margin: 0; }
        .page-body { padding: 1.75rem 1.5rem; flex: 1; }

        /* Cards */
        .card { border: 1px solid #e5e7eb; border-radius: .75rem; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
        .stat-card .card-body { padding: 1.25rem 1.5rem; }
        .stat-card .stat-num { font-size: 2rem; font-weight: 700; color: var(--primary); }
        .stat-card .stat-label { font-size: .8rem; color: #6b7280; margin-top: 2px; }

        /* Buttons */
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: #4338ca; border-color: #4338ca; }
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 .2rem rgba(79,70,229,.15); }

        /* Badge */
        .badge-trial { background: #fef3c7; color: #92400e; font-size: .72rem; padding: .3em .6em; border-radius: .4rem; }
        .badge-active { background: #d1fae5; color: #065f46; font-size: .72rem; padding: .3em .6em; border-radius: .4rem; }

        /* Toast */
        .toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; }

        /* Spinner overlay */
        .spinner-overlay { display: none; position: fixed; inset: 0; background: rgba(255,255,255,.7); z-index: 9998; align-items: center; justify-content: center; }
        .spinner-overlay.show { display: flex; }

        @media (max-width: 768px) {
            #sidebar { width: 100%; min-height: auto; position: relative; }
            #main { margin-left: 0; }
        }
    </style>
    @stack('head')
</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
    <a href="/dashboard" class="sidebar-brand">
        <i class="bi bi-layers-fill me-1"></i> SaaSStarter
        <small id="sidebar-tenant-name">Loading...</small>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="/dashboard" class="{{ Request::is('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="/team" class="{{ Request::is('team') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Team
        </a>
        {{-- Activity: admin + manager only --}}
        <a href="/activity" class="{{ Request::is('activity') ? 'active' : '' }} role-hide role-manager role-admin">
            <i class="bi bi-activity"></i> Activity Log
        </a>
        <div class="nav-label role-hide role-admin">Admin</div>
        <a href="/settings" class="{{ Request::is('settings') ? 'active' : '' }} role-hide role-admin">
            <i class="bi bi-gear"></i> Settings
        </a>
        <a href="/billing" class="{{ Request::is('billing') ? 'active' : '' }} role-hide role-admin">
            <i class="bi bi-credit-card"></i> Billing
        </a>
    </nav>

    <div class="sidebar-footer">
        <strong id="sidebar-user-name">...</strong>
        <span id="sidebar-user-role" class="text-muted d-block mb-2"></span>
        <a href="#" onclick="logout()" class="text-danger text-decoration-none small">
            <i class="bi bi-box-arrow-left me-1"></i>Sign out
        </a>
    </div>
</div>

<!-- Main -->
<div id="main">
    <div class="topbar">
        <h1>@yield('page-title', 'Dashboard')</h1>
        <div id="topbar-right">
            <span id="topbar-plan-badge"></span>
        </div>
    </div>
    <div class="page-body">
        @yield('content')
    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>

<!-- Spinner -->
<div class="spinner-overlay" id="spinner">
    <div class="spinner-border text-primary"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ─── Auth helpers ────────────────────────────────────────────────────────────
function getToken() { return localStorage.getItem('saas_token'); }

function requireAuth() {
    if (!getToken()) { window.location.href = '/login'; return false; }
    return true;
}

async function api(method, path, data = null) {
    const token = getToken();
    const opts = {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
    };
    if (token) opts.headers['Authorization'] = `Bearer ${token}`;
    if (data) opts.body = JSON.stringify(data);

    const res = await fetch(`/api${path}`, opts);
    if (res.status === 401) { localStorage.removeItem('saas_token'); window.location.href = '/login'; return null; }
    return res.json();
}

async function logout() {
    await api('POST', '/logout');
    localStorage.removeItem('saas_token');
    window.location.href = '/login';
}

// ─── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const id = 'toast-' + Date.now();
    const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
    const color = type === 'success' ? 'text-success' : 'text-danger';
    document.getElementById('toast-container').insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast show align-items-center bg-white border shadow-sm mb-2" role="alert">
            <div class="d-flex align-items-center p-3 gap-2">
                <i class="bi bi-${icon} ${color}"></i>
                <span class="small">${msg}</span>
                <button type="button" class="btn-close ms-auto btn-sm" onclick="document.getElementById('${id}').remove()"></button>
            </div>
        </div>
    `);
    setTimeout(() => { const el = document.getElementById(id); if (el) el.remove(); }, 4000);
}

// ─── Spinner ──────────────────────────────────────────────────────────────────
function showSpinner() { document.getElementById('spinner').classList.add('show'); }
function hideSpinner() { document.getElementById('spinner').classList.remove('show'); }

// ─── Load sidebar user info ───────────────────────────────────────────────────
// Expose role globally so page scripts can read it
window.currentUserRole = null;

async function loadSidebarUser() {
    const data = await api('GET', '/me');
    if (!data) return;
    const u = data.user;
    window.currentUserRole = u.roles[0] ?? 'member';

    document.getElementById('sidebar-user-name').textContent = u.name;

    const roleLabels = { admin: '⚙ Admin', manager: '👔 Manager', member: '👤 Member' };
    document.getElementById('sidebar-user-role').textContent = roleLabels[window.currentUserRole] ?? window.currentUserRole;

    applyRoleVisibility(window.currentUserRole);
}

function applyRoleVisibility(role) {
    // Hide all role-gated elements first
    document.querySelectorAll('[class*="role-hide"]').forEach(el => {
        el.style.display = 'none';
    });

    // Show elements matching current role or lower roles
    const visible = role === 'admin'
        ? ['role-admin', 'role-manager', 'role-member']
        : role === 'manager'
            ? ['role-manager', 'role-member']
            : ['role-member'];

    visible.forEach(cls => {
        document.querySelectorAll('.' + cls).forEach(el => {
            el.style.removeProperty('display');
        });
    });
}

async function loadSidebarTenant() {
    const data = await api('GET', '/dashboard');
    if (!data) return;
    const t = data.tenant;
    document.getElementById('sidebar-tenant-name').textContent = t.name;
    const badge = t.on_trial
        ? `<span class="badge-trial">Trial</span>`
        : `<span class="badge-active">${t.plan}</span>`;
    document.getElementById('topbar-plan-badge').innerHTML = badge;
}

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    if (!requireAuth()) return;

    // Handle ?token= from post-registration redirect
    const params = new URLSearchParams(window.location.search);
    if (params.has('token')) {
        localStorage.setItem('saas_token', params.get('token'));
        window.history.replaceState({}, '', window.location.pathname);
    }

    await Promise.all([loadSidebarUser(), loadSidebarTenant()]);
    if (typeof pageInit === 'function') pageInit();
});
</script>
@stack('scripts')
</body>
</html>
