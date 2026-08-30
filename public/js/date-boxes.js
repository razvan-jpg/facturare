(function () {
    function pad(n, len) {
        return String(n).padStart(len, '0');
    }

    function syncFromParts(root) {
        const dEl = root.querySelector('[data-part="d"]');
        const mEl = root.querySelector('[data-part="m"]');
        const yEl = root.querySelector('[data-part="y"]');
        const hidden = root.querySelector('.dc-datebox-value');
        const native = root.querySelector('.dc-datebox-native');
        const d = parseInt(dEl.value, 10);
        const m = parseInt(mEl.value, 10);
        const y = parseInt(yEl.value, 10);
        if (!d || !m || !y || y < 1900 || m > 12 || d > 31) {
            hidden.value = '';
            return;
        }
        const iso = `${pad(y, 4)}-${pad(m, 2)}-${pad(d, 2)}`;
        const check = new Date(y, m - 1, d);
        if (check.getFullYear() !== y || check.getMonth() !== m - 1 || check.getDate() !== d) {
            hidden.value = '';
            return;
        }
        hidden.value = iso;
        if (native) native.value = iso;
        root.dispatchEvent(new CustomEvent('dc-date-change', { bubbles: true, detail: { iso } }));
    }

    function syncFromNative(root) {
        const native = root.querySelector('.dc-datebox-native');
        if (!native.value) return;
        const [y, m, d] = native.value.split('-');
        root.querySelector('[data-part="d"]').value = d;
        root.querySelector('[data-part="m"]').value = m;
        root.querySelector('[data-part="y"]').value = y;
        root.querySelector('.dc-datebox-value').value = native.value;
        root.dispatchEvent(new CustomEvent('dc-date-change', { bubbles: true, detail: { iso: native.value } }));
    }

    function bind(root) {
        if (root.dataset.bound) return;
        root.dataset.bound = '1';
        root.querySelectorAll('.dc-datebox').forEach((el) => {
            el.addEventListener('input', () => {
                el.value = el.value.replace(/\D/g, '');
                syncFromParts(root);
            });
            el.addEventListener('blur', () => {
                const part = el.dataset.part;
                if (el.value && part !== 'y') el.value = pad(parseInt(el.value, 10) || 0, 2);
                syncFromParts(root);
            });
        });
        const native = root.querySelector('.dc-datebox-native');
        if (native) {
            native.addEventListener('change', () => syncFromNative(root));
        }
        syncFromParts(root);
    }

    function initAll(scope) {
        (scope || document).querySelectorAll('[data-dateboxes]').forEach(bind);
    }

    document.addEventListener('DOMContentLoaded', () => initAll());
    window.dcInitDateBoxes = initAll;
})();
