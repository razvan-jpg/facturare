@extends('layouts.app')
@section('heading', 'Solduri inițiale clienți')
@section('subheading', 'Sold inițial necompletat = 0, cu dată implicită = data creării clientului. Completează doar datoria care nu e deja în facturile din DateConta.')
@section('actions')
<a href="{{ route('clients.index') }}" class="dc-btn-secondary">Înapoi la clienți</a>
@endsection
@section('content')
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('clients.opening-balances.update') }}" class="space-y-4">
    @csrf
    <div class="dc-card p-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[12rem]">
            <label class="dc-label" for="default-opening-date">Dată implicită</label>
            <input type="text" id="default-opening-date" class="dc-input dc-date-input" inputmode="numeric"
                   placeholder="zz/ll/aaaa" pattern="\d{1,2}/\d{1,2}/\d{4}" autocomplete="off"
                   value="{{ old('default_date', dc_date_input(now())) }}">
        </div>
        <button type="button" id="apply-opening-date" class="dc-btn-secondary">Aplică data la toți</button>
        <button type="submit" class="dc-btn-primary">Salvează soldurile</button>
    </div>

    <div class="dc-card overflow-hidden">
        <table class="w-full dc-table">
            <thead>
            <tr>
                <th>{{ __('Client') }}</th>
                <th class="w-40">{{ __('Sold inițial') }}</th>
                <th class="w-40">Data</th>
                <th class="text-right w-36">{{ __('Facturi deschise') }}</th>
                <th class="text-right w-36">Sold curent</th>
            </tr>
            </thead>
            <tbody>
            @forelse($clients as $i => $client)
                @php
                    $open = (float) ($openByClient[$client->id] ?? 0);
                    $opening = old('clients.'.$i.'.opening_balance', $client->opening_balance ?? 0);
                    $openingF = (float) str_replace(',', '.', (string) $opening);
                @endphp
                <tr class="opening-balance-row" data-open="{{ $open }}">
                    <td class="font-medium">
                        {{ $client->name }}
                        <input type="hidden" name="clients[{{ $i }}][id]" value="{{ $client->id }}">
                        <div class="text-xs text-slate-500">{{ $client->cui ?: $client->cnp }}</div>
                    </td>
                    <td>
                        <input type="text" inputmode="decimal" name="clients[{{ $i }}][opening_balance]"
                               value="{{ $opening === null || $opening === '' ? '0' : $opening }}"
                               placeholder="0" class="dc-input opening-amount tabular-nums" autocomplete="off">
                    </td>
                    <td>
                        <input type="text" name="clients[{{ $i }}][opening_balance_date]"
                               value="{{ old('clients.'.$i.'.opening_balance_date', dc_date_input($client->effectiveOpeningBalanceDate())) }}"
                               class="dc-input dc-date-input opening-date" inputmode="numeric"
                               placeholder="zz/ll/aaaa" pattern="\d{1,2}/\d{1,2}/\d{4}" autocomplete="off">
                    </td>
                    <td class="text-right tabular-nums text-slate-600">{{ number_format($open, 2, ',', '.') }}</td>
                    <td class="text-right tabular-nums font-medium opening-current">{{ number_format($openingF + $open, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-slate-500">Niciun client încă.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($clients->isNotEmpty())
        <div class="flex justify-end">
            <button type="submit" class="dc-btn-primary">Salvează soldurile</button>
        </div>
    @endif
</form>

<script>
(() => {
    const applyBtn = document.getElementById('apply-opening-date');
    const defaultDate = document.getElementById('default-opening-date');
    applyBtn?.addEventListener('click', () => {
        const v = (defaultDate?.value || '').trim();
        if (!v) return;
        document.querySelectorAll('.opening-date').forEach((el) => { el.value = v; });
    });

    function parseAmount(raw) {
        const n = parseFloat(String(raw || '0').replace(/\s/g, '').replace(',', '.'));
        return Number.isFinite(n) ? n : 0;
    }
    function fmt(n) {
        return n.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    document.querySelectorAll('.opening-balance-row').forEach((row) => {
        const amount = row.querySelector('.opening-amount');
        const current = row.querySelector('.opening-current');
        const open = parseAmount(row.dataset.open);
        const refresh = () => {
            if (current) current.textContent = fmt(parseAmount(amount?.value) + open);
        };
        amount?.addEventListener('input', refresh);
    });
})();
</script>
@endsection
