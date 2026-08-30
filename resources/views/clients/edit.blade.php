@extends('layouts.app')
@section('heading', 'Editează client')
@section('actions')
<a href="{{ route('clients.show', $client) }}" class="dc-btn-secondary">Fișă client</a>
@endsection
@section('content')
<div class="dc-card p-6 max-w-3xl mx-auto w-full">
@include('partials.anaf-lookup', ['client' => $client])
@isset($currentBalance)
<div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 flex flex-wrap gap-x-6 gap-y-1">
    <span>Sold inițial: <strong class="tabular-nums">{{ number_format((float) ($client->opening_balance ?? 0), 2, ',', '.') }}</strong></span>
    <span>Facturi deschise: <strong class="tabular-nums">{{ number_format((float) ($openRemaining ?? 0), 2, ',', '.') }}</strong></span>
    <span>Sold curent: <strong class="tabular-nums text-teal-900">{{ number_format((float) $currentBalance, 2, ',', '.') }}</strong></span>
</div>
@endisset
<form method="POST" action="{{ route('clients.update', $client) }}" class="grid sm:grid-cols-2 gap-4">
    @csrf @method('PUT')
    @include('clients._form', ['client' => $client])
    <div class="sm:col-span-2 flex flex-wrap items-center gap-2">
        <button type="submit" class="dc-btn-primary">{{ __('Actualizează') }}</button>
        <a href="{{ route('clients.index') }}" class="dc-btn-secondary">Renunță</a>
    </div>
</form>
</div>
@endsection
