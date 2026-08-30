@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    În DateConta Facturare există două tipuri distincte de „limbă” și mai multe preferințe:
    limba <strong>interfeței (UI)</strong>, limbile <strong>documentului PDF</strong>, preferințele
    generale ale firmei și preferințele personale ale utilizatorului.
</p>

@include('help._figure', [
    'shot' => 'ui-locale',
    'label' => 'Figura 1',
    'caption' => 'Selectorul de limbă UI — schimbă meniurile și etichetele ecranelor, nu PDF-ul facturii.',
])

<h3>Limba interfeței (UI)</h3>
<ol class="help-steps">
    <li>Folosește selectorul de limbă din antet (cu steag) — și pe pagina principală / site-ul public, în colțul din dreapta sus, fără să fii logat — sau Setări → <strong>Preferințe personale</strong>.</li>
    <li>Pe pagina principală, sub selector, apare bannerul <strong>„Noi vorbim pe limba ta!”</strong> — poți apăsa <strong>Alege limba ta</strong> ca să deschizi/focus-ezi selectorul; limba aleasă traduce site-ul pentru oaspeți.</li>
    <li>Pe site-ul public (pagina principală, prețuri, lansare, autentificare/înregistrare, documente legale), limba din selector traduce integral paginile, inclusiv pentru vizitatori nelogați — cu traduceri native pentru toate limbile din selector (nu doar engleză).</li>
    <li>În selector: <strong>Română</strong> prima, apoi <strong>English (US)</strong> și <strong>English (UK)</strong>,
        iar restul limbilor/variantelor sunt ordonate alfabetic (europeene, asiatice, africane, latino-americane, arabe regionale etc.).</li>
    <li>Meniurile, butoanele și mesajele interfeței se traduc. Facturile PDF rămân în limba documentului setată la emitere.</li>
    <li>Emailurile trimise din aplicație (documente, invitații, reminder-e etc.) folosesc <strong>limba de lucru</strong>
        a utilizatorului care a acționat (limba UI), nu limba PDF-ului — decât dacă ai text personalizat la Setări → Email.</li>
</ol>

<div class="help-note">
    Textul din UI spune explicit că limba interfeței <strong>nu afectează limba facturilor PDF</strong>.
    Poți lucra în engleză în meniuri și emite PDF în română.
</div>

<h3>Limbile documentelor PDF</h3>
<ol class="help-steps">
    <li>Deschide Setări → <strong>Limbi</strong>.</li>
    <li>Activează limbile pe care vrei să le oferi la emitere (româna este mereu disponibilă).</li>
    <li>La crearea facturii, câmpul <strong>Limbă document</strong> listează doar limbile activate.</li>
    <li>PDF-ul generat folosește etichetele în limba aleasă (Factură / Invoice etc.).</li>
</ol>

@include('help._figure', [
    'shot' => 'settings-limbi',
    'label' => 'Figura 2',
    'caption' => 'Setări → Limbi — activezi limbile disponibile pe documente.',
])

@include('help._figure', [
    'shot' => 'documents-create',
    'label' => 'Figura 3',
    'caption' => 'La emitere, „Limbă document” setează limba PDF independent de limba UI.',
])

<h3>Preferințe generale (firmă)</h3>
<p>
    Fila <strong>Preferințe</strong> controlează ce informații apar pe documente (CUI, Reg. Com., conturi bancare)
    și valori implicite precum zilele de scadență. Se aplică societății active, pentru documentele noi.
</p>

@include('help._figure', [
    'shot' => 'settings-preferinte',
    'label' => 'Figura 4',
    'caption' => 'Preferințe generale — afișare date pe document și implicite de scadență.',
])

<ol class="help-steps">
    <li>Deschide Setări → Preferințe (generale).</li>
    <li>Bifează / debifează ce trebuie tipărit pe factură.</li>
    <li>Setează zilele implicite de scadență dacă vrei un standard pe firmă.</li>
    <li>Alege <strong>documente pe pagină</strong> în liste (10 / 25 / 50 / 100).</li>
    <li>Salvează și emite un document de test pentru verificare.</li>
</ol>

<h3>Preferințe personale vs. setări firmă</h3>
<ul>
    <li><strong>Personale</strong> — limbă UI, opțiuni legate de contul tău (email/notificări unde există).</li>
    <li><strong>Firmă</strong> — serii, PDF, e-Factura, limbi document, cote TVA, conturi bancare.</li>
</ul>

<div class="help-warn">
    Schimbarea limbii UI nu „traduce” automat observațiile sau denumirile de produse pe care le-ai tastat tu.
    Traducerea automată acoperă etichetele de sistem din PDF, nu textele libere.
</div>

@include('help._figure', [
    'shot' => 'nav-setari',
    'label' => 'Figura 5',
    'caption' => 'Din meniul Setări accesezi atât preferințele personale, cât și filele societății.',
])

<p>
    Închide manualul cu <a href="{{ route('help.show', 'intrebari') }}">Întrebări frecvente</a>
    dacă ai nevoie de soluții rapide.
</p>
@endsection
