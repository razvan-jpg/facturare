@extends('layouts.app')
@php
    $title = \App\Models\Document::LIST_LABELS[$type]
        ?? \App\Models\Document::TYPE_LABELS[$type]
        ?? 'Documente';
    $hasActiveSeries = $hasActiveSeries ?? true;
    $docPerm = app(\App\Services\CompanyPermission::class);
    $docUser = auth()->user();
    $canManageDocs = $type === 'receipt'
        ? $docPerm->can($docUser, $company, 'payments_manage')
        : $docPerm->can($docUser, $company, 'documents_manage');
    $canManageEfactura = $docPerm->can($docUser, $company, 'efactura_manage');
@endphp
@section('heading', $title)
@section('actions')
    @php
        $createUrl = match ($type) {
            'receipt' => route('payments.create'),
            'storno' => route('documents.corrections.create', ['kind' => 'storno']),
            'credit_note' => route('documents.corrections.create', ['kind' => 'credit_note']),
            default => route('documents.create', ['type' => $type]),
        };
        $createLabel = match ($type) {
            'invoice' => __('Factură nouă'),
            'proforma' => __('Proformă nouă'),
            'delivery' => __('Aviz nou'),
            'receipt' => __('Încasare nouă'),
            'storno' => __('Factură storno'),
            'credit_note' => __('Notă de creditare'),
            default => $title,
        };
    @endphp
    @if($canManageDocs)
        @if($hasActiveSeries)
            <a href="{{ $createUrl }}" class="dc-btn-primary">{{ $createLabel }}</a>
        @else
            <span class="dc-btn-disabled"
                  title="Definiți întâi o serie activă în Setări → Serii"
                  aria-disabled="true">{{ $createLabel }}</span>
        @endif
    @endif
@endsection
@section('content')
@php
    // Facturi + storno + note de creditare: același flux e-Factura (status, select, trimite, XML).
    $isEfacturaList = in_array($type, ['invoice', 'storno', 'credit_note'], true);
    // Selectabile pentru XML (emise/storno/NC) — trimiterea e-Factura filtrează separat cele neeligibile.
    $selectableIds = $isEfacturaList
        ? $documents->getCollection()->filter(fn ($d) => $d->canExportEfacturaXml())->pluck('id')->values()->all()
        : [];
    $sendableIds = $isEfacturaList
        ? $documents->getCollection()->filter(fn ($d) => $d->canSendEfactura())->pluck('id')->values()->all()
        : [];
    $pendingEfacturaRefresh = $isEfacturaList && $documents->getCollection()->contains(
        fn ($d) => in_array($d->efactura_status, ['uploaded', 'processing'], true)
    );
@endphp

@if(! $hasActiveSeries)
    <div class="mb-6 px-2 text-center">
        <p class="text-rose-600 text-xl sm:text-2xl md:text-3xl font-bold leading-snug max-w-4xl mx-auto">
            Nu aveți definită nicio serie activă în setări pentru acest tip de documente. Definiți întâi o serie și apoi puteți emite documente!
        </p>
        <a href="{{ route('companies.edit', ['company' => $company, 'tab' => 'serii']) }}"
           class="inline-block mt-3 text-sm font-semibold text-teal-800 underline hover:text-teal-950">
            Mergi la Setări → Serii
        </a>
    </div>
@endif

@if($isEfacturaList && ($hasEfacturaOverdue ?? false))
    <div class="mb-5 px-2 text-center">
        <p class="text-rose-600 text-xl sm:text-2xl md:text-3xl font-bold leading-snug max-w-5xl mx-auto uppercase tracking-wide">
            ATENȚIE!!! Aveți facturi emise și netrimise în E-Factura... Procedați imediat la trimiterea în E-Factura sau Anularea/Ștergerea acestora!
        </p>
    </div>
@endif

<div @if($isEfacturaList) x-data="dcInvoiceBulkActions({
    selectable: @js($selectableIds),
    sendable: @js($sendableIds),
    xmlUrl: @js(route('documents.efactura.xml-export')),
    csrf: @js(csrf_token()),
    autoRefresh: @js($pendingEfacturaRefresh),
})" @endif>

@if($isEfacturaList && ($canManageDocs || $canManageEfactura || $docPerm->can($docUser, $company, 'efactura_view')))
<form method="POST" action="{{ route('documents.efactura.send-bulk') }}" class="mb-3 flex flex-wrap items-center gap-3"
      @submit="if (selected.length === 0) { $event.preventDefault(); alert('Selectează cel puțin un document.'); }">
    @csrf
    <template x-for="id in selected" :key="id">
        <input type="hidden" name="document_ids[]" :value="id">
    </template>
    @if($canManageEfactura)
    <button type="submit"
            class="dc-btn-primary"
            :disabled="count() === 0"
            :class="count() === 0 ? 'opacity-50 cursor-not-allowed' : ''"
            @click="if (count() > 0 && !confirm('Trimiți ' + count() + (count() === 1 ? ' document' : ' documente') + ' în e-Factura?')) $event.preventDefault()">
        Trimite în e-Factura
        <span x-show="count() > 0" x-text="'(' + count() + ')'"></span>
    </button>
    @endif
    @if($canManageDocs && in_array($type, ['invoice', 'storno'], true))
    <button type="submit"
            formaction="{{ route('documents.email-bulk') }}"
            class="dc-btn-secondary"
            :disabled="count() === 0"
            :class="count() === 0 ? 'opacity-50 cursor-not-allowed' : ''"
            @click="if (count() > 0 && !confirm('Retrimiți pe email ' + count() + (count() === 1 ? ' factură' : ' facturi') + ' selectate către clienți?')) $event.preventDefault()">
        Retrimite pe email
        <span x-show="count() > 0" x-text="'(' + count() + ')'"></span>
    </button>
    @endif
    <button type="button"
            class="dc-btn-secondary"
            :disabled="count() === 0 || xmlBusy"
            :class="(count() === 0 || xmlBusy) ? 'opacity-50 cursor-not-allowed' : ''"
            @click="exportXml()">
        <span x-text="xmlBusy ? 'Se genereză XML…' : 'Generare / Salvare fișiere .xml'"></span>
        <span x-show="count() > 0 && !xmlBusy" x-text="'(' + count() + ')'"></span>
    </button>
    <span class="text-sm text-slate-500" x-show="selectable.length > 0">
        <span x-text="count()"></span> selectate din <span x-text="selectable.length"></span> eligibile
    </span>
    @if($selectableIds === [])
        <span class="text-sm text-slate-500">Niciun document emis eligibil pe această pagină.</span>
    @endif
</form>
@endif

@include('partials.pagination', ['paginator' => $documents, 'class' => 'mb-4'])
<div class="dc-card overflow-hidden">
<table class="w-full dc-table">
<thead>
<tr>
    @if($isEfacturaList)
    <th class="w-10">
        <input type="checkbox"
               class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
               title="Selectează toate eligibile"
               :checked="allSelected()"
               :disabled="selectable.length === 0"
               @change="toggleAll()">
    </th>
    @endif
    <th>{{ __('Număr') }}</th>
    <th>Data</th>
    <th>{{ __('Client') }}</th>
    <th>{{ __('Total') }}</th>
    <th>{{ __('Status') }}</th>
    <th>{{ __('Plată') }}</th>
    @if($isEfacturaList)
    <th>e-Factura</th>
    @endif
    <th class="text-right">{{ __('Acțiuni') }}</th>
</tr>
</thead>
<tbody>
@forelse($documents as $doc)
@php
    $locked = $doc->isSentToEfactura();
    $lockTitle = 'Indisponibil: document trimis în e-Factura';
    $canSelect = $isEfacturaList && $doc->canExportEfacturaXml();
    $efacturaOverdue = $isEfacturaList && $doc->isEfacturaSubmissionOverdue();
    $rowWarn = $efacturaOverdue ? 'dc-efactura-overdue' : '';
@endphp
<tr class="{{ $rowWarn }}" @if($canSelect) :class="selected.includes({{ $doc->id }}) ? 'bg-amber-50/50' : ''" @endif>
    @if($isEfacturaList)
    <td>
        @if($canSelect)
            <input type="checkbox"
                   class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                   value="{{ $doc->id }}"
                   :checked="selected.includes({{ $doc->id }})"
                   @change="selected.includes({{ $doc->id }})
                       ? selected = selected.filter(i => i !== {{ $doc->id }})
                       : selected.push({{ $doc->id }})">
        @else
            <input type="checkbox" class="rounded border-slate-200 opacity-30" disabled title="Doar documentele emise pot genera XML / e-Factura">
        @endif
    </td>
    @endif
    <td class="font-medium {{ $efacturaOverdue ? 'text-rose-600 font-bold' : '' }}">
        <a href="{{ route('documents.show', $doc) }}" class="{{ $efacturaOverdue ? 'text-rose-600 font-bold hover:underline' : 'text-teal-800 hover:underline' }}">{{ $doc->number_full ?: 'Draft #'.$doc->id }}</a>
        @if($doc->related_document_id)
            <div class="text-[10px] uppercase tracking-wide {{ $efacturaOverdue ? 'text-rose-500 font-bold' : 'text-slate-400' }}">storno</div>
        @endif
    </td>
    <td class="{{ $efacturaOverdue ? 'text-rose-600 font-bold' : '' }}">{{ dc_date($doc->issue_date) }}</td>
    <td class="{{ $efacturaOverdue ? 'text-rose-600 font-bold' : '' }}">{{ $doc->client_name ?: '—' }}</td>
    <td class="{{ $efacturaOverdue ? 'text-rose-600 font-bold' : '' }}">{{ number_format($doc->total, 2, ',', '.') }} {{ $doc->currency }}</td>
    <td><span class="text-xs font-medium {{ $efacturaOverdue ? 'text-rose-600 font-bold' : '' }}">{{ $doc->statusLabel() }}</span></td>
    <td class="{{ $efacturaOverdue ? 'text-rose-600 font-bold' : '' }}">{{ $doc->paymentStatusLabel() }}</td>
    @if($isEfacturaList)
    <td class="text-xs align-top">
        <div class="{{ $efacturaOverdue ? 'text-rose-600 font-bold' : ($doc->efactura_status === 'ok' ? 'text-teal-700 font-semibold' : ($doc->efactura_status === 'error' || $doc->efactura_status === 'nok' ? 'text-rose-700' : 'text-slate-600')) }}">
            {{ $doc->efacturaStatusLabel() }}
        </div>
        @if($doc->isEfacturaAutoRetrying())
            <div class="mt-1 text-[10px] text-amber-700">În reîncercare automată…</div>
        @endif
        @if(filled($doc->efactura_upload_id) || filled($doc->efactura_download_id))
            <div class="mt-1 space-y-0.5 text-[10px] leading-snug text-slate-500 font-normal">
                @if(filled($doc->efactura_upload_id))
                    <div>ID încărcare: <span class="font-mono text-slate-700">{{ $doc->efactura_upload_id }}</span></div>
                @endif
                @if(filled($doc->efactura_download_id))
                    <div>ID descărcare: <span class="font-mono text-slate-700">{{ $doc->efactura_download_id }}</span></div>
                @endif
            </div>
        @endif
        @if(in_array($doc->efactura_status, ['nok', 'error'], true) && filled($doc->efactura_error))
            <div class="mt-1 text-[10px] text-rose-700 max-w-[14rem] line-clamp-2" title="{{ $doc->efactura_error }}">{{ $doc->efactura_error }}</div>
        @endif
    </td>
    @endif
    <td class="text-right">
        <div class="dc-act-wrap">
            <a href="{{ route('documents.pdf', $doc) }}" class="doc-act">PDF</a>

            @if($doc->canEdit())
                <a href="{{ route('documents.edit', $doc) }}" class="doc-act">{{ __('Editează') }}</a>
            @else
                <span class="doc-act doc-act-disabled" title="{{ $locked ? $lockTitle : 'Nu poate fi editat' }}">{{ __('Editează') }}</span>
            @endif

            @if($type === 'invoice')
                @if($doc->canStorno())
                    <form method="POST" action="{{ route('documents.storno', $doc) }}" class="inline" onsubmit="return confirm('Emți factură storno pentru {{ $doc->number_full ?: '#'.$doc->id }}?')">
                        @csrf
                        <button class="doc-act">{{ __('Stornează') }}</button>
                    </form>
                @else
                    <span class="doc-act doc-act-disabled" title="Stornarea e disponibilă doar pentru facturi emise, o singură dată">{{ __('Stornează') }}</span>
                @endif
                @if($doc->canCreditNote())
                    <form method="POST" action="{{ route('documents.corrections.store', ['kind' => 'credit_note']) }}" class="inline" onsubmit="return confirm('Emți notă de creditare pentru {{ $doc->number_full ?: '#'.$doc->id }}?')">
                        @csrf
                        <input type="hidden" name="document_id" value="{{ $doc->id }}">
                        <button class="doc-act">Notă credit</button>
                    </form>
                @endif
            @endif

            @if($doc->canCancel())
                <form method="POST" action="{{ route('documents.cancel', $doc) }}" class="inline" onsubmit="return confirm('Anulezi documentul?')">
                    @csrf
                    <button class="doc-act">{{ __('Anulează') }}</button>
                </form>
            @else
                <span class="doc-act doc-act-disabled" title="{{ $locked ? $lockTitle : 'Nu poate fi anulat' }}">{{ __('Anulează') }}</span>
            @endif

            @if($doc->canDelete())
                <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="inline" onsubmit="return confirm('Ștergi definitiv documentul?')">
                    @csrf @method('DELETE')
                    <button class="doc-act doc-act-danger">{{ __('Șterge') }}</button>
                </form>
            @else
                <span class="doc-act doc-act-disabled" title="{{ $locked ? $lockTitle : 'Nu poate fi șters' }}">{{ __('Șterge') }}</span>
            @endif
        </div>
    </td>
</tr>
@empty
<tr><td colspan="{{ $isEfacturaList ? 9 : 7 }}" class="text-slate-500">Niciun document.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
@include('partials.pagination', ['paginator' => $documents, 'class' => 'mt-4'])
@if($isEfacturaList)
@push('scripts')
<script>
function dcInvoiceBulkActions({ selectable, sendable, xmlUrl, csrf, autoRefresh }) {
    return {
        selected: [],
        selectable: selectable || [],
        sendable: sendable || [],
        xmlUrl,
        csrf,
        xmlBusy: false,
        autoRefresh: !!autoRefresh,
        _refreshTimer: null,
        init() {
            if (! this.autoRefresh) return;
            this._refreshTimer = setInterval(() => {
                // Păstrează query-ul curent (filtre / pagină).
                window.location.reload();
            }, 30000);
        },
        destroy() {
            if (this._refreshTimer) clearInterval(this._refreshTimer);
        },
        toggleAll() {
            this.selected = this.allSelected() ? [] : [...this.selectable];
        },
        allSelected() {
            return this.selectable.length > 0 && this.selected.length === this.selectable.length;
        },
        count() { return this.selected.length; },
        async exportXml() {
            if (this.selected.length === 0) {
                alert('Selectează cel puțin o factură.');
                return;
            }
            this.xmlBusy = true;
            try {
                const res = await fetch(this.xmlUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ document_ids: this.selected, format: 'json' }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    alert(data.message || 'Nu am putut genera fișierele XML.');
                    return;
                }
                const files = data.files || [];
                if (files.length === 0) {
                    alert((data.errors && data.errors.join('\\n')) || 'Niciun XML generat.');
                    return;
                }

                // Chromium: alege folderul și salvează câte un .xml per factură
                if (files.length > 1 && typeof window.showDirectoryPicker === 'function') {
                    try {
                        const dir = await window.showDirectoryPicker({ mode: 'readwrite' });
                        for (const file of files) {
                            const handle = await dir.getFileHandle(file.filename, { create: true });
                            const writable = await handle.createWritable();
                            await writable.write(file.content);
                            await writable.close();
                        }
                        let msg = 'Salvate ' + files.length + ' fișiere XML în folderul ales.';
                        if (data.errors && data.errors.length) {
                            msg += '\n\nAtenție:\n' + data.errors.join('\n');
                        }
                        alert(msg);
                        return;
                    } catch (e) {
                        if (e && e.name === 'AbortError') return;
                        // fallback ZIP mai jos
                    }
                }

                // Un singur fișier: dialog Salvare ca…
                if (files.length === 1 && typeof window.showSaveFilePicker === 'function') {
                    try {
                        const handle = await window.showSaveFilePicker({
                            suggestedName: files[0].filename,
                            types: [{
                                description: 'XML e-Factura (ANAF)',
                                accept: { 'application/xml': ['.xml'] },
                            }],
                        });
                        const writable = await handle.createWritable();
                        await writable.write(files[0].content);
                        await writable.close();
                        return;
                    } catch (e) {
                        if (e && e.name === 'AbortError') return;
                    }
                }

                if (files.length === 1) {
                    this.downloadBlob(files[0].content, files[0].filename, 'application/xml;charset=utf-8');
                    return;
                }

                // Fallback multi: ZIP din server (browserul cere locația arhivei)
                const zipRes = await fetch(this.xmlUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/zip',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ document_ids: this.selected, format: 'zip' }),
                });
                if (!zipRes.ok) {
                    // ultimă rezervă: descarcă XML-urile pe rând
                    for (const file of files) {
                        this.downloadBlob(file.content, file.filename, 'application/xml;charset=utf-8');
                        await new Promise(r => setTimeout(r, 250));
                    }
                    alert('Au fost declanșate ' + files.length + ' descărcări XML (browserul nu a putut alege un folder).');
                    return;
                }
                const blob = await zipRes.blob();
                const disp = zipRes.headers.get('Content-Disposition') || '';
                const match = /filename=\"?([^\";]+)\"?/i.exec(disp);
                this.downloadBlob(blob, match ? match[1] : 'e-factura-xml.zip', 'application/zip');
            } catch (e) {
                alert('Eroare la generarea XML: ' + (e && e.message ? e.message : e));
            } finally {
                this.xmlBusy = false;
            }
        },
        downloadBlob(data, filename, mime) {
            const blob = data instanceof Blob ? data : new Blob([data], { type: mime || 'application/octet-stream' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 2000);
        },
    };
}
</script>
@endpush
@endif

<style>
tr.dc-efactura-overdue td {
    color: #e11d48;
    font-weight: 700;
    background: #fff1f2;
}
tr.dc-efactura-overdue td a {
    color: #e11d48;
    font-weight: 700;
}
</style>
@endsection
