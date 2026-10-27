'use strict';

// Show one shared heartbeat indicator while network-backed screens load.
(function initializeGlobalLoadingIndicator() {
    if (window.__uhLoadingIndicatorInitialized) { return; }
    window.__uhLoadingIndicatorInitialized = true;

    const originalFetch = window.fetch.bind(window);
    let activeRequests = 0;
    let showTimer = null;
    let indicator = null;

    const ensureIndicator = () => {
        if (indicator || !document.body) { return indicator; }
        indicator = document.createElement('div');
        indicator.className = 'uh-global-loading';
        indicator.setAttribute('role', 'status');
        indicator.setAttribute('aria-live', 'polite');
        indicator.setAttribute('aria-label', 'Loading');
        indicator.setAttribute('aria-hidden', 'true');
        indicator.innerHTML = '<span class="uh-global-loading-pulse" aria-hidden="true">' +
            '<svg viewBox="0 0 180 54" focusable="false">' +
            '<path d="M2 28h34l8-1 7-13 8 30 12-49 13 34 7-1h24l7 2 6-12 7 25 10-37 9 22h18" />' +
            '</svg></span><span class="uh-global-loading-label">Loading</span>';
        document.body.appendChild(indicator);
        return indicator;
    };

    window.fetch = function (...args) {
        activeRequests += 1;
        if (!showTimer) {
            showTimer = window.setTimeout(() => {
                showTimer = null;
                if (activeRequests > 0) {
                    const loadingIndicator = ensureIndicator();
                    if (loadingIndicator) {
                        loadingIndicator.setAttribute('aria-hidden', 'false');
                        loadingIndicator.classList.add('is-visible');
                    }
                }
            }, 180);
        }

        return originalFetch(...args).finally(() => {
            activeRequests = Math.max(0, activeRequests - 1);
            if (activeRequests === 0) {
                if (showTimer) {
                    window.clearTimeout(showTimer);
                    showTimer = null;
                }
                if (indicator) {
                    indicator.classList.remove('is-visible');
                    indicator.setAttribute('aria-hidden', 'true');
                }
            }
        });
    };
}());

// ── Auth helpers ────────────────────────────────────────
function uhGetUser() {
    try { return JSON.parse(sessionStorage.getItem('clinic_user') || 'null'); } catch { return null; }
}

function uhLogout() {
    sessionStorage.removeItem('clinic_user');
    // Clear the role cookie
    document.cookie = 'clinic_role=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';
    // POST to server-side logout so Laravel clears its side too
    fetch('/clinic/logout', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
    }).finally(() => { window.location.href = '/login'; });
}

// ── Page switching ──────────────────────────────────────
function uhShowPage(pageId, navEl) {
    document.querySelectorAll('.uh-page').forEach(p => p.classList.remove('active'));
    const page = document.getElementById('page-' + pageId);
    if (page) {
        page.classList.add('active');
        page.style.paddingLeft = '16px';
        page.style.paddingTop  = (pageId === 'dashboard') ? '16px' : '0';
        page.style.marginTop   = '0';
    }
    document.querySelectorAll('.uh-nav-item').forEach(n => n.classList.remove('active'));
    if (navEl) { navEl.classList.add('active'); }
    const title = document.getElementById('uh-page-title');
    if (title && navEl) {
        title.textContent = navEl.dataset.title
            || navEl.querySelector('span')?.textContent
            || '';
    }
    // Scroll content area back to top on page switch
    const content = document.querySelector('.uh-content');
    if (content) { content.scrollTop = 0; }
}

// ── Toast ───────────────────────────────────────────────
function uhToast(msg, type = 'success') {
    const c = document.getElementById('uh-toasts');
    if (!c) { return; }
    const t = document.createElement('div');
    t.className = `uh-toast uh-toast-${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => {
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 300);
    }, 3000);
}

function uhSystemDialog(message, options = {}) {
    return new Promise(resolve => {
        const previousFocus = document.activeElement;
        const dialog = document.createElement('dialog');
        const heading = document.createElement('h2');
        const content = document.createElement('p');
        const actions = document.createElement('div');
        const cancelButton = document.createElement('button');
        const confirmButton = document.createElement('button');
        const isConfirmation = options.confirm === true;
        const isPrompt = options.prompt === true;
        let promptInput = null;
        let promptLabel = null;
        let settled = false;

        dialog.className = 'uh-system-dialog';
        dialog.setAttribute('aria-labelledby', 'uh-system-dialog-title');
        dialog.setAttribute('aria-describedby', 'uh-system-dialog-message');
        heading.className = 'uh-system-dialog-title';
        heading.id = 'uh-system-dialog-title';
        heading.textContent = options.title || (isConfirmation ? 'Please confirm' : 'UniHealth');
        content.className = 'uh-system-dialog-message';
        content.id = 'uh-system-dialog-message';
        content.textContent = String(message);
        if (isPrompt) {
            if (Array.isArray(options.selectOptions)) {
                promptInput = document.createElement('select');
                promptInput.className = 'uh-system-dialog-input';
                promptInput.required = true;
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = options.placeholder || 'Select a person';
                promptInput.append(placeholder);
                options.selectOptions.forEach(option => {
                    const item = document.createElement('option');
                    item.value = String(option.value);
                    item.textContent = String(option.label);
                    promptInput.append(item);
                });
            } else {
                promptInput = document.createElement('textarea');
                promptInput.className = 'uh-system-dialog-input';
                promptInput.maxLength = options.maxLength || 2000;
                promptInput.rows = options.rows || 4;
                promptInput.value = options.value || '';
                promptInput.placeholder = options.placeholder || '';
            }
            if (options.label) {
                promptLabel = document.createElement('label');
                promptLabel.className = 'uh-system-dialog-label';
                promptLabel.textContent = options.label;
                promptLabel.append(promptInput);
            }
        }
        actions.className = 'uh-system-dialog-actions';
        cancelButton.className = 'uh-btn uh-btn-outline uh-system-dialog-cancel';
        cancelButton.type = 'button';
        cancelButton.textContent = options.cancelText || 'Cancel';
        confirmButton.className = options.danger
            ? 'uh-btn uh-btn-danger uh-system-dialog-confirm'
            : 'uh-btn uh-btn-primary uh-system-dialog-confirm';
        confirmButton.type = 'button';
        confirmButton.textContent = options.confirmText || 'OK';

        function finish(confirmed) {
            if (settled) { return; }
            if (confirmed && isPrompt && !promptInput.value.trim()) {
                promptInput.focus();
                return;
            }
            settled = true;
            dialog.close();
            dialog.remove();
            if (previousFocus instanceof HTMLElement && previousFocus.isConnected) {
                previousFocus.focus();
            }
            resolve(isPrompt ? (confirmed ? promptInput.value : null) : confirmed);
        }

        cancelButton.addEventListener('click', () => finish(false));
        confirmButton.addEventListener('click', () => finish(true));
        dialog.addEventListener('cancel', event => {
            event.preventDefault();
            finish(false);
        });

        if (isConfirmation || isPrompt) { actions.append(cancelButton); }
        if (!isConfirmation) {
            confirmButton.textContent = options.confirmText || (isPrompt ? 'Continue' : 'Close');
        }
        actions.append(confirmButton);
        dialog.append(heading, content);
        if (isPrompt) {
            if (promptLabel) {
                dialog.append(promptLabel);
            } else {
                dialog.append(promptInput);
            }
        }
        dialog.append(actions);
        document.body.append(dialog);
        dialog.showModal();
        (promptInput || confirmButton).focus();
    });
}

function uhConfirm(message, options = {}) {
    return uhSystemDialog(message, { ...options, confirm: true });
}

function uhAlert(message, options = {}) {
    return uhSystemDialog(message, options);
}

function uhPrompt(message, options = {}) {
    return uhSystemDialog(message, { ...options, prompt: true });
}

function uhEscapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[character]));
}

function uhPresenceText(person) {
    if (person?.is_online) {
        return 'Online';
    }

    const lastOnline = person?.last_online_at ? new Date(person.last_online_at) : null;
    if (!lastOnline || Number.isNaN(lastOnline.getTime())) {
        return 'Last online unavailable';
    }

    const elapsedSeconds = Math.max(0, Math.floor((Date.now() - lastOnline.getTime()) / 1000));
    if (elapsedSeconds < 60) {
        return 'Last seen just now';
    }
    if (elapsedSeconds < 3600) {
        const minutes = Math.floor(elapsedSeconds / 60);
        return 'Last seen ' + minutes + ' min' + (minutes === 1 ? '' : 's') + ' ago';
    }
    if (elapsedSeconds < 86400) {
        const hours = Math.floor(elapsedSeconds / 3600);
        return 'Last seen ' + hours + ' hour' + (hours === 1 ? '' : 's') + ' ago';
    }

    const days = Math.floor(elapsedSeconds / 86400);
    return 'Last seen ' + days + ' day' + (days === 1 ? '' : 's') + ' ago';
}

function uhSetChatPresence(person) {
    const status = document.querySelector('.uh-chat-active-status');
    if (!status) {
        return;
    }

    const isOnline = Boolean(person?.is_online);
    status.textContent = uhPresenceText(person);
    status.classList.remove('is-unavailable');
    status.classList.toggle('is-online', isOnline);
    status.classList.toggle('is-offline', !isOnline);
}

function uhRememberChatMessages(cache, channelId, messages) {
    const id = Number(channelId);
    cache.delete(id);
    cache.set(id, Array.isArray(messages) ? messages : []);
    while (cache.size > 10) {
        cache.delete(cache.keys().next().value);
    }
}

function uhFilterConversationMessages(messages, query) {
    const searchTerm = String(query || '').trim().toLowerCase();
    if (!searchTerm) {
        return Array.isArray(messages) ? messages : [];
    }

    return (Array.isArray(messages) ? messages : []).filter(message => {
        const body = message.is_unsent ? 'This message was unsent.' : message.body;
        return String(body || '').toLowerCase().includes(searchTerm);
    });
}

function uhToggleConversationSearch(inputId) {
    const input = document.getElementById(inputId);
    if (!input) {
        return;
    }

    const toggle = Array.from(document.querySelectorAll('.uh-chat-conversation-search-toggle'))
        .find(button => button.getAttribute('aria-controls') === inputId);
    if (input.hidden) {
        input.hidden = false;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
        input.focus();
        return;
    }

    input.value = '';
    input.hidden = true;
    if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
    }
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function uhResetConversationSearch(inputId) {
    const input = document.getElementById(inputId);
    if (!input) {
        return;
    }

    input.value = '';
    input.hidden = true;
    const toggle = Array.from(document.querySelectorAll('.uh-chat-conversation-search-toggle'))
        .find(button => button.getAttribute('aria-controls') === inputId);
    if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
    }
}

function uhMessageMarkup(messages, userId, initialsFor, colorFor) {
    let markup = '';
    let lastDate = '';
    const lastSeenMessage = messages.slice().reverse().find(message =>
        uhChatIsOwnMessage(message.sender_id, userId)
        && message.seen_at
        && !message.is_unsent
        && !message.is_system
    );
    messages.forEach(message => {
        const date = message.created_at ? uhFormatDate(message.created_at) : '';
        if (date && date !== lastDate) {
            markup += `<div class="uh-msg-date-sep">${uhEscapeHtml(date)}</div>`;
            lastDate = date;
        }

        const isMine = uhChatIsOwnMessage(message.sender_id, userId);
        const canChange = isMine && !message.is_unsent && !message.is_system;
        const actions = message.is_unsent ? [] : [
            ...(canChange ? [['edit', 'Edit'], ['unsend', 'Unsend']] : []),
            ['forward', 'Forward'],
            ['pin', message.is_pinned ? 'Unpin' : 'Pin'],
            ['report', 'Report'],
        ];
        const time = message.created_at ? uhFormatTime(message.created_at) : '';
        const body = message.is_unsent ? 'This message was unsent.' : message.body;
        const pinnedLabel = message.is_pinned
            ? '<span class="uh-msg-pinned" aria-label="Pinned message"><i class="fa fa-thumbtack"></i> Pinned</span>'
            : '';
        const editedLabel = message.edited_at && !message.is_unsent
            ? '<span class="uh-msg-edited">Edited</span>'
            : '';
        const seenLabel = lastSeenMessage && Number(lastSeenMessage.id) === Number(message.id)
            ? '<span class="uh-msg-seen-status" title="Seen by recipient"><i class="fa fa-check-double" aria-hidden="true"></i> Seen</span>'
            : '';
        const inlineImageMimes = ['image/jpeg', 'image/png', 'image/webp'];
        const attachmentMime = String(message.attachment_mime || '').toLowerCase();
        const attachmentName = uhEscapeHtml(message.attachment_name || 'Download attachment');
        const attachmentUrl = String(message.attachment_url || '');
        const safeAttachmentUrl = uhEscapeHtml(attachmentUrl);
        const downloadUrl = uhEscapeHtml(`${attachmentUrl}${attachmentUrl.includes('?') ? '&' : '?'}download=1`);
        const attachment = message.attachment_url
            ? inlineImageMimes.includes(attachmentMime)
                ? `<div class="uh-msg-attachment"><a class="uh-msg-image-link" href="${safeAttachmentUrl}" target="_blank" rel="noopener noreferrer"><img class="uh-msg-image" src="${safeAttachmentUrl}" alt="${attachmentName}" loading="lazy"></a><a class="uh-msg-download-link" href="${downloadUrl}" download="${attachmentName}" aria-label="Download ${attachmentName}"><i class="fa fa-download" aria-hidden="true"></i><span>Download</span></a></div>`
                : `<a class="uh-msg-file-link" href="${downloadUrl}" download="${attachmentName}"><i class="fa fa-paperclip" aria-hidden="true"></i><span>${attachmentName}</span><i class="fa fa-download" aria-hidden="true"></i></a>`
            : '';
        const menu = actions.map(([action, label]) =>
            `<button type="button" role="menuitem" data-message-action="${action}" data-message-id="${Number(message.id)}">${label}</button>`
        ).join('');
        const actionButton = actions.length
            ? `<button type="button" class="uh-msg-more" aria-label="Message actions" aria-haspopup="menu" aria-expanded="false" data-message-action="toggle" data-message-id="${Number(message.id)}"><i class="fa fa-ellipsis"></i></button><div class="uh-msg-menu" role="menu" hidden>${menu}</div>`
            : '';

        markup += `<div class="uh-msg-row ${isMine ? 'mine' : 'theirs'}" data-message-id="${Number(message.id)}">
            <div class="uh-msg-avatar ${colorFor(message.sender_id)} ${isMine ? 'hidden' : ''}">${uhChatAvatarContent(message.sender_picture_url, initialsFor(message.sender_name || '?'))}</div>
            <div class="uh-msg-body">
                ${message.is_forwarded ? '<span class="uh-msg-forwarded">Forwarded</span>' : ''}
                <div class="uh-msg-bubble ${message.is_unsent ? 'unsent' : ''}">${pinnedLabel}${attachment}${body ? uhEscapeHtml(body) : ''}${editedLabel}</div>
                <div class="uh-msg-actions"><span class="uh-msg-time">${uhEscapeHtml(time)}</span>${seenLabel}${actionButton}</div>
            </div>
        </div>`;
    });
    return markup;
}

async function uhPerformMessageAction(action, message, options) {
    const channelId = options.channelId;
    const messageId = message.id;
    let response;

    if (action === 'edit') {
        const body = await uhPrompt('Update your message.', {
            title: 'Edit message',
            value: message.body,
            label: 'Message text',
            confirmText: 'Save',
        });
        if (body === null) { return; }
        response = await uhPut(`/messages/${channelId}/${messageId}`, { body: body.trim() });
    } else if (action === 'unsend') {
        if (!await uhConfirm('This removes the message for everyone in this conversation.', {
            title: 'Unsend message?',
            confirmText: 'Unsend',
            danger: true,
        })) { return; }
        response = await uhDelete(`/messages/${channelId}/${messageId}`);
    } else if (action === 'forward') {
        const recipients = options.staffList()
            .filter(staff => Number(staff.id) !== Number(options.userId))
            .map(staff => ({ value: staff.id, label: `${staff.name} (${staff.role_label})` }));
        if (recipients.length === 0) {
            uhToast('No staff members are available to forward to.', 'error');
            return;
        }
        const recipientId = await uhPrompt('Choose who should receive this message.', {
            title: 'Forward message',
            selectOptions: recipients,
            confirmText: 'Forward',
        });
        if (recipientId === null) { return; }
        response = await uhPost(`/messages/${channelId}/${messageId}/forward`, {
            recipient_id: Number(recipientId),
        });
    } else if (action === 'pin') {
        response = await uhPatch(`/messages/${channelId}/${messageId}/pin`, {
            pinned: !message.is_pinned,
        });
    } else if (action === 'report') {
        if (!await uhConfirm('This message will be sent to clinic administrators for review.', {
            title: 'Report message?',
            confirmText: 'Report',
            danger: true,
        })) { return; }
        response = await uhPost(`/messages/${channelId}/${messageId}/report`, {});
    }

    if (!response || !response.ok) {
        uhToast(response?.error || 'Could not complete that message action. Please try again.', 'error');
        return;
    }

    if (action === 'forward') {
        uhToast(`Message forwarded to ${response.channel.recipient_name}.`);
    } else if (action === 'pin') {
        uhToast(message.is_pinned ? 'Message unpinned.' : 'Message pinned.');
    } else if (action === 'report') {
        uhToast(response.already_reported ? 'You already reported this message.' : 'Message reported for review.');
    } else {
        uhToast(action === 'edit' ? 'Message updated.' : 'Message unsent.');
    }

    await options.refresh();
    if (action === 'forward') {
        await options.loadConversations();
    }
}

function uhChatAvatarContent(pictureUrl, initials) {
    const safeInitials = String(initials || '?').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[character]));
    const safePictureUrl = /^\/api\/clinic\/messages\/staff\/\d+\/profile-picture$/.test(pictureUrl || '');
    const picture = safePictureUrl
        ? `<img src="${pictureUrl}" alt="" aria-hidden="true" onerror="this.remove()">`
        : '';

    return picture + `<span>${safeInitials}</span>`;
}

function uhChatIsOwnMessage(senderId, currentUserId) {
    if (senderId === null || senderId === undefined || currentUserId === null || currentUserId === undefined) {
        return false;
    }

    return String(senderId) === String(currentUserId);
}

function uhParseDateTime(value) {
    if (!value) { return null; }
    if (value instanceof Date) { return Number.isNaN(value.getTime()) ? null : value; }

    const raw = String(value).trim();
    if (!raw) { return null; }

    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        const date = new Date(`${raw}T00:00:00+08:00`);
        return Number.isNaN(date.getTime()) ? null : date;
    }

    const normalized = raw.includes(' ') ? raw.replace(' ', 'T') : raw;
    const withTimezone = /^\d{4}-\d{2}-\d{2}T/.test(normalized)
        && !/(?:Z|[+-]\d{2}:?\d{2})$/i.test(normalized)
        ? `${normalized}+08:00`
        : normalized;
    const date = new Date(withTimezone);
    return Number.isNaN(date.getTime()) ? null : date;
}

function uhFormatDate(value, options) {
    const date = uhParseDateTime(value);
    if (!date) { return value ? String(value) : '—'; }

    return new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...(options || {}),
    }).format(date);
}

function uhManilaDateKey(value) {
    const date = uhParseDateTime(value);
    if (!date) { return ''; }
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(date);
    return parts.filter(part => part.type !== 'literal').map(part => part.value).join('-');
}

function uhFormatTime(value) {
    if (!value) { return '—'; }
    const raw = String(value).trim();

    if (/^\d{1,2}:\d{2}(?::\d{2})?$/.test(raw)) {
        const [hourText, minuteText] = raw.split(':');
        const hour = Number(hourText);
        const minute = Number(minuteText);
        if (hour > 23 || minute > 59) { return raw; }
        return `${hour % 12 || 12}:${String(minute).padStart(2, '0')} ${hour >= 12 ? 'PM' : 'AM'}`;
    }

    const date = uhParseDateTime(raw);
    if (!date) { return raw; }
    return new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(date);
}

function uhFormatDateTime(value) {
    const date = uhParseDateTime(value);
    if (!date) { return value ? String(value) : '—'; }

    return new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(date);
}

// ── Clock ────────────────────────────────────────────────
function uhStartClock() {
    const el = document.getElementById('uh-clock');
    if (!el) { return; }
    const tick = () => {
        el.textContent = uhFormatTime(new Date());
    };
    tick();
    setInterval(tick, 60000);
}

function uhStartTopbarDate() {
    const dateEl = document.getElementById('uh-topbar-date-value');
    const timeEl = document.getElementById('uh-topbar-time-value');
    if (!dateEl || !timeEl) { return; }

    const tick = () => {
        const now = new Date();
        dateEl.textContent = uhFormatDate(now);
        timeEl.textContent = uhFormatTime(now);
    };
    tick();
    setInterval(tick, 60000);
}

async function uhSavePassword(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const saveButton = document.getElementById('uh-password-save-button');
    const currentPassword = form.elements.current_password.value;
    const newPassword = form.elements.new_password.value;
    const confirmation = form.elements.new_password_confirmation.value;
    if (newPassword !== confirmation) {
        uhToast('New passwords do not match.', 'error');
        return;
    }

    saveButton.disabled = true;
    saveButton.textContent = 'Updating...';
    try {
        const response = await uhPut('/account/profile', {
            current_password: currentPassword,
            new_password: newPassword,
            new_password_confirmation: confirmation,
        });
        if (!response.ok || response.error) {
            uhToast(response.error || 'Password could not be changed.', 'error');
            return;
        }
        form.reset();
        document.getElementById('uh-password-modal').hidden = true;
        uhToast('Password updated successfully.', 'success');
    } catch (error) {
        uhToast('Password could not be changed.', 'error');
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = 'Update password';
    }
}

// ── Populate sidebar user info ──────────────────────────
function uhInitUser() {
    const user = uhGetUser();
    if (!user) { return; }

    const nameEl   = document.getElementById('uh-user-name');
    const roleEl   = document.getElementById('uh-user-role');
    const idEl     = document.getElementById('uh-user-id');
    const avatarEl = document.getElementById('uh-user-avatar');
    const roleDD   = document.getElementById('uh-role-dropdown');
    const roleText = roleDD?.querySelector('.uh-role-dropdown-text');
    const campusEl = document.getElementById('uh-campus-name');

    const fullName = [user.firstName, user.lastName].filter(Boolean).join(' ') || user.name || 'User';
    const initial  = fullName.charAt(0).toUpperCase();

    if (nameEl)   { nameEl.textContent   = fullName; }
    if (avatarEl) { avatarEl.textContent = initial; }
    if (roleEl)   { roleEl.textContent   = uhRoleLabel(user.role, user.patientType); }
    if (idEl)     { idEl.textContent     = 'ID: ' + (user.identifier || '—'); }
    if (roleText) { roleText.textContent = uhRoleLabel(user.role, user.patientType); }
    if (campusEl && user.campusName) { campusEl.textContent = user.campusName; }
    uhSetProfileAvatar(user.profilePicture || user.profile_picture, fullName);
    const greeting = document.getElementById('uh-sidebar-greeting');
    if (greeting) {
        const hour = new Date().getHours();
        const salutation = hour < 12 ? 'Bright morning' : hour < 18 ? 'Bright afternoon' : 'Bright evening';
        greeting.textContent = `${salutation}, ${fullName.split(' ')[0]}!`;
    }

    const badge = document.getElementById('uh-role-badge');
    if (badge) { badge.textContent = uhRoleLabel(user.role, user.patientType); }
}

function uhCloseAccountPanel() {
    const button = document.getElementById('uh-role-dropdown');
    const panel = document.getElementById('uh-account-panel');
    if (panel) { panel.hidden = true; }
    if (button) { button.setAttribute('aria-expanded', 'false'); }
}

function uhSetProfileAvatar(picture, fullName) {
    const initial = (fullName || 'User').charAt(0).toUpperCase();
    const avatarInitial = document.getElementById('uh-profile-avatar-initial');
    const avatarImage = document.getElementById('uh-profile-avatar-image');
    const sidebarAvatar = document.getElementById('uh-user-avatar');
    if (avatarInitial) { avatarInitial.textContent = initial; }
    if (sidebarAvatar) {
        sidebarAvatar.textContent = initial;
        sidebarAvatar.style.backgroundImage = picture ? `url("${picture}")` : '';
        sidebarAvatar.classList.toggle('uh-user-avatar-image', Boolean(picture));
    }
    if (avatarImage) {
        avatarImage.hidden = !picture;
        if (picture) { avatarImage.src = picture; }
    }
    if (avatarInitial) { avatarInitial.hidden = Boolean(picture); }
}

function uhPopulateProfileForm(profile) {
    const form = document.getElementById('uh-profile-form');
    if (!form) { return; }
    ['first_name', 'last_name', 'middle_name', 'contact', 'address', 'department', 'course_year', 'section', 'sex', 'civil_status', 'specialization']
        .forEach((field) => {
            const input = form.elements[field];
            if (input) { input.value = profile[field] || ''; }
        });
    const accountId = document.getElementById('uh-profile-account-id');
    const email = document.getElementById('uh-profile-email');
    const campus = document.getElementById('uh-profile-campus');
    if (accountId) { accountId.textContent = profile.account_id || profile.student_id || profile.employee_id || '—'; }
    if (email) { email.textContent = profile.email || '—'; }
    if (campus) { campus.textContent = profile.campus_name || '—'; }
}

function uhIsEmployeePatient(user) {
    return ['instructor_faculty', 'faculty', 'instructor', 'staff', 'faculty_staff'].includes(user?.patientType)
        || document.body?.dataset.clinicRole === 'employee';
}

async function uhOpenAccountModal() {
    const user = uhGetUser() || {};
    const fullName = [user.firstName, user.lastName].filter(Boolean).join(' ') || user.name || 'User';
    const modal = document.getElementById('uh-account-modal');
    const title = document.getElementById('uh-account-modal-title');
    if (!modal || !title) { return; }
    title.textContent = 'Profile';
    uhPopulateProfileForm(user);
    uhSetProfileAvatar(user.profilePicture || user.profile_picture, fullName);
    const isEmployeePatient = uhIsEmployeePatient(user);
    document.querySelectorAll('[data-student-profile-field]').forEach((field) => {
        field.hidden = user.role !== 'student'
            || (isEmployeePatient && field.hasAttribute('data-student-academic-profile-field'));
    });
    document.querySelectorAll('[data-staff-profile-field]').forEach((field) => {
        field.hidden = user.role === 'student';
    });
    modal.hidden = false;

    try {
        const response = await uhGet('/account/profile');
        if (response.profile) {
            uhPopulateProfileForm(response.profile);
            uhSetProfileAvatar(response.profile.profile_picture, `${response.profile.first_name || ''} ${response.profile.last_name || ''}`.trim());
            sessionStorage.setItem('clinic_user', JSON.stringify(uhSessionProfile(user, response.profile)));
            window.__clinicProfile = response.profile;
        } else if (response.error) {
            uhToast(response.error, 'error');
        }
    } catch (error) {
        uhToast('Profile could not be loaded.', 'error');
    }
}

function uhOpenPasswordModal() {
    const modal = document.getElementById('uh-password-modal');
    const form = document.getElementById('uh-password-form');
    if (!modal || !form) { return; }
    form.reset();
    modal.hidden = false;
    form.elements.current_password?.focus();
}

function uhInitAccountMenu() {
    const button = document.getElementById('uh-role-dropdown');
    const panel = document.getElementById('uh-account-panel');
    const modal = document.getElementById('uh-account-modal');
    const modalClose = document.getElementById('uh-account-modal-close');
    if (!button || !panel) { return; }

    const user = uhGetUser() || {};
    const fullName = [user.firstName, user.lastName].filter(Boolean).join(' ') || user.name || 'Account';
    const name = document.getElementById('uh-account-panel-name');
    const id = document.getElementById('uh-account-panel-id');
    if (name) { name.textContent = fullName; }
    if (id) { id.textContent = user.identifier ? `ID: ${user.identifier}` : ''; }

    button.addEventListener('click', () => {
        const isOpen = !panel.hidden;
        panel.hidden = isOpen;
        button.setAttribute('aria-expanded', String(!isOpen));
    });

    panel.addEventListener('click', (event) => {
        const action = event.target.closest('[data-account-action]')?.dataset.accountAction;
        if (!action) { return; }
        uhCloseAccountPanel();
        if (action === 'profile') { uhOpenAccountModal(); }
        if (action === 'password') { uhOpenPasswordModal(); }
        if (action === 'logout') { uhLogout(); }
    });

    if (modal && modalClose) {
        modalClose.addEventListener('click', () => { modal.hidden = true; });
        modal.addEventListener('click', (event) => {
            if (event.target === modal) { modal.hidden = true; }
        });
    }
    const passwordModal = document.getElementById('uh-password-modal');
    const passwordModalClose = document.getElementById('uh-password-modal-close');
    if (passwordModal && passwordModalClose) {
        passwordModalClose.addEventListener('click', () => { passwordModal.hidden = true; });
        passwordModal.addEventListener('click', (event) => {
            if (event.target === passwordModal) { passwordModal.hidden = true; }
        });
    }

    const profileForm = document.getElementById('uh-profile-form');
    const photoButton = document.getElementById('uh-profile-photo-button');
    const photoInput = document.getElementById('uh-profile-photo-input');
    if (photoButton && photoInput) {
        photoButton.addEventListener('click', () => photoInput.click());
        photoInput.addEventListener('change', uhHandleProfilePhoto);
    }
    if (profileForm) {
        profileForm.addEventListener('submit', uhSaveProfile);
    }
    const passwordForm = document.getElementById('uh-password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', uhSavePassword);
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.uh-account-menu')) { uhCloseAccountPanel(); }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            uhCloseAccountPanel();
            if (modal) { modal.hidden = true; }
            if (passwordModal) { passwordModal.hidden = true; }
        }
    });

}

async function uhHandleProfilePhoto(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) { return; }
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        uhToast('Choose a JPG, PNG, or WEBP image.', 'error');
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        uhToast('Image must be 2 MB or smaller.', 'error');
        return;
    }

    const user = uhGetUser() || {};
    const reader = new FileReader();
    reader.onerror = () => uhToast('The selected photo could not be read.', 'error');
    reader.onload = async () => {
        try {
            const picture = reader.result;
            const response = await uhPost('/account/profile/picture', { picture });
            if (!response.ok || response.error || !response.picture) {
                uhToast(response.error || 'Profile photo could not be saved.', 'error');
                return;
            }
            const updatedUser = { ...user, profilePicture: response.picture };
            sessionStorage.setItem('clinic_user', JSON.stringify(updatedUser));
            uhSetProfileAvatar(response.picture, updatedUser.name);
            uhToast('Profile photo updated.', 'success');
        } catch (error) {
            uhToast('Profile photo could not be saved.', 'error');
        }
    };
    reader.readAsDataURL(file);
}

function uhSessionProfile(user, profile) {
    const updatedUser = {
        ...user,
        name: `${profile.first_name || ''} ${profile.last_name || ''}`.trim(),
        firstName: profile.first_name || '',
        lastName: profile.last_name || '',
        middleName: profile.middle_name || '',
        contact: profile.contact || '',
        profilePicture: profile.profile_picture || null,
        campusName: profile.campus_name || user.campusName || '',
    };
    if (user.role === 'student') {
        Object.assign(updatedUser, {
            dept: profile.department || '',
            section: profile.section || '',
            address: profile.address || '',
            sex: profile.sex || '',
            civilStatus: profile.civil_status || '',
            courseYear: profile.course_year || '',
        });
    } else {
        updatedUser.specialization = profile.specialization || '';
    }
    return updatedUser;
}

async function uhSaveProfile(event) {
    event.preventDefault();
    const user = uhGetUser() || {};
    const form = event.currentTarget;
    const saveButton = document.getElementById('uh-profile-save-button');
    const fields = user.role === 'student'
        ? [
            'first_name', 'last_name', 'middle_name', 'contact', 'address', 'department',
            ...(uhIsEmployeePatient(user) ? [] : ['course_year', 'section']),
            'sex', 'civil_status',
        ]
        : ['first_name', 'last_name', 'middle_name', 'contact', 'specialization'];
    const payload = {};
    fields
        .forEach((field) => { payload[field] = form.elements[field]?.value.trim() || ''; });
    saveButton.disabled = true;
    saveButton.textContent = 'Saving...';
    try {
        const response = await uhPut('/account/profile', payload);
        if (!response.ok || response.error || !response.profile) {
            uhToast(response.error || 'Profile could not be saved.', 'error');
            return;
        }
        const profile = response.profile;
        const updatedUser = uhSessionProfile(user, profile);
        sessionStorage.setItem('clinic_user', JSON.stringify(updatedUser));
        uhPopulateProfileForm(profile);
        uhSetProfileAvatar(profile.profile_picture || updatedUser.profilePicture, updatedUser.name);
        uhInitUser();
        document.getElementById('uh-account-panel-name').textContent = updatedUser.name || 'Account';
        uhToast('Profile updated successfully.', 'success');
    } catch (error) {
        uhToast('Profile could not be saved.', 'error');
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = 'Save changes';
    }

}

function uhRoleLabel(role, patientType) {
    if (role === 'student') {
        const typeMap = {
            instructor_faculty: 'Instructor / Faculty',
            faculty: 'Instructor / Faculty',
            instructor: 'Instructor / Faculty',
            staff: 'Staff',
            faculty_staff: 'Category review required',
            student: 'Student',
        };
        if (document.body?.dataset.clinicRole === 'employee' && !typeMap[patientType]) {
            return 'Employee';
        }
        return typeMap[patientType] || 'Patient / Student';
    }
    const map = {
        nurse:      'Nurse',
        nurse_head: 'Head Nurse',
        physician:  'Physician',
        dentist:    'Dentist',
        admin:      'Admin',
    };
    return map[role] || (role ? role.charAt(0).toUpperCase() + role.slice(1) : 'User');
}

function uhPatientTypeLabel(patientType) {
    const labels = {
        student: 'Student',
        instructor_faculty: 'Instructor / Faculty',
        faculty: 'Instructor / Faculty',
        instructor: 'Instructor / Faculty',
        staff: 'Staff',
        faculty_staff: 'Category review required',
    };
    return labels[patientType] || 'Patient type unavailable';
}

function uhPatientTypeBadge(patientType) {
    const isEmployee = ['instructor_faculty', 'faculty', 'instructor', 'staff', 'faculty_staff'].includes(patientType);
    const isStudent = patientType === 'student';
    const category = isEmployee ? 'employee' : isStudent ? 'student' : 'unknown';
    return '<span class="uh-patient-type-badge uh-patient-type-badge--' + category + '">' +
        uhPatientTypeLabel(patientType) + '</span>';
}

function uhSetNotificationBadge(unreadCount) {
    const badge = document.getElementById('notif-badge');
    const count = Math.max(0, Number(unreadCount) || 0);
    if (badge) {
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.classList.toggle('uh-hidden', count === 0);
        badge.setAttribute('aria-label', count + ' unread notifications');
    }

    const topbarCount = document.getElementById('uh-notif-count');
    if (topbarCount) {
        topbarCount.textContent = count > 99 ? '99+' : String(count);
        topbarCount.style.display = count ? 'flex' : 'none';
    }
}

async function uhRefreshNotifications() {
    const user = uhGetUser();
    if (!user || !user.id) { return; }

    if (typeof sLoadNotifications === 'function') {
        sLoadNotifications(user);
        return;
    }

    await uhLoadNotifications();
}

function uhOpenNotificationsPage(navItem) {
    uhShowPage('notifications', navItem);
    uhRefreshNotifications();
}

async function uhLoadNotifications(markAsRead = false) {
    const user = uhGetUser();
    const list = document.getElementById('uh-notif-page-list')
        || document.getElementById('uh-notif-list');
    if (!user || !user.id) { return; }

    const recipientType = user.role === 'student' ? 'student' : 'staff';
    let data;
    try {
        data = await uhGet('/notifications?recipient_id=' + encodeURIComponent(user.id) +
            '&recipient_type=' + recipientType + '&limit=10');
        if (data.ok === false || data.error) {
            throw new Error(data.error || 'The notifications request failed.');
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
        if (list) {
            list.innerHTML = '<p class="uh-notif-empty">Notifications could not be loaded.</p>';
        }
        return;
    }

    const notifications = Array.isArray(data.notifications) ? data.notifications : [];
    const unreadCount = Math.max(0, Number(data.unread_count) || 0);
    uhSetNotificationBadge(unreadCount);

    if (list) {
        list.replaceChildren();
        if (!notifications.length) {
            const empty = document.createElement('p');
            empty.className = 'uh-notif-empty';
            empty.textContent = 'No notifications yet.';
            list.appendChild(empty);
        } else {
            notifications.forEach(function (notification) {
                const item = document.createElement('div');
                item.className = 'uh-notif-row' + (notification.is_read ? '' : ' unread');
                item.dataset.notificationId = notification.id;
                item.setAttribute('role', 'button');
                item.tabIndex = 0;

                const icon = document.createElement('span');
                icon.className = 'uh-notif-row-icon';
                icon.innerHTML = '<i class="fa fa-bell" aria-hidden="true"></i>';

                const content = document.createElement('span');
                content.className = 'uh-notif-row-content';
                const title = document.createElement('strong');
                title.textContent = notification.title || 'Notification';
                const body = document.createElement('small');
                body.textContent = notification.body || '';
                const createdAt = document.createElement('em');
                createdAt.textContent = uhFormatDateTime(notification.created_at);
                content.append(title, body, createdAt);
                item.append(icon, content);

                const markRead = async function () {
                    if (notification.is_read) { return; }

                    try {
                        const response = await uhPatch('/notifications/' + notification.id + '/read', {});
                        if (response.ok === false || response.error) {
                            throw new Error(response.error || 'The notification could not be marked as read.');
                        }
                        await uhLoadNotifications();
                    } catch (error) {
                        console.error('Failed to mark notification as read:', error);
                        uhToast('The notification could not be marked as read.', 'error');
                    }
                };

                const openNotification = async function () {
                    await markRead();
                    if (notification.type === 'prescription_issued') {
                        uhShowPage('prescriptions', document.querySelector('[data-title="Prescriptions"]'));
                    }
                };

                item.addEventListener('click', openNotification);
                item.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openNotification();
                    }
                });
                list.appendChild(item);
            });
        }
    }

    if (markAsRead && unreadCount) {
        await uhMarkAllNotificationsRead(user, recipientType);
        await uhLoadNotifications();
    }
}

async function uhMarkAllNotificationsRead(user, recipientType) {
    return uhPost('/notifications/mark-all-read?recipient_id=' + encodeURIComponent(user.id) +
        '&recipient_type=' + recipientType, {});
}

function uhInitNotifications() {
    const button = document.getElementById('uh-notif-btn');
    const panel = document.getElementById('uh-notif-panel');
    if (button && panel && !button.dataset.bound) {
        button.dataset.bound = 'true';
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            const isHidden = panel.hidden;
            panel.hidden = !isHidden;
            button.setAttribute('aria-expanded', String(isHidden));
            if (isHidden) { uhLoadNotifications(true); }
        });
        const readAll = document.getElementById('uh-notif-read-all');
        if (readAll) {
            readAll.addEventListener('click', async function () {
                const user = uhGetUser();
                if (!user || !user.id) { return; }
                const recipientType = user.role === 'student' ? 'student' : 'staff';
                await uhMarkAllNotificationsRead(user, recipientType);
                uhLoadNotifications();
            });
        }
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.uh-notif-wrap')) {
                panel.hidden = true;
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }

    uhRefreshNotifications();
    if (!window.uhNotificationRefreshTimer) {
        window.uhNotificationRefreshTimer = window.setInterval(function () {
            if (!document.hidden) { uhRefreshNotifications(); }
        }, 30000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) { uhRefreshNotifications(); }
        });
    }
}

document.addEventListener('DOMContentLoaded', uhInitNotifications);

async function uhMarkAllNotificationsReadFromPage() {
    const user = uhGetUser();
    if (!user || !user.id) { return; }

    const recipientType = user.role === 'student' ? 'student' : 'staff';
    try {
        const response = await uhMarkAllNotificationsRead(user, recipientType);
        if (response.ok === false || response.error) {
            throw new Error(response.error || 'The notifications could not be marked as read.');
        }
        uhSetNotificationBadge(0);
        await uhRefreshNotifications();
        uhToast('All notifications marked as read.', 'info');
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
        uhToast('Notifications could not be marked as read.', 'error');
    }
}

// ── API helpers ─────────────────────────────────────────
const UH_BASE = '/api/clinic';

const _csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';

async function uhGet(path) {
    const hasQuery = path.includes('?');
    const sep = hasQuery ? '&' : '?';
    const user = uhGetUser();
    let qs = '';
    if (user) {
        const p = [];
        if (user.id)       { p.push('staff_id='       + encodeURIComponent(user.id)); }
        if (user.id)       { p.push('admin_id='       + encodeURIComponent(user.id)); }
        // Only add campus params if not already in the path
        const hasCampusInPath = hasQuery && path.includes('campus_id=');
        if (user.campusId && !hasCampusInPath) {
            p.push('staff_campus_id='+ encodeURIComponent(user.campusId));
            p.push('campus_id='      + encodeURIComponent(user.campusId));
        }
        if (p.length) { qs = sep + p.join('&'); }
    }
    const r = await fetch(UH_BASE + path + qs, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    });
    const response = await _uhParse(r);
    if (response.status === 401 && response.error === 'Authentication required') {
        uhRedirectForExpiredSession();
    }
    return response;
}

function uhRedirectForExpiredSession() {
    if (window.__uhSessionRedirecting || !uhGetUser()) { return; }

    window.__uhSessionRedirecting = true;
    sessionStorage.removeItem('clinic_user');
    sessionStorage.setItem('clinic_session_expired', '1');
    document.cookie = 'clinic_role=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';
    window.location.replace('/login');
}

async function _uhParse(r) {
    // Read body as text first so we never crash on non-JSON (e.g. HTML 500 page)
    const text = await r.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (e) { data = { _raw: text }; }
    // Attach status so callers can branch on r.ok
    return Object.assign({ ok: r.ok, status: r.status }, data || {});
}

async function uhPost(path, body) {
    const r = await fetch(UH_BASE + path + uhUserParams(path), {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': _csrf(),
        },
        body: JSON.stringify(body),
    });
    return _uhParse(r);
}

function uhPostMessage(path, body, attachment) {
    const formData = new FormData();
    formData.append('body', body || '');
    if (attachment) {
        formData.append('attachment', attachment);
    }

    return fetch(UH_BASE + path + uhUserParams(path), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': _csrf(),
        },
        body: formData,
    }).then(_uhParse);
}

function uhPreviewMessageImage(input, previewId, imageId, nameId) {
    const file = input.files?.[0];
    if (!file) {
        return;
    }

    if (file.size > 50 * 1024 * 1024) {
        uhToast('Choose a file up to 50 MB.', 'error');
        uhClearMessageImage(input.id, previewId, imageId, nameId);
        return;
    }

    const image = document.getElementById(imageId);
    const preview = document.getElementById(previewId);
    const name = document.getElementById(nameId);
    if (!image || !preview || !name) {
        return;
    }

    if (image.dataset.objectUrl) {
        URL.revokeObjectURL(image.dataset.objectUrl);
        delete image.dataset.objectUrl;
    }
    if (['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        image.dataset.objectUrl = URL.createObjectURL(file);
        image.src = image.dataset.objectUrl;
        image.hidden = false;
    } else {
        image.removeAttribute('src');
        image.hidden = true;
    }
    name.textContent = file.name + ' (' + (file.size / (1024 * 1024)).toFixed(1) + ' MB)';
    preview.hidden = false;
}

function uhClearMessageImage(inputId, previewId, imageId, nameId) {
    const input = document.getElementById(inputId);
    const image = document.getElementById(imageId);
    const preview = document.getElementById(previewId);
    const name = document.getElementById(nameId);

    if (input) {
        input.value = '';
    }
    if (image) {
        if (image.dataset.objectUrl) {
            URL.revokeObjectURL(image.dataset.objectUrl);
            delete image.dataset.objectUrl;
        }
        image.removeAttribute('src');
        image.hidden = false;
    }
    if (name) {
        name.textContent = '';
    }
    if (preview) {
        preview.hidden = true;
    }
}

async function uhPatch(path, body) {
    const r = await fetch(UH_BASE + path + uhUserParams(path), {
        method:  'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': _csrf(),
        },
        body: JSON.stringify(body),
    });
    return _uhParse(r);
}

async function uhPut(path, body) {
    const r = await fetch(UH_BASE + path + uhUserParams(path), {
        method:  'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': _csrf(),
        },
        body: JSON.stringify(body),
    });
    return _uhParse(r);
}

async function uhDelete(path) {
    const r = await fetch(UH_BASE + path + uhUserParams(path), {
        method:  'DELETE',
        headers: {
            'Accept':       'application/json',
            'X-CSRF-TOKEN': _csrf(),
        },
    });
    return _uhParse(r);
}

async function uhDeleteConversationItem(channelId, action, recipientName) {
    const deletingPerson = action === 'person';
    const confirmed = await uhConfirm(
        deletingPerson
            ? `Hide ${recipientName} from your conversation list? This will not delete their account or affect anyone else's inbox.`
            : `Hide this conversation and its existing messages from your inbox? New messages will make it appear again.`,
        {
            title: deletingPerson ? 'Delete person?' : 'Delete conversation?',
            confirmText: deletingPerson ? 'Delete person' : 'Delete conversation',
            danger: true,
        },
    );
    if (!confirmed) {
        return false;
    }

    try {
        const result = await uhDelete(`/messages/${channelId}/${action}`);
        if (!result.ok) {
            uhToast(result.error || 'Could not delete this conversation.', 'error');
            return false;
        }
        uhToast(deletingPerson ? 'Person removed from your chat list.' : 'Conversation deleted from your inbox.');
        return true;
    } catch {
        uhToast('Could not delete this conversation. Check your connection and try again.', 'error');
        return false;
    }
}

/** Build user params, skipping campus if already in path */
function uhUserParams(path) {
    const user = uhGetUser();
    if (!user) { return ''; }
    const hasQuery = path.includes('?');
    const sep = hasQuery ? '&' : '?';
    const p = [];
    if (user.id)       { p.push('staff_id='       + encodeURIComponent(user.id)); }
    if (user.id)       { p.push('admin_id='       + encodeURIComponent(user.id)); }
    const hasCampusInPath = hasQuery && path.includes('campus_id=');
    if (user.campusId && !hasCampusInPath) {
        p.push('staff_campus_id='+ encodeURIComponent(user.campusId));
        p.push('campus_id='      + encodeURIComponent(user.campusId));
    }
    return p.length ? sep + p.join('&') : '';
}

// ── Modal helpers ────────────────────────────────────────
function uhOpenModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('hidden'); }
}

function uhCloseModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('hidden'); }
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('uh-modal-overlay')) {
        e.target.classList.add('hidden');
    }
});

// ── Status badge helper ──────────────────────────────────
function uhBadge(status) {
    const map = {
        completed:      'completed',
        confirmed:      'completed',
        approved:       'completed',
        pending:        'waiting',
        pending_review: 'waiting',
        'in-progress':  'in-progress',
        waiting:        'waiting',
        scheduled:      'scheduled',
        cancelled:      'cancelled',
        deleted:        'cancelled',
        rejected:       'cancelled',
        active:         'active',
        'low stock':    'urgent',
    };
    const cls = map[(status || '').toLowerCase()] || 'scheduled';
    return `<span class="uh-badge ${cls}">${status || '—'}</span>`;
}

// ── Table no-data row ────────────────────────────────────
function uhNoData(cols, msg = 'No records found.') {
    return `<tr><td colspan="${cols}" class="no-data">${msg}</td></tr>`;
}

// ── Table client-side search ─────────────────────────────
// Adds a text input above any `<table class="uh-table">` and filters visible
// rows by case-insensitive substring match against any cell. Works for both
// server-rendered tables (filter existing rows) and client-rendered tables
// (delegates to a renderer registered via `data-uh-render`).
//
// HTML pattern:
//   <div class="uh-table-toolbar">
//     <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search...">
//     <span class="uh-table-count" data-uh-table-count></span>
//   </div>
//   <div class="uh-table-wrap">
//     <table class="uh-table" data-uh-searchable>
//       ...
//     </table>
//   </div>
//
// The `data-uh-render` attribute on the table may point at a global renderer
// function that takes `(table, query)` and rebuilds `<tbody>` from data. The
// renderer owns its result count and pagination controls.
function uhInitTableSearch(root = document) {
    const tables = root.querySelectorAll('table.uh-table[data-uh-searchable]');
    tables.forEach((table) => {
        if (table._uhSearchBound) { return; }
        table._uhSearchBound = true;

        // The toolbar is the .uh-table-wrap's previous sibling — the
        // expected layout is toolbar → table-wrap → table.
        const wrap = table.parentElement;
        const toolbar = wrap && wrap.previousElementSibling
            && wrap.previousElementSibling.classList
            && wrap.previousElementSibling.classList.contains('uh-table-toolbar')
            ? wrap.previousElementSibling
            : null;
        const scope = table.closest('section, .uh-card, .uh-panel, .uh-page') || table.parentElement;
        const input = (toolbar || scope)?.querySelector('[data-uh-table-search]');
        if (!input) { return; }

        const countEl = (toolbar || scope)?.querySelector('[data-uh-table-count]');

        const apply = () => {
            const q = (input.value || '').trim().toLowerCase();
            const tbody = table.tBodies[0];
            if (!tbody) { return; }

            // If the table declared a renderer, hand off to it.
            const renderName = table.getAttribute('data-uh-render');
            if (renderName && typeof window[renderName] === 'function') {
                window[renderName](table, q);
                return;
            }

            // Otherwise filter existing rows in place.
            let visible = 0;
            Array.from(tbody.rows).forEach((row) => {
                if (row.classList.contains('no-data')) { return; }
                const text = row.textContent.toLowerCase();
                const match = !q || text.indexOf(q) !== -1;
                row.hidden = !match;
                if (match) { visible += 1; }
            });

            // Show a "no matches" row when filter eliminates everything.
            let emptyRow = tbody.querySelector('tr.uh-search-empty');
            if (visible === 0 && q) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'uh-search-empty';
                    const td = document.createElement('td');
                    td.colSpan = table.tHead && table.tHead.rows[0]
                        ? table.tHead.rows[0].cells.length
                        : 1;
                    td.className = 'no-data';
                    td.textContent = 'No matches for “' + input.value + '”.';
                    emptyRow.appendChild(td);
                    tbody.appendChild(emptyRow);
                }
            } else if (emptyRow) {
                emptyRow.remove();
            }

            if (countEl) {
                if (!q) {
                    countEl.textContent = '';
                } else {
                    countEl.textContent = visible === 0
                        ? 'No matches'
                        : `${visible} match${visible === 1 ? '' : 'es'}`;
                }
            }
        };

        input.addEventListener('input', apply);
        // Some pages repopulate tbody after fetch; re-apply the current filter
        // when the table's children change.
        if (!table.getAttribute('data-uh-render') && typeof MutationObserver === 'function' && table.tBodies[0]) {
            const mo = new MutationObserver(apply);
            mo.observe(table.tBodies[0], { childList: true });
            table._uhSearchObserver = mo;
        }
    });
}

// ── Paginated table renderer ─────────────────────────────
// Usage:
//   uhPaginate('my-tbody', rows, rowFn, cols, 'my-pagination')
//   rows    — full data array
//   rowFn   — function(item) => '<tr>...</tr>' string
//   cols    — number of columns (for the no-data cell)
//   pageEl  — id of the container where pagination controls are injected
const _uhPages = {};

function uhPaginate(tbodyId, rows, rowFn, cols, pageElId, perPage = 10) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) { return; }

    if (!rows.length) {
        tbody.innerHTML = uhNoData(cols);
        const pg = document.getElementById(pageElId);
        if (pg) { pg.innerHTML = ''; }
        return;
    }

    const totalPages = Math.ceil(rows.length / perPage);
    _uhPages[tbodyId] = _uhPages[tbodyId] || 1;
    let page = Math.min(_uhPages[tbodyId], totalPages);
    _uhPages[tbodyId] = page;

    const render = (p) => {
        _uhPages[tbodyId] = p;
        const slice = rows.slice((p - 1) * perPage, p * perPage);
        tbody.innerHTML = slice.map(rowFn).join('');

        const pg = document.getElementById(pageElId);
        if (!pg) { return; }

        if (totalPages <= 1) { pg.innerHTML = ''; return; }

        let html = '<div class="uh-pagination">';
        html += `<button class="uh-pg-btn" ${p === 1 ? 'disabled' : ''} onclick="(function(){document.getElementById('${tbodyId}') && _uhPgGo('${tbodyId}',${p - 1})})()">‹</button>`;

        // page window: always show first, last, current ±1
        const pages = new Set([1, totalPages, p, p - 1, p + 1].filter(n => n >= 1 && n <= totalPages));
        let prev = 0;
        [...pages].sort((a, b) => a - b).forEach(n => {
            if (prev && n - prev > 1) { html += '<span class="uh-pg-ellipsis">…</span>'; }
            html += `<button class="uh-pg-btn${n === p ? ' active' : ''}" onclick="_uhPgGo('${tbodyId}',${n})">${n}</button>`;
            prev = n;
        });

        html += `<button class="uh-pg-btn" ${p === totalPages ? 'disabled' : ''} onclick="_uhPgGo('${tbodyId}',${p + 1})">›</button>`;
        html += `<span class="uh-pg-info">${(p-1)*perPage+1}–${Math.min(p*perPage,rows.length)} of ${rows.length}</span>`;
        html += '</div>';
        pg.innerHTML = html;
    };

    render(page);
}

function _uhPgGo(tbodyId, page) {
    _uhPages[tbodyId] = page;
    // find and re-call the refresh function stored alongside
    const fn = _uhPgFns[tbodyId];
    if (fn) { fn(page); }
}

// Registry for per-table page-change callbacks
const _uhPgFns = {};

// Convenience wrapper: store rows + renderer so page-nav can re-render
function uhPaginateTable(tbodyId, rows, rowFn, cols, pageElId, perPage = 10) {
    _uhPgFns[tbodyId] = (p) => {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) { return; }
        const totalPages = Math.ceil(rows.length / perPage);
        const slice = rows.slice((p - 1) * perPage, p * perPage);
        tbody.innerHTML = slice.map(rowFn).join('');

        const pg = document.getElementById(pageElId);
        if (!pg) { return; }
        if (totalPages <= 1) { pg.innerHTML = ''; return; }

        let html = '<div class="uh-pagination">';
        html += `<button class="uh-pg-btn" ${p === 1 ? 'disabled' : ''} onclick="_uhPgGo('${tbodyId}',${p-1})">‹</button>`;
        const pages = new Set([1, totalPages, p, p-1, p+1].filter(n => n >= 1 && n <= totalPages));
        let prev = 0;
        [...pages].sort((a,b)=>a-b).forEach(n => {
            if (prev && n-prev > 1) { html += '<span class="uh-pg-ellipsis">…</span>'; }
            html += `<button class="uh-pg-btn${n===p?' active':''}" onclick="_uhPgGo('${tbodyId}',${n})">${n}</button>`;
            prev = n;
        });
        html += `<button class="uh-pg-btn" ${p===totalPages?'disabled':''} onclick="_uhPgGo('${tbodyId}',${p+1})">›</button>`;
        html += `<span class="uh-pg-info">${(p-1)*perPage+1}–${Math.min(p*perPage,rows.length)} of ${rows.length}</span>`;
        html += '</div>';
        pg.innerHTML = html;
    };
    _uhPages[tbodyId] = 1;
    _uhPgFns[tbodyId](1);
}

function uhPaginateServer(tbodyId, page, total, perPage, pageElId, onPageChange) {
    const container = document.getElementById(pageElId);
    if (!container) { return; }

    const totalPages = Math.ceil(total / perPage);
    _uhPages[tbodyId] = page;
    _uhPgFns[tbodyId] = onPageChange;
    if (totalPages <= 1) {
        container.replaceChildren();
        return;
    }

    let html = '<div class="uh-pagination">';
    html += `<button class="uh-pg-btn" ${page === 1 ? 'disabled' : ''} onclick="_uhPgGo('${tbodyId}',${page - 1})">‹</button>`;
    const pages = new Set([1, totalPages, page, page - 1, page + 1].filter(n => n >= 1 && n <= totalPages));
    let previous = 0;
    [...pages].sort((a, b) => a - b).forEach(n => {
        if (previous && n - previous > 1) { html += '<span class="uh-pg-ellipsis">…</span>'; }
        html += `<button class="uh-pg-btn${n === page ? ' active' : ''}" onclick="_uhPgGo('${tbodyId}',${n})">${n}</button>`;
        previous = n;
    });
    html += `<button class="uh-pg-btn" ${page === totalPages ? 'disabled' : ''} onclick="_uhPgGo('${tbodyId}',${page + 1})">›</button>`;
    html += `<span class="uh-pg-info">${(page - 1) * perPage + 1}–${Math.min(page * perPage, total)} of ${total}</span>`;
    html += '</div>';
    container.innerHTML = html;
}

// ── DOM ready ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    uhStartClock();
    uhInitUser();
    uhInitAccountMenu();
    uhInitTableSearch();
    uhStartTopbarDate();
    uhInitPresenceHeartbeat();
});

let uhPresenceHeartbeatTimer = null;

function uhInitPresenceHeartbeat() {
    const user = uhGetUser();
    if (!user || !['nurse', 'nurse_head', 'physician', 'dentist'].includes(user.role)) {
        return;
    }

    const heartbeat = () => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        uhPost('/messages/presence', {}).then(response => {
            if (!response.ok) {
                console.warn('Messaging presence heartbeat failed:', response.error || response.status);
            }
        }).catch(error => {
            console.warn('Messaging presence heartbeat failed:', error);
        });
    };

    heartbeat();
    if (uhPresenceHeartbeatTimer === null) {
        uhPresenceHeartbeatTimer = window.setInterval(heartbeat, 30000);
    }
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            heartbeat();
        }
    });
}

// ── Mobile drawer for sidebar ────────────────────────────
// Slide the sidebar in from the left on phones/tablets, with a dimmed backdrop.
// `force` can be true/false to set explicitly, or undefined to toggle.
function uhToggleDrawer(force) {
    const sb = document.querySelector('.uh-sidebar');
    const bd = document.querySelector('.uh-sidebar-backdrop');
    if (!sb) { return; }
    const isOpen = sb.classList.contains('uh-sidebar-open');
    const willOpen = (force === true) || (force === undefined && !isOpen);
    sb.classList.toggle('uh-sidebar-open', willOpen);
    if (bd) { bd.classList.toggle('uh-sidebar-backdrop-open', willOpen); }
    document.body.style.overflow = willOpen ? 'hidden' : '';
}

// Auto-close the drawer after a nav item is tapped on small screens
document.addEventListener('click', function (e) {
    const nav = e.target.closest('.uh-sidebar .uh-nav-item');
    if (nav && window.innerWidth <= 1024) {
        setTimeout(function () { uhToggleDrawer(false); }, 60);
    }
});

// Close the drawer on Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { uhToggleDrawer(false); }
});

// Auto-close the drawer if the viewport grows past the breakpoint
let _uhResizeTimer = null;
window.addEventListener('resize', function () {
    clearTimeout(_uhResizeTimer);
    _uhResizeTimer = setTimeout(function () {
        if (window.innerWidth > 1024) { uhToggleDrawer(false); }
    }, 150);
});
