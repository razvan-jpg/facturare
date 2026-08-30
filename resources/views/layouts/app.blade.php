<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ui_locale_normalize(app()->getLocale())) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Panou')) — DateConta {{ __('Facturare') }}</title>
    @include('partials.favicon')
    @include('partials.fonts')
    @include('partials.google-ads-gtag')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/app-nav.css') }}?v=20260812a">
    <style>
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        .font-display { font-family: 'Source Serif 4', Georgia, serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="dc-gradient min-h-screen"@if(auth()->user()?->is_admin) data-allow-context-menu="1"@endif>
<div class="dc-shell">
    @include('partials.app-nav')

    <div class="dc-shell-main">
        <header class="@yield('shell_pad', 'px-4 sm:px-8') py-4 flex flex-wrap items-center justify-between gap-3 w-full">
            <div class="min-w-0">
                @isset($header)
                    <div class="font-display text-2xl sm:text-3xl text-slate-900">{{ $header }}</div>
                @else
                    <h1 class="font-display text-2xl sm:text-3xl text-slate-900">@yield('heading')</h1>
                @endisset
                @hasSection('subheading')
                    <p class="text-sm text-slate-600 mt-1">@yield('subheading')</p>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">@yield('actions')</div>
        </header>

        <main class="@yield('shell_pad', 'px-4 sm:px-8') pb-10 w-full">
            @auth
                @include('partials.subscription-expiry-banner')
                @include('partials.admin-support-banner')
            @endauth
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-teal-200 bg-teal-50 text-teal-900 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-950 px-4 py-3 text-sm">
                    {!! session('warning') !!}
                </div>
            @endif
            @php
                // Avertizarea „fără serie activă” e afișată bold/centrat pe pagină — fără bara roșie.
                $hideErrorBar = $errors->has('series') && $errors->count() === 1;
            @endphp
            @if ($errors->any() && ! $hideErrorBar)
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <footer class="px-4 sm:px-8 py-6 text-xs text-slate-500 border-t border-slate-200/70 flex flex-wrap items-center justify-between gap-2">
            <div>
                DateConta Facturare · Operator: {{ config('dateconta.platform_operator.name') }} · CUI {{ config('dateconta.platform_operator.cui') }}
                @auth
                    <span class="mx-2">·</span>{{ auth()->user()->name }}
                @endauth
                @include('partials.trafic-ro', ['class' => 'dc-trafic-ro--app'])
                @include('partials.atrafic-banner', ['class' => 'dc-ad-slot--app'])
            </div>
            <div class="ml-auto tabular-nums text-slate-400" title="Versiune aplicație">v{{ config('dateconta.version') }}</div>
        </footer>
    </div>
</div>
@if(session('show_login_account_modal') && auth()->check())
    @include('partials.login-account-modal')
    @php(session()->forget('show_login_account_modal'))
@endif
@auth
    @include('partials.referral-recommend-modal')
    @if(auth()->user()->is_admin)
        @include('partials.admin-promo-mail-modal')
    @endif
@endauth
<script>
function switchCompany(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/companies/' + id + '/switch';
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(token);
    document.body.appendChild(form);
    form.submit();
}
</script>
@stack('scripts')
@include('partials.cookie-consent')
</body>
</html>
