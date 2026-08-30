@extends('layouts.public-seo')

@section('meta_title', __($meta['title'] ?? 'Proformă vs factură'))
@section('meta_description', __($meta['meta_description'] ?? ''))
@section('canonical', $canonical)

@section('heading', __($meta['title'] ?? 'Proformă vs factură'))
@section('subheading', __($meta['subtitle'] ?? ''))

@push('head_jsonld')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => __($meta['title'] ?? 'Proformă vs factură'),
    'description' => __($meta['meta_description'] ?? ''),
    'author' => [
        '@type' => 'Organization',
        'name' => config('dateconta.brand_name', 'DateConta Facturare'),
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => config('dateconta.platform_operator.name'),
        'url' => 'https://factura.dateconta.ro/',
    ],
    'mainEntityOfPage' => $canonical,
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="help-shell">
    @include('public._toc', ['guides' => $guides, 'current' => $current])

    <article class="help-article dc-card">
        <h2>{{ __($meta['title'] ?? 'Proformă vs factură') }}</h2>
        <p class="help-lead">{{ __($meta['subtitle'] ?? '') }}</p>

        <p>
            {{ __('În DateConta Facturare poți emite atât proforme, cât și facturi fiscale. Au formulare similare, dar tipul, seria și rolul comercial diferă — inclusiv dacă documentul poate merge în e-Factura ANAF.') }}
        </p>

        <h3>{{ __('Pe scurt') }}</h3>
        <table class="legal-table">
            <thead>
                <tr>
                    <th>{{ __('Aspect') }}</th>
                    <th>{{ __('Proformă') }}</th>
                    <th>{{ __('Factură fiscală') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('Rol') }}</td>
                    <td>{{ __('Ofertă / solicitare de plată anticipată') }}</td>
                    <td>{{ __('Document fiscal de vânzare') }}</td>
                </tr>
                <tr>
                    <td>{{ __('Serie tipică') }}</td>
                    <td>{{ __('PRF-#### (sau prefixul tău)') }}</td>
                    <td>{{ __('FCT-#### (sau prefixul tău)') }}</td>
                </tr>
                <tr>
                    <td>{{ __('e-Factura ANAF') }}</td>
                    <td>{{ __('Nu se trimite') }}</td>
                    <td>{{ __('Da (după emitere + SPV autorizat)') }}</td>
                </tr>
                <tr>
                    <td>{{ __('După încasare integrală') }}</td>
                    <td>{{ __('Poate genera automat factura fiscală') }}</td>
                    <td>{{ __('Se marchează achitată / parțial') }}</td>
                </tr>
            </tbody>
        </table>

        <h3>{{ __('Ce este proforma') }}</h3>
        <p>
            {{ __('Proforma este un document de ofertă / solicitare de plată anticipată. Nu înlocuiește factura fiscală. Folosește serie proprie (ex. PRF-0001). După acceptarea clientului, emiți factura reală pe seria de facturi — sau lași aplicația să o emită automat la încasarea integrală.') }}
        </p>
        <ol class="help-steps">
            <li>{{ __('Emite → Proformă.') }}</li>
            <li>{{ __('Completează clientul, liniile și moneda ca la factură.') }}</li>
            <li>{{ __('Opțional: bifează Permite plata cu cardul online (dacă ai procesator configurat).') }}</li>
            <li>{{ __('Salvează draft sau emite pe seria de proforme și trimite PDF-ul / emailul.') }}</li>
        </ol>

        <div class="help-note">
            {!! __('La <strong>încasarea integrală</strong> a unei proforme (card, OP, cash sau altă metodă), aplicația emite automat factura fiscală cu data încasării, înregistrează plata pe factură și trimite / programează e-Factura după termenul din Setări → e-Factura. Plățile parțiale pe proformă nu emit încă factura — factura apare la încasarea care acoperă restul.') !!}
        </div>

        <h3>{{ __('Ce este factura fiscală') }}</h3>
        <p>
            {{ __('Factura fiscală este documentul de vânzare pe care îl folosești în evidență și, unde e cazul, în e-Factura. Parcurgi: client, date document (dată, scadență, serie, monedă, limbă PDF), linii de produse/servicii, apoi draft sau emitere cu număr pe serie.') }}
        </p>
        <ol class="help-steps">
            <li>{{ __('Emite → Factură.') }}</li>
            <li>{{ __('Alege clientul (sau preia datele din ANAF după CUI).') }}</li>
            <li>{{ __('Completează liniile: produs obligatoriu, cantitate, preț, TVA; descrierea e opțională.') }}</li>
            <li>{{ __('Salvează și emite — alocă numărul pe serie.') }}</li>
            <li>{{ __('Din listă / fișă: PDF, email, încasare, e-Factura, storno (în condiții).') }}</li>
        </ol>

        <h3>{{ __('Când folosești fiecare') }}</h3>
        <ul>
            <li>{!! __('<strong>Proformă</strong> — oferte, avansuri, plată înainte de livrare; vrei PDF profesional fără efect de factură fiscală încă.') !!}</li>
            <li>{!! __('<strong>Factură</strong> — vânzarea s-a confirmat / livrarea e făcută; ai nevoie de document fiscal și, dacă e cazul, de trimitere în e-Factura.') !!}</li>
        </ul>

        <div class="help-warn">
            {{ __('Proforma nu se trimite în e-Factura. Doar facturile (inclusiv storno) și notele de creditare participă la fluxul SPV ANAF.') }}
            <a href="{{ route('guides.show', 'e-factura') }}" class="text-amber-900 underline">{{ __('Ghid e-Factura') }}</a>
        </div>

        <h3>{{ __('Storno și note de creditare') }}</h3>
        <p>
            {{ __('Pentru corectarea unei facturi deja emise (mai ales după e-Factura), folosești storno sau notă de creditare — linii negative pe document de corecție. Nu „ștergi” factura acceptată ANAF ca pe un draft.') }}
        </p>

        <h3>{{ __('Serii pe tip') }}</h3>
        <ul>
            <li>{{ __('Fiecare tip are seriile lui în Setări → Serii.') }}</li>
            <li>{{ __('Numerotarea rămâne PREFIX-#### pe tipul ales.') }}</li>
            <li>{{ __('Listele din aplicație filtrează pe tip (Facturi, Proforme etc.).') }}</li>
        </ul>

        <div class="help-note">
            {{ __('Acest ghid nu este consultanță fiscală. Pentru obligații legale privind facturarea, consultă un specialist.') }}
        </div>

        <p class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('register') }}" class="dc-btn-primary text-sm px-4 py-2 inline-flex">{{ __('Creează cont gratuit') }}</a>
            <a href="{{ route('faq') }}" class="dc-btn-secondary text-sm px-4 py-2 inline-flex">{{ __('Întrebări frecvente') }}</a>
            <a href="{{ route('guides.show', 'e-factura') }}" class="dc-btn-secondary text-sm px-4 py-2 inline-flex">{{ __('Cum emiți e-Factura') }} →</a>
        </p>
    </article>
</div>

@include('public._styles')
@endsection
