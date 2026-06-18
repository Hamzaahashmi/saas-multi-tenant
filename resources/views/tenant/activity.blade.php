@extends('layouts.app')

@section('title', 'Activity Log')
@section('page-title', 'Activity Log')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0">All Activity</h6>
            <span class="badge bg-secondary bg-opacity-10 text-secondary" id="total-badge">Loading...</span>
        </div>

        <div id="activity-table">
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-3" id="pagination" style="display:none!important">
            <span class="text-muted small" id="pagination-info"></span>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="prev-btn" onclick="loadPage(currentPage - 1)">
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="next-btn" onclick="loadPage(currentPage + 1)">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;

async function pageInit() {
    await new Promise(r => setTimeout(r, 50));
    if (window.currentUserRole === 'member') {
        document.querySelector('.page-body').innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-lock-fill text-muted" style="font-size:3rem"></i>
                <h5 class="mt-3 text-muted">Access Restricted</h5>
                <p class="text-muted small">Activity log is available to admins and managers only.</p>
                <a href="/dashboard" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
            </div>`;
        return;
    }
    await loadPage(1);
}

async function loadPage(page) {
    currentPage = page;
    document.getElementById('activity-table').innerHTML = `
        <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>`;

    const data = await api('GET', `/activity?page=${page}`);
    if (!data) return;

    document.getElementById('total-badge').textContent = data.total + ' events';

    const rows = data.data ?? [];
    if (!rows.length) {
        document.getElementById('activity-table').innerHTML =
            '<p class="text-muted small text-center py-4 mb-0">No activity yet.</p>';
        return;
    }

    document.getElementById('activity-table').innerHTML = `
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.85rem">
                <thead class="table-light">
                    <tr>
                        <th class="fw-semibold text-muted small">Action</th>
                        <th class="fw-semibold text-muted small">User</th>
                        <th class="fw-semibold text-muted small">Details</th>
                        <th class="fw-semibold text-muted small">IP</th>
                        <th class="fw-semibold text-muted small">Time</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows.map(a => `
                        <tr>
                            <td>
                                <span class="badge bg-${actionColor(a.action)} bg-opacity-10 text-${actionColor(a.action)}">
                                    ${escHtml(a.action)}
                                </span>
                            </td>
                            <td>${escHtml(a.user_name ?? '—')}</td>
                            <td class="text-muted">${formatProperties(a.properties)}</td>
                            <td class="text-muted font-monospace" style="font-size:.75rem">${escHtml(a.ip_address ?? '—')}</td>
                            <td class="text-muted" style="white-space:nowrap">${formatDate(a.created_at)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>`;

    // Pagination
    const pagEl = document.getElementById('pagination');
    if (data.last_page > 1) {
        pagEl.style.removeProperty('display');
        document.getElementById('pagination-info').textContent =
            `Page ${data.current_page} of ${data.last_page} (${data.total} total)`;
        document.getElementById('prev-btn').disabled = data.current_page <= 1;
        document.getElementById('next-btn').disabled = data.current_page >= data.last_page;
    } else {
        pagEl.style.display = 'none';
    }
}

function actionColor(action) {
    if (action.includes('login'))    return 'success';
    if (action.includes('logout'))   return 'secondary';
    if (action.includes('settings')) return 'primary';
    if (action.includes('invite'))   return 'warning';
    if (action.includes('delete') || action.includes('remove')) return 'danger';
    return 'info';
}

function formatProperties(props) {
    if (!props) return '—';
    if (Array.isArray(props)) return props.join(', ');
    if (typeof props === 'object') {
        return Object.entries(props).map(([k,v]) => `${k}: ${v}`).join(', ');
    }
    return String(props);
}

function formatDate(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
