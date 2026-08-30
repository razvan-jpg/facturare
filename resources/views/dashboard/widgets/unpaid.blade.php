<div class="dc-dash-widget__body">
    <div class="dc-dash-big mb-3">{{ $fmtInt($unpaidTotal) }} <span>Lei</span></div>
    <div class="dc-dash-unpaid">
        <table>
            <thead>
            <tr>
                <th>{{ __('Document') }}</th>
                <th class="text-right">{{ __('De încasat') }}</th>
                <th>{{ __('Scadență') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($unpaidInvoices as $inv)
                <tr>
                    <td>
                        <div class="font-medium text-slate-700">{{ $inv['client_name'] }}</div>
                        <div class="text-slate-500 text-xs mt-0.5">
                            {{ $inv['issue_date'] ? dc_date($inv['issue_date']) : '—' }}
                            · <a href="{{ route('documents.show', $inv['id']) }}">{{ $inv['number_full'] }}</a>
                        </div>
                    </td>
                    <td class="text-right tabular-nums font-semibold">{{ $fmt($inv['remaining']) }} {{ $inv['currency'] }}</td>
                    <td class="tabular-nums">{{ $inv['due_date'] ? dc_date($inv['due_date']) : '—' }}</td>
                    <td>
                        @if($inv['days_overdue'] !== null && $inv['days_overdue'] > 0)
                            <span class="dc-dash-badge">{{ __('de :n zile', ['n' => $inv['days_overdue']]) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="dc-dash-empty">{{ __('Totul e la zi.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
