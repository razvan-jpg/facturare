@extends('help._layout')

@section('heading', __('Ajutor'))
@section('subheading', __('Manual de utilizare DateConta Facturare'))

@section('help')
<h2>Manual de utilizare DateConta Facturare</h2>
<p class="help-lead">Ghid complet pentru emitere facturi, e-Factura ANAF, clienți, produse și setări.</p>

<div class="help-note">
    Vezi și <a href="{{ route('help.whats-new') }}"><strong>{{ __('Ce este nou…') }}</strong></a> —
    istoricul versiunilor aplicației (acum <strong>v{{ config('dateconta.version') }}</strong>).
    Pentru vizitatori (fără login):
    <a href="{{ route('faq') }}">{{ __('Întrebări frecvente') }}</a>,
    <a href="{{ route('guides.show', 'e-factura') }}">{{ __('Ghid e-Factura') }}</a>,
    <a href="{{ route('guides.show', 'proforma-vs-factura') }}">{{ __('Proformă vs factură') }}</a>.
</div>

<p>
    Bun venit în manualul DateConta Facturare. Aplicația te ajută să emiți facturi, proforme, avize și chitanțe,
    să gestionezi clienții și produsele, să înregistrezi încasări și să trimiți facturile în sistemul național
    e-Factura (SPV ANAF). Poți lucra cu una sau mai multe societăți din același cont.
</p>

@include('help._figure', [
    'shot' => 'dashboard',
    'label' => 'Figura 1',
    'caption' => 'Panoul principal după autentificare — indicatori, facturi de încasat și acces rapid la emitere.',
])

<h3>Ce poți face în aplicație</h3>
<ul>
    <li>Configura datele firmei (CUI, sedii, conturi bancare, serii de documente) și prelua date din ANAF după CUI.</li>
    <li>Emite facturi cu linii de produse/servicii, monede, curs valutar, termene de plată și limbă PDF.</li>
    <li>Lucra cu proforme, avize de însoțire și chitanțe, pe serii separate tip PREFIX-####.</li>
    <li>Trimite facturi în e-Factura după autorizarea SPV (OAuth ANAF).</li>
    <li>Personaliza PDF-ul (logo, semnătură, ștampilă, machetă) și exporta rapoarte CSV.</li>
    <li>Programa facturi recurente și urmări starea plăților (neachitată / parțial / achitată).</li>
</ul>

<div class="help-note">
    Accesul este <strong>gratuit până la 31.03.2027</strong>. Conturile noi după această dată primesc
    <strong>{{ (int) config('dateconta.trial_months_after_promo', 6) }} luni gratuite</strong>.
    Pentru întrebări: {{ config('dateconta.contact_email') }}.
</div>

@include('help._figure', [
    'shot' => 'landing',
    'label' => 'Figura 2',
    'caption' => 'Pagina de prezentare DateConta Facturare — punctul de plecare înainte de autentificare.',
])

<h3>Cum folosești acest manual</h3>
<p>
    Folosește cuprinsul din stânga sau cardurile de mai jos. Secțiunile sunt ordonate ca un flux tipic de lucru:
    cont → navigare → societate → serii → clienți/produse → emitere → liste → încasări → e-Factura → PDF → rapoarte.
</p>
<p>
    Fiecare capitol conține pași numerotați, capturi de ecran (figuri), sfaturi practice și avertismente pentru
    situațiile în care o acțiune este ireversibilă sau blocată (de exemplu după trimiterea în e-Factura).
</p>

<div class="help-warn">
    Capturile din manual pot diferi ușor față de interfața ta, în funcție de limba UI aleasă și de datele societății active.
    Fluxurile și regulile descrise rămân aceleași.
</div>

<h3>Cuprins pe secțiuni</h3>
<p>Selectează un capitol pentru instrucțiuni detaliate:</p>

<div class="help-grid-cards">
    @foreach($sections as $key => $sec)
        <a href="{{ route('help.show', $key) }}">
            <strong>{{ $sec['title'] }}</strong>
            <span>{{ $sec['subtitle'] }}</span>
        </a>
    @endforeach
</div>

<h3>De unde începi</h3>
<ol class="help-steps">
    <li>Creează contul și autentifică-te (vezi <a href="{{ route('help.show', 'cont-acces') }}">Cont și autentificare</a>).</li>
    <li>Adaugă sau activează societatea și completează datele din ANAF (vezi <a href="{{ route('help.show', 'societate') }}">Societatea</a>).</li>
    <li>Verifică seriile de documente pentru anul curent (vezi <a href="{{ route('help.show', 'serii-documente') }}">Serii și numerotare</a>).</li>
    <li>Adaugă clienți și produse, apoi emite prima factură (vezi <a href="{{ route('help.show', 'emitere-factura') }}">Emitere factură</a>).</li>
    <li>Dacă ești obligat la e-Factura, autorizează SPV ANAF (vezi <a href="{{ route('help.show', 'efactura') }}">e-Factura ANAF</a>).</li>
</ol>

@include('help._figure', [
    'shot' => 'nav-emite',
    'label' => 'Figura 3',
    'caption' => 'Meniul Emite — acces rapid la factură, proformă, aviz, chitanță, factură recurentă și încasare.',
])

<p>
    Ai nevoie de o soluție rapidă? Deschide <a href="{{ route('help.show', 'intrebari') }}">Întrebări frecvente</a>
    sau scrie-ne la {{ config('dateconta.contact_email') }}. Versiunea aplicației este afișată în subsol și în cuprins
    (în prezent v{{ config('dateconta.version') }}).
</p>
@endsection
