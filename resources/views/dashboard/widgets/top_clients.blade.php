<div class="dc-dash-widget__body">
    @if(count($topClients) === 0)
        <div class="dc-dash-empty">{{ __('Nicio vânzare în luna curentă.') }}</div>
    @else
        <div class="dc-dash-rank">
            @foreach($topClients as $i => $row)
                <div class="dc-dash-rank__row">
                    <div class="dc-dash-rank__meta">
                        <span class="dc-dash-rank__name" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                        <span class="dc-dash-rank__val">{{ $fmtInt($row['total']) }} RON</span>
                    </div>
                    <div class="dc-dash-rank__bar">
                        <div class="dc-dash-rank__fill {{ $i === 0 ? 'dc-dash-rank__fill--green' : '' }}" style="width: {{ $row['pct'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
