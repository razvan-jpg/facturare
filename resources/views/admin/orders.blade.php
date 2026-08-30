@extends('layouts.app')
@section('heading', 'Comenzi abonament')
@section('subheading', 'Confirmă OP · la plată OK se emit factura fiscală FLY DAVID + email')
@section('actions')
    @if(($missingInvoiceCount ?? 0) > 0)
        <form method="POST" action="{{ route('admin.orders.issue-missing-invoices') }}" class="inline"
              onsubmit="return confirm('Emți facturile fiscale pentru comenzile plătite fără factură?')">
            @csrf
            <button class="dc-btn-primary">Emite facturi lipsă ({{ $missingInvoiceCount }})</button>
        </form>
    @endif
    <a href="{{ route('admin.companies') }}" class="dc-btn-secondary">Societăți & promoții</a>
@endsection

@section('content')
<div class="flex flex-wrap gap-2 mb-4">
    @foreach([
        'awaiting_op' => 'Așteaptă OP'.($awaitingCount ? ' ('.$awaitingCount.')' : ''),
        'paid' => 'Plătite',
        'pending' => 'Card pending',
        'failed' => 'Eșuate',
        'all' => 'Toate',
    ] as $key => $label)
        <a href="{{ route('admin.orders', ['status' => $key]) }}"
           class="{{ $status === $key ? 'dc-btn-primary' : 'dc-btn-secondary' }} text-xs px-3 py-1.5">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="dc-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full dc-table">
            <thead>
                <tr>
                    <th>{{ __('Comandă') }}</th>
                    <th>{{ __('Societate') }}</th>
                    <th>{{ __('Client') }}</th>
                    <th>Perioadă</th>
                    <th>{{ __('Sumă') }}</th>
                    <th>{{ __('Metodă') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Factură') }}</th>
                    <th>Data</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                <tr>
                    <td class="font-mono text-sm">{{ $order->number }}</td>
                    <td>
                        <div class="font-medium">{{ $order->company?->name ?: '—' }}</div>
                        <div class="text-xs text-slate-500">{{ $order->billing_cui ?: $order->company?->cui }}</div>
                    </td>
                    <td>
                        <div class="text-sm">{{ $order->user?->name }}</div>
                        <div class="text-xs text-slate-500">{{ $order->user?->email }}</div>
                    </td>
                    <td class="text-sm">{{ $order->periodLabel() }}</td>
                    <td class="tabular-nums text-sm">
                        {{ number_format($order->amount_total, 2, ',', '.') }} {{ $order->currency }}
                    </td>
                    <td class="text-sm uppercase">
                        {{ $order->payment_method }}
                        @if($order->payment_processor)
                            <div class="text-[11px] text-slate-500 normal-case">{{ $order->payment_processor }}</div>
                        @endif
                    </td>
                    <td class="text-sm">
                        @if($order->status === 'awaiting_op')
                            <span class="text-amber-800 font-semibold">așteaptă OP</span>
                        @elseif($order->isPaid())
                            <span class="text-teal-800 font-semibold">plătit</span>
                            @if($order->access_until_after)
                                <div class="text-[11px] text-slate-500">până la {{ dc_date($order->access_until_after) }}</div>
                            @endif
                        @else
                            {{ $order->status }}
                        @endif
                    </td>
                    <td class="text-sm">
                        @if($order->invoiceDocument)
                            <span class="font-mono text-teal-800">{{ $order->invoiceDocument->number_full }}</span>
                        @elseif($order->isPaid())
                            <span class="text-amber-800 text-xs font-semibold">lipsă</span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="text-xs tabular-nums text-slate-500">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="text-right">
                        @if($order->status === 'awaiting_op' && $order->payment_method === 'op')
                            <form method="POST" action="{{ route('admin.orders.confirm-op', $order) }}" class="inline"
                                  onsubmit="return confirm(@js('Confirmi încasarea OP pentru '.$order->number.'? Abonamentul se activează automat.'))">
                                @csrf
                                <button class="dc-btn-primary text-xs px-2.5 py-1">Confirmă OP</button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-slate-500 py-8">Nicio comandă pe acest filtru.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
