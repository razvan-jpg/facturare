@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Încasările înregistrează plățile primite pe facturile emise. Actualizează starea de plată
    (Neachitată → Parțial achitată → Achitată) și apar în lista de plăți și în rapoarte.
    Poți emite și o <strong>chitanță</strong> pe serie sau un <strong>OP</strong> din formularul dedicat.
</p>

<div class="help-note">
    Pentru ca <strong>clientul să plătească online cu cardul</strong> (link pe factură / PDF), vezi
    <a href="{{ route('help.show', 'plata-card') }}">Plată cu cardul online</a>
    — NETOPIA, Eu Plătesc, Mollie sau Stripe, configurate per firmă (cheile tale, nu ale platformei).
</div>

@include('help._figure', [
    'shot' => 'payments',
    'label' => 'Figura 1',
    'caption' => 'Lista încasărilor — dată, document, client, metodă, sumă și opțiunea de ștergere.',
])

<h3>Încasare nouă (recomandat)</h3>
<ol class="help-steps">
    <li>Emite → <strong>Încasare</strong> (sau Dashboard → Document nou → Încasare nouă).</li>
    <li>Alege clientul — apar <strong>soldul inițial</strong> (dacă mai e rest) și facturile neîncasate.</li>
    <li>Soldul inițial e bifat implicit și se încasează <strong>înainte</strong> de facturi. Poți încasa doar soldul, chiar fără facturi emise.</li>
    <li>Bifează facturile pe care le încasezi; suma și „Reprezentând” se completează automat (sold + facturi).</li>
    <li>Tip document: <strong>Chitanță</strong> (max. 5000 RON / client / zi) sau <strong>OP</strong> (fără limită).</li>
    <li>Dacă suma depășește 5000 RON, tipul trece automat pe OP.</li>
    <li>Salvează: se emit chitanța (dacă e cazul); plata acoperă întâi soldul inițial, apoi facturile bifate.</li>
</ol>
<div class="help-note">
    Ordinea la alocare: <strong>1) sold inițial</strong>, <strong>2) facturi</strong> (după scadență), <strong>3) surplus</strong> (avans).
    Soldul real al clientului scade pe măsură ce încasezi din soldul inițial.
</div>

<h3>Încasare rapidă de pe factură</h3>
<ol class="help-steps">
    <li>Deschide factura emisă din Liste → Facturi (sau din Dashboard → de încasat).</li>
    <li>Apasă <strong>Înregistrează încasare</strong>.</li>
    <li>Sumă: implicit restul de plată; poți introduce o sumă parțială (≥ 0,01).</li>
    <li>Completează data plății și metoda (OP, Numerar, Chitanță, Card, Altă).</li>
    <li>Salvează. Starea de plată a facturii se recalculează.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-show',
    'label' => 'Figura 2',
    'caption' => 'Pe fișa facturii emise găsești acțiunea de înregistrare a încasării și istoricul plăților.',
])

<h3>Lista încasărilor</h3>
<p>
    Emite → Bani → <strong>Listă încasări</strong> deschide evidența globală (/payments) pentru consultare și corecturi.
</p>

<div class="help-note">
    Chitanța pe serie CHT se emite din formularul Încasare (tip Chitanță). OP actualizează doar plățile, fără document CHT.
</div>

<h3>Plăți parțiale</h3>
<ul>
    <li>Poți înregistra mai multe încasări pe aceeași factură până la acoperirea totalului.</li>
    <li>Starea Parțial achitată rămâne până când suma încasată acoperă totalul documentului.</li>
    <li>Nu înregistra sume care depășesc semnificativ logica ta internă fără verificarea totalului facturii.</li>
</ul>

<div class="help-warn">
    Ștergerea unei încasări din listă recalculează starea facturii. Folosește ștergerea doar pentru corecturi,
    nu ca înlocuitor pentru storno de factură. Dacă factura are deja storno, rămâne <strong>Achitată</strong>
    (la fel și factura de storno) — nu se redeschide ca Neachitată.
</div>

@include('help._figure', [
    'shot' => 'reports',
    'label' => 'Figura 3',
    'caption' => 'Rapoartele agregă vânzările și încasările — utile după ce ai înregistrat plățile.',
])

@include('help._figure', [
    'shot' => 'dashboard',
    'label' => 'Figura 4',
    'caption' => 'Widget-ul de facturi de încasat de pe Dashboard se golește pe măsură ce marchezi plățile.',
])

<h3>Limită chitanță numerar</h3>
<p>
    O chitanță (și totalul pe client în aceeași zi) nu poate depăși <strong>5000 RON</strong>.
    Peste această sumă folosești OP — aplicația comută automat tipul când valoarea depășește limita.
</p>

<p>
    Pentru automatizarea emiterii: <a href="{{ route('help.show', 'recurente') }}">Facturi recurente</a>.
</p>
@endsection
