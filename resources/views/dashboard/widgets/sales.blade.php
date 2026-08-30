<div class="dc-dash-widget__body">
    <div class="dc-dash-big">{{ $fmtInt($dailySales['total']) }} <span>Lei</span></div>
    <div class="dc-dash-statline">
        <span>{{ __('Emise') }}: <strong>{{ $fmtInt($dailySales['count']) }}</strong></span>
        <span>{{ __('Valoare medie/zi') }}: <strong>{{ $fmtInt($dailySales['avg_per_day']) }} Lei</strong></span>
    </div>
    <div class="dc-dash-chart-wrap"><canvas id="dc-chart-sales"></canvas></div>
</div>
