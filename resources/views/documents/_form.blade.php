@php
    $doc = $document ?? null;
    $type = $type ?? $doc->type ?? 'invoice';
    $items = old('items', $doc?->items?->map(fn ($i) => [
        'product_id' => $i->product_id,
        'name' => $i->name,
        'description' => $i->description,
        'unit' => $i->unit,
        'quantity' => $i->quantity,
        'unit_price' => $i->unit_price,
        'vat_rate' => $i->vat_rate,
        'details' => $i->details ?? [],
    ])->toArray() ?? [['name' => '', 'description' => '', 'unit' => 'H87', 'quantity' => 1, 'unit_price' => 0, 'vat_rate' => $company->default_vat_rate, 'details' => []]]);
    $defaultVat = $company->default_vat_rate;
    $currencies = $currencies ?? config('currencies');
    $paymentTerms = $paymentTerms ?? config('payment_terms');
    $seriesList = $seriesList ?? collect();
    $selectedCurrency = old('currency', $doc?->currency ?? 'RON');
    $defaultTerm = $company->defaultPaymentTerm();
    $defaultDueDays = $company->defaultDueDays();
    $selectedTerm = (string) old('payment_term', $doc?->resolvedPaymentTerm() ?? $defaultTerm);
    $dueDateValue = old('due_date', $doc?->due_date);
    if (! $dueDateValue && $selectedTerm !== 'none') {
        $issueBase = old('issue_date', $doc?->issue_date) ?: now();
        try {
            $issueCarbon = \Illuminate\Support\Carbon::parse($issueBase);
            $dueDateValue = match (true) {
                $selectedTerm === 'issue' => $issueCarbon,
                $selectedTerm === 'month_end' => $issueCarbon->copy()->endOfMonth(),
                ctype_digit($selectedTerm) => $issueCarbon->copy()->addDays((int) $selectedTerm),
                $selectedTerm === 'date' && ! $doc => $issueCarbon->copy()->addDays($defaultDueDays),
                default => null,
            };
        } catch (\Throwable) {
            $dueDateValue = null;
        }
    }
    $issueYear = (int) ($doc?->issue_date?->format('Y') ?: now()->format('Y'));
    $seriesForYear = $seriesList->where('year', $issueYear);
    $selectedSeries = old(
        'series',
        $doc?->series
            ?? $seriesForYear->firstWhere('is_default', true)?->prefix
            ?? $seriesForYear->first()?->prefix
            ?? $seriesList->first()?->prefix
    );
@endphp
<input type="hidden" name="type" value="{{ $type }}">
<link rel="stylesheet" href="{{ asset('css/date-boxes.css') }}?v=20260806f">

{{-- Antet document — stil SmartBill --}}
<div class="dc-card p-5 mb-4 w-full">
    <div class="dc-doc-head-grid">
        <div>
            <div class="dc-client-tip">
                Pentru firme: tastează CIF (CUI) și Enter — preluăm datele din ANAF.<br>
                Pentru persoane fizice: tastează CNP (13 cifre) sau „-” + Enter; la „-” se creează mereu o persoană nouă.
            </div>
            <div class="dc-field-row">
                <label class="dc-label" for="client_query">{{ __('Nume sau CIF/CNP') }}</label>
                <div class="min-w-0">
                    <div class="flex gap-2">
                        <input type="hidden" name="client_id" id="client_id" value="{{ old('client_id', $doc->client_id ?? '') }}">
                        <input type="text" id="client_query" class="dc-input flex-1" list="client-suggestions"
                               placeholder="CIF firmă / CNP sau „-” pt. pers. / caută nume"
                               autocomplete="off" value="">
                        <button type="button" id="client-resolve-btn" class="dc-btn-primary text-xs whitespace-nowrap">{{ __('Adaugă') }}</button>
                        <button type="button" id="toggle-quick-client" class="dc-btn-secondary text-xs whitespace-nowrap">+ ANAF</button>
                    </div>
                    <datalist id="client-suggestions">
                        @foreach($clients as $client)
                            @php
                                $idBit = $client->type === 'person'
                                    ? ($client->cnp ?: '-')
                                    : ($client->cui ?: '');
                                $optLabel = $client->name.($idBit !== '' ? ' ('.$idBit.')' : '');
                            @endphp
                            <option value="{{ $optLabel }}" data-id="{{ $client->id }}"></option>
                        @endforeach
                    </datalist>
                    <select id="client_id_select" class="dc-input mt-2" aria-label="Clienți existenți">
                        <option value="">— sau alege din listă —</option>
                        @foreach($clients as $client)
                            @php
                                $idBit = $client->type === 'person'
                                    ? ($client->cnp ?: '-')
                                    : ($client->cui ?: '');
                            @endphp
                            <option value="{{ $client->id }}"
                                    data-type="{{ $client->type }}"
                                    data-cui="{{ $client->cui }}"
                                    data-cnp="{{ $client->cnp }}"
                                    data-name="{{ $client->name }}"
                                    data-address="{{ $client->fullAddress() }}"
                                    @selected(old('client_id', $doc->client_id ?? '') == $client->id)>
                                {{ $client->name }}@if($idBit !== '') ({{ $idBit }})@endif
                            </option>
                        @endforeach
                    </select>
                    <p id="client-meta" class="text-xs text-slate-500 mt-1 min-h-[1rem]"></p>
                </div>
            </div>

            <div id="quick-client-box" class="hidden rounded-lg border border-amber-200 bg-amber-50/50 p-3 space-y-2 mb-2">
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <label class="dc-label">CUI / CIF firmă</label>
                        <input type="text" id="quick-client-cui" class="dc-input" placeholder="ex: 38254880" autocomplete="off">
                    </div>
                    <button type="button" id="quick-client-anaf" class="dc-btn-secondary whitespace-nowrap">Preluare ANAF</button>
                </div>
                <p id="quick-client-status" class="text-xs text-slate-600"></p>
                <div id="quick-client-preview" class="hidden text-sm text-slate-800 space-y-1"></div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="quick-client-save" class="dc-btn-primary text-xs" disabled>
                        {{ $type === 'proforma' ? 'Adaugă pe proformă' : ($type === 'invoice' ? 'Adaugă pe factură' : 'Adaugă pe document') }}
                    </button>
                    <button type="button" id="quick-client-cancel" class="dc-btn-secondary text-xs">{{ __('Anulează') }}</button>
                </div>
            </div>

            <div id="person-client-box" class="hidden rounded-lg border border-sky-200 bg-sky-50/60 p-3 space-y-2 mb-2">
                <p class="text-sm text-slate-700" id="person-client-hint">Persoană fizică nouă — completează numele.</p>
                <div class="grid sm:grid-cols-2 gap-2">
                    <div>
                        <label class="dc-label" for="person-client-id">CNP / „-”</label>
                        <input type="text" id="person-client-id" class="dc-input" autocomplete="off">
                    </div>
                    <div>
                        <label class="dc-label" for="person-client-name">Nume și prenume</label>
                        <input type="text" id="person-client-name" class="dc-input" placeholder="obligatoriu" autocomplete="name">
                    </div>
                </div>
                <p id="person-client-status" class="text-xs text-slate-600"></p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="person-client-save" class="dc-btn-primary text-xs">Adaugă pe factură</button>
                    <button type="button" id="person-client-cancel" class="dc-btn-secondary text-xs">{{ __('Anulează') }}</button>
                </div>
            </div>

        </div>

        <div>
            <div class="dc-field-row">
                <span class="dc-label">Data emiterii</span>
                @include('partials.date-boxes', ['name' => 'issue_date', 'label' => false, 'id' => 'issue_date', 'value' => old('issue_date', $doc?->issue_date ?? now()), 'required' => true])
            </div>

            <div class="dc-field-row">
                <label class="dc-label" for="payment_term">{{ __('Termen de plată') }}</label>
                <div class="dc-term-wrap">
                    <select name="payment_term" id="payment_term" class="dc-input">
                        @foreach($paymentTerms as $key => $label)
                            <option value="{{ $key }}" @selected($selectedTerm === (string) $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="due-date-wrap" style="{{ $selectedTerm === 'none' ? 'opacity:0.45' : '' }}">
                        @include('partials.date-boxes', ['name' => 'due_date', 'label' => false, 'id' => 'due_date', 'value' => $dueDateValue])
                    </div>
                </div>
            </div>

            <div class="dc-field-row">
                <label class="dc-label" for="series">{{ __('Serie și număr') }}</label>
                <div class="min-w-0 w-full">
                    @if($doc && $doc->status !== 'draft' && filled($doc->number_full))
                        <div class="dc-series-next">
                            <strong>{{ $doc->number_full }}</strong>
                            <span>{{ __('număr emis') }}</span>
                        </div>
                        <input type="hidden" name="series" value="{{ $doc->series }}">
                    @else
                        <select name="series" id="series" class="dc-input" @disabled($seriesList->isEmpty())>
                            {{-- opțiunile se umplu din JS după toate plajele active pe anul emiterii --}}
                        </select>
                        <p class="dc-series-hint" id="series-hint">
                            @if($seriesList->isEmpty())
                                Nu există serii active pentru acest tip — configurează în Setări → Serii.
                            @else
                                {{ __('Alege plaja de numerotare (seria activă). Poți schimba între toate seriile active.') }}
                            @endif
                        </p>
                        <div class="dc-series-next" id="series-next-preview">
                            <strong id="series-next-full">{{ $doc?->number_full ?: '—' }}</strong>
                            <span id="series-next-label">{{ $doc?->hasNumberReservation() ? __('număr rezervat') : __('se rezervă…') }}</span>
                        </div>
                        <div class="mt-2" id="series-number-pick-wrap" @if(!($doc?->hasNumberReservation())) style="display:none" @endif>
                            <label class="dc-label" for="series-number-pick">{{ __('Număr (inclusiv goluri libere)') }}</label>
                            <select id="series-number-pick" class="dc-input">
                                @if($doc?->number)
                                    <option value="{{ $doc->number }}" selected>{{ $doc->number_full }} — rezervat</option>
                                @endif
                            </select>
                            <p class="dc-series-hint" id="series-gaps-hint"></p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <div class="dc-field-row" style="grid-template-columns: 1fr">
                <label class="dc-label" for="currency">{{ $type === 'proforma' ? __('Moneda proformă') : ($type === 'invoice' ? __('Moneda factură') : __('Monedă')) }}</label>
                <select name="currency" id="currency" class="dc-input">
                    @foreach($currencies as $code => $label)
                        <option value="{{ $code }}" @selected($selectedCurrency === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div id="fx-rate-wrap" class="dc-field-row" style="grid-template-columns: 1fr; {{ $selectedCurrency === 'RON' ? 'display:none' : '' }}">
                <label class="dc-label" for="exchange_rate">{{ __('Curs valutar (RON)') }}</label>
                <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="exchange_rate"
                       value="{{ old('exchange_rate', $doc->exchange_rate ?? '') }}"
                       class="dc-input" placeholder="ex: 5.2489">
                <p class="dc-fx-hint" id="fx-rate-hint">Curs BNR — poți modifica manual.</p>
            </div>

            <div class="dc-field-row" style="grid-template-columns: 1fr">
                <label class="dc-label" for="document_language">{{ __('Limbă document') }}</label>
                @php
                    $docLangs = $company->availableDocumentLanguages();
                    $selectedLang = old('document_language', $doc?->document_language ?? 'ro');
                    if (! array_key_exists($selectedLang, $docLangs)) {
                        $selectedLang = 'ro';
                    }
                @endphp
                <select name="document_language" id="document_language" class="dc-input">
                    @foreach($docLangs as $code => $label)
                        <option value="{{ $code }}" @selected($selectedLang === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="dc-fx-hint">{{ __('PDF-ul se generează în limba aleasă. Activează limbi în Setări → Limbi.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Linii — produs obligatoriu, descriere opțională (independente) --}}
<div class="dc-card mb-4 inv-lines-card">
    <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap justify-between items-center gap-2">
        <span class="font-semibold">{{ __('Produse / servicii') }}</span>
        <button type="button" id="add-line-btn" class="dc-btn-secondary text-xs">{{ __('+ Adaugă linie') }}</button>
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
                @include('documents._line', ['idx' => $idx, 'item' => $item, 'products' => $products, 'defaultVat' => $defaultVat, 'company' => $company])
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100 flex flex-wrap justify-between items-center gap-3 bg-slate-50/80">
        <p class="text-xs text-slate-500">Tastează în Produs sau Descriere — lista live se deschide sub câmp. Produsul e obligatoriu; descrierea e opțională și independentă. Dacă produsul nu există, se creează la salvare. ▾ detalii e-Factura.</p>
        <div class="text-right text-sm space-y-0.5">
            <div class="text-slate-500">{{ __('Subtotal') }}: <strong id="doc-subtotal">0,00</strong></div>
            <div class="text-slate-500">{{ __('TVA') }}: <strong id="doc-vat">0,00</strong></div>
            <div class="text-base font-semibold text-slate-900">{{ __('Total') }}: <strong id="doc-total">0,00</strong></div>
        </div>
    </div>
</div>

@include('partials.document-footer-fields', [
    'source' => $doc,
    'company' => $company,
    'docType' => $type,
    'notesPlaceholder' => match ($type) {
        'proforma' => 'Mențiuni pe proformă (opțional)',
        'delivery' => 'Mențiuni pe aviz (opțional)',
        'receipt' => 'Mențiuni pe chitanță (opțional)',
        default => 'Mențiuni pe factură (opțional)',
    },
])

<div class="flex flex-wrap gap-3 items-center">
    <button type="submit" name="action" value="draft" class="dc-btn-secondary" id="btn-save-draft">{{ __('Salvează draft') }}</button>
    <button type="submit" name="action" value="issue" class="dc-btn-primary" id="btn-save-issue">{{ __('Salvează și emite') }}</button>
    @if($doc?->id)
        <a href="{{ route('documents.show', $doc) }}" class="dc-btn-secondary">{{ __('Renunță') }}</a>
    @endif
</div>

@php
    $productsForJs = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'description' => (string) ($p->description ?? ''),
        'unit' => \App\Support\MeasureUnits::canonicalName($p->unit),
        'price' => (float) $p->price,
        'vat_rate' => (float) $p->vat_rate,
    ])->values();
    $measureUnitsForJs = app(\App\Services\MeasureUnitService::class)->listForJs($company);
@endphp
@push('scripts')
<script src="{{ asset('js/date-boxes.js') }}"></script>
<script>
(() => {
    // Expuse pe window — folosite și de scriptul liniilor (alt IIFE).
    window.dcMeasureUnits = @json($measureUnitsForJs);
    window.dcEnsureUnitsDatalist = () => {
        let dl = document.getElementById('dc-units-live');
        if (! dl) {
            dl = document.createElement('datalist');
            dl.id = 'dc-units-live';
            document.body.appendChild(dl);
        }
        const units = window.dcMeasureUnits.units || [];
        dl.innerHTML = units.map(u => {
            const label = u.unece ? `${u.name} (${u.unece})` : u.name;
            return `<option value="${String(u.name).replace(/"/g,'&quot;')}">${label.replace(/</g,'&lt;')}</option>`;
        }).join('');
        return 'dc-units-live';
    };
    window.dcNormalizeUnit = (raw) => {
        const measureUnits = window.dcMeasureUnits;
        const trimmed = String(raw || '').trim();
        if (! trimmed) return measureUnits.default;
        const key = trimmed.toLowerCase();
        if (measureUnits.lookup && measureUnits.lookup[key]) {
            return measureUnits.lookup[key];
        }
        // U/M nouă — o păstrăm; se creează la salvare pe server.
        return trimmed.slice(0, 32);
    };
    window.dcUnitInputHtml = (selected) => {
        const listId = window.dcEnsureUnitsDatalist();
        const sel = window.dcNormalizeUnit(selected);
        const esc = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;');
        return `<input type="text" name="__unit__" value="${esc(sel)}" list="${listId}" class="dc-input item-unit" autocomplete="off" maxlength="32" placeholder="buc" title="Unitate de măsură — listă live sau text nou">`;
    };
    window.dcEnsureUnitsDatalist();
    const termSelect = document.getElementById('payment_term');
    const dueWrap = document.getElementById('due-date-wrap');
    const currencySelect = document.getElementById('currency');
    const fxWrap = document.getElementById('fx-rate-wrap');
    const fxInput = document.getElementById('exchange_rate');
    const fxHint = document.getElementById('fx-rate-hint');
    const fxUrl = @json(route('documents.fx-rate'));

    function issueIso() {
        return document.querySelector('#issue_date .dc-datebox-value')?.value || '';
    }
    function setDueIso(iso) {
        const root = document.querySelector('#due_date');
        if (!root) return;
        const dEl = root.querySelector('[data-part="d"]');
        const mEl = root.querySelector('[data-part="m"]');
        const yEl = root.querySelector('[data-part="y"]');
        const hidden = root.querySelector('.dc-datebox-value');
        const native = root.querySelector('.dc-datebox-native');
        if (!iso) {
            dEl.value = ''; mEl.value = ''; yEl.value = '';
            hidden.value = '';
            if (native) native.value = '';
            return;
        }
        const [y, m, d] = iso.split('-');
        dEl.value = d; mEl.value = m; yEl.value = y;
        hidden.value = iso;
        if (native) native.value = iso;
    }
    function addDays(iso, days) {
        const dt = new Date(iso + 'T12:00:00');
        dt.setDate(dt.getDate() + days);
        return dt.toISOString().slice(0, 10);
    }
    function monthEnd(iso) {
        const dt = new Date(iso + 'T12:00:00');
        const end = new Date(dt.getFullYear(), dt.getMonth() + 1, 0);
        const m = String(end.getMonth() + 1).padStart(2, '0');
        const d = String(end.getDate()).padStart(2, '0');
        return `${end.getFullYear()}-${m}-${d}`;
    }
    function applyPaymentTerm() {
        const term = termSelect.value;
        const issue = issueIso();
        if (term === 'none') {
            dueWrap.style.opacity = '0.45';
            setDueIso('');
            document.querySelector('#due_date .dc-datebox-value').value = '';
            return;
        }
        dueWrap.style.opacity = '1';
        if (!issue) return;
        if (term === 'issue') setDueIso(issue);
        else if (term === 'month_end') setDueIso(monthEnd(issue));
        else if (/^\d+$/.test(term)) setDueIso(addDays(issue, parseInt(term, 10)));
        // 'date' = user picks freely
    }
    termSelect?.addEventListener('change', () => {
        if (termSelect.value !== 'date') applyPaymentTerm();
        else dueWrap.style.opacity = '1';
    });

    async function loadFxRate() {
        const code = currencySelect.value;
        if (code === 'RON') {
            fxWrap.style.display = 'none';
            fxInput.value = '1';
            fxInput.removeAttribute('required');
            return;
        }
        fxWrap.style.display = '';
        fxInput.required = true;
        fxHint.textContent = 'Preluare curs BNR…';
        try {
            const res = await fetch(fxUrl + '?currency=' + encodeURIComponent(code), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Eroare curs');
            fxInput.value = Number(data.rate).toFixed(4);
            fxHint.textContent = 'Curs BNR — poți modifica manual.';
        } catch (e) {
            fxHint.textContent = e.message || 'Nu am putut prelua cursul. Introdu manual.';
        }
    }
    currencySelect?.addEventListener('change', loadFxRate);
    if (currencySelect && currencySelect.value !== 'RON' && !fxInput.value) {
        loadFxRate();
    }
    // La creare: calculează scadența. La editare: păstrează valorile salvate.
    @if(! $doc)
    applyPaymentTerm();
    @else
    if (termSelect?.value === 'none') dueWrap.style.opacity = '0.45';
    @endif

    const seriesSelect = document.getElementById('series');
    const seriesFull = document.getElementById('series-next-full');
    const seriesHint = document.getElementById('series-hint');
    @php
        $seriesForJs = $seriesList->map(static function ($s) {
            return [
                'id' => $s->id,
                'prefix' => $s->prefix,
                'year' => (int) $s->year,
                'next_number' => (int) $s->next_number,
                'is_default' => (bool) $s->is_default,
                'description' => (string) ($s->description ?? ''),
            ];
        })->values();
    @endphp
    const allSeries = @json($seriesForJs);
    const preferredSeries = @json($selectedSeries);
    function padNum(n) { return String(n).padStart(4, '0'); }
    function issueYear() {
        const iso = issueIso();
        if (iso && iso.length >= 4) return iso.slice(0, 4);
        return String(new Date().getFullYear());
    }
    function refreshSeriesOptions() {
        if (!seriesSelect) return;
        const year = issueYear();
        const matching = allSeries.filter(s => String(s.year) === String(year));
        const previous = seriesSelect.value || preferredSeries || '';

        seriesSelect.innerHTML = '';
        seriesSelect.disabled = matching.length === 0;

        if (matching.length === 0) {
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'Nicio serie activă pentru anul ' + year;
            seriesSelect.appendChild(empty);
            if (seriesHint) {
                seriesHint.textContent = allSeries.length
                    ? 'Nu există plajă activă pentru anul ' + year + '. Schimbă data emiterii sau activează o serie în Setări → Serii.'
                    : 'Nu există serii active pentru acest tip — configurează în Setări → Serii.';
            }
            updateSeriesPreview();
            return;
        }

        matching.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.prefix;
            opt.dataset.year = String(s.year);
            opt.dataset.next = String(s.next_number);
            opt.dataset.default = s.is_default ? '1' : '0';
            let label = s.prefix + ' — următorul nr. ' + padNum(s.next_number);
            if (s.is_default) label += ' (implicită)';
            if (s.description) label += ' · ' + s.description;
            opt.textContent = label;
            seriesSelect.appendChild(opt);
        });

        const stillValid = matching.some(s => s.prefix === previous);
        if (stillValid) {
            seriesSelect.value = previous;
        } else {
            const def = matching.find(s => s.is_default) || matching[0];
            seriesSelect.value = def.prefix;
        }

        if (seriesHint) {
            seriesHint.textContent = matching.length > 1
                ? 'Ai ' + matching.length + ' plaje active — alege seria dorită din listă.'
                : 'Serie activă pentru anul ' + year + '.';
        }
        updateSeriesPreview();
    }
    const seriesLabel = document.getElementById('series-next-label');
    const reservationMeta = {
        documentId: @json($doc?->id),
        reserveUrl: @json($doc?->id ? route('documents.reserve-number', $doc) : null),
        releaseUrl: @json($doc?->id ? route('documents.release-number', $doc) : null),
        touchUrl: @json($doc?->id ? route('documents.touch-number', $doc) : null),
        csrf: @json(csrf_token()),
        reservedFull: @json($doc?->number_full),
    };
    let reserveTimer = null;
    let issuedSaved = false;

    function updateSeriesPreview() {
        if (!seriesSelect || !seriesFull) return;
        if (reservationMeta.reservedFull) {
            seriesFull.textContent = reservationMeta.reservedFull;
            if (seriesLabel) seriesLabel.textContent = 'număr rezervat';
            return;
        }
        const opt = seriesSelect.selectedOptions[0];
        if (!opt || !opt.value) {
            seriesFull.textContent = '—';
            return;
        }
        seriesFull.textContent = opt.value + '-' + padNum(opt.dataset.next || 1);
        if (seriesLabel) seriesLabel.textContent = 'se rezervă…';
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': reservationMeta.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : '{}',
            credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Eroare rezervare număr');
        return data;
    }

    const numberPick = document.getElementById('series-number-pick');
    const numberPickWrap = document.getElementById('series-number-pick-wrap');
    const gapsHint = document.getElementById('series-gaps-hint');
    let suppressNumberChange = false;

    function pad4(n) { return String(n).padStart(4, '0'); }

    function fillNumberPick(data) {
        if (!numberPick) return;
        const series = (data.series || seriesSelect?.value || '').trim();
        const available = Array.isArray(data.available_numbers) ? data.available_numbers : [];
        const gaps = Array.isArray(data.gap_numbers) ? data.gap_numbers : [];
        const gapSet = new Set(gaps);
        const current = data.number != null ? Number(data.number) : null;
        const opts = new Set(available);
        if (current != null) opts.add(current);
        const sorted = Array.from(opts).sort((a, b) => a - b);
        suppressNumberChange = true;
        numberPick.innerHTML = '';
        sorted.forEach(n => {
            const opt = document.createElement('option');
            opt.value = String(n);
            let label = series + '-' + pad4(n);
            if (current === n) label += ' — rezervat';
            else if (gapSet.has(n)) label += ' — liber (gol)';
            else label += ' — următorul';
            opt.textContent = label;
            if (current === n) opt.selected = true;
            numberPick.appendChild(opt);
        });
        suppressNumberChange = false;
        if (numberPickWrap) numberPickWrap.style.display = sorted.length ? '' : 'none';
        if (gapsHint) {
            gapsHint.textContent = gaps.length
                ? ('Goluri libere: ' + gaps.slice(0, 20).map(n => series + '-' + pad4(n)).join(', ') + (gaps.length > 20 ? '…' : ''))
                : 'Nu există goluri — se folosește următorul număr din serie.';
        }
    }

    async function reserveNumberNow(forcedNumber) {
        if (!reservationMeta.reserveUrl || !seriesSelect) return;
        const series = seriesSelect.value;
        const issueDate = issueIso();
        const body = { series, issue_date: issueDate || undefined };
        if (forcedNumber != null && forcedNumber !== '') body.number = Number(forcedNumber);
        try {
            if (seriesLabel) seriesLabel.textContent = 'se rezervă…';
            const data = await postJson(reservationMeta.reserveUrl, body);
            reservationMeta.reservedFull = data.number_full;
            if (seriesFull) seriesFull.textContent = data.number_full || '—';
            if (seriesLabel) seriesLabel.textContent = 'număr rezervat';
            if (data.series && seriesSelect.value !== data.series) {
                seriesSelect.value = data.series;
            }
            fillNumberPick(data);
        } catch (e) {
            if (seriesLabel) seriesLabel.textContent = e.message || 'eroare rezervare';
        }
    }

    numberPick?.addEventListener('change', () => {
        if (suppressNumberChange) return;
        reserveNumberNow(numberPick.value);
    });

    function scheduleReserve() {
        if (!reservationMeta.reserveUrl) return;
        clearTimeout(reserveTimer);
        reserveTimer = setTimeout(() => { reserveNumberNow(); }, 350);
    }

    seriesSelect?.addEventListener('change', () => {
        reservationMeta.reservedFull = null;
        updateSeriesPreview();
        scheduleReserve();
    });
    document.getElementById('issue_date')?.addEventListener('dc-date-change', () => {
        refreshSeriesOptions();
        reservationMeta.reservedFull = null;
        scheduleReserve();
        if (termSelect && termSelect.value !== 'date' && termSelect.value !== 'none') applyPaymentTerm();
    });
    refreshSeriesOptions();
    updateSeriesPreview();

    if (reservationMeta.touchUrl) {
        setInterval(() => {
            postJson(reservationMeta.touchUrl).catch(() => {});
        }, 120000);
    }

    document.querySelector('form.dc-doc-editor')?.addEventListener('submit', () => {
        issuedSaved = true;
    });

    function draftLooksEmpty() {
        const names = Array.from(document.querySelectorAll('input[name*="[name]"], textarea[name*="[name]"]'))
            .map(el => (el.value || '').trim())
            .filter(Boolean);
        return names.length === 0;
    }

    // Eliberează doar ciornele goale (shell) la închidere — ciornele cu linii rămân rezervate (TTL + heartbeat).
    window.addEventListener('pagehide', () => {
        if (issuedSaved || !reservationMeta.releaseUrl || !draftLooksEmpty()) return;
        if (navigator.sendBeacon) {
            const fd = new FormData();
            fd.append('_token', reservationMeta.csrf);
            navigator.sendBeacon(reservationMeta.releaseUrl, fd);
        }
    });
})();
</script>
<style>
.dc-doc-editor {
    display: block;
    width: 100%;
    max-width: none;
}
.dc-doc-editor > .dc-card,
.dc-doc-editor .inv-lines-card {
    width: 100%;
    max-width: none;
}
.inv-lines-card { overflow: visible; }
.inv-lines-scroll { overflow-x: auto; overflow-y: visible; min-height: 220px; width: 100%; }
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
.inv-lines .col-actions { width: 70px; white-space: nowrap; }
.inv-lines .line-total { display: block; padding-top: 10px; font-size: 13px; font-weight: 600; text-align: right; white-space: nowrap; }
.inv-lines .remove-line, .inv-lines .toggle-details {
    color: #486581; font-size: 14px; line-height: 1; padding: 4px 6px; background: none; border: 0; cursor: pointer;
}
.inv-lines .remove-line { color: #b91c1c; }
.inv-line-details td { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.inv-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
@media (max-width: 900px) { .inv-details-grid { grid-template-columns: 1fr; } }
.inv-details-title {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: #486581; margin-bottom: 0.45rem; padding-bottom: 0.25rem; border-bottom: 1px solid #d9e2ec;
}
.inv-details-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 0.45rem 0.6rem; }
.inv-details-fields label { font-size: 10px; color: #627d98; display: flex; flex-direction: column; gap: 0.15rem; }
.inv-details-fields label.span-2 { grid-column: 1 / -1; }
.inv-details-fields .dc-input { font-size: 11px; min-height: auto; padding: .35rem .45rem; }
</style>
<script>
(() => {
    let rowIdx = {{ count($items) }};
    let products = @json($productsForJs);
    const defaultVat = @json((float) $defaultVat);
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

    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const clientHidden = document.getElementById('client_id');
    const clientSelect = document.getElementById('client_id_select');
    const clientQuery = document.getElementById('client_query');
    const clientMeta = document.getElementById('client-meta');
    const quickBox = document.getElementById('quick-client-box');
    const personBox = document.getElementById('person-client-box');
    let pendingClient = null;

    function money(n) {
        return (Number(n) || 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function escapeHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
    }
    function productOptionsHtml(selectedId) {
        return products.map(p =>
            `<option value="${p.id}" data-name="${escapeHtml(p.name)}" data-unit="${escapeHtml(p.unit)}" data-price="${p.price}" data-vat="${p.vat_rate}" ${String(selectedId) === String(p.id) ? 'selected' : ''}>${escapeHtml(p.name)}</option>`
        ).join('');
    }
    function clientLabel(c) {
        const idBit = c.type === 'person' ? (c.cnp || c.id_label || '-') : (c.cui || '');
        return idBit ? `${c.name} (${idBit})` : c.name;
    }
    function selectClient(c) {
        clientHidden.value = c.id;
        let opt = clientSelect.querySelector(`option[value="${c.id}"]`);
        if (!opt) {
            opt = document.createElement('option');
            opt.value = c.id;
            clientSelect.appendChild(opt);
        }
        opt.textContent = clientLabel(c);
        opt.dataset.type = c.type || '';
        opt.dataset.cui = c.cui || '';
        opt.dataset.cnp = c.cnp || '';
        opt.dataset.name = c.name || '';
        opt.dataset.address = c.address || '';
        opt.selected = true;
        clientQuery.value = clientLabel(c);
        updateClientMeta();
    }
    function updateClientMeta() {
        const opt = clientSelect.selectedOptions[0];
        if (!opt || !opt.value) { clientMeta.textContent = ''; return; }
        const idBit = opt.dataset.type === 'person'
            ? (opt.dataset.cnp ? 'CNP ' + opt.dataset.cnp : 'persoană fizică (fără CNP)')
            : (opt.dataset.cui ? 'CUI ' + opt.dataset.cui : null);
        const bits = [idBit, opt.dataset.address || null].filter(Boolean);
        clientMeta.textContent = bits.join(' · ');
    }
    function hidePersonBox() {
        personBox.classList.add('hidden');
        document.getElementById('person-client-status').textContent = '';
    }
    function showPersonBox(identifier, hint) {
        quickBox.classList.add('hidden');
        personBox.classList.remove('hidden');
        document.getElementById('person-client-id').value = identifier || '';
        document.getElementById('person-client-name').value = '';
        document.getElementById('person-client-hint').textContent = hint || 'Persoană fizică nouă — completează numele.';
        document.getElementById('person-client-name').focus();
    }
    function isAnonMarker(v) {
        return ['-', '–', '—', '−'].includes(String(v || '').trim());
    }
    async function resolveClientIdentifier(raw, name) {
        const res = await fetch(@json(route('clients.quick')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ identifier: raw, name: name || '', from_anaf: true })
        });
        const data = await res.json().catch(() => ({}));
        return { res, data };
    }
    async function tryResolveFromQuery() {
        const raw = (clientQuery.value || '').trim();
        if (!raw) {
            clientMeta.textContent = 'Tastează CIF, CNP, „-” sau alege din listă.';
            return;
        }
        // Dacă e selectat din datalist / listă după etichetă exactă
        const byLabel = [...clientSelect.options].find(o => o.value && o.textContent.trim() === raw);
        if (byLabel) {
            clientHidden.value = byLabel.value;
            byLabel.selected = true;
            updateClientMeta();
            return;
        }
        const digits = raw.replace(/\D+/g, '');
        const looksPerson = isAnonMarker(raw) || digits.length === 13;
        const looksCompany = !looksPerson && digits.length >= 2 && digits.length <= 10;

        if (!looksPerson && !looksCompany) {
            // Căutare după nume în listă
            const q = raw.toLowerCase();
            const match = [...clientSelect.options].find(o => o.value && o.textContent.toLowerCase().includes(q));
            if (match) {
                clientHidden.value = match.value;
                match.selected = true;
                clientQuery.value = match.textContent.trim();
                updateClientMeta();
                return;
            }
            clientMeta.textContent = 'Nu am găsit clientul. Pentru firmă: CIF; pentru PF: CNP sau „-”.';
            return;
        }

        clientMeta.textContent = looksPerson ? 'Pregătesc persoana fizică…' : 'Caut în ANAF…';
        const { res, data } = await resolveClientIdentifier(raw, '');
        if (res.ok && data.client) {
            selectClient(data.client);
            clientMeta.textContent = data.existing ? 'Client existent selectat.' : 'Client adăugat pe factură.';
            hidePersonBox();
            return;
        }
        if (data.need_name) {
            showPersonBox(data.cnp || raw, data.message || 'Completează numele persoanei.');
            clientMeta.textContent = data.message || '';
            return;
        }
        clientMeta.textContent = data.message || 'Nu am putut adăuga clientul.';
    }

    clientSelect.addEventListener('change', () => {
        const opt = clientSelect.selectedOptions[0];
        if (!opt || !opt.value) {
            clientHidden.value = '';
            clientMeta.textContent = '';
            return;
        }
        clientHidden.value = opt.value;
        clientQuery.value = opt.textContent.trim();
        updateClientMeta();
    });
    updateClientMeta();
    if (clientSelect.value) {
        const opt = clientSelect.selectedOptions[0];
        if (opt) clientQuery.value = opt.textContent.trim();
    }

    clientQuery.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            tryResolveFromQuery();
        }
    });
    document.getElementById('client-resolve-btn').addEventListener('click', () => tryResolveFromQuery());

    document.getElementById('person-client-cancel').addEventListener('click', hidePersonBox);
    document.getElementById('person-client-save').addEventListener('click', async () => {
        const status = document.getElementById('person-client-status');
        const identifier = document.getElementById('person-client-id').value.trim();
        const name = document.getElementById('person-client-name').value.trim();
        if (!name) { status.textContent = 'Numele este obligatoriu.'; return; }
        status.textContent = 'Salvez persoana…';
        try {
            const { res, data } = await resolveClientIdentifier(identifier || '-', name);
            if (!res.ok || !data.client) throw new Error(data.message || 'Nu am putut salva.');
            selectClient(data.client);
            status.textContent = 'Persoană adăugată pe factură.';
            hidePersonBox();
            clientMeta.textContent = data.existing ? 'Client existent selectat.' : 'Persoană nouă creată.';
        } catch (e) {
            status.textContent = e.message || 'Eroare la salvare.';
        }
    });

    document.getElementById('toggle-quick-client').addEventListener('click', () => {
        hidePersonBox();
        quickBox.classList.toggle('hidden');
    });
    document.getElementById('quick-client-cancel').addEventListener('click', () => {
        quickBox.classList.add('hidden');
        pendingClient = null;
        document.getElementById('quick-client-preview').classList.add('hidden');
        document.getElementById('quick-client-save').disabled = true;
        document.getElementById('quick-client-status').textContent = '';
    });

    document.getElementById('quick-client-anaf').addEventListener('click', async () => {
        const cui = document.getElementById('quick-client-cui').value.trim();
        const status = document.getElementById('quick-client-status');
        const preview = document.getElementById('quick-client-preview');
        const saveBtn = document.getElementById('quick-client-save');
        if (!cui) { status.textContent = 'Introdu CUI-ul.'; return; }
        status.textContent = 'Caut în ANAF…';
        saveBtn.disabled = true;
        preview.classList.add('hidden');
        try {
            const res = await fetch(@json(route('anaf.lookup')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ cui })
            });
            if (!res.ok) throw new Error('Nu am găsit firma în ANAF.');
            pendingClient = await res.json();
            preview.innerHTML = `<strong>${escapeHtml(pendingClient.name)}</strong><br>
                CUI ${escapeHtml(pendingClient.cui)} ${pendingClient.reg_com ? '· ' + escapeHtml(pendingClient.reg_com) : ''}<br>
                <span class="text-slate-600">${escapeHtml([pendingClient.address, pendingClient.city, pendingClient.county].filter(Boolean).join(', '))}</span>`;
            preview.classList.remove('hidden');
            status.textContent = 'Date preluate. Confirmă pentru a adăuga clientul.';
            saveBtn.disabled = false;
        } catch (e) {
            status.textContent = e.message || 'Eroare ANAF.';
            pendingClient = null;
        }
    });

    document.getElementById('quick-client-save').addEventListener('click', async () => {
        const status = document.getElementById('quick-client-status');
        const cui = document.getElementById('quick-client-cui').value.trim();
        status.textContent = 'Salvez clientul…';
        try {
            const { res, data } = await resolveClientIdentifier(cui, '');
            if (!res.ok || !data.client) throw new Error(data.message || 'Nu am putut salva clientul.');
            selectClient(data.client);
            status.textContent = data.existing ? 'Client existent selectat.' : 'Client adăugat pe factură.';
            quickBox.classList.add('hidden');
        } catch (e) {
            status.textContent = e.message || 'Eroare la salvare.';
        }
    });

    function recalcRow(tr) {
        const qty = parseFloat(tr.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(tr.querySelector('.item-price')?.value) || 0;
        const vat = parseFloat(tr.querySelector('.item-vat')?.value) || 0;
        const sub = qty * price;
        const total = sub * (1 + vat / 100);
        const el = tr.querySelector('.line-total');
        if (el) el.textContent = money(total);
        recalcDoc();
    }
    function mainRows() {
        return [...document.querySelectorAll('#items-table tbody tr[data-line-row]')];
    }
    function detailsRow(tr) {
        return tr.nextElementSibling?.matches?.('[data-line-details]') ? tr.nextElementSibling : null;
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
            unit: (tr.querySelector('.item-unit')?.value || '').trim(),
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
                problems.push('Linia ' + (i + 1) + ' este goală — completeaz-o sau șterge-o.');
                return;
            }
            if (!isRowComplete(tr)) {
                problems.push('Linia ' + (i + 1) + ': produsul e obligatoriu (cantitate ≠ 0, preț, TVA). Descrierea e opțională.');
                return;
            }
            complete++;
        });
        if (complete === 0) {
            return ['Adaugă cel puțin o linie completă pe factură.'];
        }
        return problems;
    }

    function applyProduct(tr, p) {
        tr.querySelector('.product-id').value = p.id;
        tr.querySelector('.item-name').value = p.name || '';
        tr.querySelector('.item-unit').value = window.dcNormalizeUnit(p.unit);
        tr.querySelector('.item-price').value = Number(p.price ?? 0).toFixed(2);
        setVatSelect(tr.querySelector('.item-vat'), p.vat_rate ?? defaultVat);
        // Descrierea rămâne independentă — nu o suprascriem la alegerea produsului.
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

    function bindLinePair(tr, details) {
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

        const scope = [tr, details].filter(Boolean);
        scope.forEach(node => {
            node.querySelectorAll('input, select, textarea').forEach(el => {
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
                    if (el.tagName === 'TEXTAREA') return;
                    e.preventDefault();
                    e.stopPropagation();
                    addRow();
                });
            });
        });

        tr.querySelector('.toggle-details')?.addEventListener('click', () => {
            if (!details) return;
            details.classList.toggle('hidden');
            const open = !details.classList.contains('hidden');
            tr.querySelector('.toggle-details').setAttribute('aria-expanded', open ? 'true' : 'false');
            tr.querySelector('.toggle-details').textContent = open ? '▴' : '▾';
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
            details?.remove();
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
                    <input type="text" name="items[${idx}][name]" class="dc-input item-name item-product-input" placeholder="Produs / serviciu (obligatoriu)" autocomplete="off" data-autocomplete aria-required="true">
                    <input type="hidden" name="items[${idx}][product_id]" class="product-id" value="">
                    <div class="inv-ac-list hidden" data-ac-list></div>
                </div>
            </td>
            <td class="col-description">
                <div class="inv-desc-wrap">
                    <input type="text" name="items[${idx}][description]" class="dc-input item-description" placeholder="Descriere (opțional)" autocomplete="off" data-autocomplete>
                    <div class="inv-ac-list hidden" data-ac-list></div>
                </div>
            </td>
            <td class="col-unit">${window.dcUnitInputHtml(window.dcMeasureUnits.default).replace('name="__unit__"', `name="items[${idx}][unit]"`)}</td>
            <td class="col-qty"><input name="items[${idx}][quantity]" type="number" step="any" inputmode="decimal" value="1.00" class="dc-input item-qty"></td>
            <td class="col-price"><input name="items[${idx}][unit_price]" type="number" step="any" inputmode="decimal" value="0.00" class="dc-input item-price"></td>
            <td class="col-vat">${vatSelectHtml(`items[${idx}][vat_rate]`, defaultVat)}</td>
            <td class="col-total"><span class="line-total">0,00</span></td>
            <td class="col-actions">
                <button type="button" class="toggle-details" title="Detalii opționale" aria-expanded="false">▾</button>
                <button type="button" class="remove-line" title="{{ __('Șterge') }}">×</button>
            </td>`;
        const details = document.createElement('tr');
        details.className = 'inv-line-details hidden';
        details.setAttribute('data-line-details', '');
        details.innerHTML = `
            <td colspan="8">
                <div class="inv-details-grid">
                    <div class="inv-details-block">
                        <div class="inv-details-title">Detalii produs</div>
                        <div class="inv-details-fields">
                            <label>Identif. cumpărătorului art. (BT-156)<input name="items[${idx}][details][buyer_item_id]" class="dc-input"></label>
                            <label>Identif. std. art. (BT-157)<input name="items[${idx}][details][standard_item_id]" class="dc-input"></label>
                            <label>Tip<input name="items[${idx}][details][standard_item_scheme]" class="dc-input" placeholder="ex: SA"></label>
                            <label>Cod NC (BT-158)<input name="items[${idx}][details][nc_code]" class="dc-input"></label>
                            <label>Cod CPV (BT-158)<input name="items[${idx}][details][cpv_code]" class="dc-input"></label>
                            <label>Țara de origine (BT-159)<input name="items[${idx}][details][origin_country]" class="dc-input" placeholder="RO" maxlength="2"></label>
                        </div>
                    </div>
                    <div class="inv-details-block">
                        <div class="inv-details-title">Detalii linie</div>
                        <div class="inv-details-fields">
                            <label class="span-2">Comentariu linie (BT-127)<textarea name="items[${idx}][details][note]" rows="2" class="dc-input"></textarea></label>
                            <label>Identif. liniei (BT-128)<input name="items[${idx}][details][sellers_item_id]" class="dc-input"></label>
                            <label>Tip<input name="items[${idx}][details][sellers_item_scheme]" class="dc-input"></label>
                            <label>Referință comenzii (BT-132)<input name="items[${idx}][details][order_reference]" class="dc-input"></label>
                            <label>Referință contabilă cumpărător (BT-133)<input name="items[${idx}][details][buyer_accounting_ref]" class="dc-input"></label>
                            <label>Perioadă start (BT-134)<input type="date" name="items[${idx}][details][period_start]" class="dc-input"></label>
                            <label>Perioadă end (BT-135)<input type="date" name="items[${idx}][details][period_end]" class="dc-input"></label>
                        </div>
                    </div>
                </div>
            </td>`;
        tbody.appendChild(main);
        tbody.appendChild(details);
        bindLinePair(main, details);
        main.querySelector('.item-name').focus();
    }

    document.getElementById('add-line-btn')?.addEventListener('click', addRow);
    mainRows().forEach(tr => bindLinePair(tr, detailsRow(tr)));
    recalcDoc();

    const form = document.getElementById('items-table')?.closest('form');
    form?.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        if (e.target.closest('#items-table') && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
    form?.addEventListener('submit', (e) => {
        const problems = validateLines();
        if (problems.length) {
            e.preventDefault();
            alert(problems.join('\n'));
            mainRows().find(tr => !isRowComplete(tr))?.querySelector('.item-name')?.focus();
        }
    });
})();
</script>
@endpush
