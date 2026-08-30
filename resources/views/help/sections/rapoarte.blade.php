@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Modulul Rapoarte agregă vânzările și încasările societății active pe un interval de date,
    plus soldurile clienților la o dată aleasă. Poți consulta carduri de sumar, tabele și export CSV.
</p>

@include('help._figure', [
    'shot' => 'reports',
    'label' => 'Figura 1',
    'caption' => 'Rapoarte → Vânzări și încasări — interval, carduri Vânzări / Încasări / Neîncasat și tabele.',
])

<h3>Solduri clienți</h3>
<ol class="help-steps">
    <li>Deschide <strong>Rapoarte → Clienți (solduri)</strong>.</li>
    <li>Data implicită este <strong>azi</strong>; o poți schimba (zz/ll/aaaa).</li>
    <li>Alege <strong>Toți clienții</strong> sau un client din listă.</li>
    <li>Vezi pe fiecare rând: sold inițial, facturi deschise și sold; sus apare totalul de încasat.</li>
</ol>
<div class="help-note">
    Sold la dată = rest sold inițial aplicabil până la acea dată + restul facturilor emise până la dată
    (minus încasările pe sold / nealocate pe facturi, cu data plății ≤ data raportului)
    (minus încasările înregistrate până la dată). Același total (la azi) apare pe Dashboard.
</div>

<h3>Fișă de partener</h3>
<ol class="help-steps">
    <li>Tot din <strong>Rapoarte → Clienți (solduri)</strong>, secțiunea <strong>Fișă de partener</strong>.</li>
    <li>Alege clientul. Implicit, perioada este <strong>1 ale lunii curente → azi</strong>; poți modifica ambele date (zz/ll/aaaa sau calendar).</li>
    <li>Opțional, bifează <strong>Toată perioada (de la sold inițial până azi)</strong> — „De la” = data soldului inițial (implicit data creării clientului; sold necompletat = 0), „Până la” = azi.</li>
    <li>Apasă <strong>Afișează</strong> — raportul se deschide peste aplicație (fără fereastră popup a browserului).</li>
    <li>În panou: <strong>Export PDF</strong> descarcă PDF-ul; <strong>Print</strong> tipărește; <strong>Închide</strong> închide panoul.</li>
    <li>Conținutul urmează modelul contabil (societate, partener, cont 4111-Clienți, rulaje debit/credit/sold, totaluri).</li>
    <li>Tipuri document: <strong>FC</strong> factură, <strong>INC</strong>/<strong>CH</strong> încasare, <strong>NC</strong> notă de creditare, <strong>ST</strong> storno.</li>
</ol>

<h3>Balanță parteneri</h3>
<ol class="help-steps">
    <li>Din aceeași pagină, secțiunea <strong>Balanță parteneri</strong>.</li>
    <li>Implicit: <strong>1 ale lunii curente → azi</strong>; poți schimba datele din câmpuri / calendar.</li>
    <li>Opțional, bifează <strong>Toată perioada (de la sold inițial până azi)</strong> — de la cea mai veche dată de sold inițial (implicit data creării fiecărui client) până azi.</li>
    <li>Opțional: <strong>Ascunde clienții cu sold 0</strong> (sold final 0) și/sau <strong>Ascunde liniile integral pe 0</strong> (fără nicio sumă pe rând).</li>
    <li>Apasă <strong>Afișează</strong> — raportul se deschide peste aplicație, cu <strong>Export PDF</strong> și <strong>Print</strong>.</li>
    <li>Implicit, „BALANTA TERTI” listează <strong>toți</strong> clienții (cont 4111); cu bifele de mai sus poți reduce lista. Coloane: rulaje precedente / curente, total sume, solduri finale.</li>
</ol>

<h3>Consultarea raportului de vânzări</h3>
<ol class="help-steps">
    <li>Deschide <strong>Rapoarte → Vânzări și încasări</strong>.</li>
    <li>Setează <strong>De la</strong> și <strong>Până la</strong>.</li>
    <li>Apasă <strong>Actualizează</strong>.</li>
    <li>Citește cardurile: Vânzări, Încasări, Neîncasat (include restul facturilor deschise + soldurile inițiale ale clienților).</li>
    <li>Parcurge tabelele „Pe client” și lista facturilor neplatite pentru acțiuni operaționale.</li>
</ol>

<div class="help-note">
    Raportul ia în calcul facturile <strong>emise</strong> din interval. Draft-urile și alte tipuri
    (proforme, avize) nu intră în vânzările facturate în același fel.
</div>

<h3>Export CSV</h3>
<ol class="help-steps">
    <li>Din Rapoarte folosește <strong>Export CSV</strong> (sau ruta de export cu aceiași parametri from/to).</li>
    <li>Fișierul descărcat are un nume de forma <code class="help-kbd">raport-vanzari-YYYY-MM-DD-YYYY-MM-DD.csv</code>.</li>
    <li>Coloane tipice: Număr, Data, Client, Total, Încasat, Status plată, Monedă.</li>
    <li>Deschide CSV-ul în Excel / Google Sheets / aplicația de contabilitate.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-list',
    'label' => 'Figura 2',
    'caption' => 'Pentru detalii pe un rând din raport, deschide factura corespunzătoare din Liste → Facturi.',
])

<div class="help-warn">
    Exportul reflectă datele din DateConta Facturare, nu înlocuiește declarațiile fiscale.
    Verifică monedele și cursurile când ai facturi în EUR/USD/GBP.
</div>

<h3>Cum folosești raportul operațional</h3>
<ul>
    <li>La final de lună: setează intervalul 1–ultima zi, exportă CSV, arhivează.</li>
    <li>Săptămânal: filtrează Neîncasat și înregistrează plățile lipsă.</li>
    <li>Pe client: identifică restanțierii din tabelul agregat.</li>
</ul>

@include('help._figure', [
    'shot' => 'payments',
    'label' => 'Figura 3',
    'caption' => 'Încasările din listă alimentează coloana Încasat din rapoarte și CSV.',
])

@include('help._figure', [
    'shot' => 'dashboard',
    'label' => 'Figura 4',
    'caption' => 'Dashboard-ul oferă un rezumat rapid; rapoartele permit interval personalizat și export.',
])

<p>
    Setări de limbă și UI: <a href="{{ route('help.show', 'preferinte') }}">Preferințe și limbi</a>.
</p>
@endsection
