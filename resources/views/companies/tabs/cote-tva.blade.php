@php
    $vatStatus = old('vat_status', $company->vat_payer ? 'payer' : 'non_payer');
@endphp
<form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4" id="cote-tva-form">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="cote-tva">
    <h2 class="text-lg font-semibold">{{ __('Cote TVA') }}</h2>
    <p class="text-sm text-slate-600">Alege regimul TVA al firmei (obligatoriu) și cota implicită folosită pe documente.</p>

    <fieldset class="space-y-3">
        <legend class="dc-label">Regim TVA <span class="text-amber-700">*</span></legend>
        @error('vat_status')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
            <input type="radio" name="vat_status" value="payer" required
                   @checked($vatStatus === 'payer')
                   class="mt-0.5 border-slate-300 text-amber-600 focus:ring-amber-500"
                   data-vat-default="21">
            <span>
                <span class="block text-sm font-semibold text-slate-900">Plătitor de TVA</span>
                <span class="block text-xs text-slate-500 mt-0.5">Cota implicită se setează la 21% (poți modifica).</span>
            </span>
        </label>
        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
            <input type="radio" name="vat_status" value="non_payer" required
                   @checked($vatStatus === 'non_payer')
                   class="mt-0.5 border-slate-300 text-amber-600 focus:ring-amber-500"
                   data-vat-default="0">
            <span>
                <span class="block text-sm font-semibold text-slate-900">Neplătitor de TVA</span>
                <span class="block text-xs text-slate-500 mt-0.5">Cota implicită se setează automat la 0%.</span>
            </span>
        </label>
    </fieldset>

    <div class="grid sm:grid-cols-2 gap-4">
        <label class="flex items-center gap-2 sm:col-span-2" id="vat-on-collection-wrap">
            <input type="checkbox" name="vat_on_collection" value="1" id="vat_on_collection"
                   @checked(old('vat_on_collection', $company->vat_on_collection))
                   class="rounded border-slate-300">
            <span class="text-sm font-medium">TVA la încasare</span>
        </label>
        <div>
            <label class="dc-label" for="default_vat_rate">Cotă TVA implicită %</label>
            <input name="default_vat_rate" id="default_vat_rate" type="number" step="0.01" min="0" max="100"
                   value="{{ old('default_vat_rate', $company->default_vat_rate) }}"
                   class="dc-input">
            <p class="mt-1 text-xs text-slate-500" id="vat-rate-hint"></p>
        </div>
    </div>
    <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
        Cote uzuale RO: 21%, 11%, 5%, 0% (scutit). Cota implicită se aplică la produsele / liniile noi.
    </div>
    <button class="dc-btn-primary">{{ __('Salvează') }}</button>
</form>

<script>
(function () {
    const form = document.getElementById('cote-tva-form');
    if (!form) return;
    const rateInput = document.getElementById('default_vat_rate');
    const collectionWrap = document.getElementById('vat-on-collection-wrap');
    const collectionInput = document.getElementById('vat_on_collection');
    const hint = document.getElementById('vat-rate-hint');
    function applyVatStatus(setDefault) {
        const selected = form.querySelector('input[name="vat_status"]:checked');
        if (!selected) return;
        const isPayer = selected.value === 'payer';
        const def = selected.getAttribute('data-vat-default');

        if (setDefault) {
            rateInput.value = def;
        } else if (!isPayer) {
            rateInput.value = '0';
        }

        rateInput.readOnly = !isPayer;
        rateInput.classList.toggle('bg-slate-100', !isPayer);
        if (collectionWrap) {
            collectionWrap.style.opacity = isPayer ? '1' : '0.45';
            collectionWrap.style.pointerEvents = isPayer ? '' : 'none';
        }
        if (!isPayer && collectionInput) {
            collectionInput.checked = false;
        }
        if (hint) {
            hint.textContent = isPayer
                ? 'Poți modifica cota (ex. 21, 11, 5).'
                : 'Neplătitor TVA → cota este blocată la 0%.';
        }
    }

    form.querySelectorAll('input[name="vat_status"]').forEach((el) => {
        el.addEventListener('change', () => applyVatStatus(true));
    });

    applyVatStatus(false);
})();
</script>
