function bumpBadge(badge) {
    const count = (parseInt(badge.textContent, 10) || 0) + 1;
    badge.textContent = count > 99 ? '99+' : count;
    badge.classList.remove('d-none');
    return count;
}

function isViewingChannel(channel) {
    return window.__activeChatChannel === channel;
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function buildHiddenCsrfInput() {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_token';
    input.value = csrfToken();
    return input;
}

function buildPostForm(action, buttonText, buttonClass) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.appendChild(buildHiddenCsrfInput());

    const btn = document.createElement('button');
    btn.className = buttonClass;
    btn.textContent = buttonText;
    form.appendChild(btn);

    return form;
}

// สร้าง modal แบนผู้ใช้ (เหมือน admin/reports.blade.php) แต่ผูก toggle ช่องจำนวนวันด้วย
// event listener ตรงๆ แทน onchange + ฟังก์ชันชื่อ global ต่อรายงาน (ไม่จำเป็นเมื่อสร้างผ่าน JS)
function buildBanModal(payload) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.tabIndex = -1;

    const dialog = document.createElement('div');
    dialog.className = 'modal-dialog';

    const content = document.createElement('div');
    content.className = 'modal-content';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = payload.ban_url;
    form.appendChild(buildHiddenCsrfInput());

    const header = document.createElement('div');
    header.className = 'modal-header';

    const title = document.createElement('h5');
    title.className = 'modal-title';
    title.textContent = 'แบนผู้ใช้: ' + payload.owner_display_name;
    header.appendChild(title);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'modal');
    header.appendChild(closeBtn);
    form.appendChild(header);

    const body = document.createElement('div');
    body.className = 'modal-body';

    const typeGroup = document.createElement('div');
    typeGroup.className = 'mb-3';

    const typeLabel = document.createElement('label');
    typeLabel.className = 'form-label';
    typeLabel.textContent = 'ประเภทการแบน';
    typeGroup.appendChild(typeLabel);

    const typeSelect = document.createElement('select');
    typeSelect.name = 'ban_type';
    typeSelect.className = 'form-select';
    typeSelect.required = true;

    const permOpt = document.createElement('option');
    permOpt.value = 'permanent';
    permOpt.textContent = 'แบนถาวร';
    typeSelect.appendChild(permOpt);

    const tempOpt = document.createElement('option');
    tempOpt.value = 'temporary';
    tempOpt.textContent = 'แบนชั่วคราว';
    typeSelect.appendChild(tempOpt);

    typeGroup.appendChild(typeSelect);
    body.appendChild(typeGroup);

    const daysGroup = document.createElement('div');
    daysGroup.className = 'mb-3';
    daysGroup.style.display = 'none';

    const daysLabel = document.createElement('label');
    daysLabel.className = 'form-label';
    daysLabel.textContent = 'จำนวนวัน';
    daysGroup.appendChild(daysLabel);

    const daysInput = document.createElement('input');
    daysInput.type = 'number';
    daysInput.name = 'days';
    daysInput.className = 'form-control';
    daysInput.min = '1';
    daysInput.placeholder = 'เช่น 7';
    daysGroup.appendChild(daysInput);
    body.appendChild(daysGroup);

    typeSelect.addEventListener('change', () => {
        daysGroup.style.display = typeSelect.value === 'temporary' ? 'block' : 'none';
    });

    form.appendChild(body);

    const footer = document.createElement('div');
    footer.className = 'modal-footer';

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn btn-secondary';
    cancelBtn.setAttribute('data-bs-dismiss', 'modal');
    cancelBtn.textContent = 'ยกเลิก';
    footer.appendChild(cancelBtn);

    const submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.className = 'btn btn-danger';
    submitBtn.textContent = 'ยืนยันแบน';
    footer.appendChild(submitBtn);
    form.appendChild(footer);

    content.appendChild(form);
    dialog.appendChild(content);
    modal.appendChild(dialog);

    return modal;
}

// สร้างการ์ดรายงานใหม่ (โครงเดียวกับ admin/reports.blade.php) ด้วย textContent ล้วน
// กันชื่อหนังสือ/ร้าน/เหตุผล/รายละเอียดที่ผู้ใช้พิมพ์เองกลายเป็นช่องโหว่ XSS
function buildReportCard(payload) {
    const card = document.createElement('div');
    card.className = 'border rounded p-3 mb-2';
    card.dataset.reportId = payload.id;

    const topRow = document.createElement('div');
    topRow.className = 'd-flex justify-content-between align-items-start';

    const info = document.createElement('div');
    info.className = 'flex-grow-1';

    const newBadge = document.createElement('span');
    newBadge.className = 'badge bg-danger';
    newBadge.textContent = 'ใหม่';
    info.appendChild(newBadge);
    info.appendChild(document.createTextNode(' '));

    const typeBadge = document.createElement('span');
    typeBadge.className = 'badge bg-secondary';
    typeBadge.textContent = payload.type_label;
    info.appendChild(typeBadge);
    info.appendChild(document.createTextNode(' '));

    const reasonBadge = document.createElement('span');
    reasonBadge.className = 'badge bg-warning text-dark';
    reasonBadge.textContent = payload.reason;
    info.appendChild(reasonBadge);

    const targetLine = document.createElement('div');
    targetLine.className = 'mt-2';

    if (payload.reportable_label) {
        const label = document.createElement('strong');
        label.textContent = (payload.reportable_type_key === 'book' ? 'หนังสือ' : 'ร้าน') + ': ';
        targetLine.appendChild(label);
        targetLine.appendChild(document.createTextNode(payload.reportable_label + ' '));

        if (payload.reportable_url) {
            const link = document.createElement('a');
            link.href = payload.reportable_url;
            link.target = '_blank';
            link.className = 'small';
            link.textContent = '(ดู)';
            targetLine.appendChild(link);
        }
    } else {
        const gone = document.createElement('span');
        gone.className = 'text-muted';
        gone.textContent = '(สิ่งที่ถูกรายงานถูกลบไปแล้ว)';
        targetLine.appendChild(gone);
    }
    info.appendChild(targetLine);

    if (payload.detail) {
        const detailBox = document.createElement('div');
        detailBox.className = 'alert alert-light mt-2 mb-1 small';
        detailBox.textContent = payload.detail;
        info.appendChild(detailBox);
    }

    const metaLine = document.createElement('div');
    metaLine.className = 'small text-muted';
    metaLine.textContent = 'รายงานโดย: ' + payload.reporter_name + ' · เมื่อสักครู่';
    info.appendChild(metaLine);

    topRow.appendChild(info);
    card.appendChild(topRow);

    const actions = document.createElement('div');
    actions.className = 'd-flex gap-1 mt-2 flex-wrap';

    const chatReporterLink = document.createElement('a');
    chatReporterLink.href = payload.chat_with_reporter_url;
    chatReporterLink.className = 'btn btn-sm btn-outline-primary';
    chatReporterLink.textContent = '💬 คุยกับผู้รายงาน';
    actions.appendChild(chatReporterLink);

    let banModal = null;

    if (payload.chat_with_owner_url) {
        const chatOwnerLink = document.createElement('a');
        chatOwnerLink.href = payload.chat_with_owner_url;
        chatOwnerLink.className = 'btn btn-sm btn-outline-info';
        chatOwnerLink.textContent = '💬 คุยกับผู้ถูกรายงาน';
        actions.appendChild(chatOwnerLink);

        const banBtn = document.createElement('button');
        banBtn.type = 'button';
        banBtn.className = 'btn btn-sm btn-outline-danger';
        banBtn.textContent = '🚫 แบน';
        actions.appendChild(banBtn);

        banModal = buildBanModal(payload);
        banBtn.addEventListener('click', () => {
            if (window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(banModal).show();
            }
        });
    }

    actions.appendChild(buildPostForm(payload.resolve_url, '✓ จัดการแล้ว', 'btn btn-sm btn-success'));
    actions.appendChild(buildPostForm(payload.dismiss_url, 'ปิดเรื่อง', 'btn btn-sm btn-outline-secondary'));

    card.appendChild(actions);

    if (banModal) {
        card.appendChild(banModal);
    }

    return card;
}

// เพิ่มการ์ดรายงานใหม่ขึ้นบนสุดของหน้า "จัดการรายงาน" แบบสด ไม่ต้อง refresh
function insertNewReportCard(list, payload) {
    list.prepend(buildReportCard(payload));

    const wrapper = document.getElementById('pendingReportsCard');
    if (wrapper) {
        wrapper.style.display = '';
    }

    const emptyState = document.querySelector('[data-reports-empty-state]');
    if (emptyState) {
        emptyState.style.display = 'none';
    }

    const countEl = document.querySelector('[data-pending-count]');
    if (countEl) {
        countEl.textContent = (parseInt(countEl.textContent, 10) || 0) + 1;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Echo) {
        return;
    }

    const container = document.querySelector('[data-current-user-id]');

    if (container) {
        const userId = container.dataset.currentUserId;
        const personalChannel = window.Echo.private('chat-user.' + userId);

        // ตัวเลขแดงข้อความแชทซื้อขาย (สำหรับผู้ใช้ทั่วไป)
        const chatBadge = document.getElementById('chatUnreadBadge');
        if (chatBadge) {
            personalChannel.listen('.message.sent', (payload) => {
                if (isViewingChannel('chat.' + payload.conversation_id)) {
                    return;
                }

                bumpBadge(chatBadge);
            });
        }

        // ตัวเลขแดงข้อความจากแอดมิน (สำหรับผู้ใช้ทั่วไป)
        const reportMessageBadge = document.getElementById('reportMessageUnreadBadge');
        if (reportMessageBadge) {
            personalChannel.listen('.report-message.sent', (payload) => {
                if (isViewingChannel('report-chat.' + payload.report_chat_id)) {
                    return;
                }

                bumpBadge(reportMessageBadge);
            });
        }
    }

    // ช่องแจ้งเตือนรวมของแอดมิน (ข้อความแชทรายงาน + รายงานใหม่)
    const reportBadge = document.getElementById('reportUnreadBadge');
    const newReportsBadge = document.getElementById('newReportsBadge');
    const reportsList = document.querySelector('[data-reports-list]');

    if (reportBadge || newReportsBadge || reportsList) {
        const adminChannel = window.Echo.private('admin-notifications');

        // ตัวเลขแดงข้อความแชทรายงาน (สำหรับแอดมิน)
        if (reportBadge) {
            adminChannel.listen('.report-message.sent', (payload) => {
                if (isViewingChannel('report-chat.' + payload.report_chat_id)) {
                    return;
                }

                bumpBadge(reportBadge);
            });
        }

        // ตัวเลขแดงรายงานใหม่ที่ยังไม่มีแอดมินเห็น (สำหรับแอดมิน)
        if (newReportsBadge) {
            adminChannel.listen('.report.created', () => {
                bumpBadge(newReportsBadge);
            });
        }

        // เพิ่มการ์ดรายงานใหม่ในหน้า "จัดการรายงาน" แบบสด (เฉพาะตอนอยู่หน้านี้)
        if (reportsList) {
            adminChannel.listen('.report.created', (payload) => {
                insertNewReportCard(reportsList, payload);
            });
        }
    }
});
