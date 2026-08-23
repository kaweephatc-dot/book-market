const CONFIRM_COLOR = '#3248f2';

function fireToast(icon, message) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });
}

// แปลง flash message (session success/error/info) ที่ render มาจาก server เป็น toast
// องค์ประกอบต้นฉบับถูกลบทิ้งทันทีเพื่อไม่ให้กล่อง Bootstrap alert เดิมโผล่ค้างมาด้วย
function initFlashToasts() {
    document.querySelectorAll('[data-flash]').forEach((el) => {
        const icon = el.dataset.flash;
        const message = el.textContent.trim();
        el.remove();

        if (message) {
            fireToast(icon, message);
        }
    });
}

function askConfirm(message) {
    return Swal.fire({
        title: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: CONFIRM_COLOR,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
    }).then((result) => result.isConfirmed);
}

// รองรับ 2 แบบ:
// 1) <form data-confirm="..."> ฟอร์มปุ่มเดียว ยืนยันแล้ว submit ฟอร์มตามปกติ
// 2) <button data-confirm="..."> ในฟอร์มที่มีหลายปุ่ม submit (เช่น ตัดสินข้อพิพาท) ใช้ requestSubmit(button)
//    เพื่อให้ name/value ของปุ่มที่กดถูกส่งไปด้วย ไม่ใช่ submit ฟอร์มเฉยๆ
function initConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (form.dataset.confirmed === 'true') {
                return;
            }

            e.preventDefault();

            askConfirm(form.dataset.confirm).then((confirmed) => {
                if (confirmed) {
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                }
            });
        });
    });

    document.querySelectorAll('button[data-confirm]').forEach((button) => {
        button.addEventListener('click', (e) => {
            const form = button.closest('form');
            if (!form) {
                return;
            }

            e.preventDefault();

            askConfirm(button.dataset.confirm).then((confirmed) => {
                if (confirmed) {
                    form.dataset.confirmed = 'true';
                    form.requestSubmit(button);
                }
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initFlashToasts();
    initConfirmForms();
});
