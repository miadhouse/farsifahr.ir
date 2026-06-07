<?php
/**
 * FarsiFahr Live Chat - Admin Panel
 * Place at: /chat/admin/index.php
 * Access: Only super admin
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../incloud/functions.php';

// Only super admin
if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'miadaleali@gmail.com') {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>پنل چت - فارسی‌فهر</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
:root {
    --c-bg: #0f1117;
    --c-surface: #161b27;
    --c-surface2: #1e2538;
    --c-border: rgba(255,255,255,0.07);
    --c-primary: #667eea;
    --c-text: #e2e8f0;
    --c-muted: #64748b;
    --c-online: #43e97b;
    --c-waiting: #f6ad55;
    --c-closed: #fc5c7d;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background: var(--c-bg);
    color: var(--c-text);
    font-family: 'Tahoma', sans-serif;
    height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* ---- TOP BAR ---- */
.topbar {
    background: var(--c-surface);
    border-bottom: 1px solid var(--c-border);
    padding: 0 20px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.topbar-title { font-size: 15px; font-weight: 700; color: var(--c-text); }
.topbar-title span { color: var(--c-primary); }
.topbar-stats { display: flex; gap: 16px; }
.stat-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--c-surface2);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid var(--c-border);
}
.stat-chip .dot { width: 8px; height: 8px; border-radius: 50%; }

/* ---- MAIN LAYOUT ---- */
.main-layout {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* ---- SIDEBAR ---- */
.sidebar {
    width: 300px;
    background: var(--c-surface);
    border-left: 1px solid var(--c-border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.sidebar-tabs {
    display: flex;
    border-bottom: 1px solid var(--c-border);
    flex-shrink: 0;
}
.sidebar-tab {
    flex: 1;
    padding: 10px 8px;
    font-size: 11px;
    background: none;
    border: none;
    color: var(--c-muted);
    cursor: pointer;
    transition: color 0.2s, border-color 0.2s;
    border-bottom: 2px solid transparent;
    font-family: inherit;
}
.sidebar-tab.active { color: var(--c-primary); border-bottom-color: var(--c-primary); }

.sidebar-search {
    padding: 10px 12px;
    border-bottom: 1px solid var(--c-border);
    flex-shrink: 0;
}
.sidebar-search input {
    width: 100%;
    background: var(--c-surface2);
    border: 1px solid var(--c-border);
    color: var(--c-text);
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 12px;
    outline: none;
    direction: rtl;
    font-family: inherit;
}
.sidebar-search input:focus { border-color: var(--c-primary); }

.sessions-list {
    flex: 1;
    overflow-y: auto;
}
.sessions-list::-webkit-scrollbar { width: 3px; }
.sessions-list::-webkit-scrollbar-thumb { background: var(--c-border); }

.session-item {
    padding: 12px 14px;
    border-bottom: 1px solid var(--c-border);
    cursor: pointer;
    transition: background 0.15s;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}
.session-item:hover { background: var(--c-surface2); }
.session-item.active { background: rgba(102,126,234,0.1); border-right: 3px solid var(--c-primary); }

.session-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--c-surface2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    position: relative;
}
.session-avatar::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 11px;
    height: 11px;
    border-radius: 50%;
    border: 2px solid var(--c-surface);
    background: var(--c-muted);
}
.session-avatar.online::after { background: var(--c-online); }

.session-info { flex: 1; overflow: hidden; }
.session-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.session-preview { font-size: 11px; color: var(--c-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }

.session-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.session-time { font-size: 10px; color: var(--c-muted); }
.session-badge {
    background: var(--c-primary);
    color: #fff;
    border-radius: 10px;
    padding: 1px 7px;
    font-size: 10px;
    font-weight: 700;
}
.status-chip {
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
}
.status-chip.waiting { background: rgba(246,173,85,0.15); color: var(--c-waiting); }
.status-chip.active { background: rgba(67,233,123,0.15); color: var(--c-online); }
.status-chip.closed { background: rgba(252,92,125,0.15); color: var(--c-closed); }

/* ---- MAIN CHAT AREA ---- */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-area-header {
    background: var(--c-surface);
    border-bottom: 1px solid var(--c-border);
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    height: 64px;
}

.chat-area-user-info { flex: 1; }
.chat-area-user-info h4 { font-size: 14px; font-weight: 700; margin: 0; }
.chat-area-user-info p { font-size: 11px; color: var(--c-muted); margin: 0; }

.chat-area-actions { display: flex; gap: 8px; }
.icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--c-surface2);
    border: 1px solid var(--c-border);
    color: var(--c-text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: background 0.15s;
}
.icon-btn:hover { background: var(--c-border); }
.icon-btn.danger { color: var(--c-closed); }

/* Messages */
.admin-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.admin-messages::-webkit-scrollbar { width: 4px; }
.admin-messages::-webkit-scrollbar-thumb { background: var(--c-border); }

.msg-day-sep {
    text-align: center;
    font-size: 11px;
    color: var(--c-muted);
    padding: 4px 12px;
    background: var(--c-surface2);
    border-radius: 20px;
    align-self: center;
}

.admin-msg {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    max-width: 70%;
}
.admin-msg.from-user { align-self: flex-start; }
.admin-msg.from-admin { align-self: flex-end; flex-direction: row-reverse; }
.admin-msg.from-system { align-self: center; max-width: 100%; }

.admin-msg-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--c-surface2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.admin-msg-bubble {
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.6;
    word-break: break-word;
}
.admin-msg.from-user .admin-msg-bubble {
    background: var(--c-surface2);
    border: 1px solid var(--c-border);
    border-bottom-right-radius: 4px;
}
.admin-msg.from-admin .admin-msg-bubble {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    border-bottom-left-radius: 4px;
}
.admin-msg.from-system .admin-msg-bubble {
    background: rgba(102,126,234,0.08);
    color: var(--c-muted);
    font-size: 12px;
    border-radius: 10px;
    text-align: center;
    border: 1px solid rgba(102,126,234,0.15);
}
.admin-msg-meta { font-size: 10px; color: var(--c-muted); margin-top: 3px; text-align: center; }

/* Admin input */
.admin-input-area {
    border-top: 1px solid var(--c-border);
    padding: 12px 16px;
    background: var(--c-surface);
    flex-shrink: 0;
}

.quick-replies-bar {
    display: flex;
    gap: 6px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.qr-chip {
    background: var(--c-surface2);
    border: 1px solid var(--c-border);
    color: var(--c-text);
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 11px;
    cursor: pointer;
    transition: border-color 0.15s;
    font-family: inherit;
}
.qr-chip:hover { border-color: var(--c-primary); color: var(--c-primary); }

.admin-input-row { display: flex; gap: 8px; }
#admin-msg-input {
    flex: 1;
    background: var(--c-surface2);
    border: 1px solid var(--c-border);
    color: var(--c-text);
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    resize: none;
    outline: none;
    font-family: inherit;
    line-height: 1.5;
    direction: rtl;
    max-height: 80px;
}
#admin-msg-input:focus { border-color: var(--c-primary); }

.admin-send-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    border: none;
    padding: 0 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: opacity 0.2s;
    white-space: nowrap;
}
.admin-send-btn:hover { opacity: 0.9; }

/* Empty state */
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--c-muted);
    gap: 12px;
}
.empty-state .icon { font-size: 48px; opacity: 0.3; }
.empty-state p { font-size: 14px; }

/* ---- EMOJIS ---- */
.admin-emoji-bar {
    display: flex;
    gap: 4px;
    margin-bottom: 8px;
}
.admin-emoji-btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    padding: 3px;
    border-radius: 4px;
    transition: background 0.1s;
}
.admin-emoji-btn:hover { background: var(--c-surface2); }

/* No session selected */
#no-session-selected { display: flex; }
#chat-detail-view { display: none; flex-direction: column; height: 100%; }

/* Notification dot */
.notif-dot {
    width: 8px; height: 8px;
    background: var(--c-primary);
    border-radius: 50%;
    animation: blink 1s ease-in-out infinite;
}
@keyframes blink {
    0%,100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
    <div class="topbar-title">پنل چت <span>فارسی‌فهر</span></div>
    <div class="topbar-stats" id="topbar-stats">
        <div class="stat-chip"><div class="dot" style="background:#43e97b"></div><span id="stat-online">0</span> آنلاین</div>
        <div class="stat-chip"><div class="dot" style="background:#f6ad55"></div><span id="stat-waiting">0</span> در انتظار</div>
        <div class="stat-chip"><div class="dot" style="background:#667eea"></div><span id="stat-active">0</span> فعال</div>
    </div>
    <a href="/admin/" class="btn btn-sm" style="background:rgba(255,255,255,0.08);color:#fff;font-size:12px;border-radius:8px;padding:6px 14px;">بازگشت به پنل</a>
</div>

<!-- MAIN LAYOUT -->
<div class="main-layout">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-tabs">
            <button class="sidebar-tab active" onclick="filterSessions('all', this)">همه</button>
            <button class="sidebar-tab" onclick="filterSessions('waiting', this)">در انتظار <span class="notif-dot" id="waiting-notif" style="display:none;"></span></button>
            <button class="sidebar-tab" onclick="filterSessions('active', this)">فعال</button>
            <button class="sidebar-tab" onclick="filterSessions('online', this)">آنلاین</button>
        </div>
        <div class="sidebar-search">
            <input type="text" id="session-search" placeholder="جستجو نام یا ایمیل..." oninput="filterByName(this.value)">
        </div>
        <div class="sessions-list" id="sessions-list">
            <div style="padding:20px;text-align:center;color:var(--c-muted);font-size:13px">در حال بارگذاری...</div>
        </div>
    </div>

    <!-- CHAT AREA -->
    <div class="chat-area">

        <!-- No session selected -->
        <div class="empty-state" id="no-session-selected">
            <div class="icon">💬</div>
            <p>یک چت را از سمت راست انتخاب کنید</p>
        </div>

        <!-- Chat detail -->
        <div id="chat-detail-view">
            <div class="chat-area-header" id="chat-area-header">
                <div class="session-avatar" id="detail-avatar">👤</div>
                <div class="chat-area-user-info">
                    <h4 id="detail-name">-</h4>
                    <p id="detail-meta">-</p>
                </div>
                <div class="chat-area-actions">
                    <button class="icon-btn" title="بستن چت" onclick="closeSession()" id="btn-close-session">
                        <i class="bi bi-x-circle"></i>
                    </button>
                    <button class="icon-btn" title="رفرش" onclick="loadSessionMessages(currentSessionId)">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <div class="admin-messages" id="admin-messages"></div>

            <div class="admin-input-area" id="admin-input-area">
                <div class="admin-emoji-bar" id="admin-emoji-bar"></div>
                <div class="quick-replies-bar" id="quick-replies-bar"></div>
                <div class="admin-input-row">
                    <button class="admin-send-btn" onclick="adminSend()">ارسال <i class="bi bi-send"></i></button>
                    <textarea id="admin-msg-input" placeholder="پاسخ خود را بنویسید..." rows="1"></textarea>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const API = '/chat/api/handler.php';
const EMOJIS = ['😊','👋','🙏','✅','❓','😅','🎉','💯','🔥','👍','❤️','😢','👌','🤔','💪'];

let currentSessionId = null;
let currentFilter = 'all';
let allSessions = [];
let lastAdminMsgId = 0;
let pollTimer = null;
let sessionsPollTimer = null;

// ========= INIT =========
document.addEventListener('DOMContentLoaded', () => {
    buildEmojiBar();
    loadQuickReplies();
    loadSessions();
    startSessionsPolling();

    // Input events
    const input = document.getElementById('admin-msg-input');
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); adminSend(); }
    });
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });
});

// ========= SESSIONS =========
function loadSessions(filter = currentFilter) {
    fetch(`${API}?action=get_sessions&filter=${filter}`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        allSessions = data.sessions;
        renderSessions(allSessions);

        // Update stats
        document.getElementById('stat-online').textContent = data.stats.total_online;
        document.getElementById('stat-waiting').textContent = data.stats.waiting;
        document.getElementById('stat-active').textContent = data.stats.active;

        const notif = document.getElementById('waiting-notif');
        notif.style.display = data.stats.waiting > 0 ? 'inline-block' : 'none';
    }).catch(() => {});
}

function renderSessions(sessions) {
    const list = document.getElementById('sessions-list');
    const search = document.getElementById('session-search').value.toLowerCase();
    const filtered = sessions.filter(s =>
        !search ||
        s.name.toLowerCase().includes(search) ||
        s.email.toLowerCase().includes(search)
    );

    if (!filtered.length) {
        list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--c-muted);font-size:13px">چتی یافت نشد</div>';
        return;
    }

    list.innerHTML = filtered.map(s => `
        <div class="session-item ${s.id == currentSessionId ? 'active' : ''}" onclick="selectSession(${s.id})">
            <div class="session-avatar ${s.is_online ? 'online' : ''}">${s.is_member ? '👤' : '🌐'}</div>
            <div class="session-info">
                <div class="session-name">${escapeHtml(s.name)}</div>
                <div class="session-preview">${escapeHtml(s.last_message || 'بدون پیام')}</div>
            </div>
            <div class="session-meta">
                <span class="session-time">${s.last_time || s.created_at}</span>
                ${s.unread > 0 ? `<span class="session-badge">${s.unread}</span>` : `<span class="status-chip ${s.status}">${statusLabel(s.status)}</span>`}
            </div>
        </div>
    `).join('');
}

function statusLabel(s) {
    return {waiting: 'انتظار', active: 'فعال', closed: 'بسته'}[s] || s;
}

function filterSessions(f, btn) {
    currentFilter = f;
    document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    loadSessions(f);
}

function filterByName(val) {
    renderSessions(allSessions);
}

function startSessionsPolling() {
    sessionsPollTimer = setInterval(() => loadSessions(), 5000);
}

// ========= SELECT SESSION =========
function selectSession(id) {
    currentSessionId = id;
    lastAdminMsgId = 0;
    clearInterval(pollTimer);

    document.getElementById('no-session-selected').style.display = 'none';
    const detail = document.getElementById('chat-detail-view');
    detail.style.display = 'flex';

    loadSessionMessages(id);

    // Re-render sessions to update active state
    renderSessions(allSessions);

    // Start polling for this session
    pollTimer = setInterval(() => pollAdminMessages(), 3000);
}

function loadSessionMessages(id) {
    fetch(`${API}?action=get_session_messages&session_id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const s = data.session;

        // Update header
        document.getElementById('detail-name').textContent = s.name;
        document.getElementById('detail-avatar').textContent = s.is_member ? '👤' : '🌐';
        document.getElementById('detail-avatar').className = `session-avatar ${s.is_online ? 'online' : ''}`;
        document.getElementById('detail-meta').innerHTML =
            `${s.email || 'بدون ایمیل'} • ${s.is_member ? 'کاربر عضو' : 'مهمان'} • ${s.created_at}
            <span class="status-chip ${s.status} me-2">${statusLabel(s.status)}</span>`;

        // Render messages
        const container = document.getElementById('admin-messages');
        container.innerHTML = '';

        let lastDate = '';
        data.messages.forEach(m => {
            if (m.date !== lastDate) {
                const sep = document.createElement('div');
                sep.className = 'msg-day-sep';
                sep.textContent = m.date;
                container.appendChild(sep);
                lastDate = m.date;
            }
            container.appendChild(buildAdminMessage(m));
            lastAdminMsgId = Math.max(lastAdminMsgId, m.id);
        });

        scrollAdminToBottom();

        // Disable input if closed
        const inputArea = document.getElementById('admin-input-area');
        if (s.status === 'closed') {
            document.getElementById('admin-msg-input').disabled = true;
            document.getElementById('admin-msg-input').placeholder = 'این چت بسته شده است';
            document.querySelector('.admin-send-btn').disabled = true;
        } else {
            document.getElementById('admin-msg-input').disabled = false;
            document.getElementById('admin-msg-input').placeholder = 'پاسخ خود را بنویسید...';
            document.querySelector('.admin-send-btn').disabled = false;
        }
    }).catch(() => {});
}

function buildAdminMessage(m) {
    if (m.type === 'system') {
        const d = document.createElement('div');
        d.className = 'admin-msg from-system';
        d.innerHTML = `<div class="admin-msg-bubble">${escapeHtml(m.message)}</div>`;
        return d;
    }

    const div = document.createElement('div');
    div.className = `admin-msg from-${m.type === 'admin' ? 'admin' : 'user'}`;

    const avatar = document.createElement('div');
    avatar.className = 'admin-msg-avatar';
    avatar.textContent = m.type === 'admin' ? '🎧' : '👤';

    const inner = document.createElement('div');

    const bubble = document.createElement('div');
    bubble.className = 'admin-msg-bubble';
    bubble.innerHTML = escapeHtml(m.message).replace(/\n/g, '<br>');

    const meta = document.createElement('div');
    meta.className = 'admin-msg-meta';
    meta.textContent = m.time;

    inner.appendChild(bubble);
    inner.appendChild(meta);
    div.appendChild(avatar);
    div.appendChild(inner);
    return div;
}

function pollAdminMessages() {
    if (!currentSessionId) return;

    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=poll&session_id=${currentSessionId}&last_id=${lastAdminMsgId}`
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.messages.length) return;
        const container = document.getElementById('admin-messages');
        data.messages.forEach(m => {
            container.appendChild(buildAdminMessage(m));
            lastAdminMsgId = Math.max(lastAdminMsgId, m.id);
        });
        scrollAdminToBottom();
    }).catch(() => {});
}

function scrollAdminToBottom() {
    const el = document.getElementById('admin-messages');
    if (el) el.scrollTop = el.scrollHeight;
}

// ========= ADMIN SEND =========
function adminSend() {
    if (!currentSessionId) return;
    const input = document.getElementById('admin-msg-input');
    const message = input.value.trim();
    if (!message) return;

    input.value = '';
    input.style.height = 'auto';

    // Optimistic
    const container = document.getElementById('admin-messages');
    const fakeMsg = buildAdminMessage({
        type: 'admin',
        message,
        time: new Date().toLocaleTimeString('fa-IR', {hour:'2-digit',minute:'2-digit'}),
        date: ''
    });
    container.appendChild(fakeMsg);
    scrollAdminToBottom();

    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=send&session_id=${currentSessionId}&message=${encodeURIComponent(message)}`
    }).catch(() => {});
}

// ========= CLOSE SESSION =========
function closeSession() {
    if (!currentSessionId) return;
    if (!confirm('آیا می‌خواهید این چت را ببندید؟')) return;

    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=close_session&session_id=${currentSessionId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadSessionMessages(currentSessionId);
            loadSessions();
        }
    });
}

// ========= QUICK REPLIES =========
function loadQuickReplies() {
    fetch(`${API}?action=get_quick_replies`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const bar = document.getElementById('quick-replies-bar');
        bar.innerHTML = '';
        data.replies.forEach(r => {
            const btn = document.createElement('button');
            btn.className = 'qr-chip';
            btn.textContent = r.title;
            btn.title = r.message;
            btn.addEventListener('click', () => {
                document.getElementById('admin-msg-input').value = r.message;
                document.getElementById('admin-msg-input').focus();
            });
            bar.appendChild(btn);
        });
    }).catch(() => {});
}

// ========= EMOJI =========
function buildEmojiBar() {
    const bar = document.getElementById('admin-emoji-bar');
    EMOJIS.forEach(e => {
        const b = document.createElement('button');
        b.className = 'admin-emoji-btn';
        b.textContent = e;
        b.addEventListener('click', () => {
            const input = document.getElementById('admin-msg-input');
            const pos = input.selectionStart;
            input.value = input.value.slice(0, pos) + e + input.value.slice(pos);
            input.focus();
        });
        bar.appendChild(b);
    });
}

// ========= UTILS =========
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
