<div class="dc-dash-widget__body">
    <div class="dc-dash-big">{{ $fmtInt($dailyPayments['total']) }} <span>Lei</span></div>
    <div class="dc-dash-statline">
        <span>{{ __('Înregistrate') }}: <strong>{{ $fmtInt($dailyPayments['count']) }}</strong></span>
        <span>{{ __('Valoare medie/zi') }}: <strong>{{ $fmtInt($dailyPayments['avg_per_day']) }} Lei</strong></span>
    </div>
    <div class="dc-dash-chart-wrap"><canvas id="dc-chart-payments"></canvas></div>
</div>
