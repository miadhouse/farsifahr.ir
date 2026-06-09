/**
 * farsifahr Live Chat Widget JS
 * Place at: /chat/widget.js
 */
(function() {
    'use strict';

    const CHAT_API = '/chat/api/handler.php';
    const HEARTBEAT_INTERVAL = 30000; // 30 seconds
    const STORAGE_KEY = 'ff_chat_token';
    const WELCOME_KEY = 'ff_chat_welcomed';

    const EMOJIS = ['😊','👋','🙏','✅','❓','😅','🎉','💯','🔥','👍','❤️','😢','👌','🤔','💪'];

    let chatToken = localStorage.getItem(STORAGE_KEY) || null;
    let isOpen = false;
    let lastMessageId = 0;
    let pollTimer = null;
    let heartbeatTimer = null;
    let needsGuestInfo = false;
    let isClosed = false;
    let isPolling = false;

    // ========= BUILD HTML =========
    function buildWidget() {
        // Toggle button
        const btn = document.createElement('button');
        btn.id = 'chat-toggle-btn';
        btn.setAttribute('aria-label', 'چت پشتیبانی');
        btn.innerHTML = `
            <svg class="icon-chat" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
            <svg class="icon-close" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            <span id="chat-toggle-badge"></span>
        `;

        // Main window
        const win = document.createElement('div');
        win.id = 'chat-window';
        win.setAttribute('role', 'dialog');
        win.setAttribute('aria-label', 'پشتیبانی آنلاین');
        win.innerHTML = `
            <div id="chat-header">
                <div class="chat-header-avatar">🎧</div>
                <div class="chat-header-info">
                    <h4>پشتیبانی farsifahr</h4>
                    <p id="chat-status-text">آنلاین • معمولاً در چند دقیقه پاسخ می‌دهیم</p>
                </div>
                <button class="chat-header-close" id="chat-close-btn" aria-label="بستن">×</button>
            </div>
            <div id="chat-messages"></div>
            <div id="chat-footer">
                <div class="chat-emoji-bar" id="chat-emoji-bar" style="display:none !important"></div>
                <div class="chat-input-row">
                    <button id="chat-send-btn" aria-label="ارسال">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                    <textarea id="chat-input" placeholder="پیام خود را بنویسید..." rows="1" maxlength="2000"></textarea>
                    <button class="chat-emoji-toggle" id="chat-emoji-toggle" title="انتخاب ایموجی" type="button">😊</button>
                </div>
            </div>
        `;

        // Welcome popup
        const popup = document.createElement('div');
        popup.id = 'chat-welcome-popup';
        popup.style.display = 'none';
        popup.innerHTML = `
            <button class="chat-welcome-close" id="chat-popup-close">×</button>
            <div class="avatar">👋</div>
            <h5>سلام! چطور می‌توانیم کمک کنیم؟</h5>
            <p>تیم پشتیبانی farsifahr آماده پاسخگویی است.</p>
            <button class="open-btn" id="chat-popup-open">شروع گفتگو</button>
        `;

        document.body.appendChild(popup);
        document.body.appendChild(win);
        document.body.appendChild(btn);

        // Emoji bar
        const emojiBar = document.getElementById('chat-emoji-bar');
        EMOJIS.forEach(e => {
            const b = document.createElement('button');
            b.className = 'chat-emoji-btn';
            b.textContent = e;
            b.addEventListener('click', () => insertEmoji(e));
            emojiBar.appendChild(b);
        });
    }

    // ========= TOGGLE =========
    function toggleChat() {
        isOpen = !isOpen;
        const btn = document.getElementById('chat-toggle-btn');
        const win = document.getElementById('chat-window');
        const popup = document.getElementById('chat-welcome-popup');

        btn.classList.toggle('is-open', isOpen);
        win.classList.toggle('is-open', isOpen);
        popup.style.display = 'none';

        if (window.innerWidth <= 480) {
            document.body.classList.toggle('chat-open-lock', isOpen);
        }

        if (isOpen) {
            clearBadge();
            initSession();
            document.getElementById('chat-input')?.focus();
        } else {
            // Close emoji bar when chat closes
            const bar = document.getElementById('chat-emoji-bar');
            if (bar) {
                bar.style.setProperty('display', 'none', 'important');
                bar.classList.remove('is-open');
            }
            document.getElementById('chat-emoji-toggle')?.classList.remove('is-active');
        }
    }

    function closeChat() {
        isOpen = false;
        document.getElementById('chat-toggle-btn')?.classList.remove('is-open');
        document.getElementById('chat-window')?.classList.remove('is-open');
        document.getElementById('chat-emoji-bar')?.classList.remove('is-open');
        document.getElementById('chat-emoji-toggle')?.classList.remove('is-active');
        document.body.classList.remove('chat-open-lock');
    }

    function toggleEmojiBar() {
        const bar = document.getElementById('chat-emoji-bar');
        const btn = document.getElementById('chat-emoji-toggle');
        const isHidden = bar.style.display === 'none' || !bar.classList.contains('is-open');
        
        if (isHidden) {
            bar.style.setProperty('display', 'flex', 'important');
            bar.classList.add('is-open');
            btn.classList.add('is-active');
        } else {
            bar.style.setProperty('display', 'none', 'important');
            bar.classList.remove('is-open');
            btn.classList.remove('is-active');
        }
    }

    // ========= SESSION INIT =========
    function initSession() {
        const messagesEl = document.getElementById('chat-messages');
        if (messagesEl.children.length === 0) {
            messagesEl.innerHTML = '<div class="chat-typing"><span></span><span></span><span></span></div>';
        }

        fetch(CHAT_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=init&token=${encodeURIComponent(chatToken || '')}&page_url=${encodeURIComponent(location.href)}`
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            chatToken = data.token;
            localStorage.setItem(STORAGE_KEY, chatToken);
            isClosed = data.session.status === 'closed';

            // Check if we need to show/remove guest form
            needsGuestInfo = data.session.needs_info;
            
            // Only clear and re-render if it's the first init or session changed significantly
            if (messagesEl.querySelector('.chat-typing') || lastMessageId === 0) {
                messagesEl.innerHTML = '';
                
                if (needsGuestInfo) {
                    messagesEl.appendChild(buildGuestForm());
                    disableInput('ابتدا اطلاعات خود را وارد کنید');
                } else {
                    enableInput();
                }

                // Render existing messages
                data.messages.forEach(m => {
                    lastMessageId = Math.max(lastMessageId, m.id);
                    messagesEl.appendChild(buildMessage(m));
                });
                scrollToBottom();
            }

            if (isClosed) {
                disableInput('این چت بسته شده است.');
            }

            // Start polling
            startPolling();
            startHeartbeat();
        })
        .catch(() => {
            if (lastMessageId === 0) {
                messagesEl.innerHTML = '<p style="color:#f56565;text-align:center;font-size:13px">خطا در اتصال. لطفاً دوباره تلاش کنید.</p>';
            }
        });
    }

    // ========= GUEST FORM =========
    function buildGuestForm() {
        const div = document.createElement('div');
        div.id = 'chat-guest-form';
        div.innerHTML = `
            <h5>ابتدا خودتان را معرفی کنید</h5>
            <input type="text" id="guest-name" placeholder="نام شما" />
            <input type="email" id="guest-email" placeholder="ایمیل شما" />
            <button id="guest-submit-btn">شروع چت 🚀</button>
        `;
        setTimeout(() => {
            div.querySelector('#guest-submit-btn')?.addEventListener('click', submitGuestInfo);
        }, 0);
        return div;
    }

    function submitGuestInfo() {
        const name = document.getElementById('guest-name')?.value.trim();
        const email = document.getElementById('guest-email')?.value.trim();

        if (!name || !email) {
            alert('لطفاً نام و ایمیل را وارد کنید.');
            return;
        }

        const btn = document.getElementById('guest-submit-btn');
        btn.disabled = true;
        btn.textContent = 'در حال ثبت...';

        fetch(CHAT_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=set_guest_info&token=${encodeURIComponent(chatToken)}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                needsGuestInfo = false;
                document.getElementById('chat-guest-form')?.remove();
                enableInput();
                // Reload messages
                const messagesEl = document.getElementById('chat-messages');
                messagesEl.innerHTML = '';
                lastMessageId = 0;
                initSession();
            } else {
                alert(data.message || 'خطا در ثبت اطلاعات');
                btn.disabled = false;
                btn.textContent = 'شروع چت 🚀';
            }
        });
    }

    // ========= MESSAGES =========
    function buildMessage(m) {
        if (m.type === 'system') {
            const d = document.createElement('div');
            d.className = 'chat-status-msg';
            d.innerHTML = m.message;
            return d;
        }

        const div = document.createElement('div');
        div.className = `chat-msg ${m.type}`;

        const avatar = document.createElement('div');
        avatar.className = 'chat-msg-avatar';
        avatar.textContent = m.type === 'admin' ? '🎧' : '👤';

        const bubble = document.createElement('div');
        bubble.className = 'chat-msg-bubble';
        bubble.innerHTML = m.message.replace(/\n/g, '<br>');

        const time = document.createElement('div');
        time.className = 'chat-msg-time';
        time.textContent = m.time;

        const inner = document.createElement('div');
        inner.style.display = 'flex';
        inner.style.flexDirection = 'column';
        inner.appendChild(bubble);
        inner.appendChild(time);

        div.appendChild(avatar);
        div.appendChild(inner);
        return div;
    }

    function addMessage(m) {
        const messagesEl = document.getElementById('chat-messages');
        if (!messagesEl) return;
        messagesEl.appendChild(buildMessage(m));
        scrollToBottom();
    }

    function scrollToBottom() {
        const el = document.getElementById('chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
    }

    // ========= SEND =========
    function sendMessage() {
        if (needsGuestInfo || isClosed) return;

        const input = document.getElementById('chat-input');
        const message = input?.value.trim();
        if (!message) return;

        input.value = '';
        input.style.height = 'auto';

        // Close emoji bar after send
        const bar = document.getElementById('chat-emoji-bar');
        if (bar) {
            bar.style.setProperty('display', 'none', 'important');
            bar.classList.remove('is-open');
        }
        document.getElementById('chat-emoji-toggle')?.classList.remove('is-active');

        // Optimistic UI
        addMessage({ type: 'user', message, time: new Date().toLocaleTimeString('fa-IR', {hour:'2-digit',minute:'2-digit'}) });

        fetch(CHAT_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=send&token=${encodeURIComponent(chatToken)}&message=${encodeURIComponent(message)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // We'll get confirmation through polling or next poll
            }
        })
        .catch(() => {});
    }

    function insertEmoji(e) {
        if (needsGuestInfo || isClosed) return;
        const input = document.getElementById('chat-input');
        if (input) {
            const pos = input.selectionStart;
            input.value = input.value.slice(0, pos) + e + input.value.slice(pos);
            input.focus();
            input.selectionStart = input.selectionEnd = pos + e.length;
        }
    }

    // ========= POLLING =========
    function startPolling() {
        if (isPolling) return;
        pollMessages();
    }

    function pollMessages() {
        if (!chatToken || isClosed) {
            isPolling = false;
            return;
        }

        isPolling = true;
        fetch(CHAT_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=poll&token=${encodeURIComponent(chatToken)}&last_id=${lastMessageId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(m => {
                    lastMessageId = Math.max(lastMessageId, m.id);
                    addMessage(m);
                    if (!isOpen && m.type !== 'user') showBadge();
                });
            }
            // Wait a bit then poll again. Server-side long poll handles the delay.
            setTimeout(pollMessages, 1000);
        })
        .catch(() => {
            setTimeout(pollMessages, 5000);
        });
    }

    // ========= HEARTBEAT =========
    function startHeartbeat() {
        clearInterval(heartbeatTimer);
        heartbeatTimer = setInterval(() => {
            if (!chatToken) return;
            fetch(CHAT_API, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=heartbeat&token=${encodeURIComponent(chatToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'closed') {
                    isClosed = true;
                    disableInput('چت توسط پشتیبانی بسته شد.');
                }
            }).catch(() => {});
        }, HEARTBEAT_INTERVAL);
    }

    // ========= BADGE =========
    function showBadge() {
        const badge = document.getElementById('chat-toggle-badge');
        if (badge) { badge.style.display = 'flex'; badge.textContent = '●'; }
    }
    function clearBadge() {
        const badge = document.getElementById('chat-toggle-badge');
        if (badge) badge.style.display = 'none';
    }

    // ========= DISABLE/ENABLE INPUT =========
    function disableInput(msg) {
        const input = document.getElementById('chat-input');
        const btn = document.getElementById('chat-send-btn');
        const emo = document.getElementById('chat-emoji-toggle');
        if (input) { input.disabled = true; input.placeholder = msg; }
        if (btn) btn.disabled = true;
        if (emo) emo.style.display = 'none';
    }

    function enableInput() {
        const input = document.getElementById('chat-input');
        const btn = document.getElementById('chat-send-btn');
        const emo = document.getElementById('chat-emoji-toggle');
        if (input) { input.disabled = false; input.placeholder = 'پیام خود را بنویسید...'; }
        if (btn) btn.disabled = false;
        if (emo) emo.style.display = 'flex';
    }

    // ========= WELCOME POPUP =========
    function showWelcomePopup() {
        if (localStorage.getItem(WELCOME_KEY)) return;
        if (chatToken) return; // Already chatted before

        setTimeout(() => {
            const popup = document.getElementById('chat-welcome-popup');
            if (popup && !isOpen) {
                popup.style.display = 'block';
                localStorage.setItem(WELCOME_KEY, '1');
            }
        }, 4000);
    }

    // ========= INIT =========
    function init() {
        buildWidget();

        // Events
        document.getElementById('chat-toggle-btn')?.addEventListener('click', toggleChat);
        document.getElementById('chat-close-btn')?.addEventListener('click', closeChat);
        document.getElementById('chat-emoji-toggle')?.addEventListener('click', toggleEmojiBar);
        document.getElementById('chat-popup-close')?.addEventListener('click', () => {
            document.getElementById('chat-welcome-popup').style.display = 'none';
        });
        document.getElementById('chat-popup-open')?.addEventListener('click', () => {
            document.getElementById('chat-welcome-popup').style.display = 'none';
            toggleChat();
        });
        document.getElementById('chat-send-btn')?.addEventListener('click', sendMessage);

        const chatInput = document.getElementById('chat-input');
        chatInput?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        chatInput?.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 80) + 'px';
        });

        showWelcomePopup();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
