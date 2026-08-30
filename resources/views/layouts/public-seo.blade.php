<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ui_locale_normalize(app()->getLocale())) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('meta_title') — {{ config('dateconta.brand_name', 'DateConta Facturare') }}</title>
    <meta name="description" content="@yield('meta_description')">
    <link rel="canonical" href="@yield('canonical')">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ str_replace('-', '_', str_replace('_', '-', ui_locale_normalize(app()->getLocale()))) }}">
    <meta property="og:site_name" content="DateConta Facturare">
    <meta property="og:url" content="@yield('canonical')">
    <meta property="og:title" content="@yield('meta_title')">
    <meta property="og:description" content="@yield('meta_description')">
    <meta property="og:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('meta_title')">
    <meta name="twitter:description" content="@yield('meta_description')">
    <meta name="twitter:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    @stack('head_jsonld')
    @include('partials.favicon')
    @include('partials.fonts')
    @include('partials.google-ads-gtag')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        .font-display { font-family: 'Source Serif 4', Georgia, serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="dc-gradient min-h-screen text-slate-900">
<header class="border-b border-slate-200/80 bg-white/80 backdrop-blur">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 min-w-0">
            <img src="{{ asset('images/brand/dateconta-icon.png') }}" alt="" class="w-8 h-8 rounded-lg">
            <span class="font-display text-lg text-slate-900 truncate">
                {{ config('dateconta.brand_name', 'DateConta Facturare') }}
            </span>
        </a>
        <div class="flex items-center gap-2 text-sm flex-wrap justify-end">
            <a href="{{ route('pricing') }}" class="text-teal-800 hover:underline">{{ __('Prețuri') }}</a>
            <a href="{{ route('faq') }}" class="text-teal-800 font-semibold hover:underline">{{ __('Întrebări frecvente') }}</a>
            <a href="{{ route('login') }}" class="dc-btn-secondary text-xs px-3 py-1.5">{{ __('Autentificare') }}</a>
            <a href="{{ route('register') }}" class="dc-btn-primary text-xs px-3 py-1.5">{{ __('Cont nou') }}</a>
            @include('partials.public-locale-select', ['variant' => 'plain'])
        </div>
    </div>
</header>
<main class="max-w-6xl mx-auto px-4 sm:px-6 py-8 w-full">
    <header class="mb-5">
        <h1 class="font-display text-2xl sm:text-3xl text-slate-900">@yield('heading')</h1>
        @hasSection('subheading')
            <p class="text-sm text-slate-600 mt-1">@yield('subheading')</p>
        @endif
    </header>
    @yield('content')
</main>
@include('partials.trafic-ro', ['class' => 'dc-trafic-ro--seo py-2'])
@include('partials.atrafic-banner', ['class' => 'dc-ad-slot--seo py-2'])
<footer class="max-w-6xl mx-auto px-4 sm:px-6 py-6 text-xs text-slate-500 border-t border-slate-200/70 flex flex-wrap gap-x-4 gap-y-2 justify-between">
    <div>
        {{ config('dateconta.platform_operator.name') }} · {{ __('CUI') }} {{ config('dateconta.platform_operator.cui') }} ·
        <a class="text-teal-800 hover:underline" href="mailto:{{ config('dateconta.contact_email') }}">{{ config('dateconta.contact_email') }}</a>
    </div>
    <div class="flex flex-wrap gap-x-4 gap-y-1">
        <a href="{{ route('home') }}" class="underline hover:text-slate-800">{{ __('Acasă') }}</a>
        <a href="{{ route('faq') }}" class="underline hover:text-slate-800">{{ __('Întrebări frecvente') }}</a>
        <a href="{{ route('guides.show', 'e-factura') }}" class="underline hover:text-slate-800">{{ __('Ghid e-Factura') }}</a>
        <a href="{{ route('guides.show', 'proforma-vs-factura') }}" class="underline hover:text-slate-800">{{ __('Proformă vs factură') }}</a>
        <a href="{{ route('pricing') }}" class="underline hover:text-slate-800">{{ __('Prețuri') }}</a>
        <a href="{{ route('legal.show', 'termeni') }}" class="underline hover:text-slate-800">{{ __('Termeni') }}</a>
    </div>
</footer>
@include('partials.cookie-consent')
</body>
</html>
