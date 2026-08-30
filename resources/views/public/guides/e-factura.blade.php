@extends('layouts.public-seo')

@section('meta_title', __($meta['title'] ?? 'Cum emiți e-Factura'))
@section('meta_description', __($meta['meta_description'] ?? ''))
@section('canonical', $canonical)

@section('heading', __($meta['title'] ?? 'Cum emiți e-Factura'))
@section('subheading', __($meta['subtitle'] ?? ''))

@push('head_jsonld')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => __($meta['title'] ?? 'Cum emiți e-Factura'),
    'description' => __($meta['meta_description'] ?? ''),
    'step' => [
        ['@type' => 'HowToStep', 'name' => __('Completează CUI-ul societății'), 'text' => __('În Date generale, societatea trebuie să aibă CUI corect înainte de autorizarea SPV.')],
        ['@type' => 'HowToStep', 'name' => __('Autorizează SPV ANAF'), 'text' => __('Din Setări → e-Factura apeși Autorizează SPV și te autentifici cu certificatul digital pe CUI.')],
        ['@type' => 'HowToStep', 'name' => __('Emite factura'), 'text' => __('Emite factura fiscală (cu număr pe serie). Proformele nu se trimit în e-Factura.')],
        ['@type' => 'HowToStep', 'name' => __('Trimite și urmărește starea'), 'text' => __('Trimite e-Factura (manual sau automat) și urmărește până la Acceptată ANAF.')],
    ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="help-shell">
    @include('public._toc', ['guides' => $guides, 'current' => $current])

    <article class="help-article dc-card">
        <h2>{{ __($meta['title'] ?? 'Cum emiți e-Factura') }}</h2>
        <p class="help-lead">{{ __($meta['subtitle'] ?? '') }}</p>

        <p>
            {{ __('Modulul e-Factura din DateConta Facturare conectează societatea ta la SPV ANAF prin OAuth (autorizare cu certificat digital). După autorizare poți trimite facturile emise (și storno / note de creditare) în sistemul național și urmări starea: în coadă, trimisă, acceptată sau respinsă.') }}
        </p>

        <div class="help-note">
            {{ __('Acest ghid este public. După autentificare, manualul Ajutor din aplicație include capturi de ecran și pași detaliați pe fiecare ecran.') }}
        </div>

        <h3>{{ __('Condiții preliminare') }}</h3>
        <ul>
            <li>{!! __('Societatea are <strong>CUI</strong> corect completat în Date generale.') !!}</li>
            <li>{!! __('Ai acces la <strong>certificatul digital SPV</strong> asociat CUI-ului (token / browser configurat pentru ANAF).') !!}</li>
            <li>{!! __('Factura pe care o trimiți este <strong>emisă</strong> (are număr) sau este un storno / notă de creditare emisă.') !!}</li>
        </ul>

        <div class="help-warn">
            {{ __('Fără CUI pe societate nu poți asocia autorizarea SPV. Completează și salvează Date generale înainte de „Autorizează SPV”.') }}
        </div>

        <h3>{{ __('1. Autorizare SPV (OAuth ANAF)') }}</h3>
        <ol class="help-steps">
            <li>{{ __('Activează societatea corectă în antet.') }}</li>
            <li>{{ __('Deschide Setări → e-Factura ANAF.') }}</li>
            <li>{{ __('Verifică starea: Neautorizat înseamnă că trebuie să legi SPV.') }}</li>
            <li>{{ __('Apasă Autorizează SPV (sau Reautorizează SPV dacă tokenul a expirat).') }}</li>
            <li>{{ __('Te autentifici pe portalul ANAF (logincert.anaf.ro) cu certificatul SPV și aprobi accesul DateConta Facturare.') }}</li>
            <li>{{ __('După callback, în aplicație apare starea Autorizat.') }}</li>
        </ol>

        <div class="help-note">
            {{ __('Autorizarea este pe societate (CUI). Dacă administrezi mai multe firme, repetă fluxul pentru fiecare CUI care trebuie să trimită e-Factura.') }}
        </div>

        <p>
            {{ __('Dacă certificatul SPV este la contabil, folosește „Invită contabilul pe email”: contabilul deschide linkul (valabil 7 zile), parcurge OAuth cu certificatul firmei și leagă SPV — fără a-ți partaja parola DateConta. Nu trimite pe email fișierele certificatului.') }}
        </p>

        <h3>{{ __('2. Moduri de trimitere') }}</h3>
        <p>{{ __('În aceeași filă alegi când se trimit facturile către ANAF:') }}</p>
        <ul>
            <li>{!! __('<strong>La salvarea facturii</strong> — după emitere, documentul intră în fluxul de trimitere.') !!}</li>
            <li>{!! __('<strong>La 1 / 2 / 3 zile după emitere</strong> — programare întârziată (util dacă mai corectezi rapid după emitere).') !!}</li>
            <li>{!! __('<strong>Manual</strong> — trimiți tu din fișa facturii sau din listă (buton Trimite e-Factura).') !!}</li>
        </ul>

        <h3>{{ __('3. Trimitere de pe o factură') }}</h3>
        <ol class="help-steps">
            <li>{{ __('Emite factura (număr PREFIX-####) și verifică datele clientului (CUI, adresă structurată).') }}</li>
            <li>{{ __('Deschide fișa documentului.') }}</li>
            <li>{{ __('Apasă Trimite e-Factura dacă modul este manual sau dacă vrei trimitere imediată.') }}</li>
            <li>{{ __('Așteaptă stările: programată / în coadă → trimisă → în prelucrare → Acceptată ANAF.') }}</li>
            <li>{{ __('Sistemul verifică automat starea și, la respingere/eroare, încearcă corectări (ex. adresă) și retrimite — până la acceptare.') }}</li>
            <li>{{ __('Poți folosi Actualizează stare ANAF manual dacă vrei un refresh imediat.') }}</li>
        </ol>

        <h3>{{ __('Stări e-Factura în aplicație') }}</h3>
        <ul>
            <li>{!! __('<strong>Netrimisă</strong> — nu a intrat în coadă.') !!}</li>
            <li>{!! __('<strong>Programată / în coadă</strong> — așteaptă procesare locală sau fereastra de N zile.') !!}</li>
            <li>{!! __('<strong>Trimisă (așteaptă validare)</strong> — încărcată, răspuns ANAF în așteptare.') !!}</li>
            <li>{!! __('<strong>În prelucrare ANAF</strong> — în curs la ANAF.') !!}</li>
            <li>{!! __('<strong>Acceptată ANAF</strong> — validată; editarea / anularea clasică sunt blocate.') !!}</li>
            <li>{!! __('<strong>Respinsă ANAF</strong> — citește motivul; corectează datele; sistemul poate reîncerca automat.') !!}</li>
            <li>{!! __('<strong>Eroare trimitere</strong> — problemă tehnică; reîncercare automată + eventual reautorizare SPV.') !!}</li>
        </ul>

        <div class="help-warn">
            {!! __('După trimisă / în prelucrare / acceptată, nu mai poți edita sau anula factura ca pe un draft. Pentru corectare folosește <strong>Storno</strong> sau <strong>Notă de creditare</strong> și trimite și documentul de corecție în e-Factura.') !!}
        </div>

        <h3>{{ __('Ce NU se trimite în e-Factura') }}</h3>
        <p>
            {{ __('Proformele, avizele și chitanțele nu se trimit în e-Factura. Doar facturile (inclusiv storno) și notele de creditare participă la flux.') }}
        </p>

        <h3>{{ __('Date care trebuie să fie corecte') }}</h3>
        <ul>
            <li>{{ __('CUI emitent (societate) și CUI/date client complete.') }}</li>
            <li>{{ __('Adresă, localitate, județ pe câmpuri separate (nu totul lipit în „Adresă”).') }}</li>
            <li>{{ __('Pentru București: sector 1–6 în județ/localitate (cerință tipică BR-RO-100).') }}</li>
            <li>{{ __('Linii cu produs, UM, cantitate, preț, cotă TVA coerente.') }}</li>
            <li>{{ __('Serie și număr alocate (document emis).') }}</li>
        </ul>

        <div class="help-note">
            {{ __('XML-ul e-Factura folosește reguli CIUS-RO. Acest ghid nu este consultanță fiscală — pentru obligații legale consultă un specialist.') }}
        </div>

        <p class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('register') }}" class="dc-btn-primary text-sm px-4 py-2 inline-flex">{{ __('Creează cont și începe') }}</a>
            <a href="{{ route('faq') }}" class="dc-btn-secondary text-sm px-4 py-2 inline-flex">{{ __('Întrebări frecvente') }}</a>
            <a href="{{ route('guides.show', 'proforma-vs-factura') }}" class="dc-btn-secondary text-sm px-4 py-2 inline-flex">{{ __('Proformă vs factură') }} →</a>
        </p>
    </article>
</div>

@include('public._styles')
@endsection
