@extends('layouts.app')
@section('heading', 'Comandă abonament')
@section('subheading', $company->name)
@section('actions')
    <a href="{{ route('companies.index', ['all' => 1]) }}" class="dc-btn-secondary">{{ __('Societățile mele') }}</a>
@endsection

@section('content')
@php
    $defaultPeriod = array_key_first($periods) ?: '1m';
@endphp
<div class="max-w-3xl mx-auto"
     x-data="{
        period: @js(old('period', $defaultPeriod)),
        method: @js(old('payment_method', $cardReady ? 'card' : 'op')),
        processor: @js($defaultProcessor),
        periods: @js($periods),
        netopiaRonByPeriod: @js($netopiaRonByPeriod ?? []),
        ready: {
            netopia: @js((bool) $netopiaReady),
            euplatesc: @js((bool) $euplatescReady),
            mollie: @js((bool) $mollieReady),
            stripe: @js((bool) $stripeReady),
        },
        pickProcessor(key) {
            if (this.ready[key]) this.processor = key;
        },
        get p() { return this.periods[this.period] || Object.values(this.periods)[0] || null; },
        get netopiaRon() { return this.netopiaRonByPeriod[this.period] || null; }
     }">
    <div class="grid md:grid-cols-[minmax(0,1fr)_15.5rem] gap-4 items-start">

    <form method="POST" action="{{ route('billing.order.place', $company) }}" class="dc-card p-4 sm:p-5 space-y-4">
        @csrf
        <input type="hidden" name="payment_processor" :value="method === 'card' ? processor : ''">
        <h2 class="font-display text-lg text-slate-900">{{ __('Comandă') }}</h2>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-3 py-2 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div>
            <label class="dc-label" for="period">Perioada</label>
            <select id="period" name="period" class="dc-input" x-model="period" required>
                @foreach($periods as $key => $p)
                    @php
                        $optionLabel = $p['label'].' — '.number_format($p['amount_net'], 2, ',', '.').' '.$p['currency'].' + TVA';
                        if (! empty($p['bonus_label'])) {
                            $optionLabel .= ' ('.$p['bonus_label'].')';
                        }
                    @endphp
                    <option value="{{ $key }}" @selected(old('period', $defaultPeriod) === $key)>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1.5">
                Total cu TVA (<span x-text="p?.vat_rate"></span>%):
                <strong x-text="p ? (Number(p.amount_total).toFixed(2).replace('.', ',') + ' ' + p.currency) : '—'"></strong>
                <span class="text-teal-800 font-medium" x-show="p?.bonus_label" x-cloak> · <span x-text="p?.bonus_label"></span></span>
            </p>
            @if(! empty($netopiaRonByPeriod))
                <p class="text-xs text-teal-900 mt-1" x-show="method === 'card' && processor === 'netopia'" x-cloak>
                    La NETOPIA plătești
                    <strong x-text="netopiaRon ? (Number(netopiaRon.amount_total).toFixed(2).replace('.', ',') + ' RON') : '—'"></strong>
                    (curs BNR + {{ (int) round((((float) config('dateconta.subscription.netopia_ron_markup', 1.02)) - 1) * 100) }}%) — factura fiscală tot în RON.
                </p>
            @endif
        </div>

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
            <div class="dc-label">Opțiuni plată</div>

            <label class="flex gap-2.5 items-start rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer"
                   :class="method === 'op' && 'border-teal-500 bg-teal-50/50'">
                <input type="radio" name="payment_method" value="op" class="mt-0.5" x-model="method"
                       @checked(old('payment_method') === 'op')>
                <span>
                    <span class="block text-sm font-semibold text-slate-800">Prin bancă</span>
                    <span class="block text-xs text-slate-600">OP / Internet Banking — confirmare după încasare</span>
                </span>
            </label>

            <label class="flex gap-2.5 items-start rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer"
                   :class="method === 'card' && 'border-teal-500 bg-teal-50/50'">
                <input type="radio" name="payment_method" value="card" class="mt-0.5" x-model="method"
                       @checked(old('payment_method', $cardReady ? 'card' : 'op') === 'card')
                       @disabled(! $cardReady)>
                <span class="min-w-0">
                    <span class="flex flex-wrap items-center gap-1.5">
                        <span class="text-sm font-semibold text-slate-800">Cu cardul</span>
                        @if($cardReady)
                            <span class="text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded">Recomandat</span>
                        @else
                            <span class="text-[10px] font-bold uppercase tracking-wide bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">În configurare</span>
                        @endif
                    </span>
                    <span class="block text-xs text-slate-600 mt-0.5">Visa / Mastercard · activare automată după plată</span>
                </span>
            </label>

            <div class="rounded-lg border border-slate-200 px-3 py-2.5 space-y-2 bg-slate-50/50"
                 x-show="method === 'card'" x-cloak>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Procesator plată</div>
                <p class="text-xs text-slate-600">Alege procesatorul înainte de finalizarea comenzii.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    {{-- Col 1: NETOPIA + Eu Plătesc --}}
                    <div class="space-y-2">
                        <label class="flex gap-2 items-start rounded-md border border-slate-200 bg-white px-2.5 py-2.5 min-h-[5.5rem]"
                               :class="{
                                   'border-teal-500 bg-teal-50/40': processor === 'netopia',
                                   'cursor-pointer': ready.netopia,
                                   'opacity-60 cursor-not-allowed': !ready.netopia
                               }"
                               @click.prevent="pickProcessor('netopia')">
                            <input type="radio" name="payment_processor_ui" value="netopia" class="mt-1 shrink-0 pointer-events-none"
                                   :checked="processor === 'netopia'"
                                   :disabled="!ready.netopia">
                            <span class="min-w-0 grow space-y-1.5">
                                <span class="block text-sm font-semibold text-slate-800">NETOPIA</span>
                                <span class="block text-xs text-slate-600">
                                    MobilPay · RO · încasare RON (BNR + {{ (int) round((((float) config('dateconta.subscription.netopia_ron_markup', 1.02)) - 1) * 100) }}%)
                                    <span class="text-amber-800" x-show="!ready.netopia"> — indisponibil</span>
                                </span>
                                <span class="block pt-0.5 max-w-[160px]" @click.stop>
                                    <script src="https://mny.ro/npId.js?p=167767" type="text/javascript" data-version="orizontal" data-contrast-color="#ffffff"></script>
                                </span>
                            </span>
                        </label>

                        <label class="flex gap-2 items-start rounded-md border border-slate-200 bg-white px-2.5 py-2.5 min-h-[5.5rem]"
                               :class="{
                                   'border-teal-500 bg-teal-50/40': processor === 'euplatesc',
                                   'cursor-pointer': ready.euplatesc,
                                   'opacity-60 cursor-not-allowed': !ready.euplatesc
                               }"
                               @click.prevent="pickProcessor('euplatesc')">
                            <input type="radio" name="payment_processor_ui" value="euplatesc" class="mt-1 shrink-0 pointer-events-none"
                                   :checked="processor === 'euplatesc'"
                                   :disabled="!ready.euplatesc">
                            <span class="min-w-0 grow space-y-1.5">
                                <span class="block text-sm font-semibold text-slate-800">Eu Plătesc</span>
                                <span class="block text-xs text-slate-600">
                                    Card · RO
                                    <span class="text-amber-800" x-show="!ready.euplatesc"> — indisponibil</span>
                                </span>
                                <span class="block pt-0.5">
                                    <img src="{{ asset('images/euplatesc.png') }}" alt="Eu Plătesc" width="160" height="40"
                                         class="h-9 w-auto max-w-[160px] object-contain rounded bg-black">
                                </span>
                            </span>
                        </label>
                    </div>

                    {{-- Col 2: Mollie + Stripe --}}
                    <div class="space-y-2">
                        <label class="flex gap-2 items-start rounded-md border border-slate-200 bg-white px-2.5 py-2.5 min-h-[5.5rem]"
                               :class="{
                                   'border-teal-500 bg-teal-50/40': processor === 'mollie',
                                   'cursor-pointer': ready.mollie,
                                   'opacity-60 cursor-not-allowed': !ready.mollie
                               }"
                               @click.prevent="pickProcessor('mollie')">
                            <input type="radio" name="payment_processor_ui" value="mollie" class="mt-1 shrink-0 pointer-events-none"
                                   :checked="processor === 'mollie'"
                                   :disabled="!ready.mollie">
                            <span class="min-w-0 grow space-y-1.5">
                                <span class="block text-sm font-semibold text-slate-800">Mollie</span>
                                <span class="block text-xs text-slate-600">
                                    Checkout EU
                                    <span class="text-amber-800" x-show="!ready.mollie"> — indisponibil</span>
                                </span>
                                <span class="block pt-0.5">
                                    <img src="{{ asset('images/mollie.svg') }}" alt="Mollie" width="110" height="28"
                                         class="h-7 w-auto max-w-[110px] object-contain rounded">
                                </span>
                            </span>
                        </label>

                        <label class="flex gap-2 items-start rounded-md border border-slate-200 bg-white px-2.5 py-2.5 min-h-[5.5rem]"
                               :class="{
                                   'border-teal-500 bg-teal-50/40': processor === 'stripe',
                                   'cursor-pointer': ready.stripe,
                                   'opacity-60 cursor-not-allowed': !ready.stripe
                               }"
                               @click.prevent="pickProcessor('stripe')">
                            <input type="radio" name="payment_processor_ui" value="stripe" class="mt-1 shrink-0 pointer-events-none"
                                   :checked="processor === 'stripe'"
                                   :disabled="!ready.stripe">
                            <span class="min-w-0 grow space-y-1.5">
                                <span class="block text-sm font-semibold text-slate-800">Stripe</span>
                                <span class="block text-xs text-slate-600">
                                    Checkout global
                                    <span class="text-amber-800" x-show="!ready.stripe"> — indisponibil</span>
                                </span>
                                <span class="block pt-0.5">
                                    <img src="{{ asset('images/stripe.svg') }}" alt="Stripe" width="110" height="28"
                                         class="h-7 w-auto max-w-[110px] object-contain rounded">
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <label class="flex items-start gap-1.5 text-xs text-slate-700" x-show="method === 'card'" x-cloak>
                    <input type="checkbox" name="recurring" value="1" class="mt-0.5 rounded border-slate-300" @checked(old('recurring'))>
                    <span>
                        <span class="font-medium text-slate-800">Plată recurentă (opțional)</span>
                        <span class="block text-slate-600 mt-0.5">
                            La Stripe / Mollie: reînnoirea se debitează automat.
                            La NETOPIA / Eu Plătesc: se marchează preferința de reînnoire.
                        </span>
                    </span>
                </label>
            </div>
            @error('payment_processor')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-start gap-2 text-xs text-slate-700">
            <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-slate-300" required @checked(old('terms'))>
            <span>
                Sunt de acord cu
                <a href="{{ route('legal.show', 'termeni') }}" target="_blank" rel="noopener" class="text-teal-800 underline font-semibold">termenii și condițiile</a>,
                <a href="{{ route('legal.show', 'confidentialitate') }}" target="_blank" rel="noopener" class="text-teal-800 underline">confidențialitatea</a>
                și
                <a href="{{ route('legal.show', 'gdpr') }}" target="_blank" rel="noopener" class="text-teal-800 underline">GDPR</a>.
            </span>
        </label>

        @error('terms')
            <p class="text-sm text-rose-600">{{ $message }}</p>
        @enderror

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <button type="submit" class="dc-btn-primary px-6">{{ __('Comandă') }}</button>
            <a href="https://reclamatiisal.anpc.ro" target="_blank" rel="noopener noreferrer"
               class="inline-block shrink-0 ml-auto" title="ANPC — Soluționarea alternativă a litigiilor"
               style="width:250px;height:50px">
                <img src="{{ asset('images/anpc-sal.jpg') }}" alt="ANPC — Soluționarea alternativă a litigiilor"
                     width="250" height="50" class="block w-[250px] h-[50px] object-contain">
            </a>
        </div>
    </form>

    <aside class="space-y-3 md:sticky md:top-4">
        <div class="dc-card p-4">
            <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-2">Detalii abonament</h3>
            <div class="rounded-lg border border-teal-200 bg-teal-50/60 p-3 space-y-1.5">
                <div class="text-sm font-semibold text-slate-900 leading-snug">{{ config('dateconta.subscription.product_name') }}</div>
                <div class="text-xs text-slate-700 leading-snug">{{ $company->name }}</div>
                @if($until)
                    <div class="text-xs text-slate-600">
                        Acces până la <strong>{{ dc_date($until) }}</strong>
                    </div>
                @else
                    <div class="text-xs text-rose-700">Acces expirat / nedefinit</div>
                @endif
                @if(!empty($summary['promotions']))
                    <ul class="text-[11px] text-slate-600 list-disc pl-3.5 space-y-0.5 pt-0.5">
                        @foreach(array_slice($summary['promotions'], 0, 3) as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
                <div class="pt-1.5 text-[11px] text-teal-800 font-medium space-y-0.5 border-t border-teal-200/70 mt-1.5">
                    <div>+<span x-text="p?.months"></span> luni după plată</div>
                    <div x-show="p?.bonus_label" x-cloak x-text="p?.bonus_label"></div>
                </div>
            </div>
        </div>

        <div class="dc-card p-4 text-xs text-slate-600 space-y-1">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Operator</div>
            <div>{{ $operator['name'] ?? '' }}</div>
            <div>CUI {{ $operator['cui'] ?? '' }}</div>
            <a href="mailto:{{ config('dateconta.contact_email') }}" class="text-teal-800 hover:underline break-all">
                {{ config('dateconta.contact_email') }}
            </a>
        </div>
    </aside>

    </div>
</div>
@endsection
