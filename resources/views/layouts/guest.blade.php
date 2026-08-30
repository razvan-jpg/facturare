<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ui_locale_normalize(app()->getLocale())) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'DateConta Facturare') }}</title>
    @include('partials.favicon')
    @include('partials.fonts')
    @include('partials.google-ads-gtag')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        .font-display { font-family: 'Source Serif 4', Georgia, serif; }
    </style>
</head>
<body class="dc-auth antialiased text-slate-900"@if(auth()->user()?->is_admin) data-allow-context-menu="1"@endif>
    <div class="dc-auth-bg" aria-hidden="true">
        <span class="dc-auth-blob dc-auth-blob-a"></span>
        <span class="dc-auth-blob dc-auth-blob-b"></span>
        <span class="dc-auth-blob dc-auth-blob-c"></span>
        <span class="dc-auth-grid"></span>
    </div>

    <div class="dc-auth-shell">
        <aside class="dc-auth-aside">
            <div class="dc-auth-aside-inner">
                @include('partials.brand-mark', [
                    'variant' => 'full',
                    'light' => true,
                    'href' => route('home'),
                    'imgClass' => 'h-14 w-14 rounded-2xl object-cover shadow-md ring-1 ring-white/25',
                ])
                <h1 class="font-display dc-auth-aside-title">{{ __('Facturare care ține pasul cu firma ta') }}</h1>
                <p class="dc-auth-aside-lead">
                    {{ __('Emite facturi, proforme și chitanțe în minute. Multi-firmă, PDF și încasări — dintr-un singur cont.') }}
                </p>
                <ul class="dc-auth-aside-list">
                    <li>{{ __('Gratuit până la :date', ['date' => \Illuminate\Support\Carbon::parse(config('dateconta.promo_free_until'))->format('d.m.Y')]) }}</li>
                    <li>{{ __('Date firmă din ANAF, serii și catalog pe societate') }}</li>
                    <li>{{ __('Rapoarte și încasări la îndemână') }}</li>
                </ul>
                <div class="dc-auth-aside-mock" aria-hidden="true">
                    <div class="dc-auth-mock-card">
                        <span>{{ __('Factură') }}</span>
                        <strong>{{ __('RO · PDF · Încasare') }}</strong>
                        <em>{{ __('Gata de trimis clientului') }}</em>
                    </div>
                </div>
            </div>
        </aside>

        <main class="dc-auth-main">
            <div class="dc-auth-main-brand md:hidden">
                @include('partials.brand-mark', [
                    'variant' => 'full',
                    'href' => route('home'),
                ])
            </div>

            <div class="flex justify-end mb-3">
                @include('partials.public-locale-select', ['variant' => 'plain'])
            </div>

            <div class="dc-auth-panel">
                {{ $slot }}
            </div>

            <p class="dc-auth-footer">
                <a href="{{ route('home') }}">{{ __('← Pagina principală') }}</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('pricing') }}">{{ __('Prețuri') }}</a>
                @unless(request()->routeIs('register'))
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('register') }}">{{ __('Creează cont') }}</a>
                @else
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('login') }}">{{ __('Autentificare') }}</a>
                @endunless
            </p>
            @include('partials.trafic-ro', ['class' => 'dc-trafic-ro--guest'])
            @include('partials.atrafic-banner', ['class' => 'dc-ad-slot--guest'])
        </main>
    </div>
@include('partials.cookie-consent')
</body>
</html>
