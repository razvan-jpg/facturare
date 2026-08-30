@php
    $rec = $receivables;
    $recTotal = max(0.01, (float) ($rec['total'] ?? 0));
    $overduePct = round(((float) ($rec['overdue'] ?? 0) / $recTotal) * 100, 1);
    $onTimePct = round(100 - $overduePct, 1);
@endphp
<div class="dc-dash-widget__body">
    <div class="dc-dash-big">{{ $fmtInt($rec['total'] ?? 0) }} <span>Lei</span></div>
    <div class="dc-dash-bar">
        <div class="dc-dash-bar__labels">
            <span>{{ __('Depășit') }}</span>
            <span>{{ __('În termen') }}</span>
        </div>
        <div class="dc-dash-bar__track">
            <div class="dc-dash-bar__seg dc-dash-bar__seg--over" style="width: {{ $overduePct }}%"></div>
            <div class="dc-dash-bar__seg dc-dash-bar__seg--ok" style="width: {{ $onTimePct }}%"></div>
        </div>
        <div class="dc-dash-bar__vals">
            <span>{{ $fmtInt($rec['overdue'] ?? 0) }}</span>
            <span>{{ $fmtInt($rec['on_time'] ?? 0) }}</span>
        </div>
    </div>
    <div class="dc-dash-kv mt-3 pt-3 border-t border-slate-100">
        @foreach([
            [__('Depășit'), $rec['buckets']['overdue_total'] ?? 0],
            [__('Azi'), $rec['buckets']['due_today'] ?? 0],
            [__('1 – 7 zile'), $rec['buckets']['overdue_1_7'] ?? 0],
            [__('8 – 14 zile'), $rec['buckets']['overdue_8_14'] ?? 0],
            [__('15 – 30 zile'), $rec['buckets']['overdue_15_30'] ?? 0],
            [__('Peste 30 zile'), $rec['buckets']['overdue_over_30'] ?? 0],
        ] as [$label, $val])
            <div><span>{{ $label }}</span><strong>{{ $fmtInt($val) }}</strong></div>
        @endforeach
    </div>
</div>
