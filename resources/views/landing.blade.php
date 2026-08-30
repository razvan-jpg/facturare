<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ui_locale_normalize(app()->getLocale())) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('DateConta Facturare — Facturi online pentru firme din România') }}</title>
    <meta name="description" content="{{ __('Emite facturi, proforme, avize și chitanțe online. Gratuit până la 31.03.2027. Multi-firmă, PDF, încasări și rapoarte. Lansare 15 august 2026.') }}">
    <link rel="canonical" href="https://factura.dateconta.ro/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ str_replace('-', '_', str_replace('_', '-', ui_locale_normalize(app()->getLocale()))) }}">
    <meta property="og:site_name" content="DateConta Facturare">
    <meta property="og:url" content="https://factura.dateconta.ro/">
    <meta property="og:title" content="{{ __('DateConta Facturare — Facturi online pentru firme din România') }}">
    <meta property="og:description" content="{{ __('Soft de facturare online. Gratuit până la 31.03.2027. Lansare oficială 15 august 2026, ora 10:00. Emite facturi, proforme, PDF și încasări.') }}">
    <meta property="og:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __('DateConta Facturare — Facturi online România') }}">
    <meta name="twitter:description" content="{{ __('Gratuit până la 31.03.2027. Lansare 15 august 2026, ora 10:00. factura.dateconta.ro') }}">
    <meta name="twitter:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'DateConta Facturare',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'url' => 'https://factura.dateconta.ro/',
        'description' => __('Soft de facturare online pentru firme din România. Facturi, proforme, avize, chitanțe, PDF, email și încasări.'),
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'RON',
            'description' => __('Gratuit până la 31.03.2027'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('dateconta.platform_operator.name'),
            'url' => 'https://factura.dateconta.ro/',
        ],
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
    </script>
    @include('partials.favicon')
    @include('partials.fonts')
    @include('partials.google-ads-gtag')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif}</style>
</head>
@php
    $launchAt = \Illuminate\Support\Carbon::create(2026, 8, 15, 10, 0, 0, 'Europe/Bucharest');
    $bannerUntil = \Illuminate\Support\Carbon::create(2026, 8, 31, 23, 59, 59, 'Europe/Bucharest');
    $launchPreview = request()->boolean('preview_lansare');
    $communityPreview = request()->boolean('preview_comunitate');
    // Din 1.09.2026 bannerul de lansare dispare; mulțumirile trec jos în locul lui.
    $thanksDock = $communityPreview || now('Europe/Bucharest')->gt($bannerUntil);
    $showLaunchBanner = ! $thanksDock && ($launchPreview || now('Europe/Bucharest')->lte($bannerUntil));
    $activeUsersCount = (int) ($activeUsersCount ?? 0);
    $companiesCount = (int) ($companiesCount ?? 0);
    $activeVisitorsCount = (int) ($activeVisitorsCount ?? 0);
@endphp
<body class="mkt-body{{ $showLaunchBanner ? ' has-launch-banner' : '' }}{{ $thanksDock ? ' has-thanks-dock' : '' }}"@if(auth()->user()?->is_admin) data-allow-context-menu="1"@endif>
@php
    $thanksMessageHtml = __('Mulțumim celor :users utilizatori și celor :companies societăți care au avut încredere în noi până acum!!! și le urăm bun venit celor :visitors vizitatori activi acum pe aplicația noastră!!!', [
        'users' => '<strong data-thanks="users">'.e(number_format($activeUsersCount, 0, ',', '.')).'</strong>',
        'companies' => '<strong data-thanks="companies">'.e(number_format($companiesCount, 0, ',', '.')).'</strong>',
        'visitors' => '<strong data-thanks="visitors">'.e(number_format($activeVisitorsCount, 0, ',', '.')).'</strong>',
    ]);
@endphp
<a href="{{ route('login') }}"
   id="mkt-thanks"
   class="mkt-thanks {{ $thanksDock ? 'mkt-thanks--dock' : 'mkt-thanks--corner' }}"
   aria-live="polite"
   aria-label="{{ __('Autentificare — comunitatea DateConta Facturare') }}"
   data-stats-url="{{ route('community.stats') }}">
    @if($thanksDock)
        <span class="mkt-thanks-dock-row">
            <span class="mkt-thanks-kicker mkt-thanks-kicker--dock">{{ __('Comunitate') }}</span>
            <span class="mkt-thanks-marquee">
                <span class="mkt-thanks-track">
                    <span class="mkt-thanks-seg">{!! $thanksMessageHtml !!}</span>
                    <span class="mkt-thanks-seg" aria-hidden="true">{!! $thanksMessageHtml !!}</span>
                </span>
            </span>
        </span>
    @else
        <span class="mkt-thanks-inner">
            <span class="mkt-thanks-kicker">{{ __('Comunitate') }}</span>
            <p class="mkt-thanks-text">{!! $thanksMessageHtml !!}</p>
        </span>
    @endif
</a>
<a href="{{ route('register') }}" class="mkt-promise" aria-label="{{ __('Promisiunea DateConta Facturare') }}">
    <span class="mkt-promise-kicker">{{ __('Promisiunea noastră') }}</span>
    <span class="mkt-promise-label">{{ __('Să devenim cel mai bun și cel mai ieftin soft de facturare de pe piață!!!') }}</span>
    <span class="mkt-promise-price">{!! __('După perioada de grație: abonamente începând de la :price', ['price' => '<strong>1,99 EUR / lună + TVA</strong>']) !!}</span>
</a>
<a href="{{ route('register') }}" class="mkt-referral" aria-label="{{ __('Promoție recomandări DateConta Facturare') }}">
    <span class="mkt-referral-kicker">{{ __('Recomandă & câștigă') }}</span>
    <span class="mkt-referral-label">{{ __('Adu clienți noi cu codul tău promoțional — tu și ei primiți timp gratuit în plus') }}</span>
    <span class="mkt-referral-price">{{ __('Ei: +2 săptămâni · Tu: +1 lună la fiecare 2 societăți aduse') }}</span>
</a>
<div class="mkt-band">
    {{ __('Lansare: cont gratuit până la 31.03.2027 — fără card, fără perioadă de probă limitată acum') }}
</div>

<section class="mkt-hero">
    <header class="mkt-nav">
        @include('partials.brand-mark', [
            'variant' => 'compact',
            'light' => true,
            'href' => route('home'),
            'imgClass' => 'h-12 w-12 rounded-xl object-cover shadow-md ring-1 ring-white/25',
        ])
        <div class="mkt-nav-actions">
            <div class="mkt-nav-links">
                <a href="{{ route('pricing') }}" class="mkt-cta mkt-cta-ghost">{{ __('Prețuri') }}</a>
                <a href="{{ route('faq') }}" class="mkt-cta mkt-cta-ghost">{{ __('Întrebări frecvente') }}</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="mkt-cta mkt-cta-amber">{{ __('Intră în aplicație') }}</a>
                @else
                    <a href="{{ route('login') }}" class="mkt-cta mkt-cta-ghost">{{ __('Autentificare') }}</a>
                    <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber">{{ __('Creează cont') }}</a>
                @endauth
            </div>
            <div class="mkt-lang-cluster">
                @include('partials.public-locale-select', ['variant' => 'light'])
                @include('partials.public-lang-banner')
            </div>
        </div>
    </header>

    <a href="{{ route('register') }}" class="mkt-promise-hero mkt-promise-hero--mobile" aria-label="{{ __('Promisiunea DateConta Facturare') }}">
        <img src="{{ asset('images/brand/dateconta-logo-96.png') }}" srcset="{{ asset('images/brand/dateconta-logo-96.png') }} 96w, {{ asset('images/brand/dateconta-logo-192.png') }} 192w" sizes="72px" alt="" class="mkt-promise-hero-logo" width="72" height="72" decoding="async">
        <div class="mkt-promise-hero-copy">
            <span>{{ __('Atenție · Ofertă agresivă') }}</span>
            <strong>{{ __('Promisiunea noastră: să devenim cel mai bun și cel mai ieftin soft de facturare de pe piață!!!') }}</strong>
            <em class="mkt-promise-hero-price">{{ __('După perioada de grație: abonamente începând de la 1,99 EUR / lună + TVA') }}</em>
        </div>
    </a>
    <a href="{{ route('register') }}" class="mkt-referral-hero mkt-referral-hero--mobile" aria-label="{{ __('Promoție recomandări') }}">
        <img src="{{ asset('images/brand/dateconta-logo-96.png') }}" srcset="{{ asset('images/brand/dateconta-logo-96.png') }} 96w, {{ asset('images/brand/dateconta-logo-192.png') }} 192w" sizes="72px" alt="" class="mkt-referral-hero-logo" width="72" height="72" decoding="async">
        <div class="mkt-referral-hero-copy">
            <span>{{ __('Recomandă & câștigă') }}</span>
            <strong>{{ __('Codul tău promoțional aduce clienți noi — și timp gratuit în plus') }}</strong>
            <em class="mkt-referral-hero-price">{{ __('Ei +2 săptămâni · Tu +1 lună / 2 societăți') }}</em>
        </div>
    </a>

    <div class="mkt-hero-inner">
        <div>
            <h1 class="mkt-display mkt-anim mkt-d1">{{ __('Facturare care ține pasul cu firma ta') }}</h1>
            <p class="mkt-anim mkt-d2">{{ __('Emite documente în minute, urmărește încasările și rapoartele dintr-un singur loc. Gratuit până la 31 martie 2027.') }}</p>
            <div class="mt-8 flex flex-wrap gap-3 mkt-anim mkt-d3">
                @auth
                    <a href="{{ route('dashboard') }}" class="mkt-cta mkt-cta-amber">{{ __('Înapoi în dashboard') }}</a>
                    <a href="{{ route('launch') }}" class="mkt-cta mkt-cta-ghost">{{ __('Vezi oferta de lansare') }}</a>
                @else
                    <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber">{{ __('Începe gratuit acum') }}</a>
                    <a href="{{ route('launch') }}" class="mkt-cta mkt-cta-ghost">{{ __('Vezi oferta de lansare') }}</a>
                @endauth
            </div>
        </div>
        <div class="mkt-float mkt-anim mkt-d3">
            @include('partials.mock-invoice')
        </div>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-split">
        <div>
            <h2 class="mkt-display">{{ __('Din aplicație, nu din prezentări') }}</h2>
            <p class="mt-4 text-slate-600 max-w-md">{{ __('Interfața e construită pentru emitere rapidă: societate activă, documente, clienți, încasări și rapoarte — fără meniuri inutile.') }}</p>
        </div>
        <div class="mkt-panel">
            @include('partials.mock-dashboard')
        </div>
    </div>
</section>

<section class="bg-white border-y border-slate-200/80">
    <div class="mkt-section">
        <h2 class="mkt-display mb-8">{{ __('Ce rezolvi în DateConta Facturare') }}</h2>
        <div class="mkt-features">
            <div class="mkt-feature">
                <h3>{{ __('Documente complete') }}</h3>
                <p class="text-slate-600 text-sm leading-relaxed">{{ __('Facturi, proforme, avize, chitanțe. Draft, emitere, PDF și trimitere pe email către client.') }}</p>
            </div>
            <div class="mkt-feature">
                <h3>{{ __('Multi-firmă reală') }}</h3>
                <p class="text-slate-600 text-sm leading-relaxed">{{ __('Lucrezi cu mai multe societăți din același cont. Schimbi firma activă și continui fără să te reconectezi.') }}</p>
            </div>
            <div class="mkt-feature">
                <h3>{{ __('Bani sub control') }}</h3>
                <p class="text-slate-600 text-sm leading-relaxed">{{ __('Încasări legate de facturi, status neplătit/parțial/achitat, rapoarte și export CSV pentru contabil.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-split reverse">
        <div class="mkt-panel overflow-hidden p-0">
            <div class="bg-[var(--dc-teal)] text-white px-5 py-3 text-sm font-semibold">{{ __('Previzualizare factură emisă') }}</div>
            <div class="p-5">
                @include('partials.mock-invoice')
            </div>
        </div>
        <div>
            <h2 class="mkt-display">{{ __('Arată profesionist. Calculează fără greșeli.') }}</h2>
            <p class="mt-4 text-slate-600 max-w-md">{{ __('Serii automate, TVA, scadențe, snapshot date client pe document. Tot ce trebuie ca factura să plece corect din prima.') }}</p>
            <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber mt-8">{{ __('Creează contul gratuit') }}</a>
        </div>
    </div>
</section>

<section class="mkt-section">
    <h2 class="mkt-display mb-3">{{ __('De ce acum') }}</h2>
    <p class="text-slate-600 max-w-2xl mb-8">{{ __('Lansăm public DateConta Facturare cu acces gratuit pentru toți utilizatorii până la 31.03.2027. După această dată, noii veniți primesc 6 luni gratuite.') }}</p>
    <div class="grid md:grid-cols-3 gap-6">
        <div class="border-l-4 border-[var(--dc-amber)] pl-4">
            <div class="mkt-display text-2xl">{{ __('0 lei') }}</div>
            <p class="text-sm text-slate-600 mt-1">{{ __('până la 31.03.2027 — fără limită artificială de facturi în perioada promo') }}</p>
        </div>
        <div class="border-l-4 border-[var(--dc-teal)] pl-4">
            <div class="mkt-display text-2xl">{{ __('CUI din ANAF') }}</div>
            <p class="text-sm text-slate-600 mt-1">{{ __('preiei datele firmei/clientului rapid, la adăugare') }}</p>
        </div>
        <div class="border-l-4 border-[var(--dc-mint)] pl-4">
            <div class="mkt-display text-2xl">{{ __('RO-ready') }}</div>
            <p class="text-sm text-slate-600 mt-1">{{ __('flux gândit pentru firme din România: serii, TVA, documente uzuale') }}</p>
        </div>
    </div>
</section>

<section class="mkt-section pt-0" id="recomanda">
    <div class="mkt-referral-card">
        <p class="text-xs font-extrabold tracking-[0.18em] uppercase text-[var(--dc-mint)] mb-2">{{ __('Promoție recomandări') }}</p>
        <h2 class="mkt-display text-3xl md:text-4xl">{{ __('Atrage clienți noi. Primești timp gratuit.') }}</h2>
        <p class="mt-3 text-slate-600 max-w-2xl">
            {!! __('Fiecare societate are un :code. Îl trimiți pe email, WhatsApp sau social — când cineva își creează firma cu codul tău, amândoi câștigați.', [
                'code' => '<strong>'.e(__('cod promoțional unic')).'</strong>',
            ]) !!}
        </p>
        <div class="mkt-referral-steps">
            <div class="mkt-referral-step">
                <strong>{{ __('1. Copiază codul') }}</strong>
                <p>{{ __('Din meniul societății din aplicație — un click și e în clipboard.') }}</p>
            </div>
            <div class="mkt-referral-step">
                <strong>{{ __('2. Recomandă DateConta') }}</strong>
                <p>{!! __('Prietenul introduce codul la crearea societății. El primește :bonus.', [
                    'bonus' => '<strong>'.e(__('+2 săptămâni')).'</strong>',
                ]) !!}</p>
            </div>
            <div class="mkt-referral-step">
                <strong>{{ __('3. Tu câștigi') }}</strong>
                <p>{!! __('La fiecare :count aduse, tu primești :reward la abonament.', [
                    'count' => '<strong>'.e(__('2 societăți')).'</strong>',
                    'reward' => '<strong>'.e(__('+1 lună')).'</strong>',
                ]) !!}</p>
            </div>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="mkt-cta mkt-cta-amber">{{ __('Deschide aplicația & copiază codul') }}</a>
            @else
                <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber">{{ __('Creează cont și începe să recomanzi') }}</a>
            @endauth
            <a href="{{ route('launch') }}" class="mkt-cta mkt-cta-ghost text-[var(--dc-teal)] border border-[var(--dc-teal)]/25">{{ __('Vezi oferta de lansare') }}</a>
        </div>
    </div>
</section>

<section class="mkt-soon" id="soon" aria-labelledby="mkt-soon-title">
    <div class="mkt-soon-inner">
        <div class="mkt-soon-copy">
            <p class="mkt-soon-kicker">{{ __('În lucru') }}</p>
            <h2 class="mkt-display mkt-soon-title" id="mkt-soon-title" aria-label="{{ __('SOON !!!') }}">
                <span>S</span><span>O</span><span>O</span><span>N</span>
                <span class="mkt-soon-bang">!</span><span class="mkt-soon-bang">!</span><span class="mkt-soon-bang">!</span>
            </h2>
            <p class="mkt-soon-lead">
                {!! __('Lucrăm intens și la :app — iPhone și iPad (iOS / Apple).', [
                    'app' => '<strong>'.e(__('aplicația pentru mobil')).'</strong>',
                ]) !!}
            </p>
            <p class="mkt-soon-text">
                {{ __('Vei putea emite și urmări documentele din mers, direct de pe telefon sau tabletă.') }}
                {!! __(':announce, pe această pagină.', [
                    'announce' => '<em>'.e(__('Lansarea în App Store va fi anunțată aici')).'</em>',
                ]) !!}
            </p>
        </div>
        <div class="mkt-soon-visual" aria-hidden="true">
            <div class="mkt-soon-device mkt-soon-phone">
                <span class="mkt-soon-notch"></span>
                <div class="mkt-soon-screen">
                    <b>DateConta</b>
                    <small>{{ __('iOS · App Store') }}</small>
                    <span>{{ __('SOON') }}</span>
                </div>
            </div>
            <div class="mkt-soon-device mkt-soon-tablet">
                <div class="mkt-soon-screen">
                    <b>DateConta</b>
                    <small>{{ __('iPad · în lucru') }}</small>
                    <span>{{ __('SOON') }}</span>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
@keyframes mkt-soon-pulse{
    0%,100%{transform:scale(1);filter:brightness(1) drop-shadow(0 0 0 rgba(255,184,77,0))}
    45%{transform:scale(1.06);filter:brightness(1.2) drop-shadow(0 0 18px rgba(255,184,77,.55))}
    60%{transform:scale(.98);filter:brightness(1.05) drop-shadow(0 0 6px rgba(255,90,40,.35))}
}
@keyframes mkt-soon-letter{
    0%,100%{transform:translateY(0) scale(1)}
    50%{transform:translateY(-4px) scale(1.08)}
}
@keyframes mkt-soon-shine{
    0%{background-position:0% 50%}
    100%{background-position:100% 50%}
}
@keyframes mkt-soon-glow{
    0%,100%{opacity:.55}
    50%{opacity:1}
}
.mkt-soon{
    position:relative;overflow:hidden;
    background:
        radial-gradient(90% 70% at 12% 20%, rgba(255,184,77,.38), transparent 50%),
        radial-gradient(80% 60% at 88% 80%, rgba(255,214,102,.28), transparent 55%),
        linear-gradient(115deg, #0a3440 0%, #0f4c5c 38%, #b86a0a 78%, #e08a1e 100%);
    background-size:100% 100%,100% 100%,220% 100%;
    animation:mkt-soon-shine 8s ease-in-out infinite alternate;
    color:#fff;
    border-top:3px solid #ffb84d;
    border-bottom:3px solid #ffb84d;
}
.mkt-soon::before{
    content:"";position:absolute;inset:0;pointer-events:none;
    background:repeating-linear-gradient(-18deg, rgba(255,232,150,.07) 0 12px, transparent 12px 28px);
    animation:mkt-soon-glow 2.4s ease-in-out infinite;
}
.mkt-soon-inner{
    position:relative;z-index:1;
    max-width:72rem;margin:0 auto;padding:4.25rem 1.25rem;
    display:grid;gap:2.25rem;align-items:center;
}
@media (min-width:900px){
    .mkt-soon-inner{
        grid-template-columns:1.15fr .85fr;gap:3rem;
        padding-left:max(1.25rem, min(14rem, 24vw));
    }
}
.mkt-soon-kicker{
    display:inline-block;margin:0 0 .75rem;
    padding:.32rem .8rem;border-radius:999px;
    background:linear-gradient(90deg,#fff36a,#ffb84d 55%,#ff8a1a);
    color:#5a1400;
    font-size:.72rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase;
    box-shadow:0 0 0 2px rgba(255,255,255,.25),0 6px 18px rgba(0,0,0,.2);
}
.mkt-soon-title{
    display:flex;flex-wrap:wrap;align-items:baseline;gap:.06em;
    max-width:none;margin:0 0 1.1rem;
    font-size:clamp(2.8rem, 8vw, 4.6rem);letter-spacing:.04em;line-height:1;
    animation:mkt-soon-pulse 1.35s ease-in-out infinite;
}
.mkt-soon-title span{
    display:inline-block;
    font-weight:900;
    -webkit-text-stroke:1px rgba(90,20,0,.15);
    animation:mkt-soon-letter 1.35s ease-in-out infinite;
}
.mkt-soon-title span:nth-child(1){color:#fff36a;animation-delay:0s}
.mkt-soon-title span:nth-child(2){color:#ffb84d;animation-delay:.08s}
.mkt-soon-title span:nth-child(3){color:#ff8a1a;animation-delay:.16s}
.mkt-soon-title span:nth-child(4){color:#ff5a28;animation-delay:.24s}
.mkt-soon-title .mkt-soon-bang{color:#ffe08a;animation-delay:.32s}
.mkt-soon-title .mkt-soon-bang:nth-child(6){color:#fff36a;animation-delay:.4s}
.mkt-soon-title .mkt-soon-bang:nth-child(7){color:#ffb84d;animation-delay:.48s}
.mkt-soon-lead{font-size:1.15rem;line-height:1.55;font-weight:600;max-width:34rem;margin:0 0 .85rem;color:#fff8e8}
.mkt-soon-lead strong{color:#ffe08a;font-weight:800;text-shadow:0 1px 0 rgba(0,0,0,.25)}
.mkt-soon-text{font-size:1rem;line-height:1.6;max-width:34rem;margin:0;color:#fff1cc}
.mkt-soon-text em{font-style:normal;font-weight:800;color:#fff36a;text-decoration:underline;text-underline-offset:3px;text-decoration-color:#ffb84d}
.mkt-soon-visual{position:relative;min-height:16rem;display:flex;align-items:flex-end;justify-content:center;gap:1rem}
.mkt-soon-device{
    background:#102a43;border:2px solid rgba(255,184,77,.65);border-radius:1.35rem;
    box-shadow:0 22px 50px rgba(0,0,0,.35),0 0 0 1px rgba(255,243,106,.2);position:relative;
}
.mkt-soon-phone{width:8.2rem;height:15.5rem;padding:.55rem;border-radius:1.5rem;transform:rotate(-8deg) translateY(.4rem);z-index:2}
.mkt-soon-tablet{width:13.5rem;height:10.2rem;padding:.55rem;border-radius:1.1rem;transform:rotate(7deg) translate(-.6rem,.8rem);z-index:1}
.mkt-soon-notch{position:absolute;top:.45rem;left:50%;transform:translateX(-50%);width:2.4rem;height:.35rem;border-radius:999px;background:rgba(255,184,77,.45)}
.mkt-soon-screen{
    height:100%;border-radius:1.05rem;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.35rem;text-align:center;
    background:linear-gradient(160deg,#0f4c5c 0%,#c45c10 45%,#ffb84d 100%);
    padding:.75rem;
}
.mkt-soon-tablet .mkt-soon-screen{border-radius:.75rem}
.mkt-soon-screen b{font-family:'Source Serif 4',Georgia,serif;font-size:1.05rem;color:#fff}
.mkt-soon-screen small{font-size:.68rem;letter-spacing:.04em;text-transform:uppercase;color:#ffe8b0}
.mkt-soon-screen span{
    margin-top:.35rem;padding:.2rem .55rem;border-radius:999px;
    background:linear-gradient(90deg,#fff36a,#ffb84d,#ff8a1a);
    color:#5a1400;font-size:.7rem;font-weight:900;letter-spacing:.08em;
    animation:mkt-soon-pulse 1.1s ease-in-out infinite;
}
@media (max-width:640px){
    .mkt-soon-inner{padding:3.25rem 1.1rem}
    .mkt-soon-phone{width:6.8rem;height:13rem}
    .mkt-soon-tablet{width:10.5rem;height:8rem}
}
@media (prefers-reduced-motion:reduce){
    .mkt-soon,.mkt-soon-title,.mkt-soon-title span,.mkt-soon-screen span,.mkt-soon::before{animation:none}
}
</style>

<section class="mkt-cta-strip">
    <h2 class="mkt-display">{{ __('Nu mai amâna facturarea. Deschide contul azi.') }}</h2>
    <p class="text-white/80 max-w-xl mx-auto mb-8">{{ __('Durează un minut. Prima societate, primul client, prima factură — în aceeași sesiune.') }}</p>
    <div class="flex flex-wrap justify-center gap-3">
        <a href="{{ route('register') }}" class="mkt-cta mkt-cta-amber">{{ __('Vreau cont gratuit') }}</a>
        <a href="mailto:{{ config('dateconta.contact_email') }}" class="mkt-cta mkt-cta-ghost">{{ config('dateconta.contact_email') }}</a>
    </div>
</section>

@include('partials.atrafic-banner', ['class' => 'dc-ad-slot--landing py-6'])
@include('partials.trafic-ro', ['class' => 'dc-trafic-ro--landing pb-2'])

<footer class="max-w-6xl mx-auto px-4 py-8 text-xs text-slate-500 flex flex-wrap gap-x-4 gap-y-2 justify-between">
    <div>{{ __('© :year DateConta Facturare · :operator · CUI :cui', [
        'year' => date('Y'),
        'operator' => config('dateconta.platform_operator.name'),
        'cui' => config('dateconta.platform_operator.cui'),
    ]) }}</div>
    <div class="flex flex-wrap gap-x-4 gap-y-1">
        <a href="{{ route('pricing') }}" class="underline hover:text-slate-800">{{ __('Prețuri') }}</a>
        <a href="{{ route('faq') }}" class="underline hover:text-slate-800">{{ __('Întrebări frecvente') }}</a>
        <a href="{{ route('guides.show', 'e-factura') }}" class="underline hover:text-slate-800">{{ __('Ghid e-Factura') }}</a>
        <a href="{{ route('guides.show', 'proforma-vs-factura') }}" class="underline hover:text-slate-800">{{ __('Proformă vs factură') }}</a>
        <a href="{{ route('legal.show', 'termeni') }}" class="underline hover:text-slate-800">{{ __('Termeni') }}</a>
        <a href="{{ route('legal.show', 'confidentialitate') }}" class="underline hover:text-slate-800">{{ __('Confidențialitate') }}</a>
        <a href="{{ route('legal.show', 'gdpr') }}" class="underline hover:text-slate-800">{{ __('GDPR') }}</a>
        <a href="{{ route('launch') }}" class="underline hover:text-slate-800">{{ __('Campanie lansare') }}</a>
        <a href="mailto:{{ config('dateconta.contact_email') }}" class="underline hover:text-slate-800">{{ config('dateconta.contact_email') }}</a>
    </div>
</footer>

<a href="{{ route('register') }}" class="mkt-promise-hero mkt-promise-hero--tilt" aria-label="{{ __('Promisiunea DateConta Facturare') }}">
    <img src="{{ asset('images/brand/dateconta-logo-96.png') }}" srcset="{{ asset('images/brand/dateconta-logo-96.png') }} 96w, {{ asset('images/brand/dateconta-logo-192.png') }} 192w" sizes="72px" alt="" class="mkt-promise-hero-logo" width="72" height="72" decoding="async">
    <div class="mkt-promise-hero-copy">
        <span>{{ __('Atenție · Ofertă agresivă') }}</span>
        <strong>{{ __('Promisiunea noastră: să devenim cel mai bun și cel mai ieftin soft de facturare de pe piață!!!') }}</strong>
        <em class="mkt-promise-hero-price">{{ __('După perioada de grație: abonamente începând de la 1,99 EUR / lună + TVA') }}</em>
    </div>
</a>
<a href="{{ route('register') }}" class="mkt-referral-hero mkt-referral-hero--tilt" aria-label="{{ __('Promoție recomandări DateConta Facturare') }}">
    <img src="{{ asset('images/brand/dateconta-logo-96.png') }}" srcset="{{ asset('images/brand/dateconta-logo-96.png') }} 96w, {{ asset('images/brand/dateconta-logo-192.png') }} 192w" sizes="72px" alt="" class="mkt-referral-hero-logo" width="72" height="72" decoding="async">
    <div class="mkt-referral-hero-copy">
        <span>{{ __('Recomandă & câștigă') }}</span>
        <strong>{{ __('Adu clienți noi cu codul promoțional — +2 săptămâni pentru ei, +1 lună pentru tine la fiecare 2') }}</strong>
        <em class="mkt-referral-hero-price">{{ __('Copiază codul din aplicație și trimite-l pe WhatsApp / email') }}</em>
    </div>
</a>

@if($showLaunchBanner)
<div class="mkt-prelaunch" id="mkt-prelaunch" role="status" aria-live="polite"
     data-launch-at="{{ $launchAt->toIso8601String() }}"
     data-banner-until="{{ $bannerUntil->toIso8601String() }}"
     data-celebrate-hours="8"
     @if($launchPreview) data-preview-launch="1" @endif>
    <div class="mkt-prelaunch-star" aria-hidden="true">
        <svg viewBox="0 0 120 120" width="120" height="120" focusable="false">
            <defs>
                <linearGradient id="mktStarGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#fff36a"/>
                    <stop offset="45%" stop-color="#ff8a1a"/>
                    <stop offset="100%" stop-color="#ff3b30"/>
                </linearGradient>
            </defs>
            <g transform="rotate(-8 60 62)">
                <polygon
                    points="60,4 74,42 114,42 82,68 94,108 60,84 26,108 38,68 6,42 46,42"
                    fill="url(#mktStarGrad)"
                    stroke="#fff8c4"
                    stroke-width="3"
                    stroke-linejoin="round"/>
                <text id="mkt-star-line1" x="60" y="58" text-anchor="middle" fill="#5a1400"
                      font-size="11" font-weight="900" letter-spacing="0.5"
                      style="font-family:Arial Black,Arial,sans-serif">{{ __('LANSARE') }}</text>
                <text id="mkt-star-line2" x="60" y="72" text-anchor="middle" fill="#5a1400"
                      font-size="14" font-weight="900" letter-spacing="1"
                      style="font-family:Arial Black,Arial,sans-serif">!!!</text>
            </g>
        </svg>
    </div>
    <div class="mkt-prelaunch-inner">
        <div class="mkt-prelaunch-copy">
            <strong data-prelaunch-badge>{{ __('Prelansare · Atenție') }}</strong>
            <span data-prelaunch-text>
                {!! __('Lansarea oficială: :launch. Poți crea cont și testa acum — ne cerem scuze anticipat dacă apar sincope; urmează o perioadă intensivă de testare până la :until.', [
                    'launch' => '<em>15 august 2026, ora 10:00</em>',
                    'until' => '<em>1 octombrie 2026</em>',
                ]) !!}
            </span>
        </div>
        <div class="mkt-prelaunch-timer" aria-label="{{ __('Timp rămas până la lansare') }}">
            <div class="mkt-prelaunch-unit"><b data-unit="days">--</b><small>{{ __('zile') }}</small></div>
            <div class="mkt-prelaunch-unit"><b data-unit="hours">--</b><small>{{ __('ore') }}</small></div>
            <div class="mkt-prelaunch-unit"><b data-unit="mins">--</b><small>{{ __('min') }}</small></div>
            <div class="mkt-prelaunch-unit"><b data-unit="secs">--</b><small>{{ __('sec') }}</small></div>
        </div>
    </div>
</div>
<style>
@keyframes mkt-pre-star{
    0%,100%{transform:translateY(-26%) scale(1);filter:brightness(1.1) drop-shadow(0 0 10px rgba(255,184,77,.85))}
    40%{transform:translateY(-26%) scale(.94);filter:brightness(.75) drop-shadow(0 0 2px rgba(255,90,40,.3))}
    55%{transform:translateY(-26%) scale(1.1);filter:brightness(1.45) drop-shadow(0 0 22px rgba(255,90,40,.95))}
}
@keyframes mkt-pre-pulse{
    0%,100%{box-shadow:0 -10px 40px rgba(224,138,30,.35),0 0 0 0 rgba(224,138,30,.25)}
    50%{box-shadow:0 -14px 48px rgba(224,138,30,.55),0 0 0 3px rgba(255,184,77,.18)}
}
@keyframes mkt-pre-shine{
    0%{background-position:0% 50%}
    100%{background-position:100% 50%}
}
@keyframes mkt-live-pulse{
    0%,100%{box-shadow:0 -12px 44px rgba(16,185,129,.35),0 0 0 0 rgba(52,211,153,.2)}
    50%{box-shadow:0 -16px 56px rgba(251,191,36,.55),0 0 0 4px rgba(253,224,71,.22)}
}
.mkt-body.has-launch-banner{padding-bottom:9.5rem}
.mkt-prelaunch{
    position:fixed;left:0;right:0;bottom:0;z-index:70;
    pointer-events:none;
    overflow:visible;
    display:flex;align-items:stretch;
    min-height:6.25rem;
    background:linear-gradient(105deg,#0a3440 0%,#0f4c5c 38%,#c45c10 78%,#e08a1e 100%);
    background-size:220% 100%;
    animation:mkt-pre-shine 7s ease-in-out infinite alternate,mkt-pre-pulse 1.8s ease-in-out infinite;
    border-top:3px solid #ffb84d;
    color:#fff;
}
.mkt-prelaunch.is-launched{
    background:linear-gradient(105deg,#064e3b 0%,#0f766e 36%,#b45309 72%,#f59e0b 100%);
    border-top-color:#fde68a;
    animation:mkt-pre-shine 6s ease-in-out infinite alternate,mkt-live-pulse 1.6s ease-in-out infinite;
}
.mkt-prelaunch-star{
    position:absolute;left:.1rem;bottom:-.45rem;z-index:2;
    width:10.25rem;height:10.25rem;
    display:grid;place-items:center;
    pointer-events:none;
    animation:mkt-pre-star .7s steps(2,end) infinite;
    transform-origin:center center;
}
.mkt-prelaunch-star svg{width:100%;height:100%;display:block;overflow:visible}
.mkt-prelaunch-star text{paint-order:stroke fill;stroke:rgba(255,248,196,.65);stroke-width:.6px}
.mkt-prelaunch-inner{
    position:relative;z-index:1;
    flex:1 1 auto;max-width:72rem;margin:0 auto;
    padding:1.15rem 1.15rem 1.25rem 10.5rem;
    display:flex;flex-wrap:wrap;gap:1rem 1.5rem;align-items:center;justify-content:space-between;
    pointer-events:auto;
}
.mkt-prelaunch-copy{flex:1 1 17rem;min-width:0;font-size:1rem;line-height:1.5;font-weight:600;text-shadow:0 1px 2px rgba(0,0,0,.35)}
.mkt-prelaunch-copy strong{
    display:inline-block;margin:0 .55rem .35rem 0;padding:.28rem .7rem;border-radius:999px;
    background:#fff;color:#b45309;font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;
    box-shadow:0 0 0 2px rgba(255,184,77,.65),0 2px 10px rgba(0,0,0,.2);
}
.mkt-prelaunch.is-launched .mkt-prelaunch-copy strong{
    color:#065f46;
    box-shadow:0 0 0 2px rgba(52,211,153,.7),0 2px 10px rgba(0,0,0,.2);
}
.mkt-prelaunch-copy em{font-style:normal;font-weight:800;color:#ffe08a;text-decoration:underline;text-underline-offset:2px}
.mkt-prelaunch-copy .mkt-live-title{
    display:block;font-size:clamp(1.15rem,2.4vw,1.55rem);line-height:1.2;font-weight:900;
    letter-spacing:.02em;color:#fffef5;margin:.15rem 0 .35rem;
    text-shadow:0 2px 12px rgba(0,0,0,.35),0 0 24px rgba(253,224,71,.35);
}
.mkt-prelaunch-timer{display:flex;gap:.5rem;flex-shrink:0}
.mkt-prelaunch-unit{
    min-width:3.55rem;text-align:center;padding:.5rem .45rem .4rem;
    border-radius:.7rem;background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.35);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.15);
}
.mkt-prelaunch-unit b{display:block;font-size:1.45rem;line-height:1.05;font-variant-numeric:tabular-nums;color:#fff}
.mkt-prelaunch-unit small{display:block;font-size:.65rem;letter-spacing:.06em;text-transform:uppercase;color:#ffd089;margin-top:.2rem;font-weight:700}
#mkt-launch-fx{
    position:fixed;inset:0;z-index:80;pointer-events:none;
    width:100%;height:100%;display:block;
}
@media (max-width:640px){
    .mkt-body.has-launch-banner{padding-bottom:14.5rem}
    .mkt-prelaunch{min-height:0}
    .mkt-prelaunch-star{width:8rem;height:8rem;left:0;bottom:-.25rem}
    .mkt-prelaunch-inner{padding:1rem .9rem 1.05rem 8.2rem;gap:.75rem}
    .mkt-prelaunch-copy{font-size:.9rem}
    .mkt-prelaunch-unit{min-width:2.9rem;padding:.4rem .35rem .35rem}
    .mkt-prelaunch-unit b{font-size:1.15rem}
}
@media (prefers-reduced-motion: reduce){
    #mkt-launch-fx{display:none !important}
    .mkt-prelaunch,.mkt-prelaunch-star{animation:none !important}
}
</style>
@php
    $launchBannerI18n = [
        'live' => __('Live · 15.08.2026'),
        'title' => __('S-A LANSAT! Aplicația este live!'),
        'body' => __('Bine ai venit pe :brand — facturare online pentru firme din România. Creează cont acum și începe să emiți. Continuăm testarea intensivă până la :until.', [
            'brand' => '<em>DateConta Facturare</em>',
            'until' => '<em>1 octombrie 2026</em>',
        ]),
        'starLive' => __('LIVE'),
        'starBang' => '!!!',
        'timerAria' => __('Lansare — contor la zero'),
    ];
@endphp
<script>
(function () {
    var el = document.getElementById('mkt-prelaunch');
    if (!el) return;
    document.body.classList.add('has-launch-banner');
    var target = Date.parse(el.getAttribute('data-launch-at') || '');
    if (!target) return;
    var bannerUntil = Date.parse(el.getAttribute('data-banner-until') || '');
    var celebrateHours = parseFloat(el.getAttribute('data-celebrate-hours') || '8') || 8;
    var celebrateMs = celebrateHours * 3600 * 1000;
    var forcePreview = el.getAttribute('data-preview-launch') === '1'
        || /(?:\?|&)preview_lansare=1(?:&|$)/.test(location.search);
    var units = {
        days: el.querySelector('[data-unit="days"]'),
        hours: el.querySelector('[data-unit="hours"]'),
        mins: el.querySelector('[data-unit="mins"]'),
        secs: el.querySelector('[data-unit="secs"]')
    };
    var launched = false;
    var fxStarted = false;
    var removed = false;
    var tickTimer = null;
    var launchedCopy = @json($launchBannerI18n);

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function setZeros() {
        if (units.days) units.days.textContent = '0';
        if (units.hours) units.hours.textContent = '00';
        if (units.mins) units.mins.textContent = '00';
        if (units.secs) units.secs.textContent = '00';
    }

    function removeBanner() {
        if (removed) return;
        removed = true;
        if (tickTimer) clearInterval(tickTimer);
        var fx = document.getElementById('mkt-launch-fx');
        if (fx) fx.remove();
        el.remove();
        document.body.classList.remove('has-launch-banner');
    }

    function applyLaunchedCopy() {
        el.classList.add('is-launched');
        var badge = el.querySelector('[data-prelaunch-badge]');
        var copy = el.querySelector('[data-prelaunch-text]');
        var t1 = document.getElementById('mkt-star-line1');
        var t2 = document.getElementById('mkt-star-line2');
        if (badge) badge.textContent = launchedCopy.live;
        if (copy) {
            copy.innerHTML =
                '<span class="mkt-live-title">' + launchedCopy.title + '</span>' +
                launchedCopy.body;
        }
        if (t1) { t1.textContent = launchedCopy.starLive; t1.setAttribute('font-size', '16'); }
        if (t2) t2.textContent = launchedCopy.starBang;
        var timer = el.querySelector('.mkt-prelaunch-timer');
        if (timer) timer.setAttribute('aria-label', launchedCopy.timerAria);
        setZeros();
    }

    function startCelebration(untilTs) {
        if (fxStarted) return;
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (Date.now() >= untilTs) return;
        fxStarted = true;
        var canvas = document.createElement('canvas');
        canvas.id = 'mkt-launch-fx';
        canvas.setAttribute('aria-hidden', 'true');
        document.body.appendChild(canvas);
        var ctx = canvas.getContext('2d');
        if (!ctx) return;

        var confetti = [];
        var rockets = [];
        var sparks = [];
        var colors = ['#f43f5e','#f59e0b','#22c55e','#3b82f6','#a855f7','#ec4899','#eab308','#14b8a6','#fff'];
        var running = true;
        var paused = document.hidden;
        var rafId = 0;
        var lastBurst = 0;

        function resize() {
            canvas.width = window.innerWidth * (window.devicePixelRatio || 1);
            canvas.height = window.innerHeight * (window.devicePixelRatio || 1);
            canvas.style.width = window.innerWidth + 'px';
            canvas.style.height = window.innerHeight + 'px';
            ctx.setTransform(window.devicePixelRatio || 1, 0, 0, window.devicePixelRatio || 1, 0, 0);
        }
        resize();
        window.addEventListener('resize', resize);

        function spawnConfetti(n) {
            for (var i = 0; i < n; i++) {
                confetti.push({
                    x: Math.random() * window.innerWidth,
                    y: -30 - Math.random() * window.innerHeight * 0.55,
                    w: 7 + Math.random() * 10,
                    h: 9 + Math.random() * 12,
                    vx: -1.8 + Math.random() * 3.6,
                    vy: 1.8 + Math.random() * 4.2,
                    rot: Math.random() * Math.PI,
                    vr: -0.18 + Math.random() * 0.36,
                    color: colors[(Math.random() * colors.length) | 0],
                    alpha: 0.85 + Math.random() * 0.15
                });
            }
        }

        function burst(x, y) {
            var base = colors[(Math.random() * colors.length) | 0];
            var count = 70 + ((Math.random() * 50) | 0);
            for (var i = 0; i < count; i++) {
                var ang = (Math.PI * 2 * i) / count + Math.random() * 0.25;
                var sp = 2.8 + Math.random() * 6.8;
                sparks.push({
                    x: x, y: y,
                    vx: Math.cos(ang) * sp,
                    vy: Math.sin(ang) * sp,
                    life: 70 + ((Math.random() * 45) | 0),
                    max: 110,
                    color: Math.random() > 0.3 ? base : colors[(Math.random() * colors.length) | 0],
                    size: 2 + Math.random() * 2.8
                });
            }
        }

        function launchRocket() {
            rockets.push({
                x: window.innerWidth * (0.06 + Math.random() * 0.88),
                y: window.innerHeight + 10,
                vx: -1.4 + Math.random() * 2.8,
                vy: -(9 + Math.random() * 5.5),
                color: colors[(Math.random() * colors.length) | 0]
            });
        }

        function stopFx() {
            running = false;
            if (rafId) cancelAnimationFrame(rafId);
            rafId = 0;
            window.removeEventListener('resize', resize);
            document.removeEventListener('visibilitychange', onVis);
            if (canvas && canvas.parentNode) canvas.remove();
        }

        function onVis() {
            paused = document.hidden;
            if (!paused && running) {
                lastBurst = 0;
                rafId = requestAnimationFrame(frame);
            }
        }
        document.addEventListener('visibilitychange', onVis);

        spawnConfetti(160);
        launchRocket();
        launchRocket();
        launchRocket();
        burst(window.innerWidth * 0.28, window.innerHeight * 0.32);
        burst(window.innerWidth * 0.72, window.innerHeight * 0.26);

        function frame(ts) {
            if (!running) return;
            if (Date.now() >= untilTs) {
                stopFx();
                return;
            }
            if (paused || document.hidden) {
                rafId = 0;
                return;
            }
            ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);

            if (ts - lastBurst > 380 + Math.random() * 520) {
                lastBurst = ts;
                launchRocket();
                launchRocket();
                if (Math.random() > 0.35) launchRocket();
                spawnConfetti(28);
            }

            for (var r = rockets.length - 1; r >= 0; r--) {
                var rk = rockets[r];
                rk.x += rk.vx;
                rk.y += rk.vy;
                rk.vy += 0.045;
                ctx.beginPath();
                ctx.fillStyle = rk.color;
                ctx.arc(rk.x, rk.y, 2.6, 0, Math.PI * 2);
                ctx.fill();
                ctx.beginPath();
                ctx.strokeStyle = rk.color;
                ctx.globalAlpha = 0.45;
                ctx.moveTo(rk.x, rk.y);
                ctx.lineTo(rk.x - rk.vx * 3, rk.y - rk.vy * 3);
                ctx.stroke();
                ctx.globalAlpha = 1;
                if (rk.vy >= -1.2 || rk.y < window.innerHeight * (0.12 + Math.random() * 0.28)) {
                    burst(rk.x, rk.y);
                    rockets.splice(r, 1);
                }
            }

            for (var s = sparks.length - 1; s >= 0; s--) {
                var p = sparks[s];
                p.x += p.vx;
                p.y += p.vy;
                p.vy += 0.04;
                p.vx *= 0.985;
                p.life--;
                var a = Math.max(0, p.life / p.max);
                ctx.globalAlpha = a;
                ctx.fillStyle = p.color;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fill();
                if (p.life <= 0) sparks.splice(s, 1);
            }
            ctx.globalAlpha = 1;

            for (var c = confetti.length - 1; c >= 0; c--) {
                var cf = confetti[c];
                cf.x += cf.vx;
                cf.y += cf.vy;
                cf.rot += cf.vr;
                cf.vy += 0.012;
                ctx.save();
                ctx.translate(cf.x, cf.y);
                ctx.rotate(cf.rot);
                ctx.globalAlpha = cf.alpha;
                ctx.fillStyle = cf.color;
                ctx.fillRect(-cf.w / 2, -cf.h / 2, cf.w, cf.h);
                ctx.restore();
                if (cf.y > window.innerHeight + 40) confetti.splice(c, 1);
            }
            ctx.globalAlpha = 1;

            if (confetti.length < 140) spawnConfetti(24);
            rafId = requestAnimationFrame(frame);
        }
        if (!paused) rafId = requestAnimationFrame(frame);
    }

    function enterLaunched(celebrateUntil) {
        if (!launched) {
            launched = true;
            applyLaunchedCopy();
        } else {
            setZeros();
        }
        if (celebrateUntil > Date.now()) {
            startCelebration(celebrateUntil);
        }
    }

    function tick() {
        if (forcePreview) {
            enterLaunched(Date.now() + celebrateMs);
            return;
        }
        var now = Date.now();
        if (bannerUntil && now > bannerUntil) {
            removeBanner();
            return;
        }
        var diff = target - now;
        if (diff <= 0) {
            // Banner live (cu 0·0·0·0) până la 31.08; fireworks doar 8h după lansare.
            enterLaunched(target + celebrateMs);
            return;
        }
        var s = Math.floor(diff / 1000);
        var d = Math.floor(s / 86400); s -= d * 86400;
        var h = Math.floor(s / 3600); s -= h * 3600;
        var m = Math.floor(s / 60); s -= m * 60;
        if (units.days) units.days.textContent = String(d);
        if (units.hours) units.hours.textContent = pad(h);
        if (units.mins) units.mins.textContent = pad(m);
        if (units.secs) units.secs.textContent = pad(s);
    }
    tick();
    tickTimer = setInterval(tick, 1000);
})();
</script>
@endif
<script>
(function () {
    var el = document.getElementById('mkt-thanks');
    if (!el) return;
    var url = el.getAttribute('data-stats-url');
    if (!url) return;
    var fmt = (typeof Intl !== 'undefined' && Intl.NumberFormat)
        ? new Intl.NumberFormat('ro-RO')
        : null;
    var pollTimer = null;
    function formatN(n) {
        n = Math.max(0, parseInt(n, 10) || 0);
        return fmt ? fmt.format(n) : String(n);
    }
    function apply(data) {
        if (!data || typeof data !== 'object') return;
        var map = { users: data.users, companies: data.companies, visitors: data.visitors };
        Object.keys(map).forEach(function (key) {
            if (map[key] == null) return;
            var nodes = el.querySelectorAll('[data-thanks="' + key + '"]');
            var label = formatN(map[key]);
            nodes.forEach(function (node) { node.textContent = label; });
        });
    }
    function refresh() {
        if (document.hidden) return;
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(apply)
            .catch(function () {});
    }
    function stopPoll() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }
    function startPoll() {
        stopPoll();
        if (document.hidden) return;
        pollTimer = setInterval(refresh, 60000);
    }
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopPoll();
        } else {
            refresh();
            startPoll();
        }
    });
    startPoll();
})();
</script>
@include('partials.cookie-consent')
</body>
</html>
