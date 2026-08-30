@extends('layouts.app')
@section('heading', 'Solduri clienți')
@section('subheading', 'Sold la o dată: rest sold inițial + rest facturi emise până la acea dată (minus încasări pe facturi și pe sold, până la dată).')
@section('actions')
<a href="{{ route('reports.index') }}" class="dc-btn-secondary">{{ __('Vânzări și încasări') }}</a>
@endsection
@section('content')
<link rel="stylesheet" href="{{ asset('css/date-boxes.css') }}?v=20260808a">
<form class="dc-card p-4 mb-6 flex flex-wrap gap-3 items-end" method="GET" action="{{ route('reports.clients') }}">
    @include('partials.date-input', ['name' => 'as_of', 'label' => 'Sold la data', 'value' => $asOf])
    <div class="min-w-[16rem] flex-1">
        <label class="dc-label" for="client_id">{{ __('Client') }}</label>
        <select name="client_id" id="client_id" class="dc-input">
            <option value="">Toți clienții</option>
            @foreach($clients as $c)
                <option value="{{ $c->id }}" @selected((int) $clientId === (int) $c->id)>
                    {{ $c->name }}@if($c->cui || $c->cnp) ({{ $c->cui ?: $c->cnp }})@endif
                </option>
            @endforeach
        </select>
    </div>
    <label class="flex items-center gap-2 text-sm text-slate-700 pb-2">
        <input type="checkbox" name="show_zero" value="1" @checked($showZero) class="rounded border-slate-300">
        Afișează și sold 0
    </label>
    <button class="dc-btn-primary">{{ __('Actualizează') }}</button>
</form>

<div class="dc-card p-4 mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <div class="text-xs uppercase tracking-wide text-slate-500">Total de încasat de la clienți</div>
        <div class="text-3xl font-display font-semibold tabular-nums text-teal-950 mt-1">
            {{ number_format($total, 2, ',', '.') }} RON
        </div>
        <div class="text-sm text-slate-500 mt-1">la data {{ dc_date($asOf) }}@if($clientId) · client selectat @else · toți clienții @endif</div>
    </div>
    <a href="{{ route('clients.opening-balances.edit') }}" class="dc-btn-secondary text-sm">Editează solduri inițiale</a>
</div>

@php
    $defaultLedgerFrom = $ledgerFrom ?? now()->startOfMonth()->toDateString();
    $defaultLedgerTo = $ledgerTo ?? now()->toDateString();
@endphp

<div class="grid lg:grid-cols-2 gap-4 mb-6">
    <div class="dc-card p-5">
        <div class="font-semibold text-slate-900 mb-1">Fișă de partener</div>
        <p class="text-sm text-slate-600 mb-4">
            Fișă contabilă ledger pentru un client și o perioadă (cont 4111-Clienți). Se deschide peste aplicație; de acolo poți exporta PDF sau printa.
        </p>
        <form id="fisa-partner-form" class="flex flex-wrap gap-3 items-end js-report-modal-form" method="GET" action="{{ route('reports.clients.partner') }}"
              data-title="Fișă de partener"
              data-pdf-action="{{ route('reports.clients.partner-pdf') }}"
              data-default-from="{{ $defaultLedgerFrom }}"
              data-default-to="{{ $defaultLedgerTo }}"
              data-opening-from="{{ $openingPeriodFrom }}"
              data-today="{{ now()->toDateString() }}">
            <div class="min-w-[14rem] w-full">
                <label class="dc-label" for="ledger_client_id">{{ __('Client') }}</label>
                <select name="client_id" id="ledger_client_id" class="dc-input" required>
                    <option value="">Alege clientul…</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}"
                                data-opening-from="{{ $clientOpeningDates[$c->id] ?? '' }}"
                                @selected((int) $clientId === (int) $c->id)>
                            {{ $c->name }}@if($c->cui || $c->cnp) ({{ $c->cui ?: $c->cnp }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                @include('partials.date-boxes', ['name' => 'from', 'id' => 'fisa_from', 'label' => 'De la', 'value' => $defaultLedgerFrom, 'required' => true])
            </div>
            <div>
                @include('partials.date-boxes', ['name' => 'to', 'id' => 'fisa_to', 'label' => 'Până la', 'value' => $defaultLedgerTo, 'required' => true])
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 pb-2 w-full">
                <input type="checkbox" id="fisa_full_period" class="rounded border-slate-300 ledger-full-period">
                Toată perioada (de la sold inițial până azi)
            </label>
            <button type="button" class="dc-btn-primary js-report-open">Afișează</button>
        </form>
    </div>
    <div class="dc-card p-5">
        <div class="font-semibold text-slate-900 mb-1">Balanță parteneri</div>
        <p class="text-sm text-slate-600 mb-4">
            Balanță terți pe toți clienții (4111): rulaje precedente / curente, total sume, solduri finale. Se deschide peste aplicație; de acolo poți exporta PDF sau printa.
        </p>
        <form id="balanta-partneri-form" class="flex flex-wrap gap-3 items-end js-report-modal-form" method="GET" action="{{ route('reports.clients.balance') }}"
              data-title="Balanță parteneri"
              data-pdf-action="{{ route('reports.clients.balance-pdf') }}"
              data-default-from="{{ $defaultLedgerFrom }}"
              data-default-to="{{ $defaultLedgerTo }}"
              data-opening-from="{{ $openingPeriodFrom }}"
              data-today="{{ now()->toDateString() }}">
            <div>
                @include('partials.date-boxes', ['name' => 'from', 'id' => 'bal_from', 'label' => 'De la', 'value' => $defaultLedgerFrom, 'required' => true])
            </div>
            <div>
                @include('partials.date-boxes', ['name' => 'to', 'id' => 'bal_to', 'label' => 'Până la', 'value' => $defaultLedgerTo, 'required' => true])
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 pb-2 w-full">
                <input type="checkbox" id="bal_full_period" class="rounded border-slate-300 ledger-full-period">
                Toată perioada (de la sold inițial până azi)
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700 pb-2 w-full">
                <input type="checkbox" name="hide_zero_sold" value="1" class="rounded border-slate-300">
                Ascunde clienții cu sold 0
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700 pb-2 w-full">
                <input type="checkbox" name="hide_zero_lines" value="1" class="rounded border-slate-300">
                Ascunde liniile integral pe 0
            </label>
            <button type="button" class="dc-btn-primary js-report-open">Afișează</button>
        </form>
    </div>
</div>

<div class="dc-card overflow-hidden">
    <table class="w-full dc-table">
        <thead>
        <tr>
            <th>{{ __('Client') }}</th>
            <th class="text-right">{{ __('Sold inițial') }}</th>
            <th class="text-right">{{ __('Facturi deschise') }}</th>
            <th class="text-right">Sold</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            @php $client = $row['client']; @endphp
            <tr>
                <td class="font-medium">
                    {{ $client->name }}
                    <div class="text-xs text-slate-500">{{ $client->cui ?: $client->cnp }}</div>
                </td>
                <td class="text-right tabular-nums">{{ number_format($row['opening'], 2, ',', '.') }}</td>
                <td class="text-right tabular-nums">{{ number_format($row['invoices'], 2, ',', '.') }}</td>
                <td class="text-right tabular-nums font-semibold {{ $row['balance'] > 0.009 ? 'text-amber-900' : 'text-slate-600' }}">
                    {{ number_format($row['balance'], 2, ',', '.') }}
                </td>
                <td class="text-right whitespace-nowrap">
                    <div class="dc-act-wrap">
                        <a href="{{ route('clients.show', $client) }}" class="dc-act">{{ __('Fișă') }}</a>
                        <a href="{{ route('reports.clients.partner', [
                            'client_id' => $client->id,
                            'from' => $defaultLedgerFrom,
                            'to' => $defaultLedgerTo,
                            'embed' => 1,
                        ]) }}"
                           class="dc-act js-report-modal-link"
                           data-title="Fișă de partener — {{ $client->name }}"
                           data-pdf-url="{{ route('reports.clients.partner-pdf', [
                               'client_id' => $client->id,
                               'from' => $defaultLedgerFrom,
                               'to' => $defaultLedgerTo,
                           ]) }}">Fișă partener</a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-slate-500">Niciun sold de afișat pentru filtrele alese.</td></tr>
        @endforelse
        </tbody>
        @if($rows->isNotEmpty())
        <tfoot>
        <tr class="border-t border-slate-200 bg-slate-50">
            <td class="font-semibold">{{ __('Total') }}</td>
            <td class="text-right tabular-nums font-semibold">{{ number_format($rows->sum('opening'), 2, ',', '.') }}</td>
            <td class="text-right tabular-nums font-semibold">{{ number_format($rows->sum('invoices'), 2, ',', '.') }}</td>
            <td class="text-right tabular-nums font-semibold text-teal-950">{{ number_format($total, 2, ',', '.') }}</td>
            <td></td>
        </tr>
        </tfoot>
        @endif
    </table>
</div>

<div id="dc-report-modal" class="dc-report-modal" hidden aria-hidden="true">
    <div class="dc-report-modal-backdrop" id="dc-report-modal-backdrop"></div>
    <div class="dc-report-modal-panel" role="dialog" aria-modal="true" aria-labelledby="dc-report-modal-title">
        <div class="dc-report-modal-toolbar">
            <div id="dc-report-modal-title" class="dc-report-modal-title">Raport</div>
            <div class="dc-report-modal-actions">
                <button type="button" id="dc-report-modal-pdf" class="dc-btn-primary">Export PDF</button>
                <button type="button" id="dc-report-modal-print" class="dc-btn-secondary">Print</button>
                <button type="button" id="dc-report-modal-close" class="dc-btn-secondary">{{ __('Închide') }}</button>
            </div>
        </div>
        <iframe id="dc-report-modal-frame" class="dc-report-modal-frame" title="Previzualizare raport"></iframe>
    </div>
</div>

<style>
.dc-report-modal[hidden] { display: none !important; }
.dc-report-modal {
    position: fixed; inset: 0; z-index: 80;
    display: flex; align-items: stretch; justify-content: center;
}
.dc-report-modal-backdrop {
    position: absolute; inset: 0; background: rgba(15, 23, 42, .55);
}
.dc-report-modal-panel {
    position: relative; z-index: 1;
    display: flex; flex-direction: column;
    width: min(1120px, calc(100vw - 24px));
    height: min(920px, calc(100vh - 24px));
    margin: 12px auto;
    background: #fff; border-radius: 12px; overflow: hidden;
    box-shadow: 0 24px 60px rgba(15, 23, 42, .35);
}
.dc-report-modal-toolbar {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;
    padding: 12px 14px; background: #0f766e; color: #fff;
}
.dc-report-modal-title { font-weight: 600; font-size: 14px; }
.dc-report-modal-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.dc-report-modal-actions .dc-btn-primary,
.dc-report-modal-actions .dc-btn-secondary {
    border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.dc-report-modal-actions .dc-btn-primary {
    background: #fff; color: #115e59; border: 1px solid #fff;
}
.dc-report-modal-actions .dc-btn-secondary {
    background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.45);
}
.dc-report-modal-frame { flex: 1; width: 100%; border: 0; background: #fff; }
body.dc-report-modal-open { overflow: hidden; }
@media (max-width: 720px) {
    .dc-report-modal-panel { width: 100vw; height: 100vh; margin: 0; border-radius: 0; }
}
</style>

<script src="{{ asset('js/date-boxes.js') }}"></script>
<script>
(function () {
    function setDateBox(id, iso) {
        const root = document.getElementById(id);
        if (!root || !iso) return;
        const parts = String(iso).split('-');
        if (parts.length !== 3) return;
        const [y, m, d] = parts;
        const dEl = root.querySelector('[data-part="d"]');
        const mEl = root.querySelector('[data-part="m"]');
        const yEl = root.querySelector('[data-part="y"]');
        const hidden = root.querySelector('.dc-datebox-value');
        const native = root.querySelector('.dc-datebox-native');
        if (dEl) dEl.value = d;
        if (mEl) mEl.value = m;
        if (yEl) yEl.value = y;
        if (hidden) hidden.value = iso;
        if (native) native.value = iso;
        root.dispatchEvent(new CustomEvent('dc-date-change', { bubbles: true, detail: { iso } }));
    }

    function setDateBoxesDisabled(form, disabled) {
        form.querySelectorAll('.dc-datebox, .dc-datebox-native').forEach((el) => {
            el.disabled = disabled;
        });
        form.querySelectorAll('.dc-dateboxes').forEach((root) => {
            root.classList.toggle('opacity-60', disabled);
        });
    }

    function openingFromForForm(form) {
        const clientSelect = form.querySelector('#ledger_client_id');
        if (clientSelect && clientSelect.value) {
            const opt = clientSelect.selectedOptions[0];
            const clientFrom = (opt && opt.dataset && opt.dataset.openingFrom) || '';
            if (clientFrom) return clientFrom;
        }
        return form.dataset.openingFrom || form.dataset.defaultFrom;
    }

    function applyPeriod(form, full) {
        const boxes = form.querySelectorAll('[data-dateboxes]');
        if (boxes.length < 2) return;
        const fromBoxId = boxes[0].id;
        const toBoxId = boxes[1].id;
        if (full) {
            setDateBox(fromBoxId, openingFromForForm(form));
            setDateBox(toBoxId, form.dataset.today || form.dataset.defaultTo);
            setDateBoxesDisabled(form, true);
        } else {
            setDateBoxesDisabled(form, false);
            setDateBox(fromBoxId, form.dataset.defaultFrom);
            setDateBox(toBoxId, form.dataset.defaultTo);
        }
    }

    function bindForm(form) {
        if (!form) return;
        const cb = form.querySelector('.ledger-full-period');
        if (cb) {
            cb.addEventListener('change', () => applyPeriod(form, cb.checked));
        }
        const clientSelect = form.querySelector('#ledger_client_id');
        if (clientSelect) {
            clientSelect.addEventListener('change', () => {
                if (cb && cb.checked) applyPeriod(form, true);
            });
        }
    }

    const modal = document.getElementById('dc-report-modal');
    const backdrop = document.getElementById('dc-report-modal-backdrop');
    const frame = document.getElementById('dc-report-modal-frame');
    const titleEl = document.getElementById('dc-report-modal-title');
    const pdfBtn = document.getElementById('dc-report-modal-pdf');
    const printBtn = document.getElementById('dc-report-modal-print');
    const closeBtn = document.getElementById('dc-report-modal-close');
    let currentPdfUrl = '';
    let closeArmed = false;
    let closeArmTimer = null;

    function formToUrl(form, actionOverride) {
        const action = actionOverride || form.getAttribute('action');
        const url = new URL(action, window.location.origin);
        // Include și câmpurile disabled (date blocate la „toată perioada”).
        const wasDisabled = Array.from(form.querySelectorAll('[disabled]'));
        wasDisabled.forEach((el) => { el.disabled = false; });
        const data = new FormData(form);
        wasDisabled.forEach((el) => { el.disabled = true; });
        data.forEach((value, key) => {
            if (value !== null && value !== undefined && String(value) !== '') {
                url.searchParams.set(key, String(value));
            }
        });
        return url;
    }

    function openReportModal(viewUrl, pdfUrl, title) {
        if (!modal || !frame) return;
        const url = typeof viewUrl === 'string' ? new URL(viewUrl, window.location.origin) : viewUrl;
        // Nu încărca niciodată PDF în iframe (ar declanșa download și „închidere” aparentă).
        if (/\.pdf($|\?)/i.test(url.pathname)) {
            console.error('Preview URL invalid (PDF):', url.toString());
            return;
        }
        url.searchParams.set('embed', '1');
        currentPdfUrl = typeof pdfUrl === 'string' ? pdfUrl : (pdfUrl ? pdfUrl.toString() : '');
        titleEl.textContent = title || 'Raport';
        closeArmed = false;
        if (closeArmTimer) clearTimeout(closeArmTimer);
        frame.src = url.toString();
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('dc-report-modal-open');
        // Evită click-ul care a deschis modalul să îl închidă / să apese Export PDF.
        closeArmTimer = setTimeout(() => { closeArmed = true; }, 400);
    }

    function closeReportModal() {
        if (!modal || !frame || !closeArmed) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('dc-report-modal-open');
        frame.src = 'about:blank';
        currentPdfUrl = '';
        closeArmed = false;
    }

    function openFromForm(form) {
        if (!form.reportValidity()) return;
        const viewUrl = formToUrl(form);
        const pdfUrl = formToUrl(form, form.dataset.pdfAction);
        openReportModal(viewUrl, pdfUrl.toString(), form.dataset.title || 'Raport');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.dcInitDateBoxes === 'function') {
            window.dcInitDateBoxes();
        }
        bindForm(document.getElementById('fisa-partner-form'));
        bindForm(document.getElementById('balanta-partneri-form'));

        document.querySelectorAll('.js-report-modal-form').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                openFromForm(form);
            });
            form.querySelectorAll('.js-report-open').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    openFromForm(form);
                });
            });
        });

        document.querySelectorAll('.js-report-modal-link').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openReportModal(link.href, link.dataset.pdfUrl || '', link.dataset.title || 'Raport');
            });
        });

        if (backdrop) {
            backdrop.addEventListener('click', (e) => {
                e.preventDefault();
                closeReportModal();
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                closeReportModal();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.hidden) {
                closeReportModal();
            }
        });

        if (printBtn) {
            printBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                try {
                    if (frame.contentWindow) {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    }
                } catch (err) { /* ignore */ }
            });
        }

        if (pdfBtn) {
            pdfBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!currentPdfUrl || !/\.pdf($|\?)/i.test(currentPdfUrl)) return;
                // Navigare ascunsă doar la click explicit pe Export PDF.
                const dl = document.createElement('iframe');
                dl.style.cssText = 'position:fixed;width:0;height:0;border:0;visibility:hidden';
                dl.setAttribute('aria-hidden', 'true');
                dl.src = currentPdfUrl;
                document.body.appendChild(dl);
                setTimeout(() => dl.remove(), 120000);
            });
        }
    });
})();
</script>
@endsection
