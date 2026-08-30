<form method="POST" action="{{ route('companies.update', $company) }}" id="company-banks-form" class="dc-card p-6 space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="conturi">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold">{{ __('Conturi bancare') }}</h2>
            <p class="text-sm text-slate-600 mt-1">Completează IBAN-ul — banca se completează automat. Bifează maxim 3 conturi pe factură.</p>
        </div>
        <button type="button" id="add-bank" class="dc-btn-secondary">+ Bancă</button>
    </div>
    <div id="invoice-iban-hint" class="text-xs text-slate-500">Conturi bifate pe factură: <strong id="invoice-iban-count">0</strong>/3</div>
    <div id="banks-root" class="space-y-4"></div>
    <button class="dc-btn-primary">Salvează conturile</button>
</form>

<script>
(() => {
    const initial = @json($banksPayload);
    const root = document.getElementById('banks-root');
    const countEl = document.getElementById('invoice-iban-count');
    const hint = document.getElementById('invoice-iban-hint');
    let banks = Array.isArray(initial) && initial.length ? initial : [{name:'', accounts:[{iban:'', currency:'RON', show_on_invoice:true}]}];

    function checkedCount() {
        return banks.reduce((n, b) => n + (b.accounts || []).filter(a => !!a.show_on_invoice && String(a.iban || '').trim() !== '').length, 0);
    }
    function updateCount() {
        const n = checkedCount();
        countEl.textContent = String(n);
        hint.className = 'text-xs ' + (n > 3 ? 'text-rose-600 font-medium' : 'text-slate-500');
    }
    function escapeAttr(v) {
        return String(v).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
    }
    function applyBankFromIban(bi, ai, rawIban, inputEl) {
        const api = window.dcIbanBank;
        const formatted = api ? api.formatIbanDisplay(rawIban) : String(rawIban || '').toUpperCase();
        banks[bi].accounts[ai].iban = formatted;
        if (inputEl && inputEl.value !== formatted) inputEl.value = formatted;
        const name = api ? api.bankFromIban(formatted) : null;
        if (name && banks[bi]._manualName !== true) {
            banks[bi].name = name;
            const nameInput = root.querySelector(`[data-bank-index="${bi}"] .bank-name`);
            if (nameInput) nameInput.value = name;
        }
        return formatted;
    }
    function render() {
        root.innerHTML = '';
        banks.forEach((bank, bi) => {
            const box = document.createElement('div');
            box.className = 'rounded-xl border border-slate-200 p-4 space-y-3 bg-slate-50/50';
            box.dataset.bankIndex = String(bi);
            box.innerHTML = `
                <div class="accounts space-y-2"></div>
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="dc-label">{{ __('Bancă') }}</label>
                        <input class="dc-input bank-name" value="${escapeAttr(bank.name || '')}" placeholder="se completează din IBAN">
                    </div>
                    <button type="button" class="dc-btn-secondary remove-bank">Șterge banca</button>
                </div>
                <button type="button" class="text-sm text-teal-800 underline add-account">+ Cont IBAN</button>`;
            const accountsEl = box.querySelector('.accounts');
            (bank.accounts || []).forEach((acc, ai) => {
                const row = document.createElement('div');
                row.className = 'grid sm:grid-cols-[1fr_90px_auto_auto] gap-2 items-end';
                row.innerHTML = `
                    <div><label class="dc-label">IBAN</label><input class="dc-input acc-iban" value="${escapeAttr(acc.iban || '')}" placeholder="RO49 AAAA ..." autocomplete="off"></div>
                    <div><label class="dc-label">{{ __('Monedă') }}</label><input class="dc-input acc-currency" value="${escapeAttr(acc.currency || 'RON')}" maxlength="3"></div>
                    <label class="flex items-center gap-2 text-sm pb-2 whitespace-nowrap"><input type="checkbox" class="acc-show rounded border-slate-300" ${acc.show_on_invoice ? 'checked' : ''}> Pe factură</label>
                    <button type="button" class="dc-btn-secondary remove-account mb-0.5">×</button>`;
                const ibanInput = row.querySelector('.acc-iban');
                ibanInput.style.textTransform = 'uppercase';
                ibanInput.addEventListener('input', e => {
                    applyBankFromIban(bi, ai, e.target.value, e.target);
                    updateCount();
                });
                ibanInput.addEventListener('blur', e => applyBankFromIban(bi, ai, e.target.value, e.target));
                row.querySelector('.acc-currency').addEventListener('input', e => {
                    e.target.value = e.target.value.toLocaleUpperCase('ro-RO');
                    banks[bi].accounts[ai].currency = e.target.value;
                });
                row.querySelector('.acc-show').addEventListener('change', e => {
                    if (e.target.checked && checkedCount() >= 3 && !banks[bi].accounts[ai].show_on_invoice) {
                        e.target.checked = false; alert('Poți bifa maxim 3 conturi pe factură.'); return;
                    }
                    banks[bi].accounts[ai].show_on_invoice = e.target.checked; updateCount();
                });
                row.querySelector('.remove-account').addEventListener('click', () => {
                    banks[bi].accounts.splice(ai, 1);
                    if (!banks[bi].accounts.length) banks[bi].accounts.push({iban:'', currency:'RON', show_on_invoice:false});
                    render();
                });
                accountsEl.appendChild(row);
            });
            const bankNameInput = box.querySelector('.bank-name');
            bankNameInput.style.textTransform = 'uppercase';
            bankNameInput.addEventListener('input', e => {
                const upper = e.target.value.toLocaleUpperCase('ro-RO');
                if (e.target.value !== upper) e.target.value = upper;
                banks[bi].name = e.target.value;
                banks[bi]._manualName = e.target.value.trim() !== '';
            });
            box.querySelector('.add-account').addEventListener('click', () => { banks[bi].accounts.push({iban:'', currency:'RON', show_on_invoice:false}); render(); });
            box.querySelector('.remove-bank').addEventListener('click', () => {
                banks.splice(bi, 1);
                if (!banks.length) banks = [{name:'', accounts:[{iban:'', currency:'RON', show_on_invoice:true}]}];
                render();
            });
            root.appendChild(box);
        });
        updateCount();
    }
    document.getElementById('add-bank').addEventListener('click', () => {
        banks.push({name:'', accounts:[{iban:'', currency:'RON', show_on_invoice:false}]}); render();
    });
    document.getElementById('company-banks-form').addEventListener('submit', (e) => {
        if (checkedCount() > 3) { e.preventDefault(); alert('Poți bifa maxim 3 conturi IBAN pe factură.'); return; }
        root.querySelectorAll('input[data-sync]').forEach(n => n.remove());
        banks.forEach((bank, bi) => {
            appendHidden(`banks[${bi}][name]`, bank.name || '');
            (bank.accounts || []).forEach((acc, ai) => {
                appendHidden(`banks[${bi}][accounts][${ai}][iban]`, acc.iban || '');
                appendHidden(`banks[${bi}][accounts][${ai}][currency]`, acc.currency || 'RON');
                if (acc.show_on_invoice) appendHidden(`banks[${bi}][accounts][${ai}][show_on_invoice]`, '1');
            });
        });
    });
    function appendHidden(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value; input.setAttribute('data-sync', '1');
        root.appendChild(input);
    }
    function waitApi(tries = 40) {
        if (window.dcIbanBank || tries <= 0) { render(); return; }
        setTimeout(() => waitApi(tries - 1), 50);
    }
    waitApi();
})();
</script>
