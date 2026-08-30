@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Interfața DateConta Facturare este organizată pe un meniu superior cu panouri: Emite, Liste, Catalog,
    Rapoarte, Ajutor și Setări. În dreapta (sau în zona de cont) găsești societatea activă și limba interfeței.
</p>

@include('help._figure', [
    'shot' => 'nav-emite',
    'label' => 'Figura 1',
    'caption' => 'Meniul Emite — documente noi (factură, proformă, aviz, încasare, storno, notă de creditare), factură recurentă.',
])

<h3>Meniul Emite</h3>
<ul>
    <li><strong>Factură</strong> — formular de emitere factură.</li>
    <li><strong>Proformă</strong>, <strong>Aviz de însoțire</strong>, <strong>Încasare</strong> — tipuri distincte (încasarea emite chitanță/OP).</li>
    <li><strong>Factură storno</strong>, <strong>Notă de creditare</strong> — alegi o factură emisă; se creează documentul de corecție cu linii negative.</li>
    <li><strong>Factură recurentă</strong> — programare emitere automată.</li>
</ul>

<h3>Meniul Liste</h3>
<ul>
    <li>Facturi, Proforme, Avize, Chitanțe, Facturi storno, Note de creditare — filtre pe tip.</li>
    <li>Recurente — abonamentele de facturare programată.</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-list',
    'label' => 'Figura 2',
    'caption' => 'Lista documentelor emise — filtrare, deschidere, PDF și acțiuni ca butoane (Editează, PDF, Șterge…).',
])

<div class="help-note">
    În liste (facturi, recurente, clienți, produse etc.), acțiunile din dreapta apar ca <strong>butoane</strong>, nu ca link-uri text.
    Dacă lista are mai multe pagini, selectorul de pagini apare atât <strong>sus</strong>, cât și <strong>jos</strong>.
</div>
<h3>Catalog, Rapoarte, Ajutor</h3>
<ul>
    <li><strong>Catalog</strong> — Clienți; Produse și servicii.</li>
    <li><strong>Rapoarte</strong> — Vânzări și încasări; Clienți (solduri); Export CSV.</li>
    <li><strong>Ajutor</strong> — acest manual (cuprins și capitole cheie).</li>
</ul>

<h3>Meniul Setări</h3>
<p>
    Setările se deschid pe societatea activă. File tipice: Date generale, Sedii, Conturi bancare, Serii,
    Personalizare PDF, Cote TVA, e-Factura ANAF, <strong>Integrări</strong> (plată card per firmă),
    Limbi, Preferințe, Preferințe personale (limbă UI), Email / Notificări,
    <strong>Utilizatori</strong> și <strong>Abonament utilizatori</strong>
    (doar dacă ești proprietar de firmă — vezi
    <a href="{{ route('help.show', 'utilizatori') }}">Utilizatori</a>).
</p>
<div class="help-note">
    Dacă meniul Setări e lung, derulează lista din dropdown — toate opțiunile sunt acolo, inclusiv Integrări
    și Utilizatori (la final, în grupul Cont). Subuserii nu văd aceste două opțiuni.
</div>

@include('help._figure', [
    'shot' => 'nav-setari',
    'label' => 'Figura 3',
    'caption' => 'Meniul Setări — legături directe către filele de configurare ale firmei active.',
])

<h3>Societatea activă</h3>
<ol class="help-steps">
    <li>Identifică selectorul de societate din antet.</li>
    <li>Alege firma pe care vrei să lucrezi. Listele și formularul de emitere se filtrează după această alegere.</li>
    <li>Pentru administrarea tuturor firmelor, deschide Setări → Societățile mele.</li>
    <li>Folosește Activează / Configurează pentru a schimba contextul sau a edita datele.</li>
</ol>

<div class="help-warn">
    Documentele, seriile, clienții și produsele aparțin societății active. Dacă „dispar” datele, verifică mai întâi
    că nu ai schimbat accidental firma din selector.
</div>

<h3>Limba interfeței (UI)</h3>
<p>
    Limba UI schimbă meniurile, etichetele și mesajele din ecrane. Nu schimbă limba textelor din PDF-ul facturii.
    Limba documentului se alege pe fiecare factură (și se configurează la Setări → Limbi).
</p>

@include('help._figure', [
    'shot' => 'ui-locale',
    'label' => 'Figura 4',
    'caption' => 'Selectorul de limbă a interfeței — independent de limba PDF a documentului.',
])

<div class="help-note">
    Poți lucra în engleză sau germană în meniuri și totuși emite facturi în română pentru clienții din RO.
</div>

<h3>Navigare pe mobil</h3>
<ul>
    <li>Pe ecrane înguste meniul se compactează; aceleași secțiuni rămân accesibile din panouri.</li>
    <li>Pentru emitere și setări complexe, un ecran mai lat este mai confortabil.</li>
</ul>

<p>
    Următorul capitol: <a href="{{ route('help.show', 'dashboard') }}">Panou (Dashboard)</a>.
</p>
@endsection
