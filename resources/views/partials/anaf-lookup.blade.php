@php
    $anafDefaultCui = old('cui', $anafDefaultCui ?? ($client->cui ?? ''));
@endphp
<div class="flex gap-2 items-end mb-4">
    <div class="flex-1">
        <label class="dc-label">Caută după CUI (ANAF)</label>
        <input type="text" id="anaf-cui" class="dc-input" placeholder="ex: 38254880" value="{{ $anafDefaultCui }}">
    </div>
    <button type="button" id="anaf-btn" class="dc-btn-secondary">Preluare date</button>
</div>
<script>
document.getElementById('anaf-btn')?.addEventListener('click', async () => {
    const anafInput = document.getElementById('anaf-cui');
    const formCui = document.querySelector('[name="cui"]');
    let cui = (anafInput?.value || '').trim();
    if (!cui && formCui) {
        cui = (formCui.value || '').trim();
        if (anafInput && cui) anafInput.value = cui;
    }
    if (!cui) {
        alert('Introdu CUI-ul firmei.');
        anafInput?.focus();
        return;
    }

    const res = await fetch(@json(route('anaf.lookup')), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ cui })
    });
    if (!res.ok) { alert('Nu am găsit firma.'); return; }
    const data = await res.json();
    const digits = String(data.cui || '').replace(/\D+/g, '');
    const vatPayer = !!data.vat_payer;
    const formattedCui = digits ? (vatPayer ? 'RO' + digits : digits) : (data.cui || '');

    const map = {
        name: data.name,
        cui: formattedCui,
        reg_com: data.reg_com,
        address: data.address,
        city: data.city,
        phone: data.phone,
    };
    Object.entries(map).forEach(([k, v]) => {
        const el = document.querySelector(`[name="${k}"]`);
        if (el && v !== undefined && v !== null && String(v).length) el.value = v;
    });

    const cuiEl = document.querySelector('[name="cui"]');
    if (cuiEl) {
        cuiEl.dataset.vatPayer = vatPayer ? '1' : '0';
        cuiEl.placeholder = vatPayer ? 'RO12345678' : '12345678';
    }
    if (anafInput && formattedCui) {
        anafInput.value = formattedCui;
    }

    const countyEl = document.querySelector('[name="county"]');
    if (countyEl && data.county) {
        let wanted = String(data.county);
        const sectorMatch = wanted.match(/sector(?:ul)?\s*([1-6])/i);
        if (sectorMatch) {
            wanted = 'București - Sector ' + sectorMatch[1];
        }
        const exists = Array.from(countyEl.options || []).some((o) => o.value === wanted);
        if (!exists && countyEl.tagName === 'SELECT') {
            const opt = document.createElement('option');
            opt.value = wanted;
            opt.textContent = wanted;
            countyEl.appendChild(opt);
        }
        countyEl.value = wanted;
    }

    const vat = document.querySelector('[name="vat_payer"]');
    if (vat) vat.checked = vatPayer;
});
</script>
