@extends('layouts.app')
@php
    $canManage = app(\App\Services\CompanyPermission::class)->can(auth()->user(), $company, 'products_manage');
@endphp
@section('heading', 'Produse / Servicii')
@section('actions')
@if($canManage)
<a href="{{ route('products.create') }}" class="dc-btn-primary">{{ __('Adaugă') }}</a>
@endif
@endsection
@section('content')
@include('partials.pagination', ['paginator' => $products, 'class' => 'mb-4'])
<div class="dc-card overflow-hidden">
<table class="w-full dc-table">
<thead><tr><th>Denumire</th><th>Tip</th><th>UM</th><th>{{ __('Preț') }}</th><th>{{ __('TVA') }}</th>@if($canManage)<th></th>@endif</tr></thead>
<tbody>
@forelse($products as $product)
<tr>
    <td class="font-medium">{{ $product->name }}</td>
    <td>{{ $product->type === 'service' ? 'Serviciu' : 'Produs' }}</td>
    <td>{{ \App\Support\MeasureUnits::short($product->unit) }}</td>
    <td>{{ number_format($product->price, 2, ',', '.') }}</td>
    <td>{{ number_format($product->vat_rate, 0) }}%</td>
    @if($canManage)
    <td class="text-right whitespace-nowrap">
        <div class="dc-act-wrap">
            <a href="{{ route('products.edit', $product) }}" class="dc-act">{{ __('Editează') }}</a>
            <form class="inline" method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Ștergi?')">@csrf @method('DELETE')
                <button type="submit" class="dc-act dc-act-danger">{{ __('Șterge') }}</button>
            </form>
        </div>
    </td>
    @endif
</tr>
@empty
<tr><td colspan="{{ $canManage ? 6 : 5 }}" class="text-slate-500">Niciun produs/serviciu.</td></tr>
@endforelse
</tbody>
</table>
</div>
@include('partials.pagination', ['paginator' => $products, 'class' => 'mt-4'])
@endsection
