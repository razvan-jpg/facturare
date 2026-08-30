@php
    $payload = $unbilledPenalties ?? ['rows' => [], 'total' => 0];
    if (array_is_list($payload)) {
        // compat: listă veche fără total
        $rows = $payload;
        $total = collect($rows)->sum('amount');
    } else {
        $rows = $payload['rows'] ?? [];
        $total = (float) ($payload['total'] ?? 0);
    }
@endphp
<div class="dc-dash-widget__body">
    @if(count($rows) === 0)
        <div class="dc-dash-empty">{{ __('Nicio penalitate nefacturată.') }}</div>
    @else
        <div class="dc-dash-big mb-3">{{ $fmt($total) }} <span>RON</span></div>
        <div class="text-xs text-slate-500 mb-2">{{ __('Până azi · doar clienți cu sold de penalități') }}</div>
        <div class="dc-dash-rank">
            @foreach($rows as $row)
                <div class="dc-dash-rank__row">
                    <div class="dc-dash-rank__meta">
                        <a href="{{ route('clients.show', $row['client_id']) }}"
                           class="dc-dash-rank__name hover:text-teal-700"
                           title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                        <span class="dc-dash-rank__val">{{ $fmt($row['amount']) }} RON</span>
                    </div>
                    <div class="dc-dash-rank__bar">
                        <div class="dc-dash-rank__fill" style="width: {{ $row['pct'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
