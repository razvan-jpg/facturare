<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ui_locale_normalize(app()->getLocale())) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Legal')) — {{ config('dateconta.brand_name', 'DateConta Facturare') }}</title>
    <meta name="description" content="@yield('meta_description', __('Documente legale pentru utilizarea DateConta Facturare.'))">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ str_replace('-', '_', str_replace('_', '-', ui_locale_normalize(app()->getLocale()))) }}">
    <meta property="og:site_name" content="DateConta Facturare">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', __('Legal')) — {{ config('dateconta.brand_name', 'DateConta Facturare') }}">
    <meta property="og:description" content="@yield('meta_description', __('Documente legale pentru utilizarea DateConta Facturare.'))">
    <meta property="og:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', __('Legal'))">
    <meta name="twitter:description" content="@yield('meta_description', __('Documente legale pentru utilizarea DateConta Facturare.'))">
    <meta name="twitter:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
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
            <a href="{{ route('legal.index') }}" class="text-teal-800 font-semibold hover:underline">{{ __('Legal') }}</a>
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
@include('partials.trafic-ro', ['class' => 'dc-trafic-ro--legal py-2'])
@include('partials.atrafic-banner', ['class' => 'dc-ad-slot--legal py-2'])
<footer class="max-w-6xl mx-auto px-4 sm:px-6 py-6 text-xs text-slate-500 border-t border-slate-200/70">
    {{ config('dateconta.platform_operator.name') }} · {{ __('CUI') }} {{ config('dateconta.platform_operator.cui') }} ·
    <a class="text-teal-800 hover:underline" href="mailto:{{ config('dateconta.contact_email') }}">{{ config('dateconta.contact_email') }}</a>
</footer>
@include('partials.cookie-consent')
</body>
</html>
