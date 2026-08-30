@extends('layouts.app')
@section('heading', $document->typeLabel().' '.($document->number_full ?: '#'.$document->id))
@section('subheading')
Status: {{ $document->statusLabel() }} · Plată: {{ $document->paymentStatusLabel() }}
@if(in_array($document->type, ['invoice', 'credit_note'], true))
· e-Factura: {{ $document->efacturaStatusLabel() }}
@endif
@if($document->recurring_invoice_id)
· din abonament
@endif
@endsection
@section('actions')
@php
    $showPerm = app(\App\Services\CompanyPermission::class);
    $showUser = auth()->user();
    $showCompany = $document->company ?? $company ?? null;
    $canManageDocs = $showPerm->can($showUser, $showCompany, 'documents_manage');
    $canManageEfactura = $showPerm->can($showUser, $showCompany, 'efactura_manage');
@endphp
<a href="{{ route('documents.pdf', $document) }}" class="dc-btn-secondary">PDF</a>
@if($canManageDocs && $document->canEdit())
<a href="{{ route('documents.edit', $document) }}" class="dc-btn-secondary">{{ __('Editează') }}</a>
@endif
@if($canManageDocs && $document->status === 'draft')
<form method="POST" action="{{ route('documents.issue', $document) }}">@csrf<button class="dc-btn-primary">{{ __('Emite') }}</button></form>
@endif
@if($canManageDocs && in_array($document->status, ['issued', 'storno'], true))
<form method="POST" action="{{ route('documents.email', $document) }}">@csrf<button class="dc-btn-secondary">Trimite email</button></form>
@endif
@if($canManageEfactura && in_array($document->type, ['invoice', 'credit_note'], true) && $document->canSendEfactura())
<form method="POST" action="{{ route('documents.efactura.send', $document) }}">@csrf
    <button class="dc-btn-primary">
        {{ in_array($document->efactura_status ?: 'none', ['none', 'queued'], true) ? 'Trimite e-Factura' : 'Retrimite e-Factura' }}
    </button>
</form>
@endif
@if(($canManageEfactura || $showPerm->can($showUser, $showCompany, 'efactura_view')) && $document->canExportEfacturaXml())
<form method="POST" action="{{ route('documents.efactura.xml-export') }}">
    @csrf
    <input type="hidden" name="document_ids[]" value="{{ $document->id }}">
    <input type="hidden" name="format" value="xml">
    <button class="dc-btn-secondary" title="Descarcă XML UBL pentru încărcare manuală în SPV">Descarcă XML</button>
</form>
@endif
@if($canManageEfactura && in_array($document->type, ['invoice', 'credit_note'], true) && $document->efactura_upload_id && ! in_array($document->efactura_status, ['ok', 'nok'], true))
<form method="POST" action="{{ route('documents.efactura.refresh', $document) }}">@csrf<button class="dc-btn-secondary">Actualizează stare ANAF</button></form>
@endif
@if($canManageDocs && $document->canStorno())
<form method="POST" action="{{ route('documents.storno', $document) }}" onsubmit="return confirm('Emți factură storno?')">@csrf<button class="dc-btn-secondary">{{ __('Stornează') }}</button></form>
@endif
@if($canManageDocs && $document->canCreditNote())
<form method="POST" action="{{ route('documents.corrections.store', ['kind' => 'credit_note']) }}" onsubmit="return confirm('Emți notă de creditare?')">
    @csrf
    <input type="hidden" name="document_id" value="{{ $document->id }}">
    <button class="dc-btn-secondary">{{ __('Notă de creditare') }}</button>
</form>
@endif
@if($canManageDocs && $document->canCancel())
<form method="POST" action="{{ route('documents.cancel', $document) }}" onsubmit="return confirm('Anulezi documentul?')">@csrf<button class="dc-btn-secondary">{{ __('Anulează') }}</button></form>
@endif
@if($canManageDocs && $document->canDelete())
<form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Ștergi definitiv documentul?')">@csrf @method('DELETE')<button class="dc-btn-secondary">{{ __('Șterge') }}</button></form>
@endif
@endsection

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 dc-card p-6">
        <div class="flex justify-between gap-4 mb-6">
            <div>
                <div class="text-xs uppercase text-slate-500">Furnizor</div>
                <div class="font-semibold">{{ $document->company->name }}</div>
                <div class="text-sm text-slate-600">CUI {{ $document->company->cui }} · {{ $document->company->reg_com }}</div>
                <div class="text-sm text-slate-600">{{ $document->company->fullAddress() }}</div>
                @foreach($document->company->invoiceBankAccounts() as $account)
                    <div class="text-sm text-slate-600">IBAN {{ $account->iban }}@if($account->bank?->name) · {{ $account->bank->name }}@endif</div>
                @endforeach
            </div>
            <div class="text-right">
                <div class="text-xs uppercase text-slate-500">{{ __('Client') }}</div>
                <div class="font-semibold">{{ $document->client_name }}</div>
                @php
                    $showClientId = (string) ($document->client_cui ?? '');
                    $clientIdIsCnp = ($document->client?->type === 'person')
                        || (strlen(preg_replace('/\D+/', '', $showClientId) ?? '') === 13);
                @endphp
                @if($showClientId !== '' || $document->client_reg_com)
                    <div class="text-sm text-slate-600">
                        {{ $clientIdIsCnp ? 'CNP' : 'CUI' }} {{ $showClientId }}
                        @if($document->client_reg_com) · {{ $document->client_reg_com }}@endif
                    </div>
                @endif
                <div class="text-sm text-slate-600">{{ $document->client_address }}</div>
            </div>
        </div>
        <table class="w-full dc-table mb-4">
            <thead><tr><th>Denumire</th><th>UM</th><th>Cant.</th><th>{{ __('Preț') }}</th><th>{{ __('TVA') }}</th><th>{{ __('Total') }}</th></tr></thead>
            <tbody>
            @foreach($document->items as $item)
            <tr>
                <td>
                    {{ $item->name }}
                    @if($item->description)
                        <div class="text-xs text-slate-500 mt-0.5">{{ $item->description }}</div>
                    @endif
                </td>
                <td>{{ \App\Support\MeasureUnits::short($item->unit) }}</td>
                <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                <td>{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td>{{ number_format($item->vat_rate, 2, ',', '.') }}%</td>
                <td>{{ number_format($item->line_total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <div class="text-right space-y-1 text-sm">
            <div>Subtotal: <strong>{{ number_format($document->subtotal, 2, ',', '.') }} {{ $document->currency }}</strong></div>
            <div>TVA: <strong>{{ number_format($document->vat_total, 2, ',', '.') }} {{ $document->currency }}</strong></div>
            <div class="text-lg">Total: <strong>{{ number_format($document->total, 2, ',', '.') }} {{ $document->currency }}</strong></div>
        </div>
        @if($document->notes)<p class="mt-4 text-sm text-slate-600">{{ $document->notes }}</p>@endif
        @if($document->contract_number || $document->despatch_advice || $document->prepared_by || $document->delegate_name || $document->vehicle_reg)
            <div class="mt-3 text-sm text-slate-600 space-y-0.5">
                @php($preparedByShow = $document->prepared_by ?: $document->company?->seriesResponsibleName())
                @if($preparedByShow)<div><span class="text-slate-500">Întocmit de:</span> {{ $preparedByShow }}@if($document->prepared_by_cnp) · CNP {{ $document->prepared_by_cnp }}@endif</div>@endif
                @if($document->delegate_name)<div><span class="text-slate-500">Delegat:</span> {{ $document->delegate_name }}@if($document->delegate_id_card) · CI {{ $document->delegate_id_card }}@endif</div>@endif
                @if($document->vehicle_reg)<div><span class="text-slate-500">Auto:</span> {{ $document->vehicle_reg }}</div>@endif
                @if($document->contract_number)<div><span class="text-slate-500">Contract:</span> {{ $document->contract_number }}</div>@endif
                @if($document->despatch_advice)<div><span class="text-slate-500">Aviz însoțire:</span> {{ $document->despatch_advice }}</div>@endif
            </div>
        @endif
    </div>

    @if($document->type === 'invoice' && $document->status === 'issued')
    <div class="space-y-4">
        <div class="dc-card p-5">
            <h3 class="font-semibold mb-3">e-Factura</h3>
            <div class="text-sm space-y-2">
                <div><span class="text-slate-500">Status:</span> <strong>{{ $document->efacturaStatusLabel() }}</strong></div>
                @if($document->isEfacturaAutoRetrying())
                    <div class="rounded-md bg-amber-50 text-amber-900 p-3 text-xs">
                        În reîncercare automată (încercarea {{ (int) $document->efactura_auto_attempts }}/5 azi).
                        Sistemul verifică până la Acceptată ANAF; la nevoie corectează datele și retrimite.
                        @if($document->efactura_auto_next_at)
                            Următoarea încercare: {{ dc_datetime($document->efactura_auto_next_at) }}.
                        @endif
                    </div>
                @elseif(in_array($document->efactura_status, ['uploaded', 'processing'], true))
                    <div class="rounded-md bg-sky-50 text-sky-900 p-3 text-xs">
                        Se verifică automat starea la ANAF până la Acceptată.
                    </div>
                @endif
                @if($document->efactura_upload_id)
                    <div><span class="text-slate-500">ID încărcare:</span> <span class="font-mono">{{ $document->efactura_upload_id }}</span></div>
                @endif
                @if($document->efactura_download_id)
                    <div><span class="text-slate-500">ID descărcare:</span> <span class="font-mono">{{ $document->efactura_download_id }}</span></div>
                @endif
                @if($document->efactura_scheduled_at && $document->efactura_status === 'queued')
                    <div><span class="text-slate-500">Programată:</span> {{ dc_datetime($document->efactura_scheduled_at) }}</div>
                @endif
                @if($document->efactura_sent_at)
                    <div><span class="text-slate-500">Trimisă:</span> {{ dc_datetime($document->efactura_sent_at) }}</div>
                @endif
                @if($document->efactura_checked_at)
                    <div><span class="text-slate-500">Ultima verificare:</span> {{ dc_datetime($document->efactura_checked_at) }}</div>
                @endif
                @if($document->efactura_error)
                    <div class="rounded-md bg-rose-50 text-rose-800 p-3 text-xs whitespace-pre-wrap">{{ $document->efactura_error }}</div>
                @endif
                @if(! $document->company->isAnafAuthorized())
                    <p class="text-amber-800 bg-amber-50 rounded-md p-3 text-xs">
                        Societatea nu este autorizată în SPV.
                        <a href="{{ route('companies.edit', $document->company) }}" class="underline font-medium">Configurează aici</a>
                    </p>
                @endif
            </div>
        </div>
        <div class="dc-card p-5">
            <h3 class="font-semibold mb-3">{{ __('Înregistrează încasare') }}</h3>
            <form method="POST" action="{{ route('payments.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="document_id" value="{{ $document->id }}">
                <div><label class="dc-label">{{ __('Sumă') }}</label><input name="amount" type="number" step="0.01" value="{{ $document->remainingAmount() }}" class="dc-input" required></div>
                @include('partials.date-input', ['name' => 'paid_at', 'label' => 'Data', 'value' => now(), 'required' => true])
                <div><label class="dc-label">{{ __('Metodă') }}</label>
                    <select name="method" class="dc-input">
                        <option value="op">Ordin de plată</option>
                        <option value="cash">Numerar</option>
                        <option value="receipt">{{ __('Chitanță') }}</option>
                        <option value="card">Card</option>
                        <option value="other">Altă</option>
                    </select>
                </div>
                <div><label class="dc-label">Referință</label><input name="reference" class="dc-input"></div>
                <button class="dc-btn-primary w-full">Salvează încasarea</button>
            </form>
        </div>
        <div class="dc-card p-5">
            <h3 class="font-semibold mb-3">{{ __('Încasări') }}</h3>
            <ul class="space-y-2 text-sm">
                @forelse($document->payments as $payment)
                <li class="flex justify-between gap-2 border-b border-slate-100 pb-2">
                    <span>{{ dc_date($payment->paid_at) }} · {{ $payment->method }}</span>
                    <span>{{ number_format($payment->amount, 2, ',', '.') }}</span>
                </li>
                @empty
                <li class="text-slate-500">Nicio încasare.</li>
                @endforelse
            </ul>
        </div>
    </div>
    @endif
</div>
@endsection
