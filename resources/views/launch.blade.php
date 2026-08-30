<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ui_locale_normalize(app()->getLocale())) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Lansare DateConta Facturare — Gratuit până la 31.03.2027') }}</title>
    <meta name="description" content="{{ __('Oprește haosul din facturi. DateConta Facturare e gratuit până la 31.03.2027. Creează cont acum.') }}">
    <link rel="canonical" href="{{ url('/lansare') }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ str_replace('-', '_', str_replace('_', '-', ui_locale_normalize(app()->getLocale()))) }}">
    <meta property="og:site_name" content="DateConta Facturare">
    <meta property="og:url" content="{{ url('/lansare') }}">
    <meta property="og:title" content="{{ __('Lansare DateConta Facturare — Gratuit până la 31.03.2027') }}">
    <meta property="og:description" content="{{ __('Oprește haosul din facturi. DateConta Facturare e gratuit până la 31.03.2027. Creează cont acum.') }}">
    <meta property="og:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __('Lansare DateConta Facturare') }}">
    <meta name="twitter:description" content="{{ __('Gratuit până la 31.03.2027. Creează cont acum pe factura.dateconta.ro') }}">
    <meta name="twitter:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    @include('partials.favicon')
    @include('partials.fonts')
    @include('partials.google-ads-gtag')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="mkt-body">
<section class="mkt-launch-hero">
    <header class="mkt-nav">
        @include('partials.brand-mark', [
            'variant' => 'compact',
            'light' => true,
            'href' => route('home'),
            'imgClass' => 'h-12 w-12 rounded-xl object-cover shadow-md ring-1 ring-white/25',
        ])
        <div class="flex flex-wrap gap-2 text-sm justify-end items-center">
            <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber">{{ __('Ia accesul gratuit') }}</a>
            @include('partials.public-locale-select', ['variant' => 'light'])
        </div>
    </header>

    <div class="max-w-5xl mx-auto px-4 pt-16 pb-20">
        <div class="mkt-urgent mkt-anim">{{ __('Ofertă de lansare · expiră 31.03.2027') }}</div>
        <h1 class="mkt-display mkt-punch mkt-anim mkt-d1">
            {!! __('Facturezi greu?<br><span style="color:#ffb84d">Atunci pierzi bani.</span>') !!}
        </h1>
        <p class="mt-6 text-lg text-white/85 max-w-2xl mkt-anim mkt-d2">
            {{ __('DateConta Facturare taie timpul mort dintre „trebuie să facturez” și „factura e la client”. Emite, trimite, urmărește încasarea. Fără abonament plătit până pe 31 martie 2027.') }}
        </p>
        <div class="mt-10 flex flex-wrap gap-3 mkt-anim mkt-d3">
            <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber text-base px-8 py-4">{{ __('Creează contul în 60 de secunde') }}</a>
            <a href="{{ route('home') }}" class="mkt-cta mkt-cta-ghost">{{ __('Vezi produsul') }}</a>
        </div>
        <p class="mt-6 text-sm text-amber-200/90 mkt-anim mkt-d3">
            {{ __('Nu aștepta „luna viitoare”. Firmele care facturează azi încasează mai repede. Punct.') }}
        </p>
    </div>
</section>

<section class="mkt-band">
    {{ __('GRATUIT COMPLET până la 31.03.2027 · după 01.04.2027: 6 luni gratuite pentru fiecare cont nou') }}
</section>

<section class="mkt-section">
    <h2 class="mkt-display mb-6">{{ __('Adevărul incomod') }}</h2>
    <div class="grid md:grid-cols-3 gap-8">
        <div>
            <h3 class="mkt-display text-2xl text-[var(--dc-amber-deep)]">{{ __('Excel nu e soft de facturare') }}</h3>
            <p class="mt-3 text-slate-600 text-sm">{{ __('Serii greșite, TVA calculat „din ochi”, documente pierdute pe email. Costă timp și credibilitate.') }}</p>
        </div>
        <div>
            <h3 class="mkt-display text-2xl text-[var(--dc-teal)]">{{ __('Proforma fără urmărire = bani blocați') }}</h3>
            <p class="mt-3 text-slate-600 text-sm">{{ __('În DateConta vezi ce e neplătit, ce e restant și ce trebuie încasat — fără să cauți prin foldere.') }}</p>
        </div>
        <div>
            <h3 class="mkt-display text-2xl text-[var(--dc-mint)]">{{ __('Contabilul tău merită ordine') }}</h3>
            <p class="mt-3 text-slate-600 text-sm">{{ __('Export CSV, documente clare, istoricul încasărilor. Mai puține mesaje de tip „trimite-mi iar factura”.') }}</p>
        </div>
    </div>
</section>

<section class="bg-[var(--dc-teal-dark)] text-white">
    <div class="mkt-section grid lg:grid-cols-2 gap-10 items-center">
        <div>
            <h2 class="mkt-display text-white">{{ __('Ce primești imediat') }}</h2>
            <ul class="mt-6 space-y-3 text-white/90 text-sm">
                <li>{{ __('✓ Facturi, proforme, avize, chitanțe') }}</li>
                <li>{{ __('✓ Multi-firmă + lookup CUI ANAF') }}</li>
                <li>{{ __('✓ PDF + email către client') }}</li>
                <li>{{ __('✓ Încasări și rapoarte') }}</li>
                <li>{{ __('✓ Acces gratuit până la 31.03.2027') }}</li>
            </ul>
            <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber mt-8 inline-flex">{{ __('Vreau avantajul de lansare') }}</a>
        </div>
        <div class="mkt-float">
            @include('partials.mock-dashboard')
        </div>
    </div>
</section>

<section class="mkt-section text-center">
    <h2 class="mkt-display mx-auto">{{ __('Dacă amâni, plătești mai târziu. Literalmente.') }}</h2>
    <p class="mt-4 text-slate-600 max-w-2xl mx-auto">
        {{ __('Perioada promo se termină pe 31.03.2027. După cutover, noii utilizatori au doar 6 luni gratuite. Cine intră acum profită de fereastra maximă.') }}
    </p>
    <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber mt-8 inline-flex text-base px-8 py-4">{{ __('Activează DateConta Facturare') }}</a>
    <p class="mt-6 text-sm text-slate-500">
        {{ __('Întrebări? Scrie la') }}
        <a class="underline text-[var(--dc-teal)]" href="mailto:{{ config('dateconta.contact_email') }}">{{ config('dateconta.contact_email') }}</a>
    </p>
</section>

@include('partials.trafic-ro', ['class' => 'dc-trafic-ro--launch py-2'])
@include('partials.atrafic-banner', ['class' => 'dc-ad-slot--launch py-2'])
<footer class="border-t border-slate-200 text-xs text-slate-500 px-4 py-6 text-center">
    DateConta Facturare · {{ config('dateconta.platform_operator.name') }} ·
    <a href="{{ route('home') }}" class="underline">factura.dateconta.ro</a> ·
    <a href="{{ route('launch.email') }}" class="underline">{{ __('Previzualizare email lansare') }}</a>
</footer>
@include('partials.cookie-consent')
</body>
</html>
