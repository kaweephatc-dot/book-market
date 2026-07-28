// สคริปต์แทนที่ js/sb-admin-2.js ต้นฉบับ (ตัวเดิมพึ่ง jQuery + jquery.easing)
// ทำหน้าที่เดิมทั้งหมดด้วย vanilla JS ล้วน: toggle sidebar, ยุบอัตโนมัติตามขนาดจอ, ปุ่มเลื่อนขึ้นบนสุด
(function () {
    'use strict';

    // ◀ = sidebar กำลังขยายอยู่ (กดเพื่อยุบ), ▶ = sidebar ยุบอยู่ (กดเพื่อขยาย)
    function updateToggleIcon() {
        var sidebar = document.querySelector('.sidebar');
        var btn = document.getElementById('sidebarToggle');
        if (!sidebar || !btn) return;
        btn.textContent = sidebar.classList.contains('toggled') ? '▶' : '◀';
    }

    function toggleSidebar() {
        document.body.classList.toggle('sidebar-toggled');
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        sidebar.classList.toggle('toggled');
        if (sidebar.classList.contains('toggled')) {
            sidebar.querySelectorAll('.collapse.show').forEach(function (el) {
                bootstrap.Collapse.getOrCreateInstance(el).hide();
            });
        }
        updateToggleIcon();
    }

    ['sidebarToggle', 'sidebarToggleTop'].forEach(function (id) {
        var btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', toggleSidebar);
    });

    updateToggleIcon();

    window.addEventListener('resize', function () {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        if (window.innerWidth < 768) {
            sidebar.querySelectorAll('.collapse.show').forEach(function (el) {
                bootstrap.Collapse.getOrCreateInstance(el).hide();
            });
        }

        if (window.innerWidth < 480 && !sidebar.classList.contains('toggled')) {
            document.body.classList.add('sidebar-toggled');
            sidebar.classList.add('toggled');
            sidebar.querySelectorAll('.collapse.show').forEach(function (el) {
                bootstrap.Collapse.getOrCreateInstance(el).hide();
            });
            updateToggleIcon();
        }
    });

    // ปุ่มเลื่อนขึ้นบนสุด (แสดง/ซ่อนตามตำแหน่ง scroll, เลื่อนแบบ smooth ด้วย CSS scroll-behavior)
    var scrollBtn = document.querySelector('.scroll-to-top');
    if (scrollBtn) {
        window.addEventListener('scroll', function () {
            scrollBtn.style.display = window.scrollY > 100 ? 'block' : 'none';
        });
    }
})();
