const BANK_CODES = {
    RNCB: 'Banca Comercială Română',
    BTRL: 'Banca Transilvania',
    BRDE: 'BRD - Groupe Société Générale',
    RZBR: 'Raiffeisen Bank',
    INGB: 'ING Bank',
    BACX: 'UniCredit Bank',
    CECE: 'CEC Bank',
    CECB: 'CEC Bank',
    OTPV: 'OTP Bank Romania',
    EXIM: 'Exim Banca Românească',
    PIRB: 'First Bank',
    BREL: 'Libra Internet Bank',
    CARP: 'Patria Bank',
    WBAN: 'Intesa Sanpaolo Bank',
    BISP: 'Intesa Sanpaolo Bank',
    FNNB: 'Nexent Bank',
    UGBI: 'Garanti BBVA Romania',
    MIRO: 'ProCredit Bank',
    TBIB: 'TBI Bank',
    TBIR: 'TBI Bank',
    ROIN: 'Salt Bank',
    REVO: 'Revolut Bank',
    CITI: 'Citibank Europe',
    BCHL: 'Vista Bank',
    DAFB: 'Banca Românească',
    BUCU: 'Alpha Bank Romania',
    TREZ: 'Trezoreria Statului',
    BNRF: 'Banca Națională a României',
    CRDZ: 'Credit Europe Bank',
    EGNA: 'BNP Paribas Personal Finance',
};

export function normalizeIban(value) {
    return String(value || '').replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

export function bankFromIban(iban) {
    const clean = normalizeIban(iban);
    if (clean.length < 8) return null;
    if (!clean.startsWith('RO')) return null;
    const code = clean.slice(4, 8);
    const name = BANK_CODES[code] || null;
    return name ? name.toLocaleUpperCase('ro-RO') : null;
}

export function formatIbanDisplay(iban) {
    const clean = normalizeIban(iban);
    return clean.replace(/(.{4})/g, '$1 ').trim();
}

/**
 * Leagă un input IBAN de un input Bancă: la completare IBAN, completează banca.
 * Nu suprascrie banca dacă utilizatorul a editat-o manual (dataset.dcBankManual=1).
 */
export function bindIbanToBank(ibanInput, bankInput) {
    if (!ibanInput || !bankInput) return;

    bankInput.addEventListener('input', () => {
        bankInput.dataset.dcBankManual = '1';
    });

    const apply = () => {
        const name = bankFromIban(ibanInput.value);
        if (!name) return;
        if (bankInput.dataset.dcBankManual === '1' && bankInput.value.trim() !== '') return;
        bankInput.value = name;
        bankInput.dispatchEvent(new Event('input', { bubbles: true }));
        bankInput.dataset.dcBankManual = '0';
    };

    ibanInput.addEventListener('input', () => {
        const start = ibanInput.selectionStart;
        const before = ibanInput.value;
        const formatted = formatIbanDisplay(ibanInput.value);
        if (formatted !== before) {
            ibanInput.value = formatted;
            if (typeof start === 'number' && document.activeElement === ibanInput) {
                const delta = formatted.length - before.length;
                const pos = Math.max(0, start + delta);
                ibanInput.setSelectionRange(pos, pos);
            }
        }
        apply();
    });
    ibanInput.addEventListener('blur', apply);
}

export function initIbanBankBindings(root = document) {
    root.querySelectorAll('[data-iban-bank]').forEach((ibanInput) => {
        if (ibanInput.dataset.dcIbanBound === '1') return;
        const target = ibanInput.getAttribute('data-iban-bank');
        const bankInput = target
            ? root.querySelector(target) || document.querySelector(target)
            : ibanInput.closest('form, .dc-card, div')?.querySelector('[name="bank_name"], .bank-name');
        if (!bankInput) return;
        ibanInput.dataset.dcIbanBound = '1';
        bindIbanToBank(ibanInput, bankInput);
    });
}

window.dcIbanBank = { bankFromIban, formatIbanDisplay, bindIbanToBank, normalizeIban };
