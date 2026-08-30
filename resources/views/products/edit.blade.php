@extends('layouts.app')
@section('heading', 'Editează produs')
@section('content')
<div class="dc-card p-6 max-w-2xl mx-auto w-full">
<form method="POST" action="{{ route('products.update', $product) }}" class="grid sm:grid-cols-2 gap-4">
@csrf @method('PUT')
@include('products._form', ['product' => $product])
<div class="sm:col-span-2"><button class="dc-btn-primary">{{ __('Actualizează') }}</button></div>
</form>
</div>
@endsection
