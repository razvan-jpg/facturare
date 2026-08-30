@extends('layouts.app')
@section('heading', 'Abonament utilizatori')
@section('subheading', 'Locuri subuser · '.$company->name)
@section('actions')
    <a href="{{ route('company-users.index') }}" class="dc-btn-secondary">{{ __('Utilizatori') }}</a>
@endsection

@section('content')
@php
    $defaultPeriod = array_key_first($periods) ?: '1m';
    $defaultSeats = max(1, (int) old('seats', max(1, $seatSummary['used'] ?: 1)));
@endphp
<div class="max-w-3xl mx-auto"
     x-data="{
        seats: {{ $defaultSeats }},
        period: @js(old('period', $defaultPeriod)),
        method: @js(old('payment_method', $cardReady ? 'card' : 'op')),
        processor: @js($defaultProcessor),
        unit: {{ (float) $pricePerSeatMonth }},
        vatRate: {{ (float) ($periods[$defaultPeriod]['vat_rate'] ?? 21) }},
        periods: @js($periods),
        ready: {
            netopia: @js((bool) $netopiaReady),
            euplatesc: @js((bool) $euplatescReady),
            mollie: @js((bool) $mollieReady),
            stripe: @js((bool) $stripeReady),
        },
        pickProcessor(key) { if (this.ready[key]) this.processor = key; },
        get p() { return this.periods[this.period] || Object.values(this.periods)[0] || null; },
        get months() { return this.p ? Number(this.p.months) : 1; },
        get net() { return Math.round(this.unit * Math.max(1, Number(this.seats) || 1) * this.months * 100) / 100; },
        get vat() { return Math.round(this.net * this.vatRate / 100 * 100) / 100; },
        get total() { return Math.round((this.net + this.vat) * 100) / 100; },
        fmt(n) { return Number(n).toFixed(2).replace('.', ','); }
     }">

    <div class="dc-card p-4 sm:p-5 mb-4 space-y-2 text-sm">
        <p>
            <strong>1 EUR / loc / lună</strong> (+ TVA). Abonamentul este cumpărat de proprietarul contului.
            Intră în vigoare de la <strong>{{ $billableFrom->format('d.m.Y') }}</strong>
            (până atunci locurile sunt gratuite / nelimitate).
        </p>
        <p class="text-slate-600">
            Acum:
            @if(! $seatSummary['billable'])
                perioadă gratuită · {{ $seatSummary['used'] }} subuseri creați
            @elseif($seatSummary['active'])
                {{ $seatSummary['used'] }} / {{ $seatSummary['quota'] }} locuri folosite
                · valabile până la {{ dc_date($seatSummary['until']) }}
            @else
                fără locuri active · {{ $seatSummary['used'] }} subuseri
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('billing.seats.place', $company) }}" class="dc-card p-4 sm:p-5 space-y-4">
        @csrf
        <input type="hidden" name="payment_processor" :value="method === 'card' ? processor : ''">
        <h2 class="font-display text-lg text-slate-900">Comandă locuri</h2>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-3 py-2 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="dc-label" for="seats">Număr locuri</label>
                <input id="seats" name="seats" type="number" min="1" max="100" class="dc-input"
                       x-model.number="seats" value="{{ $defaultSeats }}" required>
                <p class="text-xs text-slate-500 mt-1">Capacitate maximă de subuseri pe perioada aleasă.</p>
            </div>
            <div>
                <label class="dc-label" for="period">Perioada</label>
                <select id="period" name="period" class="dc-input" x-model="period" required>
                    @foreach($periods as $key => $p)
                        <option value="{{ $key }}" @selected(old('period', $defaultPeriod) === $key)>
                            {{ $p['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <p class="text-sm text-slate-700">
            Total fără TVA:
            <strong x-text="fmt(net) + ' EUR'"></strong>
            · cu TVA (<span x-text="vatRate"></span>%):
            <strong x-text="fmt(total) + ' EUR'"></strong>
            <span class="block text-xs text-slate-500 mt-1">
                <span x-text="seats"></span> locuri × <span x-text="months"></span> luni × {{ number_format($pricePerSeatMonth, 2, ',', '.') }} EUR
            </span>
        </p>

        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="dc-label" for="billing_name">Nume firmă</label>
                <input id="billing_name" name="billing_name" class="dc-input" required
                       value="{{ old('billing_name', $company->name) }}">
            </div>
            <div>
                <label class="dc-label" for="billing_cui">CIF / CUI</label>
                <input id="billing_cui" name="billing_cui" class="dc-input"
                       value="{{ old('billing_cui', $company->cui) }}">
            </div>
            <div>
                <label class="dc-label" for="billing_phone">{{ __('Telefon') }}</label>
                <input id="billing_phone" name="billing_phone" class="dc-input"
                       value="{{ old('billing_phone', $company->phone) }}">
            </div>
            <div class="col-span-2">
                <label class="dc-label" for="billing_email">Email</label>
                <input id="billing_email" name="billing_email" type="email" class="dc-input" required
                       value="{{ old('billing_email', $company->email ?: auth()->user()->email) }}">
            </div>
            <div class="col-span-2">
                <label class="dc-label" for="billing_address">{{ __('Adresă') }}</label>
                <input id="billing_address" name="billing_address" class="dc-input"
                       value="{{ old('billing_address', $company->address) }}">
            </div>
            <div>
                <label class="dc-label" for="billing_city">Localitate</label>
                <input id="billing_city" name="billing_city" class="dc-input"
                       value="{{ old('billing_city', $company->city) }}">
            </div>
            <div>
                <label class="dc-label" for="billing_county">{{ __('Județ') }}</label>
                <input id="billing_county" name="billing_county" class="dc-input"
                       value="{{ old('billing_county', $company->county) }}">
            </div>
        </div>

        <div class="space-y-2">
            <div class="dc-label">{{ __('Plată') }}</div>
            <label class="flex gap-2.5 items-start rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer"
                   :class="method === 'op' && 'border-teal-500 bg-teal-50/50'">
                <input type="radio" name="payment_method" value="op" class="mt-0.5" x-model="method">
                <span>
                    <span class="block text-sm font-semibold">Prin bancă (OP)</span>
                    <span class="block text-xs text-slate-600">Confirmare după încasare</span>
                </span>
            </label>
            <label class="flex gap-2.5 items-start rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer"
                   :class="method === 'card' && 'border-teal-500 bg-teal-50/50'">
                <input type="radio" name="payment_method" value="card" class="mt-0.5" x-model="method" @disabled(! $cardReady)>
                <span>
                    <span class="block text-sm font-semibold">Cu cardul</span>
                    <span class="block text-xs text-slate-600">NETOPIA / Eu Plătesc / Mollie / Stripe</span>
                </span>
            </label>
            <div class="rounded-lg border border-slate-200 px-3 py-2.5 space-y-2" x-show="method === 'card'" x-cloak>
                <div class="grid sm:grid-cols-2 gap-2">
                    @foreach(['netopia' => 'NETOPIA', 'euplatesc' => 'Eu Plătesc', 'mollie' => 'Mollie', 'stripe' => 'Stripe'] as $key => $label)
                        <label class="flex gap-2 items-center rounded-md border border-slate-200 bg-white px-2.5 py-2 cursor-pointer"
                               :class="processor === '{{ $key }}' && 'border-teal-500 bg-teal-50/40'"
                               @click.prevent="pickProcessor('{{ $key }}')">
                            <input type="radio" class="pointer-events-none" :checked="processor === '{{ $key }}'" :disabled="!ready.{{ $key }}">
                            <span class="text-sm font-semibold">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="terms" value="1" class="rounded border-slate-300 mt-0.5" required @checked(old('terms'))>
            <span>Accept <a href="{{ route('legal.show', 'termeni') }}" class="underline" target="_blank">termenii</a> și confirm comanda.</span>
        </label>

        <button type="submit" class="dc-btn-primary">Plasează comanda</button>
    </form>
</div>
@endsection
