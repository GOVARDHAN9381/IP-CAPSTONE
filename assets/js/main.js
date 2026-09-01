/* main.js — CollabIQ Global JavaScript */

'use strict';

// ── Base URL (set by PHP pages via window.APP_BASE, fallback to /ipcapstone) ──
if (typeof window.APP_BASE === 'undefined') {
    window.APP_BASE = '/ipcapstone';
}

// ── Sidebar toggle for mobile ──
function toggleSidebar() {
    document.querySelector('.sidebar')?.classList.toggle('open');
    document.querySelector('.sidebar-overlay')?.classList.toggle('show');
}

// Create sidebar overlay on load for mobile dismissal
document.addEventListener('DOMContentLoaded', () => {
    // Create overlay if sidebar exists
    if (document.querySelector('.sidebar')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.onclick = toggleSidebar;
        overlay.style.cssText = `
            display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
            z-index:199;backdrop-filter:blur(2px);
        `;
        document.body.appendChild(overlay);

        // Inject hamburger button into nav
        const nav = document.querySelector('.nav');
        if (nav && !nav.querySelector('.hamburger-btn')) {
            const hamBtn = document.createElement('button');
            hamBtn.className = 'hamburger-btn btn btn-ghost btn-sm';
            hamBtn.innerHTML = '☰';
            hamBtn.title = 'Toggle sidebar';
            hamBtn.style.cssText = 'display:none;font-size:1.3rem;padding:.4rem .7rem;';
            hamBtn.onclick = toggleSidebar;
            nav.insertBefore(hamBtn, nav.firstChild);
        }
    }
    initUploadZone();
});

// ── Auto-dismiss alerts ──
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 4000);
});

// ── Animate stats numbers ──
function animateCounter(el) {
    const target = parseFloat(el.dataset.target);
    const isFloat = String(target).includes('.');
    const duration = 1200;
    const step = target / (duration / 16);
    let current = 0;
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        el.textContent = isFloat ? current.toFixed(1) : Math.floor(current);
    }, 16);
}

document.querySelectorAll('[data-target]').forEach(el => {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { animateCounter(el); observer.disconnect(); } });
    });
    observer.observe(el);
});

// ── Task status update (AJAX) ──
function updateTaskStatus(taskId, newStatus, btn) {
    const original = btn.textContent;
    btn.textContent = '⏳';
    btn.disabled = true;

    fetch(window.APP_BASE + '/api/task_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `task_id=${taskId}&status=${newStatus}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.error === 'Unauthorized') {
            // Session expired — redirect to login
            showToast('⏰ Session expired. Redirecting to login...', 'warning');
            setTimeout(() => { window.location.href = window.APP_BASE + '/auth/login.php'; }, 1800);
            return;
        }
        if (data.success) {
            // Update badge — preserve both 'badge' and 'task-status-badge' classes
            const row = btn.closest('tr') || btn.closest('.task-row');
            const badge = row?.querySelector('.task-status-badge');
            if (badge) {
                badge.className = 'badge task-status-badge status-' + newStatus;
                badge.textContent = newStatus.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
            }
            // Update project progress bar
            if (data.progress !== undefined) {
                const bar = document.getElementById('project-progress');
                const pct = document.getElementById('project-progress-pct');
                if (bar) bar.style.width = data.progress + '%';
                if (pct) pct.textContent = data.progress + '%';
            }
            showToast('✅ Task updated!', 'success');
        } else {
            showToast('❌ ' + (data.error || 'Failed to update'), 'error');
            btn.textContent = original;
            btn.disabled = false;
        }
    })
    .catch(() => showToast('❌ Network error', 'error'))
    .finally(() => { btn.textContent = original; btn.disabled = false; });
}

// ── Post comment (AJAX) ──
function postComment(projectId) {
    const textarea = document.getElementById('comment-text');
    const list = document.getElementById('comment-list');
    const msg = textarea.value.trim();
    if (!msg) return;

    const btn = document.getElementById('comment-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Posting...';

    fetch(window.APP_BASE + '/api/comment_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `project_id=${projectId}&message=${encodeURIComponent(msg)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.error === 'Unauthorized') {
            showToast('⏰ Session expired. Please login again.', 'warning');
            setTimeout(() => { window.location.href = window.APP_BASE + '/auth/login.php'; }, 1800);
            return;
        }
        if (data.success) {
            textarea.value = '';
            // Remove empty state message if present
            list.querySelector('.empty-state')?.remove();
            const now = new Date();
            const timeStr = now.toLocaleString('en-GB', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            const html = `
            <div class="comment-item" style="animation:fadeIn .3s ease">
                <img src="${data.avatar}" class="avatar avatar-sm" alt="${escapeHtml(data.name)}">
                <div class="comment-body">
                    <div class="comment-header">
                        <span class="comment-name">${escapeHtml(data.name)}</span>
                        <span class="comment-time">${timeStr}</span>
                    </div>
                    <p class="comment-text">${escapeHtml(msg)}</p>
                </div>
            </div>`;
            list.insertAdjacentHTML('beforeend', html);
            list.scrollTop = list.scrollHeight;
            showToast('💬 Message posted!', 'success');
        } else {
            showToast('❌ ' + (data.error || 'Could not post'), 'error');
        }
    })
    .catch(() => showToast('❌ Network error', 'error'))
    .finally(() => { btn.disabled = false; btn.textContent = '💬 Post'; });
}

// ── File drag & drop ──
function initUploadZone() {
    const zone = document.getElementById('upload-zone');
    if (!zone) return;
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) submitFile(file);
    });
    zone.addEventListener('click', () => document.getElementById('file-input').click());
    document.getElementById('file-input')?.addEventListener('change', e => {
        if (e.target.files[0]) submitFile(e.target.files[0]);
    });
}

function submitFile(file) {
    const form = new FormData();
    form.append('file', file);
    form.append('project_id', document.getElementById('project-id-hidden')?.value);

    showToast('📁 Uploading...', 'info');
    fetch(window.APP_BASE + '/project/upload.php', { method: 'POST', body: form })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✅ File uploaded: ' + data.filename, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.error, 'error');
        }
    })
    .catch(() => showToast('❌ Upload failed — network error', 'error'));
}

// ── Recommendation card animations ──
function animateCards() {
    document.querySelectorAll('.rec-card').forEach((card, i) => {
        setTimeout(() => {
            card.style.transition = 'opacity .4s ease, transform .4s ease';
            card.style.opacity = '1';
            card.style.transform = 'none';
        }, i * 120);
    });
}

function animateScoreBars() {
    document.querySelectorAll('.rec-score-fill').forEach(bar => {
        const target = bar.dataset.score;
        setTimeout(() => {
            bar.style.transition = 'width 1s ease';
            bar.style.width = target + '%';
        }, 200);
    });
}

// ── Toast notification ──
function showToast(msg, type = 'info') {
    const types = { success: '#10b981', error: '#f43f5e', info: '#6366f1', warning: '#f59e0b' };
    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed;bottom:2rem;right:2rem;z-index:9999;
        background:${types[type] || types.info};color:#fff;
        padding:.875rem 1.5rem;border-radius:.75rem;
        font-size:.875rem;font-weight:600;
        box-shadow:0 8px 24px rgba(0,0,0,.4);
        animation:slideInRight .3s ease;
        max-width:320px;word-break:break-word;`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'fadeOut .3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ── CSS Animations injected ──
const style = document.createElement('style');
style.textContent = `
@keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
@keyframes slideInRight { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:none} }
@keyframes fadeOut { from{opacity:1} to{opacity:0} }
.sidebar-overlay.show { display:block !important; }

/* Mobile sidebar toggle */
@media (max-width: 768px) {
    .hamburger-btn { display:inline-flex !important; }
    .sidebar { position:fixed;top:0;left:-100%;z-index:200;height:100vh;transition:left .3s ease; }
    .sidebar.open { left:0; }
}
`;
document.head.appendChild(style);

// ══════════════════════════════════════════════════════════════
//  🔔  Notification Bell System
// ══════════════════════════════════════════════════════════════

const NotifSystem = (() => {
    let pollTimer = null;

    // Fetch unread count + recent list
    function fetchNotifications() {
        const bell = document.getElementById('notif-bell');
        if (!bell) return; // page has no bell → skip

        fetch(`${window.APP_BASE}/api/notifications.php?limit=8`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                updateBadge(data.unread_count ?? 0);
                renderDropdown(data.notifications ?? []);
            })
            .catch(() => {/* silently ignore network errors */});
    }

    function updateBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }

    function renderDropdown(items) {
        const list = document.getElementById('notif-list');
        if (!list) return;

        if (items.length === 0) {
            list.innerHTML = '<li class="notif-empty">🎉 You\'re all caught up!</li>';
            return;
        }

        list.innerHTML = items.map(n => `
            <li class="notif-item${n.is_read == 1 ? ' notif-read' : ''}" data-id="${n.id}">
                <a href="${n.link ? window.APP_BASE + n.link : '#'}"
                   class="notif-link"
                   onclick="NotifSystem.markRead(${n.id}, event)">
                    <span class="notif-msg">${escapeHtml(n.message)}</span>
                    <span class="notif-time">${timeAgo(n.created_at)}</span>
                </a>
            </li>`).join('');
    }

    function markRead(id, e) {
        const fd = new FormData();
        fd.append('type', 'single');
        fd.append('id', id);
        fetch(`${window.APP_BASE}/api/notify_read.php`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(() => {
                const item = document.querySelector(`.notif-item[data-id="${id}"]`);
                if (item) item.classList.add('notif-read');
                fetchNotifications(); // refresh badge count
            });
        // allow the link navigation to proceed
    }

    function markAllRead() {
        const fd = new FormData();
        fd.append('type', 'all');
        fetch(`${window.APP_BASE}/api/notify_read.php`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(() => {
                updateBadge(0);
                document.querySelectorAll('.notif-item').forEach(el => el.classList.add('notif-read'));
                showToast('All notifications marked as read', 'success');
            });
    }

    function toggleDropdown() {
        const panel = document.getElementById('notif-panel');
        if (!panel) return;
        const isOpen = panel.classList.toggle('open');
        if (isOpen) fetchNotifications(); // fresh data when opening
    }

    function init() {
        // Close dropdown on outside click
        document.addEventListener('click', (e) => {
            const bell  = document.getElementById('notif-bell');
            const panel = document.getElementById('notif-panel');
            if (bell && panel && !bell.contains(e.target) && !panel.contains(e.target)) {
                panel.classList.remove('open');
            }
        });

        // Start polling
        fetchNotifications();
        pollTimer = setInterval(fetchNotifications, 30_000); // every 30 s
    }

    // Kick off when DOM ready (main.js is deferred / at bottom of body)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return { fetchNotifications, markRead, markAllRead, toggleDropdown };
})();

// ── Simple time-ago helper ──
function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)  return 'just now';
    if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}
