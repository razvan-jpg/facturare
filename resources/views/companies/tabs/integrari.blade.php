@php
    $integrations = app(\App\Services\CompanyIntegrations::class);
    $processor = request('processor', 'netopia');
    if (! in_array($processor, ['netopia', 'euplatesc', 'mollie', 'stripe'], true)) {
        $processor = 'netopia';
    }
    $labels = [
        'netopia' => 'NETOPIA',
        'euplatesc' => 'Eu Plătesc',
        'mollie' => 'Mollie',
        'stripe' => 'Stripe',
    ];
    $status = [
        'netopia' => $integrations->isNetopiaReady($company),
        'euplatesc' => $integrations->isEuPlatescReady($company),
        'mollie' => $integrations->isMollieReady($company),
        'stripe' => $integrations->isStripeReady($company),
    ];
@endphp

<div class="space-y-4">
    <div class="dc-card p-4 sm:p-5 space-y-2">
        <h2 class="text-lg font-semibold">Integrări — încasare facturi cu cardul</h2>
        <p class="text-sm text-slate-600">
            Aici pui <strong>cheile tale</strong> (NETOPIA, Eu Plătesc, Mollie, Stripe) ca clienții să-ți plătească facturile online.
            Credențialele sunt private pentru această societate — nu se amestecă cu alte firme din cont.
        </p>
        <p class="text-xs text-slate-500">
            Integrările de abonament DateConta (FLY DAVID) sunt separate și nu apar aici; nu le folosești pentru facturile tale.
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($labels as $key => $label)
            <a href="{{ route('companies.edit', ['company' => $company, 'tab' => 'integrari', 'processor' => $key]) }}"
               class="{{ $processor === $key ? 'dc-btn-primary' : 'dc-btn-secondary' }} text-xs px-3 py-1.5">
                {{ $label }}
                @if($status[$key] ?? false)
                    <span class="ml-1 text-[10px] uppercase tracking-wide opacity-90">activ</span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="dc-card p-4 sm:p-5 max-w-2xl space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-900">{{ $labels[$processor] ?? $processor }}</h3>
            @if($status[$processor] ?? false)
                <span class="text-xs font-semibold text-teal-800 bg-teal-50 border border-teal-200 px-2 py-1 rounded">Configurat · activ pe facturi</span>
            @else
                <span class="text-xs font-semibold text-amber-900 bg-amber-50 border border-amber-200 px-2 py-1 rounded">Incomplet / dezactivat</span>
            @endif
        </div>

        <form method="POST" action="{{ route('companies.integrations.update', [$company, $processor]) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            @if($processor === 'netopia')
                @include('companies.partials.integrari-netopia', ['integrations' => $integrations, 'company' => $company])
            @elseif($processor === 'euplatesc')
                @include('companies.partials.integrari-euplatesc', ['integrations' => $integrations, 'company' => $company])
            @elseif($processor === 'mollie')
                @include('companies.partials.integrari-mollie', ['integrations' => $integrations, 'company' => $company])
            @else
                @include('companies.partials.integrari-stripe', ['integrations' => $integrations, 'company' => $company])
            @endif

            <button type="submit" class="dc-btn-primary px-6">{{ __('Salvează') }}</button>
        </form>
    </div>
</div>
