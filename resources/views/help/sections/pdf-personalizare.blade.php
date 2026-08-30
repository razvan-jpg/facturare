@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Facturile și celelalte documente se generează ca PDF în format <strong>A4 portret</strong>.
    Din Setări → <strong>Personalizare PDF</strong> alegi macheta, culorile, logo-ul, semnătura, ștampila
    și textele de subsol. Limba etichetelor din PDF vine din „Limbă document” de pe fiecare factură.
</p>

@include('help._figure', [
    'shot' => 'settings-personalizare',
    'label' => 'Figura 1',
    'caption' => 'Setări → Personalizare PDF — machetă, culoare, logo, semnătură, ștampilă, notă pe factură.',
])

<h3>Elemente pe care le poți personaliza</h3>
<ul>
    <li><strong>Machetă</strong> — Clasic, Modern, Compact, Bold, Elegant, Stripe, plus Nord, Ledger, Studio, Frame, Swiss, Folio, Split, Ticket.
        Unele societăți (ex. cu machetă DateConta dedicată) au macheta <strong>fixă</strong> și nu pot alege altă variantă.</li>
    <li><strong>Culoare factură</strong> — accent de brand pe antet / evidențieri.</li>
    <li><strong>Logo firmă</strong> — imagine în antet (JPEG/PNG/WebP, de regulă max. 2 MB).</li>
    <li><strong>Semnătură (imagine)</strong> — opțional; pe PDF apare eticheta, imaginea și linia de semnare centrate sub poză.</li>
    <li><strong>Ștampilă</strong> — sub semnătură (unul sub altul), nu pe aceeași linie.
        La First Consulting, imaginea de ștampilă se așază peste eticheta „ȘTAMPILĂ”.</li>
    <li><strong>Dimensiune imagini</strong> — butoane <strong>+</strong> / <strong>−</strong> pe logo, semnătură și ștampilă (25%–200%, din 25 în 25). Redimensionează doar imaginile, nu macheta.</li>
    <li><strong>Text în loc de semnătură</strong> — până la câteva rânduri; poate include mențiunea legală tip art. 319 alin. 29 Legea 227/2015.</li>
    <li><strong>Notă pe factură</strong> — text de subsol / mențiune comercială.</li>
    <li><strong>Branding DateConta</strong> — textul „Document generat cu DateConta Facturare” din subsolul PDF este un link către pagina principală a aplicației (nu se poate dezactiva).</li>
</ul>

<h3>Pași de configurare</h3>
<ol class="help-steps">
    <li>Deschide Setări → <strong>Personalizare PDF</strong> pe societatea activă.</li>
    <li>Alege macheta care se potrivește brandului (testează pe o factură draft).</li>
    <li>Setează culoarea de accent.</li>
    <li>Încarcă logo-ul (fundal clar, fără fundal aglomerat) și ajustează dimensiunea cu + / −.</li>
    <li>Opțional: încarcă semnătura scanată și/sau ștampila; ajustează și pe acestea.</li>
    <li>Completează nota de subsol și textul legal de semnare dacă nu folosești imagine de semnătură.</li>
    <li>Apasă <strong>Salvează personalizarea</strong>.</li>
    <li>Deschide o factură emisă → PDF și verifică pe o pagină A4 (fără pagini goale înaintea conținutului).</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-show',
    'label' => 'Figura 2',
    'caption' => 'Din fișa documentului deschizi PDF-ul pentru a valida logo-ul, IBAN-urile și macheta.',
])

<div class="help-note">
    Personalizarea este pe societate. Fiecare firmă din cont poate avea logo și machetă proprii.
</div>

<h3>Ce apare din alte setări</h3>
<ul>
    <li>Conturile bifate „Pe factură” (max. 3) — din Conturi bancare.</li>
    <li>CUI, Reg. Com., denumire — din Date generale.</li>
    <li>Preferințele de afișare (ce câmpuri apar pe document) — din Preferințe generale.</li>
    <li>Limba etichetelor PDF — din limba documentului, nu din limba UI.</li>
</ul>

@include('help._figure', [
    'shot' => 'settings-limbi',
    'label' => 'Figura 3',
    'caption' => 'Limbile activate aici pot fi selectate ca limbă a documentului la emitere.',
])

<div class="help-warn">
    Fișierele prea mari sau într-un format neacceptat vor fi respinse la upload.
    Folosește PNG/JPEG/WebP optimizate; evită PDF-ul ca „logo”.
</div>

<h3>Bune practici vizuale</h3>
<ul>
    <li>Logo pe fundal transparent sau alb, înălțime moderată — ca să nu împingă tabelul de linii.</li>
    <li>Ștampilă semitransparentă, ca să nu acopere totalurile.</li>
    <li>După schimbarea machetei, regenerează PDF pe un document de test înainte de volume mari.</li>
</ul>

@include('help._figure', [
    'shot' => 'settings-preferinte',
    'label' => 'Figura 4',
    'caption' => 'Preferințele generale completează personalizarea: ce date de firmă apar pe document.',
])

<p>
    Continuă cu <a href="{{ route('help.show', 'rapoarte') }}">Rapoarte și export</a>.
</p>
@endsection
