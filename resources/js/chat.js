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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-chat-root]').forEach(initChatRoom);
});
