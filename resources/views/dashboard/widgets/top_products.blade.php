<div class="dc-dash-widget__body">
    @if(count($topProducts) === 0)
        <div class="dc-dash-empty">{{ __('Nicio vânzare pe produse în luna curentă.') }}</div>
    @else
        <div class="dc-dash-rank">
            @foreach($topProducts as $row)
                <div class="dc-dash-rank__row">
                    <div class="dc-dash-rank__meta">
                        <span class="dc-dash-rank__name" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                        <span class="dc-dash-rank__val">{{ $fmtInt($row['total']) }} Lei</span>
                    </div>
                    <div class="dc-dash-rank__bar">
                        <div class="dc-dash-rank__fill" style="width: {{ $row['pct'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
