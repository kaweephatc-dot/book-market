const SLOT_LABELS = {
    cover: 'ปกหนังสือ',
    spine: 'สันหนังสือ',
    page: 'หน้าหนังสือ (สุ่ม)',
    back: 'ปกหลังหนังสือ',
};

function clearNode(node) {
    node.textContent = '';
}

function appendLine(node, text, className) {
    const line = document.createElement('div');
    if (className) {
        line.className = className;
    }
    line.textContent = text;
    node.appendChild(line);
}

function renderSlotResult(root, slot, result) {
    const target = root.querySelector(`[data-slot-result="${slot}"]`);
    if (!target || !result) {
        return;
    }

    clearNode(target);

    if (result.angle_match === false) {
        const label = SLOT_LABELS[slot] || slot;
        appendLine(target, `รูปในช่อง${label}ดูไม่เหมือน${label} กรุณาตรวจสอบ`, 'text-danger fw-bold');
    }

    if (typeof result.score !== 'undefined' && result.condition) {
        appendLine(target, `คะแนน: ${result.score}/100 (${result.condition})`, 'fw-bold');
    }

    if (result.note) {
        appendLine(target, result.note, 'text-muted');
    }

    target.classList.remove('d-none');
}

function renderOverall(root, overall) {
    const box = root.querySelector('[data-analyze-overall]');
    const headline = root.querySelector('[data-overall-headline]');
    const note = root.querySelector('[data-overall-note]');

    if (!box || !overall) {
        return;
    }

    headline.textContent = `ภาพรวม: ${overall.condition} (${overall.score}/100)`;
    note.textContent = overall.note || '';
    box.classList.remove('d-none');
}

function showError(root, message) {
    const errorBox = root.querySelector('[data-analyze-error]');
    if (!errorBox) {
        return;
    }
    errorBox.textContent = message;
    errorBox.classList.remove('d-none');
}

function initBookConditionForm(root) {
    const analyzeUrl = root.dataset.analyzeUrl;
    const analyzeBtn = root.querySelector('[data-analyze-btn]');
    const loadingEl = root.querySelector('[data-analyze-loading]');
    const errorBox = root.querySelector('[data-analyze-error]');
    const overallBox = root.querySelector('[data-analyze-overall]');
    const conditionSelect = root.querySelector('[data-condition-select]');
    const hiddenField = root.querySelector('[data-ai-analysis-field]');
    const slotInputs = root.querySelectorAll('[data-slot-input]');

    if (!analyzeUrl || !analyzeBtn) {
        return;
    }

    slotInputs.forEach((input) => {
        input.addEventListener('change', () => {
            const slot = input.dataset.slotInput;
            const preview = root.querySelector(`[data-slot-preview="${slot}"]`);
            const resultBox = root.querySelector(`[data-slot-result="${slot}"]`);

            if (resultBox) {
                resultBox.classList.add('d-none');
                clearNode(resultBox);
            }

            if (!preview) {
                return;
            }

            const file = input.files[0];
            if (!file) {
                preview.classList.add('d-none');
                preview.removeAttribute('src');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    });

    analyzeBtn.addEventListener('click', () => {
        const coverInput = root.querySelector('[data-slot-input="cover"]');
        if (!coverInput || !coverInput.files[0]) {
            showError(root, 'กรุณาเลือกรูปปกหนังสือก่อนประเมิน');
            return;
        }

        const formData = new FormData();
        slotInputs.forEach((input) => {
            const file = input.files[0];
            if (file) {
                formData.append(`${input.dataset.slotInput}_image`, file);
            }
        });

        errorBox.classList.add('d-none');
        overallBox.classList.add('d-none');
        loadingEl.classList.remove('d-none');
        analyzeBtn.disabled = true;

        window.axios.post(analyzeUrl, formData)
            .then((res) => {
                const data = res.data;

                if (!data.success) {
                    showError(root, data.error || 'ประเมินไม่สำเร็จ กรุณากรอกข้อมูลสภาพเอง');
                    return;
                }

                Object.keys(data.per_image || {}).forEach((slot) => {
                    renderSlotResult(root, slot, data.per_image[slot]);
                });

                renderOverall(root, {
                    condition: data.condition,
                    score: data.score,
                    note: data.overall_note,
                });

                if (conditionSelect && data.condition) {
                    conditionSelect.value = data.condition;
                }

                if (hiddenField) {
                    hiddenField.value = JSON.stringify(data);
                }
            })
            .catch(() => {
                showError(root, 'เชื่อมต่อ AI ไม่สำเร็จ กรุณาลองใหม่ หรือกรอกสภาพหนังสือเอง');
            })
            .finally(() => {
                loadingEl.classList.add('d-none');
                analyzeBtn.disabled = false;
            });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-book-condition-form]').forEach(initBookConditionForm);
});
