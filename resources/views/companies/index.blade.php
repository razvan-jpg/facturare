@extends('layouts.app')
@section('heading', 'Societățile mele')
@section('actions')
<a href="{{ route('companies.create') }}" class="dc-btn-primary">Adaugă societate</a>
@endsection
@section('content')
<div class="dc-card overflow-hidden">
<table class="w-full dc-table">
<thead>
    <tr>
        <th>Denumire</th>
        <th>CUI</th>
        <th>{{ __('Cod promoțional') }}</th>
        <th>{{ __('Oraș') }}</th>
        <th>Abon. expiră la:</th>
        <th>{{ __('Status') }}</th>
        <th></th>
    </tr>
</thead>
<tbody>
@foreach($companies as $company)
@php
    $until = $company->access_until_effective;
    $promos = $company->access_promotions ?? [];
    $isActive = (bool) ($company->is_active_company ?? false)
        || (isset($currentCompanyId) && (int) $currentCompanyId === (int) $company->id);
@endphp
<tr class="{{ $isActive ? 'bg-teal-50/70' : '' }}">
    <td class="font-medium">
        <div class="flex flex-wrap items-center gap-2">
            <span>{{ $company->name }}</span>
            @if($isActive)
                <span class="inline-flex items-center rounded-full bg-teal-700 text-white text-[10px] font-bold uppercase tracking-wide px-2 py-0.5">{{ __('Activă') }}</span>
            @endif
        </div>
    </td>
    <td>{{ $company->cui }}</td>
    <td class="font-mono tracking-wider text-sm">
        @if(filled($company->promo_code))
            <button type="button"
                    class="font-mono tracking-wider text-sm text-teal-800 hover:underline"
                    title="{{ __('Click pentru a copia codul') }}"
                    x-data="{ copied: false }"
                    @click="
                        navigator.clipboard.writeText(@js($company->promo_code)).then(() => {
                            copied = true;
                            setTimeout(() => copied = false, 1600);
                        }).catch(() => {
                            window.prompt(@js(__('Copiază codul promoțional:')), @js($company->promo_code));
                        })
                    ">
                <span x-show="!copied">{{ $company->promo_code }}</span>
                <span x-cloak x-show="copied">{{ __('Copiat!') }}</span>
            </button>
        @else
            —
        @endif
    </td>
    <td>{{ $company->city }}</td>
    <td class="text-sm">
        @if($company->owner?->is_admin)
            <span class="text-teal-800 font-medium">Nelimitat</span>
        @elseif($until)
            <div class="tabular-nums font-medium">{{ dc_date($until) }}</div>
            @if(!empty($promos))
                <ul class="mt-1 text-[11px] text-slate-500 space-y-0.5 max-w-[14rem]">
                    @foreach(array_slice($promos, 0, 3) as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
        @else
            <span class="text-rose-600 font-medium">Expirat</span>
        @endif
    </td>
    <td class="text-sm">
        @if($isActive)
            <span class="text-teal-800 font-semibold">{{ __('Activă') }}</span>
        @else
            <span class="text-slate-400">—</span>
        @endif
    </td>
    <td class="text-right whitespace-nowrap">
        <div class="dc-act-wrap">
            <a href="{{ route('billing.order', $company) }}" class="dc-btn-primary text-xs px-2.5 py-1 inline-flex">{{ __('Comandă') }}</a>
            @if($isActive)
                <span class="text-xs text-teal-700 font-medium">Societate curentă</span>
            @else
                <form class="inline" method="POST" action="{{ route('companies.switch', $company) }}">
                    @csrf
                    <input type="hidden" name="return" value="list">
                    <button type="submit" class="dc-act">Activează</button>
                </form>
            @endif
            <a href="{{ route('companies.edit', ['company' => $company, 'tab' => 'generale']) }}" class="dc-act">Configurează</a>
            @if(filled($company->promo_code))
                <button type="button"
                        class="dc-act"
                        @click="window.dispatchEvent(new CustomEvent('open-referral-mail', {
                            detail: {
                                id: {{ (int) $company->id }},
                                name: @js($company->name),
                                code: @js($company->promo_code)
                            }
                        }))">
                    Mail recomandare
                </button>
            @endif
        </div>
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
