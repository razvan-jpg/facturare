@php
    $rec = $recurring ?? null;
    if (old('items') !== null) {
        $items = old('items');
    } elseif ($rec?->items) {
        $items = $rec->items->map(function ($i) {
            return [
                'product_id' => $i->product_id,
                'name' => $i->name,
                'description' => $i->description,
                'unit' => $i->unit,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
                'vat_rate' => $i->vat_rate,
            ];
        })->toArray();
    } else {
        $items = [['name' => '', 'description' => '', 'unit' => 'H87', 'quantity' => 1, 'unit_price' => 0, 'vat_rate' => $company->default_vat_rate]];
    }

    $productsForJs = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'unit' => \App\Support\MeasureUnits::canonicalName($p->unit),
            'price' => $p->price,
            'vat_rate' => $p->vat_rate,
        ];
    })->values();
    $measureUnitsForJs = app(\App\Services\MeasureUnitService::class)->listForJs($company);

    $currencies = $currencies ?? config('currencies');
    $paymentTerms = $paymentTerms ?? config('payment_terms');
    $seriesList = $seriesList ?? collect();
    $selectedCurrency = old('currency', $rec?->currency ?? 'RON');
    $defaultTerm = (string) ($company->defaultPaymentTerm() ?: '15');
    $selectedTerm = (string) old('payment_term', $rec?->payment_term ?? $defaultTerm);
    $dueDays = (int) old('due_days', $rec?->due_days ?? (ctype_digit($selectedTerm) ? (int) $selectedTerm : 15));
    $startDate = old('start_date', $rec?->start_date ?? now());
    $isActive = (string) old('active', ($rec?->active ?? true) ? '1' : '0') === '1';
    $nextRunDate = old('next_run_date', $isActive ? ($rec?->next_run_date ?? $startDate) : null);
    try {
        $issueBase = \Illuminate\Support\Carbon::parse($nextRunDate ?: $startDate);
        $duePreview = match (true) {
            $selectedTerm === 'issue' => $issueBase->copy(),
            $selectedTerm === 'month_end' => $issueBase->copy()->endOfMonth(),
            ctype_digit($selectedTerm) => $issueBase->copy()->addDays((int) $selectedTerm),
            $selectedTerm === 'date' => $issueBase->copy()->addDays($dueDays),
            default => $issueBase->copy()->addDays($dueDays),
        };
    } catch (\Throwable) {
        $duePreview = null;
    }
    $selectedDocType = old('document_type', $rec?->document_type ?? 'invoice');
    if (! in_array($selectedDocType, ['invoice', 'proforma'], true)) {
        $selectedDocType = 'invoice';
    }
    try {
        $issueYear = (int) \Illuminate\Support\Carbon::parse($nextRunDate)->format('Y');
    } catch (\Throwable) {
        $issueYear = (int) now()->format('Y');
    }
    $seriesForYear = $seriesList->where('year', $issueYear)->where('type', $selectedDocType);
    $selectedSeries = old(
        'series',
        $rec?->series
            ?? $seriesForYear->firstWhere('is_default', true)?->prefix
            ?? $seriesForYear->first()?->prefix
            ?? $seriesList->where('type', $selectedDocType)->first()?->prefix
    );
    $docLangs = $company->availableDocumentLanguages();
    $selectedLang = old('document_language', $rec?->document_language ?? 'ro');
    if (! array_key_exists($selectedLang, $docLangs)) {
        $selectedLang = 'ro';
    }
    $maxDocs = old('max_documents', $rec?->max_documents);
    if ($maxDocs === null || $maxDocs === '') {
        $maxDocs = '';
    }
@endphp

<link rel="stylesheet" href="{{ asset('css/date-boxes.css') }}?v=20260806g">

{{-- Cap formular — layout tip factură + câmpuri abonament --}}
<div class="dc-card p-5 mb-4 w-full">
    <div class="dc-doc-head-grid">
        <div>
            <div class="dc-client-tip">
                Pentru firme: selectează clientul după denumire sau CIF.<br>
                Poți adăuga clienți noi din meniul Clienți.
            </div>
            <div class="dc-field-row">
                <label class="dc-label" for="client_id">Nume sau CIF client</label>
                <div class="min-w-0">
                    <select name="client_id" id="client_id" class="dc-input" required>
                        <option value="">recomandăm CIF</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}"
                                    data-cui="{{ $client->cui }}"
                                    @selected(old('client_id', $rec?->client_id ?? '') == $client->id)>
                                {{ $client->name }}@if($client->cui) ({{ $client->cui }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="dc-field-row">
                <label class="dc-label" for="title">Denumire internă</label>
                <input id="title" name="title" value="{{ old('title', $rec?->title ?? '') }}" class="dc-input" placeholder="opțional, ex: Mentenanță lunară">
            </div>
            <div class="dc-field-row" style="grid-template-columns: 1fr">
                <span class="dc-label">Tip document emis</span>
                <div class="flex flex-wrap gap-4 pt-1">
                    @foreach(\App\Models\RecurringInvoice::DOCUMENT_TYPES as $type => $label)
                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" name="document_type" value="{{ $type }}"
                                   @checked($selectedDocType === $type)
                                   class="border-slate-300 text-amber-600 focus:ring-amber-500"
                                   data-doc-type>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="dc-series-hint">Abonamentul emite facturi fiscale sau proforme, după alegerea ta.</p>
            </div>
            <div class="flex flex-wrap gap-4 pt-2">
                <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                    <input type="hidden" name="auto_issue" value="0">
                    <input type="checkbox" name="auto_issue" value="1" @checked((string) old('auto_issue', ($rec?->auto_issue ?? true) ? '1' : '0') === '1') class="rounded border-slate-300">
                    Emite automat (nu doar draft)
                </label>
                <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" id="recurring_active" value="1" @checked((string) old('active', ($rec?->active ?? true) ? '1' : '0') === '1') class="rounded border-slate-300">
                    Abonament activ
                </label>
            </div>
        </div>

        <div>
            <div class="dc-field-row">
                <span class="dc-label">Data emiterii primei facturi</span>
                @include('partials.date-boxes', ['name' => 'start_date', 'label' => false, 'id' => 'start_date', 'value' => $startDate, 'required' => true])
            </div>

            <div class="dc-field-row" id="next-run-row">
                <span class="dc-label">Data emiterii următoarei facturi</span>
                @include('partials.date-boxes', ['name' => 'next_run_date', 'label' => false, 'id' => 'next_run_date', 'value' => $nextRunDate, 'required' => false])
                <p class="dc-series-hint" id="next-run-hint-active">La creare nouă, se aliniază automat cu data primei facturi până o modifici. Obligatorie doar dacă abonamentul e activ.</p>
                <p class="dc-series-hint hidden" id="next-run-hint-paused">Abonament inactiv: fără dată de emitere și fără scadență — nu se emit facturi automat.</p>
            </div>
            <div class="dc-field-row" id="payment-term-row">
                <label class="dc-label" for="payment_term">{{ __('Termen de plată') }}</label>
                <div class="dc-term-wrap">
                    <select name="payment_term" id="payment_term" class="dc-input">
                        @foreach($paymentTerms as $key => $label)
                            <option value="{{ $key }}" @selected($selectedTerm === (string) $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="due-date-preview" class="dc-series-next" title="Scadența următorului document (calculată față de data emiterii următoarei facturi)">
                        <strong id="due-date-preview-text">{{ ($isActive && $duePreview) ? $duePreview->format('d / m / Y') : '—' }}</strong>
                        <span>scadență</span>
                    </div>
                </div>
                <input type="hidden" name="due_days" id="due_days" value="{{ $dueDays }}">
            </div>

            <div class="dc-field-row">
                <label class="dc-label" for="frequency">{{ __('Frecvență') }}</label>
                <select name="frequency" id="frequency" class="dc-input" required>
                    @foreach(\App\Models\RecurringInvoice::FREQUENCIES as $value => $label)
                        <option value="{{ $value }}" @selected(old('frequency', $rec?->frequency ?? 'monthly') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="dc-field-row">
                <label class="dc-label" for="max_documents">Câte documente?</label>
                <div class="min-w-0">
                    <input type="number" name="max_documents" id="max_documents" class="dc-input"
                           value="{{ $maxDocs }}"
                           placeholder="nelimitat" min="-1" max="9999">
                    <p class="dc-series-hint">Folosește -1 sau lasă gol pentru nr. nelimitat</p>
                </div>
            </div>

            <div class="dc-field-row">
                <label class="dc-label" for="series">Serie document</label>
                <div class="min-w-0 w-full">
                    <select name="series" id="series" class="dc-input"
                            @disabled($seriesList->where('type', $selectedDocType)->isEmpty())>
                        @foreach($seriesList->where('active', true)->sortByDesc('year') as $s)
                            <option value="{{ $s->prefix }}"
                                    data-type="{{ $s->type }}"
                                    data-year="{{ $s->year }}"
                                    data-next="{{ $s->next_number }}"
                                    data-default="{{ $s->is_default ? '1' : '0' }}"
                                    @selected($selectedSeries === $s->prefix && $s->type === $selectedDocType)
                                    @if($s->type !== $selectedDocType) hidden disabled @endif>
                                {{ $s->prefix }} ({{ $s->year }})
                            </option>
                        @endforeach
                        @if($seriesList->where('type', $selectedDocType)->isEmpty())
                            <option value="" data-type="{{ $selectedDocType }}" data-empty="1">— configurează serii pentru tipul ales —</option>
                        @endif
                    </select>
                    <p class="dc-series-hint" id="series-hint">Seria folosită la emiterea automată a documentelor.</p>
                </div>
            </div>
        </div>

        <div>
            <div class="dc-field-row" style="grid-template-columns: 1fr">
                <label class="dc-label" for="currency">{{ __('Moneda factură') }}</label>
                <select name="currency" id="currency" class="dc-input" required>
                    @foreach($currencies as $code => $label)
                        <option value="{{ $code }}" @selected($selectedCurrency === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="dc-field-row" style="grid-template-columns: 1fr">
                <label class="dc-label" for="document_language">Limbă</label>
                <select name="document_language" id="document_language" class="dc-input">
                    @foreach($docLangs as $code => $label)
                        <option value="{{ $code }}" @selected($selectedLang === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="dc-field-row" style="grid-template-columns: 1fr">
                <span class="dc-label">Dată finală (opțional)</span>
                @include('partials.date-boxes', ['name' => 'end_date', 'label' => false, 'id' => 'end_date', 'value' => old('end_date', $rec?->end_date ?? null)])
                <p class="dc-series-hint">Poți limita și după dată, pe lângă „Câte documente?”.</p>
            </div>
        </div>
    </div>
</div>

@php $defaultVat = (float) $company->default_vat_rate; @endphp
<div class="dc-card mb-4 inv-lines-card">
    <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap justify-between items-center gap-2">
        <div>
            <span class="font-semibold">Produse / servicii (template)</span>
            <p class="text-xs text-slate-500 mt-0.5">Aceleași reguli ca pe factură. În produs/descriere poți folosi #luna#, #an# — se înlocuiesc la emitere.</p>
        </div>
        <div class="flex items-center gap-2">
            @include('partials.template-variables')
            <button type="button" id="add-line-btn" class="dc-btn-secondary text-xs">{{ __('+ Adaugă linie') }}</button>
        </div>
    </div>
    <div class="inv-lines-scroll">
        <table class="w-full inv-lines" id="items-table">
            <thead>
            <tr>
                <th class="col-product">Produs / serviciu <span class="font-normal normal-case tracking-normal text-slate-400">(obligatoriu)</span></th>
                <th class="col-description">Descriere <span class="font-normal normal-case tracking-normal text-slate-400">(opțional)</span></th>
                <th class="col-unit">UM</th>
                <th class="col-qty">Cant.</th>
                <th class="col-price">{{ __('Preț') }}</th>
                <th class="col-vat">TVA%</th>
                <th class="col-total">Valoare</th>
                <th class="col-actions"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $idx => $item)
                @include('recurring._line', ['idx' => $idx, 'item' => $item, 'defaultVat' => $defaultVat, 'company' => $company])
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100 flex flex-wrap justify-between items-end gap-3 bg-slate-50/80">
        <div class="min-w-0 flex-1 max-w-sm">
            <label class="dc-label" for="subscription_number">Număr abonament</label>
            <input id="subscription_number" name="subscription_number" class="dc-input"
                   value="{{ old('subscription_number', $rec?->subscription_number ?? '') }}"
                   maxlength="40" placeholder="ex: 0119">
            <p class="dc-series-hint">Opțional — apare pe facturile emise din abonament.</p>
        </div>
        <div class="text-right text-sm space-y-0.5">
            <div class="text-slate-500">Subtotal: <strong id="doc-subtotal">0,00</strong></div>
            <div class="text-slate-500">TVA: <strong id="doc-vat">0,00</strong></div>
            <div class="text-base font-semibold text-slate-900">Total: <strong id="doc-total">0,00</strong></div>
        </div>
    </div>
    <p class="px-4 pb-3 text-xs text-slate-500">Tastează în Produs sau Descriere — lista live se deschide sub câmp. Produsul e obligatoriu; descrierea e opțională și independentă. Dacă produsul nu există, se creează la salvare.</p>
</div>

<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
    <span class="text-sm font-semibold text-slate-700">Subsol factură</span>
    @include('partials.template-variables')
</div>
@include('partials.document-footer-fields', [
    'source' => $rec,
    'company' => $company,
    'docType' => 'recurring',
    'notesPlaceholder' => 'ex: Factură recurentă emisă pentru abonamentul #luna# #an#',
    'notesRows' => 3,
])

@push('scripts')
<script src="{{ asset('js/date-boxes.js') }}"></script>
<style>
.dc-tpl-vars { position: relative; display: inline-flex; }
.dc-tpl-vars-toggle {
    display: inline-flex; align-items: center; gap: 0.3rem;
    font-size: 0.75rem; font-weight: 650; color: #0f4c5c;
    background: #e8f2f4; border: 1px solid #c5dde2; border-radius: 999px;
    padding: 0.28rem 0.65rem; cursor: pointer;
}
.dc-tpl-vars-toggle:hover { background: #d7ebef; }
.dc-tpl-vars-panel {
    position: absolute; right: 0; top: calc(100% + 0.4rem); z-index: 40;
    width: min(22rem, 90vw); background: #fffef7; border: 1px solid #e8d9a8;
    border-radius: 0.75rem; box-shadow: 0 12px 28px rgba(16,42,67,0.14);
    padding: 0.85rem 1rem; text-align: left;
}
.dc-tpl-vars-title { font-weight: 700; color: #0a3440; margin-bottom: 0.35rem; }
.dc-tpl-vars-text { font-size: 0.78rem; color: #486581; line-height: 1.45; margin-bottom: 0.65rem; }
.dc-tpl-vars-actions { display: flex; gap: 0.4rem; margin-bottom: 0.65rem; }
.dc-tpl-chip {
    font-size: 0.78rem; font-weight: 700; color: #1a1205; background: #ffd089;
    border: 1px solid #e08a1e; border-radius: 0.4rem; padding: 0.28rem 0.55rem; cursor: pointer;
}
.dc-tpl-chip:hover { background: #ffc56a; }
.dc-tpl-vars-sub { font-size: 0.72rem; font-weight: 700; color: #829ab1; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.3rem; }
.dc-tpl-vars-examples { font-size: 0.75rem; color: #334e68; margin: 0 0 0.5rem; padding-left: 1rem; }
.dc-tpl-vars-examples code { font-size: 0.72rem; background: #f0f4f8; padding: 0.05rem 0.25rem; border-radius: 0.25rem; }
.dc-tpl-vars-examples em { font-style: normal; color: #0f4c5c; }
.dc-tpl-vars-hint { font-size: 0.72rem; color: #627d98; margin: 0; }
.dc-tpl-vars-hint code { background: #f0f4f8; padding: 0.05rem 0.25rem; border-radius: 0.25rem; }
.inv-lines-card { overflow: visible; }
.inv-lines-scroll { overflow-x: auto; overflow-y: visible; min-height: 180px; width: 100%; }
.inv-lines { border-collapse: separate; border-spacing: 0; width: 100%; min-width: 100%; table-layout: auto; }
.inv-lines th {
    text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .04em;
    color: #627d98; font-weight: 700; padding: 10px 8px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
}
.inv-lines td { padding: 8px; border-bottom: 1px solid #eef2f6; vertical-align: top; }
.inv-lines .dc-input { padding: .5rem .6rem; font-size: 13px; min-height: 2.4rem; }
.inv-lines .col-product { min-width: 220px; width: 36%; }
.inv-lines .col-description { min-width: 180px; width: 28%; }
.inv-product-wrap,
.inv-desc-wrap { position: relative; width: 100%; }
.inv-ac-list {
    position: absolute; left: 0; top: calc(100% + 3px); z-index: 80;
    min-width: 100%; width: max(100%, 24rem); max-width: min(40rem, 92vw);
    background: #fff; border: 1px solid #d9e2ec; border-radius: 0.55rem;
    max-height: 340px; overflow-y: auto; box-shadow: 0 12px 32px rgba(15,42,67,.16);
}
.inv-ac-item {
    display: block; width: 100%; text-align: left; padding: 0.65rem 0.8rem;
    font-size: 13px; border: 0; background: #fff; cursor: pointer; color: #102a43;
    border-bottom: 1px solid #f0f4f8;
}
.inv-ac-item:last-child { border-bottom: 0; }
.inv-ac-item:hover, .inv-ac-item.active { background: #fff4e0; }
.inv-ac-item small { display: block; color: #627d98; font-size: 11px; margin-top: 2px; line-height: 1.35; }
.inv-ac-empty { padding: 0.7rem 0.8rem; font-size: 12px; color: #627d98; }
.inv-lines .col-unit { width: 118px; }
.inv-lines .col-unit .item-unit { font-size: 12px; padding-left: .4rem; padding-right: .2rem; }
.inv-lines .col-qty { width: 76px; }
.inv-lines .col-price { width: 92px; }
.inv-lines .col-vat { width: 68px; }
/* Fără săgeți up/down pe cantitate / preț — doar tastare liberă */
.inv-lines input.item-qty,
.inv-lines input.item-price {
    -moz-appearance: textfield;
    appearance: textfield;
}
.inv-lines input.item-qty::-webkit-outer-spin-button,
.inv-lines input.item-qty::-webkit-inner-spin-button,
.inv-lines input.item-price::-webkit-outer-spin-button,
.inv-lines input.item-price::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.inv-lines select.item-vat {
    padding-right: 1.6rem;
}
.inv-lines .col-total { width: 96px; text-align: right; }
.inv-lines .col-actions { width: 48px; white-space: nowrap; }
.inv-lines .line-total { display: block; padding-top: 10px; font-size: 13px; font-weight: 600; text-align: right; white-space: nowrap; }
.inv-lines .remove-line {
    color: #b91c1c; font-size: 14px; line-height: 1; padding: 4px 6px; background: none; border: 0; cursor: pointer;
}
</style>
<script>
(() => {
    let rowIdx = {{ count($items) }};
    let products = @json($productsForJs);
    const defaultVat = @json((float) $company->default_vat_rate);
    const vatRates = [21, 11, 5, 0];
    function nearestVatRate(rate) {
        const cur = Number(rate);
        if (! Number.isFinite(cur)) return Number(defaultVat) || 21;
        return vatRates.reduce((best, r) => Math.abs(r - cur) < Math.abs(best - cur) ? r : best, vatRates[0]);
    }
    function vatSelectHtml(name, selected) {
        const picked = nearestVatRate(selected);
        return `<select name="${name}" class="dc-input item-vat">${vatRates.map((r) =>
            `<option value="${r}"${r === picked ? ' selected' : ''}>${r}%</option>`
        ).join('')}</select>`;
    }
    function setVatSelect(el, rate) {
        if (! el) return;
        el.value = String(nearestVatRate(rate));
    }
    const measureUnits = @json($measureUnitsForJs);
    const ensureUnitsDatalist = () => {
        let dl = document.getElementById('dc-units-live-rec');
        if (! dl) {
            dl = document.createElement('datalist');
            dl.id = 'dc-units-live-rec';
            document.body.appendChild(dl);
        }
        dl.innerHTML = (measureUnits.units || []).map(u => {
            const label = u.unece ? `${u.name} (${u.unece})` : u.name;
            return `<option value="${String(u.name).replace(/"/g,'&quot;')}">${label.replace(/</g,'&lt;')}</option>`;
        }).join('');
        return 'dc-units-live-rec';
    };
    const normalizeUnit = (raw) => {
        const trimmed = String(raw || '').trim();
        if (! trimmed) return measureUnits.default;
        const key = trimmed.toLowerCase();
        if (measureUnits.lookup && measureUnits.lookup[key]) return measureUnits.lookup[key];
        return trimmed.slice(0, 32);
    };
    const unitInputHtml = (selected, idx) => {
        const listId = ensureUnitsDatalist();
        const sel = normalizeUnit(selected);
        return `<input type="text" name="items[${idx}][unit]" value="${escapeHtml(sel)}" list="${listId}" class="dc-input item-unit" autocomplete="off" maxlength="32" placeholder="buc" title="Unitate de măsură — listă live sau text nou">`;
    };
    ensureUnitsDatalist();
    let dcTplLastField = null;

    document.addEventListener('focusin', (e) => {
        if (e.target && e.target.classList && e.target.classList.contains('dc-tpl-field')) {
            dcTplLastField = e.target;
        }
    });

    window.dcInsertTemplateVar = function (token) {
        const el = dcTplLastField || document.querySelector('.dc-tpl-field');
        if (!el) return;
        el.focus();
        const start = el.selectionStart ?? el.value.length;
        const end = el.selectionEnd ?? el.value.length;
        const value = el.value || '';
        el.value = value.slice(0, start) + token + value.slice(end);
        const pos = start + token.length;
        if (typeof el.setSelectionRange === 'function') {
            el.setSelectionRange(pos, pos);
        }
        el.dispatchEvent(new Event('input', { bubbles: true }));
    };

    function money(n) {
        return (Number(n) || 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function escapeHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
    }
    function mainRows() {
        return [...document.querySelectorAll('#items-table tbody tr[data-line-row]')];
    }
    function recalcRow(tr) {
        const qty = parseFloat(tr.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(tr.querySelector('.item-price')?.value) || 0;
        const vat = parseFloat(tr.querySelector('.item-vat')?.value) || 0;
        const el = tr.querySelector('.line-total');
        if (el) el.textContent = money(qty * price * (1 + vat / 100));
        recalcDoc();
    }
    function recalcDoc() {
        let sub = 0, vat = 0;
        mainRows().forEach(tr => {
            const qty = parseFloat(tr.querySelector('.item-qty')?.value) || 0;
            const price = parseFloat(tr.querySelector('.item-price')?.value) || 0;
            const rate = parseFloat(tr.querySelector('.item-vat')?.value) || 0;
            const lineSub = qty * price;
            sub += lineSub;
            vat += lineSub * rate / 100;
        });
        document.getElementById('doc-subtotal').textContent = money(sub);
        document.getElementById('doc-vat').textContent = money(vat);
        document.getElementById('doc-total').textContent = money(sub + vat);
    }
    function rowData(tr) {
        return {
            name: (tr.querySelector('.item-name')?.value || '').trim(),
            qty: tr.querySelector('.item-qty')?.value,
            price: tr.querySelector('.item-price')?.value,
            vat: tr.querySelector('.item-vat')?.value,
            productId: (tr.querySelector('.product-id')?.value || '').trim(),
        };
    }
    function isRowEmpty(tr) {
        const r = rowData(tr);
        return !r.name && !r.productId && (r.price === '' || Number(r.price) === 0);
    }
    function isRowComplete(tr) {
        const r = rowData(tr);
        if (!r.name) return false;
        if (r.qty === '' || Number.isNaN(Number(r.qty)) || Number(r.qty) === 0) return false;
        if (r.price === '' || Number.isNaN(Number(r.price))) return false;
        if (r.vat === '' || Number.isNaN(Number(r.vat))) return false;
        return true;
    }
    function validateLines() {
        const rows = mainRows();
        const problems = [];
        let complete = 0;
        rows.forEach((tr, i) => {
            if (isRowEmpty(tr)) {
                return; // rând rezervă gol — ignorat
            }
            if (!isRowComplete(tr)) {
                problems.push('Linia ' + (i + 1) + ': produsul e obligatoriu (cantitate ≠ 0, preț, TVA). Descrierea e opțională.');
                return;
            }
            complete++;
        });
        if (complete === 0) {
            return ['Adaugă cel puțin o linie completă pe abonament.'];
        }
        return problems;
    }
    function applyProduct(tr, p) {
        tr.querySelector('.product-id').value = p.id;
        tr.querySelector('.item-name').value = p.name || '';
        tr.querySelector('.item-unit').value = normalizeUnit(p.unit);
        tr.querySelector('.item-price').value = Number(p.price ?? 0).toFixed(2);
        setVatSelect(tr.querySelector('.item-vat'), p.vat_rate ?? defaultVat);
        hideAc(tr);
        recalcRow(tr);
    }
    function hideAc(tr) {
        tr.querySelectorAll('[data-ac-list]').forEach(list => {
            list.classList.add('hidden');
            list.innerHTML = '';
        });
    }
    function matchProducts(q) {
        const query = (q || '').trim().toLowerCase();
        return products.filter(p => {
            if (!query) return true;
            const name = String(p.name || '').toLowerCase();
            const desc = String(p.description || '').toLowerCase();
            return name.includes(query) || desc.includes(query);
        }).slice(0, 20);
    }
    function renderAc(tr, input) {
        if (!input) return;
        const wrap = input.closest('.inv-product-wrap, .inv-desc-wrap');
        const list = wrap?.querySelector('[data-ac-list]');
        if (!list) return;
        tr.querySelectorAll('[data-ac-list]').forEach(el => {
            if (el !== list) {
                el.classList.add('hidden');
                el.innerHTML = '';
            }
        });
        const query = (input.value || '').trim();
        const matches = matchProducts(query);
        if (!matches.length) {
            list.innerHTML = query
                ? `<div class="inv-ac-empty">Nu există în nomenclator — se creează automat la salvare.</div>`
                : `<div class="inv-ac-empty">Nomenclatorul este gol — tastează denumirea produsului.</div>`;
            list.classList.remove('hidden');
            return;
        }
        list.innerHTML = matches.map((p, i) => {
            const desc = (p.description || '').trim();
            const meta = `${escapeHtml(p.unit)} · ${Number(p.price).toFixed(2)} · TVA ${Number(p.vat_rate).toFixed(2)}%`;
            return `<button type="button" class="inv-ac-item${i===0?' active':''}" data-id="${p.id}">
                ${escapeHtml(p.name)}
                <small>${meta}${desc ? '<br>' + escapeHtml(desc) : ''}</small>
            </button>`;
        }).join('');
        list.classList.remove('hidden');
        list.querySelectorAll('.inv-ac-item').forEach(btn => {
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                const p = products.find(x => String(x.id) === String(btn.dataset.id));
                if (p) applyProduct(tr, p);
            });
        });
    }
    function bindLine(tr) {
        tr.querySelectorAll('.item-qty, .item-price').forEach(el => {
            el.addEventListener('input', () => recalcRow(tr));
        });
        tr.querySelector('.item-vat')?.addEventListener('change', () => recalcRow(tr));
        const nameInput = tr.querySelector('.item-product-input, .item-name');
        const descInput = tr.querySelector('.item-description');
        const acInputs = [nameInput, descInput].filter(Boolean);
        acInputs.forEach(input => {
            input.addEventListener('input', () => {
                if (input === nameInput) {
                    tr.querySelector('.product-id').value = '';
                }
                renderAc(tr, input);
            });
            input.addEventListener('focus', () => renderAc(tr, input));
            input.addEventListener('blur', () => setTimeout(() => hideAc(tr), 160));
        });
        tr.querySelectorAll('input').forEach(el => {
            el.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                const openList = [...tr.querySelectorAll('[data-ac-list]')].find(l => !l.classList.contains('hidden'));
                const active = openList ? openList.querySelector('.inv-ac-item.active') : null;
                if (active && acInputs.includes(el)) {
                    e.preventDefault();
                    e.stopPropagation();
                    active.dispatchEvent(new Event('mousedown'));
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                addRow();
            });
        });
        tr.querySelector('.remove-line')?.addEventListener('click', () => {
            if (mainRows().length <= 1) {
                tr.querySelector('.item-name').value = '';
                tr.querySelector('.item-description').value = '';
                tr.querySelector('.item-qty').value = '1.00';
                tr.querySelector('.item-price').value = '0.00';
                tr.querySelector('.product-id').value = '';
                hideAc(tr);
                recalcRow(tr);
                return;
            }
            tr.remove();
            recalcDoc();
        });
        recalcRow(tr);
    }
    function addRow() {
        const tbody = document.querySelector('#items-table tbody');
        const idx = rowIdx++;
        const main = document.createElement('tr');
        main.className = 'inv-line-main';
        main.setAttribute('data-line-row', '');
        main.innerHTML = `
            <td class="col-product">
                <div class="inv-product-wrap">
                    <input type="text" name="items[${idx}][name]" class="dc-input item-name item-product-input dc-tpl-field" placeholder="Produs / serviciu (obligatoriu)" autocomplete="off" data-autocomplete aria-required="true">
                    <input type="hidden" name="items[${idx}][product_id]" class="product-id" value="">
                    <div class="inv-ac-list hidden" data-ac-list></div>
                </div>
            </td>
            <td class="col-description">
                <div class="inv-desc-wrap">
                    <input type="text" name="items[${idx}][description]" class="dc-input item-description dc-tpl-field" placeholder="Descriere (opțional) — poți folosi #luna# #an#" autocomplete="off" data-autocomplete>
                    <div class="inv-ac-list hidden" data-ac-list></div>
                </div>
            </td>
            <td class="col-unit">${unitInputHtml(measureUnits.default, idx)}</td>
            <td class="col-qty"><input name="items[${idx}][quantity]" type="number" step="any" inputmode="decimal" value="1.00" class="dc-input item-qty"></td>
            <td class="col-price"><input name="items[${idx}][unit_price]" type="number" step="any" inputmode="decimal" value="0.00" class="dc-input item-price"></td>
            <td class="col-vat">${vatSelectHtml(`items[${idx}][vat_rate]`, defaultVat)}</td>
            <td class="col-total"><span class="line-total">0,00</span></td>
            <td class="col-actions"><button type="button" class="remove-line" title="{{ __('Șterge') }}">×</button></td>`;
        tbody.appendChild(main);
        bindLine(main);
        main.querySelector('.item-name').focus();
    }

    document.getElementById('add-line-btn')?.addEventListener('click', addRow);
    mainRows().forEach(tr => bindLine(tr));
    recalcDoc();

    const form = document.getElementById('items-table')?.closest('form');
    const activeCb = document.getElementById('recurring_active');
    const termSelect = document.getElementById('payment_term');
    const dueDaysInput = document.getElementById('due_days');
    const duePreview = document.getElementById('due-date-preview-text');
    const nextHintActive = document.getElementById('next-run-hint-active');
    const nextHintPaused = document.getElementById('next-run-hint-paused');
    const nextRunRow = document.getElementById('next-run-row');
    const paymentTermRow = document.getElementById('payment-term-row');
    const isEdit = @json((bool) ($rec?->id));
    let nextRunTouched = isEdit;

    function startIso() {
        return document.querySelector('#start_date .dc-datebox-value')?.value
            || document.querySelector('input[name="start_date"]')?.value
            || '';
    }
    function nextIso() {
        return document.querySelector('#next_run_date .dc-datebox-value')?.value
            || document.querySelector('input[name="next_run_date"]')?.value
            || '';
    }
    function setNextIso(iso) {
        const hidden = document.querySelector('#next_run_date .dc-datebox-value')
            || document.querySelector('input[name="next_run_date"]');
        if (hidden) {
            hidden.value = iso || '';
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
        const box = document.getElementById('next_run_date');
        if (box && iso && /^\d{4}-\d{2}-\d{2}$/.test(iso)) {
            const [y, m, d] = iso.split('-');
            const parts = {
                d: box.querySelector('[data-part="d"]'),
                m: box.querySelector('[data-part="m"]'),
                y: box.querySelector('[data-part="y"]'),
            };
            if (parts.d) parts.d.value = d;
            if (parts.m) parts.m.value = m;
            if (parts.y) parts.y.value = y;
            const native = box.querySelector('.dc-datebox-native');
            if (native) native.value = iso;
        } else if (box && !iso) {
            box.querySelectorAll('[data-part]').forEach((el) => { el.value = ''; });
            const native = box.querySelector('.dc-datebox-native');
            if (native) native.value = '';
        }
    }
    function clearNextIso() {
        setNextIso('');
        const hidden = document.querySelector('#next_run_date .dc-datebox-value')
            || document.querySelector('input[name="next_run_date"]');
        if (hidden) hidden.removeAttribute('required');
        nextRunTouched = true;
    }
    function pad(n) { return String(n).padStart(2, '0'); }
    function formatRo(iso) {
        if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '—';
        const [y, m, d] = iso.split('-');
        return d + ' / ' + m + ' / ' + y;
    }
    function addDays(iso, days) {
        const dt = new Date(iso + 'T12:00:00');
        if (Number.isNaN(dt.getTime())) return null;
        dt.setDate(dt.getDate() + days);
        return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate());
    }
    function monthEnd(iso) {
        const dt = new Date(iso + 'T12:00:00');
        if (Number.isNaN(dt.getTime())) return null;
        const last = new Date(dt.getFullYear(), dt.getMonth() + 1, 0);
        return last.getFullYear() + '-' + pad(last.getMonth() + 1) + '-' + pad(last.getDate());
    }
    function syncTerm() {
        if (activeCb && ! activeCb.checked) {
            if (duePreview) duePreview.textContent = '—';
            return;
        }
        const start = startIso();
        if (! nextRunTouched && start) {
            setNextIso(start);
        }
        const base = nextIso() || start;
        const term = termSelect?.value || '15';
        let due = null;
        let days = 15;
        if (term === 'none' || term === 'issue') {
            days = 0;
            due = base;
        } else if (term === 'month_end') {
            due = monthEnd(base);
            days = base && due ? Math.max(0, Math.round((new Date(due + 'T12:00:00') - new Date(base + 'T12:00:00')) / 86400000)) : 0;
        } else if (/^\d+$/.test(term)) {
            days = parseInt(term, 10);
            due = addDays(base, days);
        } else {
            days = parseInt(dueDaysInput?.value || '15', 10) || 15;
            due = addDays(base, days);
        }
        if (dueDaysInput) dueDaysInput.value = String(days);
        if (duePreview) duePreview.textContent = formatRo(due);
    }
    function syncActiveUi() {
        const on = !!activeCb?.checked;
        if (nextHintActive) nextHintActive.classList.toggle('hidden', !on);
        if (nextHintPaused) nextHintPaused.classList.toggle('hidden', on);
        if (nextRunRow) nextRunRow.classList.toggle('opacity-50', !on);
        if (paymentTermRow) paymentTermRow.classList.toggle('opacity-50', !on);
        if (!on) {
            clearNextIso();
            if (duePreview) duePreview.textContent = '—';
            return;
        }
        if (! nextIso()) {
            nextRunTouched = false;
            setNextIso(startIso());
        }
        syncTerm();
    }

    form?.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        if (e.target.closest('#items-table')) {
            e.preventDefault();
        }
    });
    form?.addEventListener('submit', (e) => {
        const isActive = !!activeCb?.checked;
        if (! isActive) {
            clearNextIso();
            if (duePreview) duePreview.textContent = '—';
        } else if (! nextIso()) {
            e.preventDefault();
            alert('Completează data emiterii următoarei facturi (abonamentul e activ).');
            document.getElementById('next_run_date')?.querySelector('[data-part="d"]')?.focus();
            return;
        }
        if (! startIso()) {
            e.preventDefault();
            alert('Completează data emiterii primei facturi.');
            document.getElementById('start_date')?.querySelector('[data-part="d"]')?.focus();
            return;
        }
        const client = document.getElementById('client_id');
        if (client && ! client.value) {
            e.preventDefault();
            alert('Alege clientul.');
            client.focus();
            return;
        }
        const problems = validateLines();
        if (problems.length) {
            e.preventDefault();
            alert(problems.join('\n'));
            mainRows().find(tr => !isRowEmpty(tr) && !isRowComplete(tr))?.querySelector('.item-name')?.focus();
        }
    });

    termSelect?.addEventListener('change', syncTerm);
    document.getElementById('start_date')?.addEventListener('dc-date-change', syncTerm);
    document.getElementById('next_run_date')?.addEventListener('dc-date-change', () => {
        nextRunTouched = true;
        syncTerm();
    });
    activeCb?.addEventListener('change', syncActiveUi);
    syncActiveUi();

    const seriesSelect = document.getElementById('series');
    function currentDocType() {
        return document.querySelector('input[name="document_type"]:checked')?.value || 'invoice';
    }
    function filterSeriesByType() {
        if (! seriesSelect) return;
        const type = currentDocType();
        const prev = seriesSelect.value;
        let preferred = null;
        let first = null;
        [...seriesSelect.options].forEach((opt) => {
            if (opt.dataset.empty === '1') {
                opt.hidden = true;
                opt.disabled = true;
                return;
            }
            const match = (opt.dataset.type || '') === type;
            opt.hidden = ! match;
            opt.disabled = ! match;
            if (match) {
                if (! first) first = opt.value;
                if (opt.dataset.default === '1' && ! preferred) preferred = opt.value;
            }
        });
        const stillValid = [...seriesSelect.options].some((o) => ! o.disabled && o.value === prev);
        if (stillValid) {
            seriesSelect.value = prev;
        } else {
            seriesSelect.value = preferred || first || '';
        }
        seriesSelect.disabled = ! [...seriesSelect.options].some((o) => ! o.disabled && o.value);
    }
    document.querySelectorAll('input[name="document_type"]').forEach((el) => {
        el.addEventListener('change', filterSeriesByType);
    });
    filterSeriesByType();
})();
</script>
@endpush
