@extends('layouts.app')
@section('heading', 'Client nou')
@section('content')
<div class="dc-card p-6 max-w-3xl mx-auto w-full">
@include('partials.anaf-lookup')
<form method="POST" action="{{ route('clients.store') }}" class="grid sm:grid-cols-2 gap-4">
    @csrf
    @include('clients._form')
    <div class="sm:col-span-2"><button class="dc-btn-primary">{{ __('Salvează') }}</button></div>
</form>
</div>
@endsection
