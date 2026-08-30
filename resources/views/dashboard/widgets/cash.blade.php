<div class="dc-dash-widget__body dc-dash-cash">
    <div class="dc-dash-big">{{ $fmtInt($cashMonth) }} <span>{{ $cashCurrency ?? 'RON' }}</span></div>
    <a href="{{ route('payments.index') }}" class="dc-dash-cash__link">{{ __('Detalii încasări') }} →</a>
    <div class="dc-dash-mini-grid mt-4 text-left">
        <div class="dc-dash-mini">
            <div class="dc-dash-mini__label">{{ __('Total încasări') }}</div>
            <div class="dc-dash-mini__val">{{ $fmtInt($sales['today_paid']) }} <span>Lei</span></div>
            <div class="text-xs text-slate-500 mt-1">{{ __('Azi') }}</div>
        </div>
        <div class="dc-dash-mini">
            <div class="dc-dash-mini__label">&nbsp;</div>
            <div class="dc-dash-mini__val">{{ $fmtInt($sales['month_paid']) }} <span>Lei</span></div>
            <div class="text-xs text-slate-500 mt-1">{{ __('Luna curentă') }}</div>
        </div>
        <div class="dc-dash-mini">
            <div class="dc-dash-mini__label">{{ __('Total scadent') }}</div>
            <div class="dc-dash-mini__val">{{ $fmtInt($dueTotals['today']) }} <span>Lei</span></div>
            <div class="text-xs text-slate-500 mt-1">{{ __('Azi') }}</div>
        </div>
        <div class="dc-dash-mini">
            <div class="dc-dash-mini__label">&nbsp;</div>
            <div class="dc-dash-mini__val">{{ $fmtInt($dueTotals['next_7_days']) }} <span>Lei</span></div>
            <div class="text-xs text-slate-500 mt-1">{{ __('În 7 zile') }}</div>
        </div>
    </div>
</div>
