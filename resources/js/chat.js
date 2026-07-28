const OTHER_LABEL_ADMIN = '🛡️ ผู้ดูแลระบบ';
const SELF_LABEL_ADMIN = 'คุณ (Admin)';
const SELF_LABEL_USER = 'คุณ';

function labelFor(root, isMe, payload) {
    const role = root.dataset.viewerRole;

    if (role === 'buyer-seller') {
        return null;
    }

    if (role === 'report-admin') {
        return isMe ? SELF_LABEL_ADMIN : payload.user_name;
    }

    // report-user
    return isMe ? SELF_LABEL_USER : OTHER_LABEL_ADMIN;
}

function appendMessage(root, chatBox, payload, isMe) {
    const emptyState = chatBox.querySelector('[data-chat-empty]');
    if (emptyState) {
        emptyState.remove();
    }

    const wrapper = document.createElement('div');
    wrapper.className = `mb-2 d-flex ${isMe ? 'justify-content-end' : 'justify-content-start'}`;

    const bubble = document.createElement('div');
    bubble.className = `p-2 rounded ${isMe ? 'bg-primary text-white' : 'bg-light'}`;
    bubble.style.maxWidth = '70%';

    const label = labelFor(root, isMe, payload);
    if (label) {
        const labelEl = document.createElement('div');
        labelEl.className = 'small fw-bold';
        labelEl.textContent = label;
        bubble.appendChild(labelEl);
    }

    const textEl = document.createElement('div');
    textEl.textContent = payload.message;
    bubble.appendChild(textEl);

    const timeEl = document.createElement('div');
    timeEl.className = `small ${isMe ? 'text-white-50' : 'text-muted'}`;
    timeEl.style.fontSize = '10px';
    timeEl.textContent = payload.created_at;
    bubble.appendChild(timeEl);

    wrapper.appendChild(bubble);
    chatBox.appendChild(wrapper);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function showFormError(form, message) {
    let errorEl = form.parentElement.querySelector('.chat-send-error');

    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'chat-send-error text-danger small mt-2';
        form.parentElement.appendChild(errorEl);
    }

    errorEl.textContent = message;
}

function closeForm(form) {
    const footer = form.parentElement;
    form.remove();

    const closedNotice = document.createElement('div');
    closedNotice.className = 'text-muted text-center small';
    closedNotice.textContent = 'แชทนี้ถูกปิดแล้ว ไม่สามารถส่งข้อความได้';
    footer.appendChild(closedNotice);
}

function initChatRoom(root) {
    const chatBox = root.querySelector('#chatBox');
    const form = root.querySelector('form[data-chat-form]');
    const channel = root.dataset.channel;
    const event = root.dataset.event;
    const currentUserId = Number(root.dataset.currentUserId);
    const readUrl = root.dataset.readUrl;

    // เลื่อนไปข้อความล่าสุดล่างสุดทันทีที่เปิดหน้าแชท (ไม่ผูกกับ Echo เพื่อให้ทำงานแม้ realtime ต่อไม่ได้)
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    if (!chatBox || !channel || !event || !window.Echo) {
        return;
    }

    // บอกให้ navbar-badge.js รู้ว่ากำลังเปิดดูห้องนี้อยู่ จะได้ไม่ต้องขึ้นแจ้งเตือนสีแดงซ้ำ
    window.__activeChatChannel = channel;

    window.Echo.private(channel).listen('.' + event, (payload) => {
        if (payload.user_id === currentUserId) {
            return;
        }

        appendMessage(root, chatBox, payload, false);

        if (readUrl) {
            window.axios.post(readUrl);
        }
    });

    if (!form) {
        return;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const input = form.querySelector('input[name="message"]');
        const text = input.value.trim();

        if (!text) {
            return;
        }

        window.axios.post(form.action, { message: text })
            .then((res) => {
                appendMessage(root, chatBox, res.data.message, true);
                input.value = '';
                input.focus();
            })
            .catch((err) => {
                const status = err.response?.status;

                if (status === 409) {
                    showFormError(form, err.response.data.error);
                    closeForm(form);
                } else if (status === 422) {
                    const errors = err.response.data.errors?.message;
                    showFormError(form, errors ? errors[0] : 'ส่งข้อความไม่สำเร็จ');
                } else {
                    showFormError(form, 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่');
                }
            });
    });
}

function moveItemToTop(list, item) {
    list.prepend(item);
}

function bumpItemBadge(item) {
    const badge = item.querySelector('[data-unread-badge]');
    if (!badge) {
        return;
    }

    const count = (parseInt(badge.textContent, 10) || 0) + 1;
    badge.textContent = count > 99 ? '99+' : count;
    badge.classList.remove('d-none');
}

// เปิดให้เห็นรายการ (ครั้งแรกที่มีห้องแชทโผล่มา ตอนก่อนหน้านี้ยังไม่มีเลยเลยโชว์ empty state ค้างอยู่)
function revealList(list) {
    list.style.display = '';

    const emptyState = list.parentElement && list.parentElement.querySelector('[data-chat-empty-state]');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
}

// สร้างแถวใหม่ในหน้ารายการแชทซื้อขาย ตอนมีคนเริ่มแชทกับเราเป็นครั้งแรกขณะเปิดหน้าค้างไว้
// (ห้องนี้ยังไม่เคยถูก render จากฝั่งเซิร์ฟเวอร์ เลยต้องสร้าง element เองล้วนๆ ด้วย textContent
// ป้องกัน XSS จากชื่อหนังสือ/ชื่อร้าน/ข้อความที่มาจาก broadcast payload)
function buildConversationItem(list, payload) {
    const a = document.createElement('a');
    a.href = list.dataset.chatShowUrlTemplate.replace('__ID__', payload.conversation_id);
    a.className = 'list-group-item list-group-item-action';
    a.dataset.conversationId = payload.conversation_id;

    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-3';

    let thumb;
    if (payload.book_cover_url) {
        thumb = document.createElement('img');
        thumb.src = payload.book_cover_url;
        thumb.style.width = '50px';
        thumb.style.height = '50px';
        thumb.style.objectFit = 'cover';
        thumb.className = 'rounded';
        thumb.alt = '';
    } else {
        thumb = document.createElement('div');
        thumb.className = 'bg-light rounded d-flex align-items-center justify-content-center';
        thumb.style.width = '50px';
        thumb.style.height = '50px';
        thumb.textContent = '📚';
    }
    row.appendChild(thumb);

    const body = document.createElement('div');
    body.className = 'flex-grow-1';

    const topLine = document.createElement('div');
    topLine.className = 'd-flex justify-content-between align-items-center';

    const title = document.createElement('strong');
    title.textContent = payload.book_title;
    topLine.appendChild(title);

    const metaWrap = document.createElement('div');
    metaWrap.className = 'd-flex align-items-center gap-2';

    const badge = document.createElement('span');
    badge.className = 'badge rounded-pill bg-danger';
    badge.setAttribute('data-unread-badge', '');
    badge.textContent = '1';
    metaWrap.appendChild(badge);

    const time = document.createElement('small');
    time.className = 'text-muted';
    time.setAttribute('data-updated-at', '');
    time.textContent = 'เมื่อสักครู่';
    metaWrap.appendChild(time);

    topLine.appendChild(metaWrap);
    body.appendChild(topLine);

    const otherLine = document.createElement('div');
    otherLine.className = 'small text-muted';
    otherLine.textContent = 'กับ ' + payload.sender_display_name;
    body.appendChild(otherLine);

    const preview = document.createElement('div');
    preview.className = 'small text-truncate fw-bold';
    preview.setAttribute('data-latest-message', '');
    preview.textContent = payload.message;
    body.appendChild(preview);

    row.appendChild(body);
    a.appendChild(row);

    return a;
}

// สร้างแถวใหม่ในหน้ารายการแชทรายงาน ตอนแอดมินเปิดแชทกับเราและตอบเป็นครั้งแรกขณะเปิดหน้าค้างไว้
function buildReportChatItem(list, payload) {
    const a = document.createElement('a');
    a.href = list.dataset.reportChatShowUrlTemplate.replace('__ID__', payload.report_chat_id);
    a.className = 'list-group-item list-group-item-action';
    a.dataset.reportChatId = payload.report_chat_id;

    const row = document.createElement('div');
    row.className = 'd-flex justify-content-between align-items-center';

    const left = document.createElement('div');

    const title = document.createElement('strong');
    title.textContent = 'เรื่อง: ' + payload.report_reason;
    left.appendChild(title);

    const countLine = document.createElement('div');
    countLine.className = 'small text-muted';

    const countEl = document.createElement('span');
    countEl.setAttribute('data-message-count', '');
    countEl.textContent = '1';
    countLine.appendChild(countEl);
    countLine.appendChild(document.createTextNode(' ข้อความ'));
    left.appendChild(countLine);

    row.appendChild(left);

    const right = document.createElement('div');
    right.className = 'd-flex align-items-center gap-2';

    const unreadBadge = document.createElement('span');
    unreadBadge.className = 'badge rounded-pill bg-danger';
    unreadBadge.setAttribute('data-unread-badge', '');
    unreadBadge.textContent = '1';
    right.appendChild(unreadBadge);

    const statusBadge = document.createElement('span');
    statusBadge.className = 'badge bg-success';
    statusBadge.textContent = 'กำลังสนทนา';
    right.appendChild(statusBadge);
    row.appendChild(right);

    a.appendChild(row);

    return a;
}

// อัปเดตแถวรายการแชท/แชทรายงานแบบสด (ย้ายขึ้นบนสุด + อัปเดตพรีวิว หรือสร้างแถวใหม่ถ้ายังไม่เคยมี)
// ตอนมีข้อความใหม่เข้ามาระหว่างที่เปิดหน้ารายการอยู่ ไม่ต้องรอ refresh หน้าเหมือนก่อน
function initChatList() {
    const list = document.querySelector('[data-chat-list]');

    if (!list || !window.Echo) {
        return;
    }

    const userId = list.dataset.currentUserId;

    if (!userId) {
        return;
    }

    const channel = window.Echo.private('chat-user.' + userId);

    channel.listen('.message.sent', (payload) => {
        let item = list.querySelector(`[data-conversation-id="${payload.conversation_id}"]`);

        if (item) {
            const preview = item.querySelector('[data-latest-message]');
            if (preview) {
                preview.textContent = payload.message;
                preview.classList.remove('d-none', 'text-muted');
                preview.classList.add('fw-bold');
            }

            const timeEl = item.querySelector('[data-updated-at]');
            if (timeEl) {
                timeEl.textContent = 'เมื่อสักครู่';
            }

            bumpItemBadge(item);
        } else {
            item = buildConversationItem(list, payload);
            revealList(list);
        }

        moveItemToTop(list, item);
    });

    channel.listen('.report-message.sent', (payload) => {
        let item = list.querySelector(`[data-report-chat-id="${payload.report_chat_id}"]`);

        if (item) {
            const countEl = item.querySelector('[data-message-count]');
            if (countEl) {
                countEl.textContent = (parseInt(countEl.textContent, 10) || 0) + 1;
            }

            // ช่องนี้ (chat-user.{userId}) ได้ event นี้เฉพาะตอนแอดมินตอบเท่านั้น จึงถือว่ายังไม่อ่านเสมอ
            bumpItemBadge(item);
        } else {
            item = buildReportChatItem(list, payload);
            revealList(list);
        }

        moveItemToTop(list, item);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-chat-root]').forEach(initChatRoom);
    initChatList();
});
