@extends('layouts.app')
@section('heading', 'Redirecționare Netopia')
@section('subheading', 'Te ducem la plata cu cardul…')

@section('content')
<div class="dc-card p-8 max-w-lg mx-auto text-center space-y-4">
    <p class="text-slate-700">Comanda <strong>{{ $order->number }}</strong> — te redirecționăm către NETOPIA Payments.</p>
    <p class="text-lg font-semibold text-slate-900">
        {{ number_format($order->amount_total, 2, ',', '.') }} {{ $order->currency }}
    </p>
    @php $netopiaMarkupPct = (int) round((((float) config('dateconta.subscription.netopia_ron_markup', 1.02)) - 1) * 100); @endphp
    <p class="text-sm text-slate-500">Plata și factura fiscală sunt în RON (curs BNR + {{ $netopiaMarkupPct }}%). Dacă nu pornește automat, apasă butonul de mai jos.</p>
    <form id="netopia-form" method="POST" action="{{ $form['url'] }}" accept-charset="UTF-8">
        <input type="hidden" name="env_key" value="{{ $form['env_key'] }}">
        <input type="hidden" name="data" value="{{ $form['data'] }}">
        <input type="hidden" name="cipher" value="{{ $form['cipher'] }}">
        <input type="hidden" name="iv" value="{{ $form['iv'] }}">
        <button type="submit" class="dc-btn-primary">{{ __('Continuă către plată') }}</button>
    </form>
</div>
<script>
(function () {
    var form = document.getElementById('netopia-form');
    if (!form) return;
    // Auto-redirect către Netopia (CSP form-action trebuie să permită gateway-ul).
    setTimeout(function () { form.submit(); }, 150);
})();
</script>
@endsection
