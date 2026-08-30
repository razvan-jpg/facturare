@extends('layouts.app')
@php
    $canManage = app(\App\Services\CompanyPermission::class)->can(auth()->user(), $company, 'payments_manage');
@endphp
@section('heading', 'Încasări')
@section('content')
@include('partials.pagination', ['paginator' => $payments, 'class' => 'mb-4'])
<div class="dc-card overflow-hidden">
<table class="w-full dc-table">
<thead><tr><th>Data</th><th>{{ __('Document') }}</th><th>{{ __('Client') }}</th><th>{{ __('Metodă') }}</th><th>{{ __('Sumă') }}</th>@if($canManage)<th></th>@endif</tr></thead>
<tbody>
@forelse($payments as $payment)
<tr>
    <td>{{ dc_date($payment->paid_at) }}</td>
    <td>@if($payment->document)<a href="{{ route('documents.show', $payment->document) }}" class="text-teal-800 hover:underline">{{ $payment->document->number_full }}</a>@endif</td>
    <td>{{ $payment->client?->name }}</td>
    <td>{{ $payment->method }}</td>
    <td>{{ number_format($payment->amount, 2, ',', '.') }} {{ $payment->currency }}</td>
    @if($canManage)
    <td class="text-right">
        <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Ștergi încasarea?')">@csrf @method('DELETE')
            <button type="submit" class="dc-act dc-act-danger">{{ __('Șterge') }}</button>
        </form>
    </td>
    @endif
</tr>
@empty
<tr><td colspan="{{ $canManage ? 6 : 5 }}" class="text-slate-500">Nicio încasare.</td></tr>
@endforelse
</tbody>
</table>
</div>
@include('partials.pagination', ['paginator' => $payments, 'class' => 'mt-4'])
@endsection
