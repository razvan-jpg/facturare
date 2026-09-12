@php
    $t = $labels ?? [];
    $balance = isset($clientBalanceAsOf) ? round((float) $clientBalanceAsOf, 2) : null;
    $show = $balance !== null
        && in_array($document->type, ['invoice', 'proforma'], true)
        && $document->status === 'issued';
    // Soldul e mereu în echivalent RON (ClientBalanceService).
    $currency = 'RON';
    $fmt = fn (float $n) => number_format($n, 2, ',', '.');
    $asOfRaw = $clientBalanceAsOfDate
        ?? ($document->issue_date ? $document->issue_date->copy()->subDay()->toDateString() : null);
    $asOfLabel = $asOfRaw ? dc_date($asOfRaw) : '';
@endphp
@if($show)
<p class="outstanding-balance">
    @if($balance <= 0.009)
        <strong>{{ $t['client_balance_zero'] ?? 'Sold restant = 0. Mulțumim.' }}</strong>
    @else
        <strong>{{ $t['client_balance'] ?? 'Sold restant' }}</strong>
        {{ $t['client_balance_as_of'] ?? 'anterior emiterii' }}
        @if($asOfLabel !== '') ({{ $asOfLabel }})@endif:
        <strong>{{ $fmt($balance) }} {{ $currency }}</strong>
    @endif
</p>
@endif
