@extends('layouts.app')
@section('heading', __('Configurare').' · '.$company->name)
@section('subheading', __('Setări societate'))
@section('actions')
<a href="{{ route('companies.index') }}?all=1" class="dc-btn-secondary">{{ __('Societățile mele') }}</a>
@endsection

@section('content')
@php
    $banksPayload = old('banks');
    if (! is_array($banksPayload)) {
        $banksPayload = $company->banks->map(function ($bank) {
            return [
                'name' => $bank->name,
                'accounts' => $bank->accounts->map(fn ($a) => [
                    'iban' => $a->iban,
                    'currency' => $a->currency ?: 'RON',
                    'show_on_invoice' => (bool) $a->show_on_invoice,
                ])->values()->all(),
            ];
        })->values()->all();
        if ($banksPayload === [] && ($company->iban || $company->bank_name)) {
            $banksPayload = [[
                'name' => $company->bank_name ?: '',
                'accounts' => [[
                    'iban' => $company->iban ?: '',
                    'currency' => 'RON',
                    'show_on_invoice' => true,
                ]],
            ]];
        }
        if ($banksPayload === []) {
            $banksPayload = [['name' => '', 'accounts' => [['iban' => '', 'currency' => 'RON', 'show_on_invoice' => true]]]];
        }
    }
@endphp

@php
    $tabPerm = app(\App\Services\CompanyPermission::class);
    $tabUser = auth()->user();
    $settingsWritable = match (true) {
        $tab === 'preferinte-personale' => true,
        $tab === 'efactura' => $tabPerm->can($tabUser, $company, 'efactura_manage')
            || $tabPerm->can($tabUser, $company, 'settings_manage'),
        default => $tabPerm->can($tabUser, $company, 'settings_manage'),
    };
@endphp
<div class="dc-tabs mb-4" role="tablist" aria-label="{{ __('Setări societate') }}">
    @foreach($tabs as $key => $label)
        @continue($key !== 'preferinte-personale' && ! $tabPerm->can($tabUser, $company, 'settings_view') && ! ($key === 'efactura' && $tabPerm->can($tabUser, $company, 'efactura_view')))
        <a href="{{ route('companies.edit', ['company' => $company, 'tab' => $key]) }}"
           class="dc-tab {{ $tab === $key ? 'dc-tab-active' : '' }}"
           title="{{ __($label) }}{{ $key === 'casa-marcat' ? ' — '.__('în curând') : '' }}"
           role="tab"
           aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
            {{ __($label) }}
            @if($key === 'casa-marcat')
                <span class="dc-tab-soon">{{ __('curând') }}</span>
            @endif
        </a>
    @endforeach
</div>

<div class="{{ $tab === 'email' ? 'max-w-6xl' : 'max-w-4xl' }} mx-auto w-full">
    @unless($settingsWritable)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
            Ai doar drept de <strong>vizualizare</strong> pe această secțiune — nu poți salva modificări.
        </div>
    @endunless
    <div @unless($settingsWritable) class="pointer-events-none select-none opacity-70" aria-disabled="true" @endunless>
        @include('companies.tabs.'.$tab)
    </div>
</div>
@endsection
