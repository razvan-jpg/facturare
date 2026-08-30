function formatAsRoDate(raw) {
    const digits = String(raw || '').replace(/\D+/g, '').slice(0, 8);
    if (digits.length <= 2) return digits;
    if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
    return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
}

export function initDateInputs(root = document) {
    root.querySelectorAll('.dc-date-input').forEach((input) => {
        if (input.dataset.dcDateBound === '1') return;
        input.dataset.dcDateBound = '1';

        input.addEventListener('input', () => {
            const start = input.selectionStart;
            const before = input.value;
            input.value = formatAsRoDate(input.value);
            if (document.activeElement === input && typeof start === 'number') {
                const delta = input.value.length - before.length;
                const pos = Math.max(0, start + delta);
                input.setSelectionRange(pos, pos);
            }
        });

        input.addEventListener('blur', () => {
            if (!input.value) return;
            const m = input.value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
            if (!m) return;
            const d = String(m[1]).padStart(2, '0');
            const mo = String(m[2]).padStart(2, '0');
            input.value = `${d}/${mo}/${m[3]}`;
        });
    });
}
