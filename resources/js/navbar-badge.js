function bumpBadge(badge) {
    const count = (parseInt(badge.textContent, 10) || 0) + 1;
    badge.textContent = count > 99 ? '99+' : count;
    badge.classList.remove('d-none');
    return count;
}

function isViewingChannel(channel) {
    return window.__activeChatChannel === channel;
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

    if (reportBadge || newReportsBadge) {
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
    }
});
