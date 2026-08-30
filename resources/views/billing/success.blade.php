@extends('layouts.app')
@section('heading', 'Comandă abonament')
@section('subheading', $order->number)
@section('actions')
    <a href="{{ route('companies.index', ['all' => 1]) }}" class="dc-btn-secondary">{{ __('Societățile mele') }}</a>
@endsection

@section('content')
<div class="dc-card p-6 max-w-xl mx-auto space-y-3">
    <div class="font-semibold text-slate-900">{{ $order->company?->name }}</div>
    <div class="text-sm text-slate-600">{{ $order->periodLabel() }} · {{ number_format($order->amount_total, 2, ',', '.') }} {{ $order->currency }}</div>
    <div class="text-sm">
        Status:
        @if($order->isPaid())
            <span class="text-teal-800 font-semibold">{{ __('Plătit') }}</span>
            @if($order->access_until_after)
                · acces până la {{ dc_date($order->access_until_after) }}
            @endif
        @else
            <span class="text-amber-800 font-semibold">{{ $order->status }}</span>
        @endif
    </div>
    @if(! $order->isPaid() && $order->status !== 'failed')
        <p class="text-sm text-slate-500" id="dc-pay-wait">Verificăm confirmarea plății…</p>
    @endif
    <a href="{{ route('dashboard') }}" class="dc-btn-primary inline-flex">Înapoi în aplicație</a>
</div>
@if(! $order->isPaid() && $order->status !== 'failed')
<script>
(function () {
    var n = 0, max = 12;
    var t = setInterval(function () {
        n++;
        if (n >= max) {
            clearInterval(t);
            var el = document.getElementById('dc-pay-wait');
            if (el) el.textContent = 'Dacă ai plătit și statusul e încă în așteptare, reîncarcă pagina peste un minut.';
            return;
        }
        window.location.reload();
    }, 2500);
})();
</script>
@endif
@endsection
