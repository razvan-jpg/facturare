@php
    /** Cotele TVA standard RO afișate pe linii document / abonament. */
    $vatRates = [21, 11, 5, 0];
    $current = (float) ($selected ?? $default ?? 21);
    $picked = $vatRates[0];
    foreach ($vatRates as $rate) {
        if (abs($rate - $current) < abs($picked - $current)) {
            $picked = $rate;
        }
    }
@endphp
<select name="{{ $name }}" class="dc-input item-vat{{ ! empty($extraClass) ? ' '.$extraClass : '' }}" @if(! empty($required)) required @endif>
    @foreach ($vatRates as $rate)
        <option value="{{ $rate }}" @selected(abs($rate - $picked) < 0.001)>{{ $rate }}%</option>
    @endforeach
</select>
