@php
    $t = $labels ?? [];
    $currency = strtoupper((string) ($document->currency ?: 'RON'));
    $fx = (float) ($document->exchange_rate ?: 0);
    $showRon = $currency !== 'RON' && $fx > 0.0001;
@endphp
@if($showRon)
<div class="ron-eq" style="{{ $style ?? 'margin-top:4px;font-size:10px;color:#486581;line-height:1.35;' }}">
    <div>{{ $t['exchange_rate'] ?? 'Curs' }}: 1 {{ $currency }} = {{ number_format($fx, 4, ',', '.') }} RON</div>
    <div>
        {{ $t['ron_equivalent'] ?? 'Echivalent' }}:
        <strong style="color:inherit;">{{ number_format(round((float) $document->total * $fx, 2), 2, ',', '.') }} RON</strong>
    </div>
</div>
@endif
