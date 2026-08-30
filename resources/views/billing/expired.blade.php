@extends('layouts.app')

@section('heading', 'Acces suspendat')

@section('content')
@php
    $company = auth()->user()?->companies()->orderBy('companies.id')->first();
@endphp
<div class="dc-card p-8 max-w-2xl mx-auto w-full space-y-4">
    <p class="text-slate-700">Perioada gratuită / de probă s-a încheiat. Poți comanda un abonament pentru a continua.</p>
    @if($company)
        <a href="{{ route('billing.order', $company) }}" class="dc-btn-primary inline-flex">Comandă abonament</a>
    @endif
    <p class="text-sm text-slate-600">
        Pentru întrebări:
        <a class="text-teal-800 underline" href="mailto:{{ config('dateconta.contact_email') }}">{{ config('dateconta.contact_email') }}</a>
    </p>
</div>
@endsection
