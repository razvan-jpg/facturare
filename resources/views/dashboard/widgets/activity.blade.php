<div class="dc-dash-widget__body">
    @if(count($activity) === 0)
        <div class="dc-dash-empty">{{ __('Nicio activitate recentă.') }}</div>
    @else
        <div class="dc-dash-activity">
            @foreach($activity as $item)
                <div class="dc-dash-activity__item">
                    <div class="dc-dash-activity__icon" aria-hidden="true">✓</div>
                    <div>
                        @if($item['url'])
                            <a href="{{ $item['url'] }}" class="dc-dash-activity__label hover:text-teal-700">{{ $item['label'] }}</a>
                        @else
                            <div class="dc-dash-activity__label">{{ $item['label'] }}</div>
                        @endif
                        <div class="dc-dash-activity__detail">{{ $item['detail'] }}</div>
                        <div class="dc-dash-activity__meta">{{ $item['user'] }} · {{ \Carbon\Carbon::parse($item['at'])->locale('ro')->translatedFormat('j M, \o\r\a H:i') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
