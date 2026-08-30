@extends('layouts.app')
@php
    $company = $client->company;
    $canManage = $company
        ? app(\App\Services\CompanyPermission::class)->can(auth()->user(), $company, 'clients_manage')
        : false;
@endphp
@section('heading', 'Fișă client')
@section('subheading', $client->name)
@section('actions')
<a href="{{ route('clients.statement.pdf', $client) }}" class="dc-btn-secondary">Descarcă PDF</a>
@if($canManage)
<a href="{{ route('clients.edit', $client) }}" class="dc-btn-secondary">{{ __('Editează') }}</a>
@endif
<a href="{{ route('clients.index') }}" class="dc-btn-primary">{{ __('Înapoi') }}</a>
@endsection
@section('content')
<div class="grid gap-4 max-w-5xl">
    <div class="dc-card p-5">
        <div class="grid sm:grid-cols-2 gap-3 text-sm">
            <div>
                <div class="text-slate-500 text-xs uppercase tracking-wide">Identificare</div>
                <div class="font-medium text-lg text-slate-900">{{ $client->name }}</div>
                <div class="text-slate-600 mt-1">
                    @if($client->cui)CUI {{ $client->cui }}@endif
                    @if($client->cui && $client->reg_com) · @endif
                    {{ $client->reg_com }}
                    @if($client->cnp)<div>CNP {{ $client->cnp }}</div>@endif
                </div>
            </div>
            <div>
                <div class="text-slate-500 text-xs uppercase tracking-wide">Contact / adresă</div>
                <div class="text-slate-700 mt-1">{{ $client->fullAddress() ?: '—' }}</div>
                @if($client->email)<div class="text-slate-600 mt-1">{{ $client->email }}</div>@endif
                @if($client->phone)<div class="text-slate-600">{{ $client->phone }}</div>@endif
            </div>
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-3">
        <div class="dc-card p-4">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Sold inițial (rest)</div>
            <div class="text-2xl font-display tabular-nums text-slate-900 mt-1">{{ number_format($openingRemaining, 2, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">
                la {{ dc_date($client->effectiveOpeningBalanceDate()) }}
                @if(abs($opening - $openingRemaining) > 0.009)
                    · înregistrat {{ number_format($opening, 2, ',', '.') }}
                @endif
            </div>
        </div>
        <div class="dc-card p-4">
            <div class="text-xs text-slate-500 uppercase tracking-wide">{{ __('Facturi deschise') }}</div>
            <div class="text-2xl font-display tabular-nums text-slate-900 mt-1">{{ number_format($openRemaining, 2, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $openInvoices->count() }} documente</div>
        </div>
        <div class="dc-card p-4 border-teal-200 bg-teal-50/40">
            <div class="text-xs text-teal-800 uppercase tracking-wide">Sold total</div>
            <div class="text-2xl font-display tabular-nums text-teal-950 mt-1">{{ number_format($current, 2, ',', '.') }}</div>
            <div class="text-xs text-teal-800/80 mt-1">rest sold inițial + facturi − încasări pe sold</div>
        </div>
    </div>

    @php
        $penaltyPct = $client->penalty_percent;
        $penaltyPctLabel = $penaltyPct === null || $penaltyPct === ''
            ? '—'
            : rtrim(rtrim(number_format((float) $penaltyPct, 4, ',', ''), '0'), ',').' %';
        $penaltyOn = (bool) ($client->penalty_billing_enabled ?? false);
    @endphp
    <div class="dc-card p-5">
        <div class="text-xs text-slate-500 uppercase tracking-wide mb-3">Penalități cf. contract</div>
        <div class="grid sm:grid-cols-2 gap-4 text-sm items-start">
            <div>
                <div class="text-slate-500">Procent penalizare cf contract</div>
                <div class="mt-1 text-lg font-medium tabular-nums text-slate-900">{{ $penaltyPctLabel }}</div>
                <div class="text-xs text-slate-500 mt-1">pe zi · scadențe ≥ 11.08.2026</div>
                @php
                    $instAmt = $client->opening_installment_amount;
                    $instCnt = $client->opening_installment_count;
                    $monthlyRec = null;
                    try {
                        $monthlyRec = app(\App\Services\ClientPenaltyService::class)->monthlyRecurringTotal($client);
                    } catch (\Throwable $e) {
                        $monthlyRec = null;
                    }
                @endphp
                @if($instAmt && $instCnt)
                    <div class="text-xs text-slate-500 mt-2">
                        Sold inițial pe {{ (int) $instCnt }} tranșe × {{ number_format((float) $instAmt, 2, ',', '.') }} RON
                        (ultima scadență 11.08.2026, lunar pe 11)
                    </div>
                @elseif($monthlyRec && $monthlyRec >= 0.01)
                    <div class="text-xs text-slate-500 mt-2">
                        Sold inițial pe tranșe din recurentă: {{ number_format((float) $monthlyRec, 2, ',', '.') }} RON / lună
                        (ultima scadență 11.08.2026, lunar pe 11)
                    </div>
                @endif
            </div>
            <div>
                <div class="text-slate-500">Se calculeaza / factureaza:</div>
                @if($canManage)
                    <form method="POST" action="{{ route('clients.penalty-billing', $client) }}" class="mt-1" id="dc-penalty-billing-form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="penalty_billing_enabled" value="0">
                        <label class="dc-onoff" data-dc-onoff>
                            <input type="checkbox"
                                   name="penalty_billing_enabled"
                                   value="1"
                                   class="dc-onoff-input"
                                   @checked($penaltyOn)
                                   onchange="this.form.submit()">
                            <span class="dc-onoff-track" aria-hidden="true">
                                <span class="dc-onoff-label dc-onoff-off">OFF</span>
                                <span class="dc-onoff-label dc-onoff-on">ON</span>
                                <span class="dc-onoff-knob"></span>
                            </span>
                        </label>
                    </form>
                @else
                    <div class="mt-1">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold tracking-wide
                            {{ $penaltyOn ? 'bg-teal-100 text-teal-900 ring-1 ring-teal-300' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-300' }}">
                            {{ $penaltyOn ? 'ON' : 'OFF' }}
                        </span>
                    </div>
                @endif
                <div class="text-xs text-slate-500 mt-1">
                    {{ $penaltyOn ? 'Penalitățile nefacturate apar pe următoarea factură (fără TVA).' : 'Calculul continuă; nu se mai pun pe facturi până reactivezi.' }}
                </div>
            </div>
        </div>
        @php $ps = $penaltySummary ?? ['accrued' => 0, 'billed' => 0, 'paid' => 0]; @endphp
        <div class="mt-4 grid sm:grid-cols-3 gap-3 text-sm">
            <div class="rounded-lg border border-red-200 bg-red-50/70 px-3 py-2">
                <div class="text-xs text-red-800/80">Nefacturate</div>
                <div class="mt-0.5 font-medium tabular-nums text-red-700">{{ number_format((float) ($ps['accrued'] ?? 0), 2, ',', '.') }}</div>
            </div>
            <div class="rounded-lg border border-amber-100 bg-amber-50/60 px-3 py-2">
                <div class="text-xs text-amber-900/80">Facturate, neîncasate</div>
                <div class="mt-0.5 font-medium tabular-nums text-amber-950">{{ number_format((float) ($ps['billed'] ?? 0), 2, ',', '.') }}</div>
            </div>
            <div class="rounded-lg border border-teal-100 bg-teal-50/50 px-3 py-2">
                <div class="text-xs text-teal-800/80">Încasate</div>
                <div class="mt-0.5 font-medium tabular-nums text-teal-950">{{ number_format((float) ($ps['paid'] ?? 0), 2, ',', '.') }}</div>
            </div>
        </div>
        @php $penaltyRows = $penaltyRows ?? []; @endphp
        @if(count($penaltyRows) > 0)
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2 font-medium">Detaliu</th>
                        <th class="px-3 py-2 font-medium text-right">Zile</th>
                        <th class="px-3 py-2 font-medium text-right">Sumă</th>
                        <th class="px-3 py-2 font-medium">Status / factură</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($penaltyRows as $row)
                        <tr class="{{ ! empty($row['is_unbilled']) ? 'bg-red-50/40' : '' }}">
                            <td class="px-3 py-2 {{ ! empty($row['is_unbilled']) ? 'text-red-800' : 'text-slate-800' }}">
                                {{ $row['label'] }}
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-slate-600">{{ (int) ($row['days'] ?? 0) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-medium {{ ! empty($row['is_unbilled']) ? 'text-red-700' : 'text-slate-900' }}">
                                {{ number_format((float) ($row['amount'] ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-2">
                                @if(! empty($row['is_unbilled']))
                                    <span class="font-semibold text-red-600 uppercase tracking-wide text-xs">Nefacturate</span>
                                @elseif(! empty($row['billed_document_id']))
                                    <span class="text-slate-600 text-xs">{{ $row['status_label'] }} pe </span>
                                    <a href="{{ route('documents.show', $row['billed_document_id']) }}"
                                       class="text-teal-800 hover:underline font-medium">
                                        {{ $row['billed_document_number'] ?? ('#'.$row['billed_document_id']) }}
                                    </a>
                                @else
                                    <span class="text-slate-600 text-xs">{{ $row['status_label'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($canManage)
            <div class="mt-3 text-xs text-slate-500">
                Procentul și restul datelor se modifică din <a href="{{ route('clients.edit', $client) }}" class="text-teal-800 hover:underline font-medium">Editează</a>.
            </div>
        @endif
    </div>

    <style>
    .dc-onoff { display:inline-flex; cursor:pointer; user-select:none; }
    .dc-onoff-input { position:absolute; opacity:0; width:0; height:0; }
    .dc-onoff-track {
        position:relative; display:inline-flex; align-items:center; width:76px; height:32px;
        border-radius:999px; background:#cbd5e1; border:1px solid #94a3b8;
        transition: background .15s ease, border-color .15s ease;
    }
    .dc-onoff-label {
        position:absolute; top:0; bottom:0; display:flex; align-items:center;
        font-size:11px; font-weight:700; letter-spacing:.04em; color:#475569;
    }
    .dc-onoff-off { left:8px; }
    .dc-onoff-on { right:8px; opacity:0; color:#0f766e; }
    .dc-onoff-knob {
        position:absolute; top:3px; left:3px; width:24px; height:24px; border-radius:999px;
        background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.25);
        transition: transform .15s ease;
    }
    .dc-onoff-input:checked + .dc-onoff-track {
        background:#ccfbf1; border-color:#0f766e;
    }
    .dc-onoff-input:checked + .dc-onoff-track .dc-onoff-knob { transform: translateX(44px); }
    .dc-onoff-input:checked + .dc-onoff-track .dc-onoff-off { opacity:0; }
    .dc-onoff-input:checked + .dc-onoff-track .dc-onoff-on { opacity:1; }
    .dc-onoff-input:focus-visible + .dc-onoff-track { outline:2px solid #0f766e; outline-offset:2px; }
    </style>

    <div class="dc-card overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 font-medium text-slate-800">{{ __('Facturi deschise') }}</div>
        <table class="w-full dc-table">
            <thead>
            <tr>
                <th>{{ __('Factură') }}</th>
                <th>Emitere</th>
                <th>{{ __('Scadență') }}</th>
                <th class="text-right">{{ __('Total') }}</th>
                <th class="text-right">Achitat</th>
                <th class="text-right">{{ __('Rest') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($openInvoices as $invoice)
            <tr>
                <td>
                    <a href="{{ route('documents.show', $invoice) }}" class="text-teal-800 hover:underline font-medium">
                        {{ $invoice->number_full }}
                    </a>
                </td>
                <td>{{ dc_date($invoice->issue_date) }}</td>
                <td>{{ dc_date($invoice->due_date) }}</td>
                <td class="text-right tabular-nums">{{ number_format((float) $invoice->total, 2, ',', '.') }}</td>
                <td class="text-right tabular-nums">{{ number_format((float) $invoice->paid_amount, 2, ',', '.') }}</td>
                <td class="text-right tabular-nums font-medium">{{ number_format($invoice->remainingAmount(), 2, ',', '.') }}</td>
                <td>{{ $invoice->paymentStatusLabel() }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-slate-500">Nicio factură deschisă.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
