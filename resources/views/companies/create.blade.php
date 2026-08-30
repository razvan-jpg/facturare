@extends('layouts.app')
@section('heading', 'Adaugă societate')
@section('subheading', 'Creează firma pe care vei emite documente.')
@section('content')
<div class="dc-card p-6 max-w-3xl mx-auto w-full"
     x-data="{ hasCode: @js(old('has_referral_code', false) || filled(old('referral_code'))) }">
@include('partials.anaf-lookup')
<form method="POST" action="{{ route('companies.store') }}" class="grid sm:grid-cols-2 gap-4">
    @csrf
    <div class="sm:col-span-2"><label class="dc-label">Denumire</label><input name="name" value="{{ old('name') }}" class="dc-input" required></div>
    <div>
        <label class="dc-label">CUI</label>
        <input name="cui" value="{{ old('cui') }}" class="dc-input" data-vat-payer="1" placeholder="RO12345678">
        <p class="text-xs text-slate-500 mt-1">Plătitor TVA: cu <strong>RO</strong>; neplătitor: doar cifrele.</p>
    </div>
    <div><label class="dc-label">{{ __('Reg. Com.') }}</label><input name="reg_com" value="{{ old('reg_com') }}" class="dc-input"></div>
    <div class="sm:col-span-2"><label class="dc-label">{{ __('Adresă') }}</label><input name="address" value="{{ old('address') }}" class="dc-input"></div>
    <div><label class="dc-label">Localitate</label><input name="city" value="{{ old('city') }}" class="dc-input"></div>
    @include('partials.county-select')
    <div><label class="dc-label">{{ __('Telefon') }}</label><input name="phone" value="{{ old('phone') }}" class="dc-input"></div>
    <div><label class="dc-label">Email</label><input name="email" type="email" value="{{ old('email') }}" class="dc-input"></div>
    <div><label class="dc-label">IBAN</label><input name="iban" value="{{ old('iban') }}" class="dc-input" data-iban-bank="#create-bank-name" placeholder="RO49 AAAA ..." autocomplete="off"></div>
    <div><label class="dc-label">{{ __('Bancă') }}</label><input name="bank_name" id="create-bank-name" value="{{ old('bank_name') }}" class="dc-input" placeholder="se completează din IBAN"></div>
    <div><label class="dc-label">Cotă TVA implicită %</label><input name="default_vat_rate" type="number" step="0.01" value="{{ old('default_vat_rate', 21) }}" class="dc-input"></div>
    <div class="flex items-center gap-2 pt-6">
        <input type="checkbox" name="vat_payer" id="vat_payer" value="1" checked class="rounded border-slate-300"
               onchange="(function(cb){const el=document.querySelector('[name=cui]');if(!el)return;const d=(el.value||'').replace(/\D+/g,'');el.value=d?(cb.checked?'RO'+d:d):el.value;el.placeholder=cb.checked?'RO12345678':'12345678';el.dataset.vatPayer=cb.checked?'1':'0';})(this)">
        <span class="text-sm">Plătitor de TVA</span>
    </div>

    <div class="sm:col-span-2 rounded-xl border border-teal-200/80 bg-teal-50/50 p-4 space-y-3">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox"
                   name="has_referral_code"
                   value="1"
                   class="mt-1 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                   x-model="hasCode">
            <span>
                <span class="block text-sm font-semibold text-slate-800">Ai un cod promoțional?</span>
                <span class="block text-xs text-slate-600 mt-0.5">
                    Dacă cineva ți-a recomandat DateConta Facturare, introdu codul societății lui.
                    Tu primești <strong>2 săptămâni</strong> în plus; la fiecare 2 societăți aduse, el primește <strong>1 lună</strong> în plus.
                </span>
            </span>
        </label>
        <div x-show="hasCode" x-cloak class="space-y-1">
            <label class="dc-label" for="referral_code">{{ __('Cod promoțional') }}</label>
            <input id="referral_code"
                   name="referral_code"
                   value="{{ old('referral_code') }}"
                   class="dc-input font-mono tracking-wider uppercase @error('referral_code') border-rose-400 @enderror"
                   placeholder="XXXX-XXXX-XXXX"
                   maxlength="14"
                   autocomplete="off"
                   :required="hasCode">
            @error('referral_code')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="sm:col-span-2"><button class="dc-btn-primary">Salvează societatea</button></div>
</form>
</div>
@endsection
