function forceCase(input, mode) {
    if (!input || input.dataset.dcCaseBound === '1') return;
    input.dataset.dcCaseBound = '1';
    input.style.textTransform = mode === 'upper' ? 'uppercase' : 'lowercase';

    const apply = () => {
        if (input.dataset.dcCaseApplying === '1') return;
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const before = input.value;
        const next = mode === 'upper'
            ? before.toLocaleUpperCase('ro-RO')
            : before.toLocaleLowerCase('ro-RO');
        if (next === before) return;
        input.dataset.dcCaseApplying = '1';
        input.value = next;
        if (document.activeElement === input && typeof start === 'number') {
            try { input.setSelectionRange(start, end); } catch (_) {}
        }
        input.dataset.dcCaseApplying = '0';
    };

    input.addEventListener('input', apply);
    input.addEventListener('blur', apply);
    if (input.value) apply();
}

export function initFieldCase(root = document) {
    root.querySelectorAll(
        'input[name="iban"], input[name="bank_name"], input.acc-iban, input.bank-name'
    ).forEach((el) => forceCase(el, 'upper'));

    root.querySelectorAll('input[type="email"], input[name="email"]').forEach((el) => forceCase(el, 'lower'));
    // Client email poate avea mai multe adrese separate prin virgulă — tot lowercase.
}
