@extends('layouts.app')
@section('heading', 'Plată prin bancă (OP)')
@section('subheading', 'Comanda '.$order->number)
@section('actions')
    <a href="{{ route('companies.index', ['all' => 1]) }}" class="dc-btn-secondary">{{ __('Societățile mele') }}</a>
@endsection

@section('content')
<div class="dc-card p-6 max-w-2xl mx-auto space-y-4">
    <p class="text-slate-700">
        Comanda pentru <strong>{{ $order->company?->name }}</strong> —
        {{ $order->periodLabel() }} —
        <strong>{{ number_format($order->amount_total, 2, ',', '.') }} {{ $order->currency }}</strong>
        (din care TVA {{ number_format($order->amount_vat, 2, ',', '.') }}).
    </p>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm space-y-1">
        <div class="font-semibold text-slate-800 mb-2">Date plată OP</div>
        <div><span class="text-slate-500">Beneficiar:</span> {{ $operator['name'] ?? '—' }}</div>
        <div><span class="text-slate-500">CUI:</span> {{ $operator['cui'] ?? '—' }}</div>
        <div><span class="text-slate-500">IBAN:</span> <strong>{{ $operator['iban'] ?: '— completează PLATFORM_IBAN în .env' }}</strong></div>
        <div><span class="text-slate-500">Bancă:</span> {{ $operator['bank_name'] ?: '—' }}</div>
        <div><span class="text-slate-500">Suma:</span> {{ number_format($order->amount_total, 2, ',', '.') }} {{ $order->currency }}</div>
        <div><span class="text-slate-500">Mențiune:</span> <strong>{{ $order->number }}</strong> / {{ $order->billing_cui }}</div>
    </div>

    <p class="text-sm text-slate-600">
        După virament, trimite confirmarea (extras / dovadă) la
        <a class="text-teal-800 underline" href="mailto:{{ config('dateconta.contact_email') }}">{{ config('dateconta.contact_email') }}</a>
        cu numărul comenzii <strong>{{ $order->number }}</strong>.
        După ce încasarea e verificată, confirmăm plata în admin și <strong>abonamentul se activează automat</strong>.
    </p>
</div>
@endsection
