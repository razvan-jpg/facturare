@extends('layouts.app')
@section('heading', 'Integrări abonament DateConta')
@section('subheading', 'Chei FLY DAVID SRL — doar pentru încasarea abonamentelor platformei. Facturile clienților: fiecare firmă își pune propriile chei în Setări → Integrări.')
@section('actions')
    <a href="{{ route('admin.orders') }}" class="dc-btn-secondary">Comenzi abonament</a>
@endsection

@section('content')
@php
    $titles = [
        'netopia' => 'NETOPIA Payments',
        'euplatesc' => 'Eu Plătesc',
        'mollie' => 'Mollie',
        'stripe' => 'Stripe',
    ];
@endphp

<div class="dc-card p-3 sm:p-4 mb-4 max-w-2xl border-amber-200 bg-amber-50/60 text-sm text-amber-950">
    <p class="font-semibold">Private platformă (FLY DAVID)</p>
    <p class="mt-1 text-amber-900/90">
        Aceste credențiale nu sunt partajate cu utilizatorii. Nu apar în Setări → Integrări ale firmelor.
        Pentru încasarea facturilor emise de clienți, fiecare societate configurează separat NETOPIA / Eu Plătesc / Mollie / Stripe.
    </p>
</div>

<div class="flex flex-wrap gap-2 mb-4">
    @foreach($processors as $key)
        <a href="{{ route('admin.integrari.show', $key) }}"
           class="{{ $processor === $key ? 'dc-btn-primary' : 'dc-btn-secondary' }} text-xs px-3 py-1.5">
            {{ $labels[$key] }}
            @if($status[$key] ?? false)
                <span class="ml-1 text-[10px] uppercase tracking-wide opacity-90">activ</span>
            @endif
        </a>
    @endforeach
</div>

<div class="dc-card p-4 sm:p-5 max-w-2xl space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-display text-lg text-slate-900">{{ $titles[$processor] ?? $processor }}</h2>
        @if($status[$processor] ?? false)
            <span class="text-xs font-semibold text-teal-800 bg-teal-50 border border-teal-200 px-2 py-1 rounded">
                Configurat · activ la checkout
                @if($processor === 'netopia' && ($netopiaStatus['source'] ?? null) === 'operator_company')
                    (via FLY DAVID)
                @endif
                @if($processor === 'netopia')
                    · {{ ($netopiaStatus['sandbox'] ?? false) ? 'sandbox' : 'live' }}
                @endif
            </span>
        @else
            <span class="text-xs font-semibold text-amber-900 bg-amber-50 border border-amber-200 px-2 py-1 rounded">Incomplet / dezactivat</span>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.integrari.update', $processor) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        @if($processor === 'netopia')
            @include('admin.integrari._netopia')
        @elseif($processor === 'euplatesc')
            @include('admin.integrari._euplatesc')
        @elseif($processor === 'mollie')
            @include('admin.integrari._mollie')
        @else
            @include('admin.integrari._stripe')
        @endif

        <button type="submit" class="dc-btn-primary px-6">{{ __('Salvează') }}</button>
    </form>
</div>
@endsection
