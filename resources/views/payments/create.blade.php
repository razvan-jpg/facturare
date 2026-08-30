@extends('layouts.app')
@section('heading', 'Emitere › Încasare')
@section('subheading', 'Înregistrează o chitanță sau un ordin de plată')
@section('shell_pad', 'px-2 sm:px-3 lg:px-4')

@section('content')
@php
    $seriesForJs = $seriesList->map(fn ($s) => [
        'prefix' => $s->prefix,
        'year' => (int) $s->year,
        'next_number' => (int) $s->next_number,
        'is_default' => (bool) $s->is_default,
    ])->values();
    $issueYear = (int) now()->format('Y');
    $defaultSeries = old(
        'series',
        $seriesList->where('year', $issueYear)->firstWhere('is_default', true)?->prefix
            ?? $seriesList->where('year', $issueYear)->first()?->prefix
            ?? $seriesList->first()?->prefix
    );
@endphp

<link rel="stylesheet" href="{{ asset('css/date-boxes.css') }}?v=20260806g">

<form method="POST" action="{{ route('payments.collect') }}" class="dc-card p-6 w-full max-w-5xl mx-auto" id="dc-collect-form"
      data-unpaid-url="{{ route('payments.unpaid-invoices') }}"
      data-cash-limit="{{ $cashLimit }}">
    @csrf

    <div class="dc-collect-grid">
        <div class="dc-collect-main space-y-4">
            <div>
                <div class="text-xs text-slate-500 mb-1">Pentru firme: selectează clientul după denumire sau CIF.<br>Poți adăuga clienți noi din meniul Clienți.</div>
                <label class="dc-label" for="client_id">Nume sau CIF client</label>
                <select name="client_id" id="client_id" class="dc-input" required>
                    <option value="">recomandăm CIF</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                            {{ $client->name }}@if($client->cui) ({{ $client->cui }})@endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="unpaid-wrap" class="dc-unpaid-box" hidden>
                <div class="dc-unpaid-head">
                    <strong>{{ __('De încasat') }}</strong>
                    <span class="text-xs text-slate-500" id="unpaid-count"></span>
                </div>
                <div id="opening-row" class="dc-unpaid-list mb-2" hidden></div>
                <div id="unpaid-list" class="dc-unpaid-list"></div>
                <p class="text-xs text-slate-500 mt-2">Soldul inițial se încasează <strong>înainte</strong> de documente. Bifează facturile / proformele; suma și „Reprezentând” se completează automat. Proforma încasată integral emite automat factura fiscală.</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="dc-label" for="instrument">{{ __('Tip document') }}</label>
                    <select name="instrument" id="instrument" class="dc-input">
                        <option value="receipt" @selected(old('instrument', 'receipt') === 'receipt')>{{ __('Chitanță') }}</option>
                        <option value="op" @selected(old('instrument') === 'op')>OP</option>
                    </select>
                    <p class="text-xs text-amber-700 mt-1" id="instrument-hint" hidden>
                        Suma depășește {{ number_format($cashLimit, 0, ',', '.') }} RON — tipul a fost trecut pe OP.
                    </p>
                </div>
                <div id="series-wrap">
                    <label class="dc-label" for="series">{{ __('Serie și număr') }}</label>
                    <select name="series" id="series" class="dc-input">
                        @foreach($seriesList as $s)
                            <option value="{{ $s->prefix }}"
                                    data-year="{{ $s->year }}"
                                    data-next="{{ $s->next_number }}"
                                    @selected($defaultSeries === $s->prefix)>
                                {{ $s->prefix }} (nr. {{ str_pad((string) $s->next_number, 4, '0', STR_PAD_LEFT) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <span class="dc-label">Data emiterii</span>
                @include('partials.date-boxes', ['name' => 'paid_at', 'label' => false, 'id' => 'paid_at', 'value' => old('paid_at', now()), 'required' => true])
            </div>

            <div>
                <label class="dc-label" for="amount">Valoare</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="dc-input"
                       value="{{ old('amount') }}" required>
            </div>

            <div>
                <label class="dc-label" for="reprezentand">Reprezentând</label>
                <textarea name="reprezentand" id="reprezentand" rows="4" class="dc-input" placeholder="ex: contravaloare factură…">{{ old('reprezentand') }}</textarea>
            </div>
        </div>

        <aside class="dc-collect-side space-y-4">
            <div>
                <label class="dc-label" for="currency">Moneda</label>
                <select name="currency" id="currency" class="dc-input">
                    @foreach($currencies as $code => $label)
                        <option value="{{ $code }}" @selected(old('currency', 'RON') === $code)>{{ $code }} — {{ is_string($label) ? $label : $code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="dc-label" for="document_language">Limba</label>
                <select name="document_language" id="document_language" class="dc-input">
                    @foreach(config('document_languages', ['ro' => 'Română']) as $code => $label)
                        <option value="{{ $code }}" @selected(old('document_language', 'ro') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
                Chitanța (numerar) e limitată la <strong>{{ number_format($cashLimit, 0, ',', '.') }} RON / client / zi</strong>.
                OP nu are această limită.
            </p>
        </aside>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="dc-btn-primary" id="collect-submit">Spre salvare chitanță →</button>
    </div>
</form>

<style>
.dc-collect-grid {
    display: grid;
    gap: 1.5rem;
}
@media (min-width: 900px) {
    .dc-collect-grid { grid-template-columns: 1fr 15rem; align-items: start; }
}
.dc-unpaid-box {
    border: 1px solid #d9e2ec;
    border-radius: .65rem;
    padding: .75rem .9rem;
    background: #f8fafc;
}
.dc-unpaid-head {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: .5rem; font-size: .9rem;
}
.dc-unpaid-list { display: flex; flex-direction: column; gap: .35rem; max-height: 14rem; overflow: auto; }
.dc-unpaid-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: .65rem;
    align-items: center;
    padding: .45rem .55rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .45rem;
    font-size: .86rem;
}
.dc-unpaid-row strong { font-weight: 700; color: #243b53; }
.dc-unpaid-row .meta { color: #627d98; font-size: .78rem; }
.dc-unpaid-row .amt { font-weight: 700; white-space: nowrap; }
</style>

<script>
(function () {
    const form = document.getElementById('dc-collect-form');
    if (!form) return;

    const unpaidUrl = form.dataset.unpaidUrl;
    const cashLimit = parseFloat(form.dataset.cashLimit || '5000');
    const clientSelect = document.getElementById('client_id');
    const unpaidWrap = document.getElementById('unpaid-wrap');
    const openingRow = document.getElementById('opening-row');
    const unpaidList = document.getElementById('unpaid-list');
    const unpaidCount = document.getElementById('unpaid-count');
    const amountInput = document.getElementById('amount');
    const reprezentand = document.getElementById('reprezentand');
    const instrument = document.getElementById('instrument');
    const instrumentHint = document.getElementById('instrument-hint');
    const seriesWrap = document.getElementById('series-wrap');
    const submitBtn = document.getElementById('collect-submit');
    const currency = document.getElementById('currency');

    let invoices = [];
    let openingRemaining = 0;
    let openingDateRo = null;
    let amountManual = false;
    let reprezentandManual = false;
    let autoSwitching = false;

    amountInput.addEventListener('input', () => {
        amountManual = true;
        applyCashRule();
    });
    reprezentand.addEventListener('input', () => { reprezentandManual = true; });

    function pad(n) {
        return String(n).padStart(4, '0');
    }

    function fmtAmt(n) {
        return Number(n).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function selectedInvoices() {
        return invoices.filter(inv => {
            const el = unpaidList.querySelector('input[data-id="' + inv.id + '"]');
            return el && el.checked;
        });
    }

    function openingIncluded() {
        const el = openingRow.querySelector('input[name="include_opening"]');
        return !!(el && el.checked && openingRemaining > 0.009);
    }

    function syncFromSelection() {
        const selected = selectedInvoices();
        const withOpening = openingIncluded();
        if (selected.length === 0 && !withOpening) {
            if (!amountManual) amountInput.value = '';
            if (!reprezentandManual) reprezentand.value = '';
            applyCashRule();
            return;
        }

        const invSum = selected.reduce((a, i) => a + Number(i.remaining || 0), 0);
        const openSum = withOpening ? openingRemaining : 0;
        amountInput.value = (invSum + openSum).toFixed(2);
        amountManual = false;

        const parts = [];
        if (withOpening) {
            parts.push('sold inițial' + (openingDateRo ? ' din ' + openingDateRo : ''));
        }
        selected.forEach(i => {
            parts.push('factura ' + i.number + (i.issue_date_ro ? ' din ' + i.issue_date_ro : ''));
        });
        reprezentand.value = parts.join('; ');
        reprezentandManual = false;
        applyCashRule();
    }

    function applyCashRule() {
        const amt = parseFloat(amountInput.value || '0') || 0;
        const cur = (currency.value || 'RON').toUpperCase();
        const over = cur === 'RON' && amt > cashLimit + 0.0001;

        if (over) {
            if (instrument.value !== 'op') {
                autoSwitching = true;
                instrument.value = 'op';
                autoSwitching = false;
            }
            instrumentHint.hidden = false;
        } else if (!autoSwitching) {
            instrumentHint.hidden = instrument.value !== 'op' || amt <= cashLimit;
        }
        updateInstrumentUi();
    }

    function updateInstrumentUi() {
        const isOp = instrument.value === 'op';
        seriesWrap.style.opacity = isOp ? '0.45' : '1';
        seriesWrap.querySelector('select').disabled = isOp;
        submitBtn.textContent = isOp ? 'Spre salvare OP →' : 'Spre salvare chitanță →';
    }

    instrument.addEventListener('change', () => {
        const amt = parseFloat(amountInput.value || '0') || 0;
        const cur = (currency.value || 'RON').toUpperCase();
        if (instrument.value === 'receipt' && cur === 'RON' && amt > cashLimit) {
            instrument.value = 'op';
            instrumentHint.hidden = false;
        }
        updateInstrumentUi();
    });
    currency.addEventListener('change', applyCashRule);

    async function loadUnpaid(clientId) {
        unpaidList.innerHTML = '';
        openingRow.innerHTML = '';
        openingRow.hidden = true;
        invoices = [];
        openingRemaining = 0;
        openingDateRo = null;
        if (!clientId) {
            unpaidWrap.hidden = true;
            return;
        }
        unpaidWrap.hidden = false;
        unpaidList.innerHTML = '<div class="text-sm text-slate-500 px-1 py-2">Se încarcă…</div>';
        try {
            const url = unpaidUrl + (unpaidUrl.includes('?') ? '&' : '?') + 'client_id=' + encodeURIComponent(clientId);
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('fail');
            const data = await res.json();
            invoices = data.invoices || [];
            openingRemaining = Number((data.opening && data.opening.remaining) || 0);
            openingDateRo = (data.opening && data.opening.date_ro) || null;

            if (openingRemaining > 0.009) {
                openingRow.hidden = false;
                const row = document.createElement('label');
                row.className = 'dc-unpaid-row';
                row.innerHTML =
                    '<input type="checkbox" name="include_opening" value="1" checked>' +
                    '<span><strong>{{ __('Sold inițial') }}</strong><div class="meta">Se încasează primul' +
                    (openingDateRo ? ' · din ' + openingDateRo : '') + '</div></span>' +
                    '<span class="amt">' + fmtAmt(openingRemaining) + ' RON</span>';
                row.querySelector('input').addEventListener('change', syncFromSelection);
                openingRow.appendChild(row);
            }

            const bits = [];
            if (openingRemaining > 0.009) bits.push('sold inițial');
            if (invoices.length) bits.push(invoices.length + ' documente');
            unpaidCount.textContent = bits.join(' · ');

            if (!invoices.length) {
                unpaidList.innerHTML = openingRemaining > 0.009
                    ? '<div class="text-sm text-slate-500 px-1 py-2">Nu există documente neîncasate — poți încasa soldul inițial.</div>'
                    : '<div class="text-sm text-slate-500 px-1 py-2">Nu există facturi sau proforme neîncasate pentru acest client.</div>';
                syncFromSelection();
                return;
            }
            unpaidList.innerHTML = '';
            invoices.forEach(inv => {
                const row = document.createElement('label');
                row.className = 'dc-unpaid-row';
                const typeLabel = inv.type_label || (inv.type === 'proforma' ? 'Proformă' : 'Factură');
                row.innerHTML =
                    '<input type="checkbox" name="invoice_ids[]" value="' + inv.id + '" data-id="' + inv.id + '">' +
                    '<span><strong>' + inv.number + '</strong><div class="meta">' + typeLabel +
                    ' · emisă ' + (inv.issue_date_ro || '—') +
                    (inv.due_date ? ' · scadență ' + inv.due_date.split('-').reverse().join('/') : '') + '</div></span>' +
                    '<span class="amt">' + fmtAmt(inv.remaining) + ' ' + inv.currency + '</span>';
                row.querySelector('input').addEventListener('change', syncFromSelection);
                unpaidList.appendChild(row);
            });
            syncFromSelection();
        } catch (e) {
            unpaidList.innerHTML = '<div class="text-sm text-rose-600 px-1 py-2">Nu am putut încărca documentele de încasat.</div>';
        }
    }

    clientSelect.addEventListener('change', () => {
        amountManual = false;
        reprezentandManual = false;
        loadUnpaid(clientSelect.value);
    });

    if (clientSelect.value) loadUnpaid(clientSelect.value);
    updateInstrumentUi();
    applyCashRule();
})();
</script>
@endsection
