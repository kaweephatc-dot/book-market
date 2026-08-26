// เปิด/ปิด sidebar แบบ drawer บนจอมือถือ (คู่กับ public/css/mobile-sidebar.css)
// แยกจาก sidebar.js ที่ดูแลโหมดยุบ-ขยายของจอใหญ่ เพื่อไม่ให้สอง state ชนกัน
(function () {
    'use strict';

    var toggle = document.getElementById('mobileSidebarToggle');
    var sidebar = document.querySelector('.sidebar');
    if (!toggle || !sidebar) return;

    var backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);

    function close() {
        document.body.classList.remove('sidebar-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', function (e) {
        e.preventDefault();
        var opened = document.body.classList.toggle('sidebar-open');
        toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
    });

    backdrop.addEventListener('click', close);

    // แตะเมนูแล้วปิดเอง ไม่ต้องกดปิดซ้ำก่อนดูหน้าใหม่
    sidebar.addEventListener('click', function (e) {
        if (e.target.closest('a')) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) close();
    });
})();
