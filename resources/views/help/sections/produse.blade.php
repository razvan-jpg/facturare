@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Catalogul de produse și servicii alimentează liniile de pe factură. Pe fiecare linie,
    <strong>produsul este obligatoriu</strong>, iar <strong>descrierea este opțională</strong>.
    Autocomplete-ul live propune produse existente pe măsură ce tastezi.
</p>

@include('help._figure', [
    'shot' => 'products-list',
    'label' => 'Figura 1',
    'caption' => 'Catalog → Produse și servicii — nomenclator cu denumiri, prețuri și cote TVA.',
])

<h3>Lista de produse</h3>
<ol class="help-steps">
    <li>Deschide <strong>Catalog → Produse și servicii</strong>.</li>
    <li>Caută după denumire sau creează un produs nou.</li>
    <li>Editează prețul implicit, unitatea de măsură și cota TVA când se schimbă oferta ta.</li>
</ol>

<h3>Creare produs în catalog</h3>
<ol class="help-steps">
    <li>Apasă adăugare produs / serviciu.</li>
    <li>Completează denumirea (cea care apare ca nume de produs pe linie).</li>
    <li>Setează prețul unitar implicit și cota TVA (dacă e cazul).</li>
    <li><strong>Unitatea de măsură</strong> — alege din lista live sau scrie una nouă (se salvează în catalog). La e-Factura se mapează pe codul UNECE.</li>
    <li>Opțional: cod intern, descriere lungă de catalog.</li>
    <li>Salvează. Produsul devine disponibil imediat la autocomplete pe documente.</li>
</ol>

<div class="help-note">
    Poți porni fără catalog complet: la salvare factură, un nume de produs nou poate fi creat automat
    în nomenclator. Totuși, un catalog îngrijit reduce greșelile de preț și TVA.
</div>

<h3>Produs vs. descriere pe linia de factură</h3>
<ul>
    <li><strong>Produs</strong> — obligatoriu; identifică ce vinzi (ex. „Consultanță IT”).</li>
    <li><strong>Descriere</strong> — opțională; detalii pe linie (ex. „Pachet martie 2026 — 10 ore”).</li>
    <li>Cantitate, preț unitar și cotă TVA sunt obligatorii pe o linie completă.</li>
    <li>Linile goale trebuie completate sau șterse înainte de salvare.</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-lines',
    'label' => 'Figura 2',
    'caption' => 'Liniile documentului — produs cu autocomplete, descriere opțională, cantitate, preț, TVA.',
])

<h3>Autocomplete live</h3>
<ol class="help-steps">
    <li>În formularul de factură, click pe câmpul de produs al liniei.</li>
    <li>Tastează cel puțin câteva caractere din denumire.</li>
    <li>Selectează produsul din listă — prețul și TVA pot fi precompletate din catalog.</li>
    <li>Ajustează cantitatea, prețul și descrierea opțională.</li>
</ol>

<div class="help-warn">
    Mesajul de tip „Linia N: produsul e obligatoriu…” apare dacă ai cantitate/preț fără nume de produs
    sau o linie incompletă. Completează sau șterge linia.
</div>

<h3>TVA și produse</h3>
<p>
    Cota de pe linie trebuie să fie coerentă cu regimul firmei (plătitor / neplătitor) și cu cotele definite
    la Setări → Cote TVA. Pentru neplătitori, liniile reflectă regimul configurat; nu forța cote care nu se aplică.
</p>

@include('help._figure', [
    'shot' => 'documents-create',
    'label' => 'Figura 3',
    'caption' => 'Formularul de emitere folosește catalogul de produse pentru fiecare linie nouă.',
])

<h3>Bune practici</h3>
<ul>
    <li>Folosește denumiri stabile de produs; detaliile variabile pune-le în descriere.</li>
    <li>Actualizează prețul din catalog când schimbi lista de prețuri — documentele vechi rămân neschimbate.</li>
    <li>Evită zeci de duplicate aproape identice; editează produsul existent.</li>
</ul>

<p>
    Urmează ghidul complet: <a href="{{ route('help.show', 'emitere-factura') }}">Emitere factură</a>.
</p>
@endsection
