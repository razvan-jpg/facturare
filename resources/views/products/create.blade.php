@extends('layouts.app')
@section('heading', 'Produs / serviciu nou')
@section('content')
<div class="dc-card p-6 max-w-2xl mx-auto w-full">
<form method="POST" action="{{ route('products.store') }}" class="grid sm:grid-cols-2 gap-4">
@csrf
@include('products._form')
<div class="sm:col-span-2"><button class="dc-btn-primary">{{ __('Salvează') }}</button></div>
</form>
</div>
@endsection
