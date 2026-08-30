@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Pe lângă factură, DateConta Facturare suportă <strong>proformă</strong>, <strong>aviz de însoțire</strong>,
    <strong>chitanță</strong>, <strong>factură storno</strong> și <strong>notă de creditare</strong>.
    Formularul tipic e similar cu cel de factură, dar tipul, seria și utilizarea comercială diferă.
    e-Factura se aplică facturilor, storno și notelor de creditare — nu proformelor.
    La editare, butonul <strong>Renunță</strong> te întoarce la fișa documentului fără a salva.
</p>

@include('help._figure', [
    'shot' => 'nav-emite',
    'label' => 'Figura 1',
    'caption' => 'Meniul Emite — Factură, Proformă, Aviz, Încasare, Storno, Notă de creditare, Factură recurentă.',
])

<h3>Proformă</h3>
<p>
    Proforma este un document de ofertă / solicitare de plată anticipată. Nu înlocuiește factura fiscală.
    Folosește serie proprie (ex. PRF-0001). După acceptarea clientului, emiți factura reală pe seria FCT.
</p>
<ol class="help-steps">
    <li>Emite → <strong>Proformă</strong>.</li>
    <li>Completează clientul, liniile și moneda ca la factură.</li>
    <li>Opțional: bifează <strong>Permite plata cu cardul online</strong> (cu procesatorul firmei din Setări → Integrări).</li>
    <li>Salvează draft sau emite pe seria de proforme și trimite PDF-ul / emailul.</li>
</ol>

<div class="help-note">
    La <strong>încasarea integrală</strong> a unei proforme (card, OP, cash sau altă metodă pe fișa documentului),
    aplicația emite automat factura fiscală cu <strong>data încasării</strong>, înregistrează plata pe factură
    și trimite / programează e-Factura după termenul din Setări → e-Factura.
    Pe factură apare nota cu nr./data proformei și metoda reală de încasare (doar chitanță, doar card sau doar OP).
    Dacă proforma a fost încasată fracționat, nota listează toate încasările (metodă, dată, sumă).
    Plățile parțiale pe proformă nu emit încă factura — factura apare la încasarea care acoperă restul.
</div>

<h3>Aviz de însoțire</h3>
<p>
    Avizul documentează livrarea bunurilor. Folosește tipul Aviz și seria AVZ (sau prefixul tău).
    Completează produsele livrate, cantitățile și datele clientului / destinației conform practicii tale.
</p>
<ol class="help-steps">
    <li>Emite → <strong>Aviz de însoțire</strong>.</li>
    <li>Alege clientul și liniile de bunuri.</li>
    <li>Emite pe serie; tipărește / descarcă PDF pentru transport.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-create',
    'label' => 'Figura 2',
    'caption' => 'Formularul pentru proformă/aviz/chitanță folosește aceleași blocuri: client, antet, linii.',
])

<h3>Chitanță / OP (prin Încasare)</h3>
<p>
    Emite → <strong>Încasare</strong> deschide formularul de încasare: alegi clientul, bifezi facturile / proformele neîncasate
    (sau lași suma liberă), tipul <strong>Chitanță</strong> (serie CHT, max. 5000 RON/client/zi) sau <strong>OP</strong>.
    Detalii în capitolul <a href="{{ route('help.show', 'incasari') }}">Încasări</a>.
</p>
<ol class="help-steps">
    <li>Emite → <strong>Încasare</strong> (sau Document nou → Încasare nouă).</li>
    <li>Selectează clientul și, opțional, facturile / proformele de încasat.</li>
    <li>Salvează — pentru Chitanță se emite documentul pe serie; plățile actualizează facturile.</li>
</ol>

<h3>Factură storno și notă de creditare</h3>
<p>
    Ambele anulează / corectează o factură emisă prin linii negative. Storno rămâne tip factură (status Storno, serie FCT);
    nota de creditare e tip separat (serie NC). Pe aceeași factură poți emite fie storno, fie notă de creditare — nu ambele.
</p>
<ol class="help-steps">
    <li>Emite → <strong>Factură storno</strong> sau <strong>Notă de creditare</strong> (sau Document nou pe dashboard).</li>
    <li>Selectează factura emisă eligibilă din listă.</li>
    <li>Confirmă — documentul se emite imediat; îl poți trimite în e-Factura (cod 384 storno / 381 notă de credit).</li>
</ol>
<div class="help-note">
    La <strong>storno</strong>, atât factura de storno cât și factura originală apar automat ca <strong>Achitată</strong>
    (nu mai rămân de încasat). Starea rămâne Achitată chiar dacă ștergi o încasare de pe factură.
</div>

<h3>Serii pe tip</h3>
<ul>
    <li>Fiecare tip are seriile lui în Setări → Serii (inclusiv NC pentru note de creditare).</li>
    <li>Numerotarea rămâne PREFIX-#### pe tipul ales.</li>
    <li>Listele din meniul Liste filtrează pe tip (Proforme, Avize, Chitanțe, Facturi storno, Note de creditare).</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-list',
    'label' => 'Figura 3',
    'caption' => 'Liste separate pe tip — păstrează proformele și facturile pe fluxuri distincte.',
])

@include('help._figure', [
    'shot' => 'documents-show',
    'label' => 'Figura 4',
    'caption' => 'Pagina unui document emis — PDF și acțiuni disponibile în funcție de tip și stare.',
])

<p>
    Gestionează documentele din liste: <a href="{{ route('help.show', 'liste-documente') }}">Liste documente</a>.
</p>
@endsection
