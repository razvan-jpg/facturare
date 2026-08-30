@extends('layouts.app')
@section('heading', $title)
@section('subheading', 'Funcționalitate în pregătire')
@section('content')
<div class="dc-card p-8 max-w-xl">
    <p class="text-slate-700">
        <strong>{{ $title }}</strong> va fi disponibilă în curând în DateConta Facturare.
    </p>
    <p class="text-sm text-slate-500 mt-3">
        Poți continua cu facturi, proforme, avize, chitanțe, încasări și e-Factura din meniul Emitere.
    </p>
    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('documents.create', ['type' => 'invoice']) }}" class="dc-btn-primary">Emite factură</a>
        <a href="{{ route('dashboard') }}" class="dc-btn-secondary">{{ __('Dashboard') }}</a>
    </div>
</div>
@endsection
