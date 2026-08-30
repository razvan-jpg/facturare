@php
    $t = $labels ?? [];
    $currency = strtoupper((string) ($document->currency ?: 'RON'));
    $fx = (float) ($document->exchange_rate ?: 0);
    $showRon = $currency !== 'RON' && $fx > 0.0001;
    $fmt = fn (float $n) => number_format($n, 2, ',', '.');
@endphp
<div class="totals">
    <div>{{ $t['subtotal'] ?? 'Subtotal' }}: <strong>{{ $fmt((float) $document->subtotal) }} {{ $currency }}</strong></div>
    <div>{{ $t['vat'] ?? 'TVA' }}: <strong>{{ $fmt((float) $document->vat_total) }} {{ $currency }}</strong></div>
    <div class="grand">{{ $t['total'] ?? 'Total' }}: <strong>{{ $fmt((float) $document->total) }} {{ $currency }}</strong></div>
    @if($showRon)
        <div style="margin-top:6px;font-size:10px;color:#486581;">
            {{ $t['exchange_rate'] ?? 'Curs' }}: 1 {{ $currency }} = {{ number_format($fx, 4, ',', '.') }} RON
        </div>
        <div style="font-size:11px;margin-top:2px;">
            {{ $t['ron_equivalent'] ?? 'Echivalent' }}:
            <strong>{{ $fmt(round((float) $document->total * $fx, 2)) }} RON</strong>
        </div>
        <div style="font-size:9px;color:#627d98;margin-top:1px;">
            {{ $t['subtotal'] ?? 'Subtotal' }} {{ $fmt(round((float) $document->subtotal * $fx, 2)) }} RON
            · {{ $t['vat'] ?? 'TVA' }} {{ $fmt(round((float) $document->vat_total * $fx, 2)) }} RON
        </div>
    @endif
</div>
