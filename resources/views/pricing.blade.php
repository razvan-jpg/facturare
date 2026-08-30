@php
    $periods = config('dateconta.subscription.periods', []);
    $vat = (float) config('dateconta.subscription.vat_rate', 21);
    $currency = (string) config('dateconta.subscription.currency', 'EUR');
    $promoUntil = config('dateconta.promo_free_until', '2027-03-31');
    $promoLabel = \Illuminate\Support\Carbon::parse($promoUntil)->format('d.m.Y');
    $appHref = auth()->check() ? route('dashboard') : route('login');
    $appLabel = auth()->check() ? 'Înapoi în aplicație' : 'Autentificare';
    $eurRon = isset($eurRon) ? (float) $eurRon : null;
    $fxLabel = $fxLabel ?? 'aproximativ';
    $fmt = static function (float $n): string {
        return number_format($n, 2, ',', '.');
    };
    $vatDisplay = rtrim(rtrim(number_format($vat, 2, '.', ''), '0'), '.');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ui_locale_normalize(app()->getLocale())) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Prețuri abonament — DateConta Facturare') }}</title>
    <meta name="description" content="{{ __('Prețuri DateConta Facturare după perioada gratuită: de la 1,99 EUR / lună + TVA. Planuri 1, 3, 6 și 12 luni cu bonusuri.') }}">
    <link rel="canonical" href="{{ url('/preturi') }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ str_replace('-', '_', str_replace('_', '-', ui_locale_normalize(app()->getLocale()))) }}">
    <meta property="og:site_name" content="DateConta Facturare">
    <meta property="og:url" content="{{ url('/preturi') }}">
    <meta property="og:title" content="{{ __('Prețuri abonament — DateConta Facturare') }}">
    <meta property="og:description" content="{{ __('Prețuri DateConta Facturare după perioada gratuită: de la 1,99 EUR / lună + TVA. Planuri 1, 3, 6 și 12 luni cu bonusuri.') }}">
    <meta property="og:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __('Prețuri — DateConta Facturare') }}">
    <meta name="twitter:description" content="{{ __('De la 1,99 EUR / lună + TVA. Planuri 1, 3, 6 și 12 luni.') }}">
    <meta name="twitter:image" content="{{ asset('images/brand/dateconta-og.jpg') }}">
    @include('partials.favicon')
    @include('partials.fonts')
    @include('partials.google-ads-gtag')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="mkt-body"@if(auth()->user()?->is_admin) data-allow-context-menu="1"@endif>
<section class="mkt-hero" style="min-height:auto;padding-bottom:2rem">
    <header class="mkt-nav">
        @include('partials.brand-mark', [
            'variant' => 'compact',
            'light' => true,
            'href' => route('home'),
            'imgClass' => 'h-12 w-12 rounded-xl object-cover shadow-md ring-1 ring-white/25',
        ])
        <div class="flex flex-wrap gap-2 text-sm justify-end items-center">
            <a href="{{ route('home') }}" class="mkt-cta mkt-cta-ghost">{{ __('Pagina principală') }}</a>
            <a href="{{ route('faq') }}" class="mkt-cta mkt-cta-ghost">{{ __('Întrebări frecvente') }}</a>
            <a href="{{ $appHref }}" class="mkt-cta mkt-cta-amber">{{ __($appLabel) }}</a>
            @include('partials.public-locale-select', ['variant' => 'light'])
        </div>
    </header>
    <div class="max-w-5xl mx-auto px-4 pt-10 pb-6 text-center">
        <p class="text-amber-200/95 text-xs font-extrabold tracking-[0.16em] uppercase mb-3">{{ __('Abonamente DateConta') }}</p>
        <h1 class="mkt-display text-white mx-auto" style="max-width:18ch">{{ __('Prețuri') }}</h1>
        <p class="mt-4 text-white/85 max-w-2xl mx-auto text-base leading-relaxed">
            {!! __('Aceste prețuri se aplică <strong class="text-amber-200">după terminarea promoției</strong> (:promo) sau după <strong class="text-amber-200">perioada de gratuitate</strong> a fiecărui client (inclusiv zilele/lunile acumulate din promovări încheiate cu succes).', ['promo' => e($promoLabel)]) !!}
        </p>
    </div>
</section>

<section class="mkt-band">
    {{ __('Prețuri fără TVA · TVA RO :vat% se adaugă la plată · Monedă :currency', ['vat' => $vatDisplay, 'currency' => $currency]) }}
    @if($eurRon)
        {{ __('· Echivalent RON aproximativ (curs :fx ≈ :rate RON / 1 EUR)', ['fx' => __($fxLabel), 'rate' => $fmt($eurRon)]) }}
    @endif
</section>

<section class="mkt-section">
    <div class="mkt-price-grid">
        @foreach($periods as $key => $period)
            @php
                $net = (float) ($period['price'] ?? 0);
                $gross = round($net * (1 + $vat / 100), 2);
                $months = max(1, (int) ($period['months'] ?? 1));
                $perMonth = round($net / $months, 2);
                $bonus = $period['bonus_label'] ?? null;
                $featured = $key === '1y';
                $ronNet = $eurRon ? round($net * $eurRon, 2) : null;
                $ronGross = $eurRon ? round($gross * $eurRon, 2) : null;
            @endphp
            <article class="mkt-price-card{{ $featured ? ' mkt-price-card--featured' : '' }}">
                @if($featured)
                    <span class="mkt-price-badge">{{ __('Cel mai avantajos') }}</span>
                @endif
                <h2>{{ __($period['label'] ?? $key) }}</h2>
                <p class="mkt-price-amount">
                    <strong>{{ $fmt($net) }}</strong>
                    <span>{{ __(':currency + TVA', ['currency' => $currency]) }}</span>
                </p>
                <p class="mkt-price-meta">
                    {{ __('≈ :amount :currency / lună fără TVA · :gross :currency cu TVA', [
                        'amount' => $fmt($perMonth),
                        'currency' => $currency,
                        'gross' => $fmt($gross),
                    ]) }}
                </p>
                @if($ronGross !== null)
                    <p class="mkt-price-ron">
                        {!! __('≈ <strong>:amount RON</strong> total cu TVA', ['amount' => e($fmt($ronGross))]) !!}
                        <small>{{ __('(≈ :amount RON fără TVA)', ['amount' => $fmt($ronNet)]) }}</small>
                    </p>
                @endif
                @if($bonus)
                    <p class="mkt-price-bonus">{{ __($bonus) }}</p>
                @else
                    <p class="mkt-price-bonus mkt-price-bonus--muted">{{ __('Fără bonus suplimentar') }}</p>
                @endif
            </article>
        @endforeach
    </div>

    <aside class="mkt-price-promise" role="note">
        <p class="mkt-price-promise-kicker">{{ __('Un singur pachet · Full option') }}</p>
        <h3 class="mkt-display text-2xl md:text-3xl mb-3">{{ __('Nu plătești mai mult ca să ai mai mult.') }}</h3>
        <p>
            {!! __('Abonamentele DateConta Facturare includ <strong>un singur pachet full option</strong>: aceleași funcții pentru toată lumea. Nu există „plan Basic” vs „plan Pro” — <strong>toată lumea plătește la fel</strong> (în funcție doar de perioada aleasă) și <strong>toți beneficiază de toate opțiunile aplicației</strong>.') !!}
        </p>
    </aside>

    <div class="mkt-price-notes">
        <h3 class="mkt-display text-2xl mb-3">{{ __('Cum funcționează') }}</h3>
        <ul>
            <li>{!! __('Până la <strong>:promo</strong>, accesul de lansare rămâne gratuit conform ofertei de pe site.', ['promo' => e($promoLabel)]) !!}</li>
            <li>{{ __('După promoție / după perioada ta de gratuitate, alegi un abonament din lista de mai sus.') }}</li>
            <li>{{ __('Bonusurile (săptămâni / lună) se adaugă la perioada plătită, la confirmarea comenzii.') }}</li>
            <li>{{ __('Timpul câștigat din recomandări și alte promoții reușite se cumulează în cont și amână momentul plății.') }}</li>
            <li>{!! __('Valorile în RON sunt <strong>aproximative</strong> (curs :fx); la plată contează suma în EUR + TVA.', ['fx' => e(__($fxLabel))]) !!}</li>
        </ul>
    </div>

    <div class="mt-10 flex flex-wrap gap-3">
        <a href="{{ route('home') }}" class="mkt-cta mkt-cta-ghost text-[var(--dc-teal)] border border-[var(--dc-teal)]/25">{{ __('← Pagina principală') }}</a>
        <a href="{{ $appHref }}" class="mkt-cta mkt-cta-amber">{{ __($appLabel) }}</a>
        @guest
            <a href="{{ route('register') }}" class="mkt-cta mkt-cta-ghost text-[var(--dc-teal)] border border-[var(--dc-teal)]/25">{{ __('Creează cont gratuit') }}</a>
        @endguest
    </div>
</section>

@include('partials.trafic-ro', ['class' => 'dc-trafic-ro--pricing pb-2'])
@include('partials.atrafic-banner', ['class' => 'dc-ad-slot--pricing pb-2'])
<footer class="max-w-6xl mx-auto px-4 py-8 text-xs text-slate-500 flex flex-wrap gap-x-4 gap-y-2 justify-between">
    <div>© {{ date('Y') }} DateConta Facturare · {{ config('dateconta.platform_operator.name') }}</div>
    <div class="flex flex-wrap gap-x-4">
        <a href="{{ route('home') }}" class="underline hover:text-slate-800">{{ __('Acasă') }}</a>
        <a href="{{ route('faq') }}" class="underline hover:text-slate-800">{{ __('Întrebări frecvente') }}</a>
        <a href="{{ route('guides.show', 'e-factura') }}" class="underline hover:text-slate-800">{{ __('Ghid e-Factura') }}</a>
        <a href="{{ route('guides.show', 'proforma-vs-factura') }}" class="underline hover:text-slate-800">{{ __('Proformă vs factură') }}</a>
        <a href="{{ route('launch') }}" class="underline hover:text-slate-800">{{ __('Campanie lansare') }}</a>
        <a href="mailto:{{ config('dateconta.contact_email') }}" class="underline hover:text-slate-800">{{ config('dateconta.contact_email') }}</a>
    </div>
</footer>

<style>
.mkt-price-grid{
    display:grid;gap:1.25rem;
}
@media (min-width:720px){
    .mkt-price-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (min-width:1100px){
    .mkt-price-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
}
.mkt-price-card{
    position:relative;
    background:#fff;
    border:1px solid rgba(16,42,67,.1);
    border-radius:1.1rem;
    padding:1.35rem 1.25rem 1.4rem;
    box-shadow:0 14px 36px rgba(16,42,67,.07);
}
.mkt-price-card--featured{
    border-color:rgba(224,138,30,.55);
    box-shadow:0 18px 44px rgba(224,138,30,.18);
    background:linear-gradient(180deg,#fffdf8 0%,#fff 55%);
}
.mkt-price-badge{
    position:absolute;top:.7rem;right:.7rem;
    background:linear-gradient(90deg,#fff36a,#ffb84d);
    color:#5a1400;font-size:.65rem;font-weight:900;letter-spacing:.06em;text-transform:uppercase;
    padding:.22rem .5rem;border-radius:999px;
}
.mkt-price-card h2{
    font-family:'Source Serif 4',Georgia,serif;
    font-size:1.45rem;color:var(--dc-teal-dark);margin:0 0 .85rem;
}
.mkt-price-amount{margin:0;display:flex;flex-direction:column;gap:.15rem}
.mkt-price-amount strong{
    font-size:2rem;line-height:1;color:var(--dc-ink);font-variant-numeric:tabular-nums;
}
.mkt-price-amount span{font-size:.9rem;font-weight:700;color:var(--dc-amber-deep)}
.mkt-price-meta{margin:.7rem 0 0;font-size:.82rem;color:#627d98;line-height:1.45}
.mkt-price-ron{
    margin:.85rem 0 0;padding:.65rem .75rem;border-radius:.65rem;
    background:rgba(15,76,92,.08);color:var(--dc-teal-dark);font-size:.92rem;line-height:1.35;
}
.mkt-price-ron strong{font-size:1.05rem;font-variant-numeric:tabular-nums}
.mkt-price-ron small{display:block;margin-top:.2rem;font-size:.78rem;color:#627d98;font-weight:600}
.mkt-price-bonus{
    margin:1rem 0 0;padding:.55rem .7rem;border-radius:.65rem;
    background:rgba(31,122,108,.1);color:#0f4c5c;font-size:.88rem;font-weight:700;
}
.mkt-price-bonus--muted{background:#f1f5f9;color:#64748b;font-weight:600}
.mkt-price-promise{
    margin-top:2.5rem;padding:1.6rem 1.4rem 1.7rem;border-radius:1.15rem;
    background:linear-gradient(115deg,#0a3440 0%,#0f4c5c 45%,#c45c10 100%);
    color:#fff;border:2px solid rgba(255,184,77,.55);
    box-shadow:0 16px 40px rgba(15,76,92,.22);
}
.mkt-price-promise-kicker{
    display:inline-block;margin:0 0 .7rem;padding:.28rem .7rem;border-radius:999px;
    background:linear-gradient(90deg,#fff36a,#ffb84d);color:#5a1400;
    font-size:.7rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;
}
.mkt-price-promise .mkt-display{color:#fff;max-width:22ch}
.mkt-price-promise p{margin:0;max-width:42rem;font-size:1.02rem;line-height:1.55;color:rgba(255,255,255,.92)}
.mkt-price-promise strong{color:#ffe08a;font-weight:800}
.mkt-price-notes{
    margin-top:1.75rem;padding:1.5rem 1.35rem;border-radius:1rem;
    background:var(--dc-fog);border:1px solid rgba(16,42,67,.06);
}
.mkt-price-notes ul{margin:0;padding-left:1.1rem;color:#334e68;font-size:.95rem;line-height:1.55}
.mkt-price-notes li{margin:.45rem 0}
</style>
@include('partials.cookie-consent')
</body>
</html>
