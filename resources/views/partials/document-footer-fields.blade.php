@php
    /** @var \App\Models\Document|\App\Models\RecurringInvoice|null $source */
    $source = $source ?? ($document ?? ($recurring ?? null));
    $company = $company ?? ($source?->company ?? null);
    $users = $companyUsers ?? ($company?->users()?->orderBy('name')->get() ?? collect());
    $adminName = $company?->seriesResponsibleName();
    $defaultPrepared = old('prepared_by', $source?->prepared_by ?? $adminName ?? auth()->user()?->name);
    $preparedSuggestions = collect();
    if ($adminName) {
        $preparedSuggestions->push($adminName);
    }
    foreach ($users as $user) {
        if (filled($user->name)) {
            $preparedSuggestions->push((string) $user->name);
        }
    }
    if ($company) {
        $preparedSuggestions = $preparedSuggestions->merge(
            $company->documents()
                ->whereNotNull('prepared_by')
                ->where('prepared_by', '!=', '')
                ->orderByDesc('id')
                ->limit(40)
                ->pluck('prepared_by')
        )->merge(
            $company->recurringInvoices()
                ->whereNotNull('prepared_by')
                ->where('prepared_by', '!=', '')
                ->orderByDesc('id')
                ->limit(20)
                ->pluck('prepared_by')
        );
    }
    $preparedSuggestions = $preparedSuggestions
        ->map(fn ($n) => trim((string) $n))
        ->filter()
        ->unique(fn ($n) => mb_strtolower($n))
        ->values();

    $delegateSuggestions = collect();
    $delegateIdCards = [];
    foreach ($users as $user) {
        if (filled($user->name)) {
            $delegateSuggestions->push((string) $user->name);
        }
    }
    if ($company) {
        $pastDelegates = $company->documents()
            ->whereNotNull('delegate_name')
            ->where('delegate_name', '!=', '')
            ->orderByDesc('id')
            ->limit(60)
            ->get(['delegate_name', 'delegate_id_card'])
            ->concat(
                $company->recurringInvoices()
                    ->whereNotNull('delegate_name')
                    ->where('delegate_name', '!=', '')
                    ->orderByDesc('id')
                    ->limit(30)
                    ->get(['delegate_name', 'delegate_id_card'])
            );
        foreach ($pastDelegates as $row) {
            $name = trim((string) $row->delegate_name);
            if ($name === '') {
                continue;
            }
            $delegateSuggestions->push($name);
            $key = mb_strtolower($name);
            $card = trim((string) ($row->delegate_id_card ?? ''));
            if ($card !== '' && ! isset($delegateIdCards[$key])) {
                $delegateIdCards[$key] = $card;
            }
        }
    }
    $delegateSuggestions = $delegateSuggestions
        ->map(fn ($n) => trim((string) $n))
        ->filter()
        ->unique(fn ($n) => mb_strtolower($n))
        ->values();

    $ccDefault = old('auto_email_cc_address', $source?->auto_email_cc_address ?? ($company?->email ?: auth()->user()?->email));

    $docType = $docType
        ?? ($source instanceof \App\Models\RecurringInvoice ? 'recurring' : ($source?->type ?? 'invoice'));

    $docNoun = match ($docType) {
        'proforma' => 'proforma',
        'recurring' => 'factura',
        'delivery' => 'avizul',
        'receipt' => 'chitanța',
        default => 'factura',
    };
    $docNounAcc = match ($docType) {
        'proforma' => 'proforma',
        'recurring' => 'factura',
        'delivery' => 'avizul',
        'receipt' => 'chitanța',
        default => 'factura',
    };
    $pdfLabel = match ($docType) {
        'proforma' => 'proformă',
        'recurring' => 'factură (din abonament)',
        'delivery' => 'aviz',
        'receipt' => 'chitanță',
        default => 'factură',
    };

    // Subsol complet pe factură, proformă și recurentă; pe aviz/chitanță fără plată card / email auto.
    $isBillingDoc = in_array($docType, ['invoice', 'proforma', 'recurring'], true);
    $showCard = $showCardPayment ?? $isBillingDoc;
    $showEmail = $showAutoEmail ?? $isBillingDoc;
    $showEfacturaHints = in_array($docType, ['invoice', 'recurring'], true);
    $notesPlaceholder = $notesPlaceholder ?? ('Mențiuni pe '.$pdfLabel.' (opțional)');
    $notesRows = $notesRows ?? 3;
    $cardProcessorsReady = $company
        ? app(\App\Services\CardProcessors::class)->anyActive($company)
        : false;
    $activeCardLabels = $company
        ? collect(app(\App\Services\CardProcessors::class)->active($company))->pluck('short')->implode(', ')
        : '';
@endphp

<div class="dc-doc-footer" x-data="{ extrasOpen: true, configOpen: false }">
    @if($showCard)
        <label class="dc-doc-footer-check {{ $cardProcessorsReady ? '' : 'opacity-60 cursor-not-allowed' }}">
            <input type="checkbox" name="allow_card_payment" value="1"
                   @checked(old('allow_card_payment', ($cardProcessorsReady && ($source?->allow_card_payment ?? false))))
                   @disabled(! $cardProcessorsReady)
                   class="rounded border-slate-300 text-sky-700 focus:ring-sky-600">
            <span>
                Permite plata cu cardul online
                @if($cardProcessorsReady)
                    <span class="block text-xs text-slate-500 font-normal mt-0.5">Procesatoare: {{ $activeCardLabels }}. Linkurile apar pe PDF și în email.</span>
                @else
                    <span class="block text-xs text-amber-800 font-normal mt-0.5">Indisponibil — configurează cel puțin un procesator în Setări → Integrări (pentru firma ta).</span>
                @endif
            </span>
        </label>
    @endif

    <div class="dc-doc-footer-toolbar">
        <button type="button" class="dc-doc-footer-toggle" @click="extrasOpen = !extrasOpen">
            <span>Date adiționale</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 transition"
                 :class="extrasOpen ? 'rotate-180' : ''"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
        </button>
        <button type="button" class="dc-doc-footer-config" @click="configOpen = !configOpen" title="Informații despre câmpuri">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true"><path fill-rule="evenodd" d="M8.34 1.804A1 1 0 0 1 9.32 1h1.36a1 1 0 0 1 .98.804l.295 1.473c.497.144.971.342 1.416.587l1.36-.712a1 1 0 0 1 1.29.29l.68 1.177a1 1 0 0 1-.19 1.274l-1.12.99a6.97 6.97 0 0 1 0 1.176l1.12.99a1 1 0 0 1 .19 1.274l-.68 1.177a1 1 0 0 1-1.29.29l-1.36-.712a5.98 5.98 0 0 1-1.416.587l-.295 1.473A1 1 0 0 1 10.68 17H9.32a1 1 0 0 1-.98-.804l-.295-1.473a5.98 5.98 0 0 1-1.416-.587l-1.36.712a1 1 0 0 1-1.29-.29l-.68-1.177a1 1 0 0 1 .19-1.274l1.12-.99a6.97 6.97 0 0 1 0-1.176l-1.12-.99a1 1 0 0 1-.19-1.274l.68-1.177a1 1 0 0 1 1.29-.29l1.36.712c.445-.245.919-.443 1.416-.587l.295-1.473ZM10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" clip-rule="evenodd"/></svg>
            Configurează câmpuri
        </button>
    </div>

    <div x-show="configOpen" x-cloak class="dc-doc-footer-config-panel">
        Aceste câmpuri apar pe PDF-ul de {{ $pdfLabel }}.
        @if($showEfacturaHints)
            <strong>Număr de contract (BT-12)</strong> și <strong>Aviz însoțire (BT-16)</strong> sunt transmise și în e-Factura ANAF.
        @else
            Contractul și avizul apar ca referințe pe document.
        @endif
        @if($showEmail)
            Opțiunile de email trimit automat PDF-ul la emitere.
        @endif
    </div>

    <div x-show="extrasOpen" x-cloak class="dc-doc-footer-extras">
        <div class="dc-doc-footer-extras-head">
            <span>Încasare, livrare</span>
            <span>Referințe la alte documente</span>
        </div>
        <div class="dc-doc-footer-extras-grid">
            <div>
                <label class="dc-label" for="contract_number">Număr de contract{{ $showEfacturaHints ? ' (BT-12)' : '' }}</label>
                <input id="contract_number" name="contract_number" class="dc-input"
                       value="{{ old('contract_number', $source?->contract_number ?? '') }}"
                       maxlength="100" autocomplete="off">
            </div>
            <div>
                <label class="dc-label" for="despatch_advice">Aviz însoțire{{ $showEfacturaHints ? ' (BT-16)' : '' }}</label>
                <input id="despatch_advice" name="despatch_advice" class="dc-input"
                       value="{{ old('despatch_advice', $source?->despatch_advice ?? '') }}"
                       maxlength="100" autocomplete="off">
            </div>
        </div>
    </div>

    <div class="dc-doc-footer-main">
        <div>
            <label class="dc-label" for="prepared_by">Întocmit de</label>
            <input id="prepared_by" name="prepared_by" class="dc-input" list="dc-prepared-by-list"
                   value="{{ $defaultPrepared }}"
                   maxlength="255" autocomplete="off" placeholder="Nume liber sau alege din listă">
            <datalist id="dc-prepared-by-list">
                @foreach($preparedSuggestions as $name)
                    <option value="{{ $name }}"></option>
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="dc-label" for="prepared_by_cnp">CNP</label>
            <input id="prepared_by_cnp" name="prepared_by_cnp" class="dc-input"
                   value="{{ old('prepared_by_cnp', $source?->prepared_by_cnp ?? '') }}"
                   maxlength="20" inputmode="numeric" autocomplete="off">
        </div>
        <div>
            <label class="dc-label" for="delegate_name">Delegat</label>
            <input id="delegate_name" name="delegate_name" class="dc-input" list="dc-delegate-list"
                   value="{{ old('delegate_name', $source?->delegate_name ?? '') }}"
                   maxlength="255" autocomplete="off" placeholder="Nume liber sau alege din listă"
                   data-delegate-cards='@json($delegateIdCards)'>
            <datalist id="dc-delegate-list">
                @foreach($delegateSuggestions as $name)
                    <option value="{{ $name }}"></option>
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="dc-label" for="delegate_id_card">Buletin</label>
            <input id="delegate_id_card" name="delegate_id_card" class="dc-input"
                   value="{{ old('delegate_id_card', $source?->delegate_id_card ?? '') }}"
                   maxlength="50" autocomplete="off">
        </div>
        <div>
            <label class="dc-label" for="vehicle_reg">Auto</label>
            <input id="vehicle_reg" name="vehicle_reg" class="dc-input"
                   value="{{ old('vehicle_reg', $source?->vehicle_reg ?? '') }}"
                   maxlength="50" autocomplete="off" placeholder="ex: B 01 ABC">
        </div>
        <div class="dc-doc-footer-notes">
            <label class="dc-label" for="notes">Mențiuni</label>
            <textarea id="notes" name="notes" rows="{{ $notesRows }}" class="dc-input dc-tpl-field"
                      placeholder="{{ $notesPlaceholder }}">{{ old('notes', $source?->notes ?? '') }}</textarea>
        </div>
        @if($showEmail)
            <div class="dc-doc-footer-email" x-data="{ cc: @js((bool) old('auto_email_cc', $source?->auto_email_cc ?? false)) }">
                <label class="dc-doc-footer-check">
                    <input type="checkbox" name="auto_email_client" value="1"
                           @checked(old('auto_email_client', $source?->auto_email_client ?? false))
                           class="rounded border-slate-300 text-sky-700 focus:ring-sky-600">
                    <span>Trimite {{ $docNounAcc }} automat pe email clientului</span>
                </label>
                <label class="dc-doc-footer-check dc-doc-footer-cc">
                    <input type="checkbox" name="auto_email_cc" value="1"
                           x-model="cc"
                           @checked(old('auto_email_cc', $source?->auto_email_cc ?? false))
                           class="rounded border-slate-300 text-sky-700 focus:ring-sky-600">
                    <span>Trimite o copie la</span>
                </label>
                <input type="email" name="auto_email_cc_address" class="dc-input"
                       value="{{ $ccDefault }}"
                       placeholder="email@exemplu.ro"
                       :disabled="!cc">
            </div>
        @endif
    </div>
</div>

<script>
(() => {
    const nameInput = document.getElementById('delegate_name');
    const cardInput = document.getElementById('delegate_id_card');
    if (! nameInput || ! cardInput) return;
    let cards = {};
    try { cards = JSON.parse(nameInput.getAttribute('data-delegate-cards') || '{}') || {}; } catch (e) { cards = {}; }
    const fillCard = () => {
        const key = (nameInput.value || '').trim().toLowerCase();
        if (! key || ! cards[key]) return;
        // Completează buletinul doar dacă e gol sau e deja valoarea cunoscută (nu suprascrie alt CI).
        const current = (cardInput.value || '').trim();
        if (current === '' || current === cards[key]) {
            cardInput.value = cards[key];
        }
    };
    nameInput.addEventListener('change', fillCard);
    nameInput.addEventListener('blur', fillCard);
})();
</script>

<style>
.dc-doc-footer { margin: 1rem 0 1.25rem; }
.dc-doc-footer-check {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.875rem; color: #334e68; cursor: pointer; user-select: none;
    margin-bottom: 0.75rem;
}
.dc-doc-footer-toolbar {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem 1.25rem;
    margin-bottom: 0.65rem;
}
.dc-doc-footer-toggle {
    display: inline-flex; align-items: center; gap: 0.4rem;
    background: #1f6f8b; color: #fff; border: 0; border-radius: 0.45rem;
    padding: 0.45rem 0.85rem; font-size: 0.875rem; font-weight: 700; cursor: pointer;
}
.dc-doc-footer-toggle:hover { background: #185a70; }
.dc-doc-footer-config {
    display: inline-flex; align-items: center; gap: 0.35rem;
    background: transparent; border: 0; color: #1f6f8b; font-size: 0.875rem;
    font-weight: 600; cursor: pointer; padding: 0.25rem 0;
}
.dc-doc-footer-config:hover { color: #0a3440; text-decoration: underline; }
.dc-doc-footer-config-panel {
    background: #eef7fa; border: 1px solid #c5dde4; border-radius: 0.55rem;
    padding: 0.7rem 0.9rem; font-size: 0.8rem; color: #334e68; line-height: 1.45;
    margin-bottom: 0.75rem;
}
.dc-doc-footer-extras {
    background: #f5f7fa; border: 1px solid #e2e8f0; border-radius: 0.55rem;
    padding: 0.85rem 1rem 1rem; margin-bottom: 1rem;
}
.dc-doc-footer-extras-head {
    display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em;
    text-transform: uppercase; color: #627d98; margin-bottom: 0.55rem;
}
.dc-doc-footer-extras-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem 1.25rem;
}
.dc-doc-footer-main {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem 1rem;
    align-items: start;
}
.dc-doc-footer-notes { grid-column: span 2; }
.dc-doc-footer-email {
    grid-column: span 2;
    display: flex; flex-direction: column; gap: 0.45rem;
    padding-top: 1.45rem;
}
.dc-doc-footer-email .dc-doc-footer-check { margin-bottom: 0; }
.dc-doc-footer-cc { flex-wrap: wrap; }
@media (max-width: 1100px) {
    .dc-doc-footer-main { grid-template-columns: 1fr 1fr; }
    .dc-doc-footer-notes, .dc-doc-footer-email { grid-column: span 2; }
}
@media (max-width: 700px) {
    .dc-doc-footer-extras-head,
    .dc-doc-footer-extras-grid,
    .dc-doc-footer-main { grid-template-columns: 1fr; }
    .dc-doc-footer-notes, .dc-doc-footer-email { grid-column: auto; }
    .dc-doc-footer-email { padding-top: 0; }
}
</style>
