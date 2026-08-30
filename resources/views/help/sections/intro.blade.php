@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    DateConta Facturare este aplicația web pentru facturare electronică destinată firmelor din România.
    Poți emite documente fiscale și comerciale, urmări încasările, personaliza PDF-urile și transmite facturile
    către sistemul național e-Factura (SPV ANAF), totul dintr-un singur cont, cu una sau mai multe societăți.
</p>

<div class="help-note">
    Resurse publice (fără autentificare):
    <a href="{{ route('faq') }}">Întrebări frecvente</a>,
    <a href="{{ route('guides.show', 'e-factura') }}">Cum emiți e-Factura</a>,
    <a href="{{ route('guides.show', 'proforma-vs-factura') }}">Proformă vs factură</a>,
    <a href="{{ route('pricing') }}">Prețuri</a>,
    <a href="{{ route('legal.index') }}">Legal</a>
    — indexate în sitemap (manualul Ajutor rămâne privat).
</div>

@include('help._figure', [
    'shot' => 'landing',
    'label' => 'Figura 1',
    'caption' => 'Pagina de start DateConta Facturare — prezentarea produsului și accesul la autentificare.',
])

<h3>Pentru cine este aplicația</h3>
<ul>
    <li>PFA, SRL și alte forme juridice care emit facturi către clienți din România sau din străinătate.</li>
    <li>Contabili care administrează mai multe firme din același cont (multi-societate).</li>
    <li>Utilizatori care au nevoie de e-Factura, PDF personalizat și evidență clară a plăților.</li>
</ul>

<h3>Documentele pe care le poți emite</h3>
<ul>
    <li><strong>Factură</strong> — documentul principal, cu numerotare pe serie, PDF și trimitere e-Factura.</li>
    <li><strong>Proformă</strong> — ofertă / avans fără efect de factură fiscală; serie proprie.</li>
    <li><strong>Aviz de însoțire</strong> — pentru livrări de bunuri.</li>
    <li><strong>Chitanță</strong> — document distinct de tip chitanță (nu confundă cu metoda de plată „Chitanță” la încasări).</li>
</ul>

@include('help._figure', [
    'shot' => 'dashboard',
    'label' => 'Figura 2',
    'caption' => 'Dashboard-ul după login — rezumatul activității societății active.',
])

<h3>Fluxul recomandat la prima utilizare</h3>
<ol class="help-steps">
    <li>Înregistrează-te sau autentifică-te. Accesul este gratuit până la 31.03.2027; după aceea, conturile noi primesc 6 luni gratuite.</li>
    <li>Creează societatea și preia datele după CUI din ANAF (denumire, reg. com., adresă, localitate, județ).</li>
    <li>Adaugă cel puțin un cont bancar marcat „Pe factură” și verifică seriile (FCT, PRF, AVZ, CHT).</li>
    <li>Opțional: urcă logo, semnătură și ștampilă la Personalizare PDF.</li>
    <li>Adaugă clienți și produse (sau creează-le direct din formularul facturii).</li>
    <li>Emite prima factură: Salvează draft, verifică, apoi Salvează și emite.</li>
    <li>Dacă e cazul, autorizează SPV și trimite factura în e-Factura.</li>
</ol>

<div class="help-note">
    Societatea activă din meniul superior controlează toate listele și documentele pe care le vezi.
    Schimbă societatea înainte de a emite, ca să nu lucrezi pe firma greșită.
</div>

<h3>Ce nu acoperă acest manual</h3>
<p>
    Manualul explică utilizarea DateConta Facturare. Nu înlocuiește consultanța fiscală sau interpretarea
    obligațiilor legale privind e-Factura, TVA sau arhivarea. Pentru probleme tehnice de cont sau acces,
    contactează {{ config('dateconta.contact_email') }}.
</p>

@include('help._figure', [
    'shot' => 'nav-setari',
    'label' => 'Figura 3',
    'caption' => 'Meniul Setări — punctul de acces la date firmă, serii, e-Factura, PDF și preferințe.',
])

<h3>Convenții din manual</h3>
<ul>
    <li>Pașii numerotați apar în casete cu fundal deschis (<code class="help-kbd">1</code>, <code class="help-kbd">2</code>…).</li>
    <li>Casetele verzi sunt sfaturi; cele portocalii sunt avertismente.</li>
    <li>Figurile trimit la capturi din <code>images/help/</code>; dacă lipsește fișierul PNG, vezi un loc rezervat.</li>
</ul>

<p>
    Continuă cu <a href="{{ route('help.show', 'cont-acces') }}">Cont și autentificare</a>
    sau sari direct la <a href="{{ route('help.show', 'emitere-factura') }}">Emitere factură</a>.
</p>
@endsection
