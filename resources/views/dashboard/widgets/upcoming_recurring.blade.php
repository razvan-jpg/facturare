<div class="dc-dash-widget__body">
    @if(count($upcomingRecurring) === 0)
        <div class="dc-dash-empty">{{ __('Niciun abonament activ programat.') }}</div>
    @else
        <div class="dc-dash-activity">
            @foreach($upcomingRecurring as $item)
                @php
                    $whenLabel = match (true) {
                        $item['days_until'] === 0 => __('Azi'),
                        $item['days_until'] === 1 => __('Mâine'),
                        $item['days_until'] < 0 => __('Scadent'),
                        default => dc_date($item['next_run_date']),
                    };
                @endphp
                <div class="dc-dash-activity__item">
                    <div class="dc-dash-activity__icon dc-dash-activity__icon--auto" aria-hidden="true">↻</div>
                    <div>
                        <a href="{{ $item['url'] }}" class="dc-dash-activity__label hover:text-teal-700">{{ $item['title'] }}</a>
                        <div class="dc-dash-activity__detail">
                            {{ $item['client_name'] }}
                            · {{ $item['document_type_label'] }}
                            · {{ $fmt($item['total']) }} {{ $item['currency'] }}
                        </div>
                        <div class="dc-dash-activity__meta">
                            {{ $item['frequency_label'] }}
                            · {{ $whenLabel }}
                            @if($item['is_due'])
                                <span class="dc-dash-badge">{{ __('de emis') }}</span>
                            @elseif($item['days_until'] > 1)
                                · {{ trans_choice(':count zi|:count zile', $item['days_until'], ['count' => $item['days_until']]) }}
                            @endif
                            @if($item['auto_issue'])
                                · {{ __('emitere automată') }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
