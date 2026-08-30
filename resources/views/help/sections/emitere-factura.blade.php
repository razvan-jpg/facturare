@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Emiterea unei facturi este fluxul central al DateConta Facturare. Parcurgi: alegerea clientului, datele
    documentului (dată, scadență, serie, monedă, limbă PDF), liniile de produse/servicii, apoi salvare ca draft
    sau emitere cu număr pe serie. După emitere poți descărca PDF-ul, înregistra încasări și trimite e-Factura.
</p>

@include('help._figure', [
    'shot' => 'nav-emite',
    'label' => 'Figura 1',
    'caption' => 'Meniul Emite → Factură — punctul de start pentru un document nou de tip factură.',
])

<h3>Înainte de a începe</h3>
<ul>
    <li>Societatea corectă este activă în antet.</li>
    <li>Există o serie activă de tip Factură pentru anul datei de emitere.</li>
    <li>Datele firmei (CUI, adresă, IBAN pe factură) sunt complete.</li>
    <li>Clientul există în catalog sau îi știi CUI-ul pentru preluare ANAF.</li>
</ul>

<div class="help-warn">
    Verifică societatea activă înainte de fiecare emitere. Documentul se salvează pe firma selectată;
    mutarea ulterioară între societăți nu este un flux standard.
</div>

<h3>Deschiderea formularului</h3>
<ol class="help-steps">
    <li>Din meniu: <strong>Emite → Factură</strong> (ruta tipică: /documents/create?type=invoice).</li>
    <li>Titlul paginii indică „Emitere › Factură”.</li>
    <li>Completează secțiunile de sus (client și antet), apoi liniile, apoi acțiunea de salvare.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-create',
    'label' => 'Figura 2',
    'caption' => 'Formularul de emitere — client, date document, serie, monedă, limbă și zona de linii.',
])

<h3>1. Clientul</h3>
<ol class="help-steps">
    <li>În câmpul client tastează numele sau CIF/CNP.</li>
    <li>Selectează clientul din sugestii.</li>
    <li>Dacă este firmă nouă: folosește <strong>+ ANAF</strong> / Preluare ANAF, verifică datele, apoi Adaugă pe factură.</li>
    <li>Pentru persoane: completează identificatorul (CNP sau „-”) conform politicii tale de date.</li>
</ol>

<div class="help-note">
    La preluarea ANAF, adresa vine fără localitate/județ în același câmp — acestea rămân pe câmpuri separate,
    exact ca la setările societății.
</div>

<h3>2. Observații, dată, termen de plată</h3>
<ul>
    <li><strong>Observații</strong> — text opțional (apare pe document în funcție de șablon).</li>
    <li><strong>Data emiterii</strong> — obligatorie; determină și anul seriei disponibile.</li>
    <li><strong>Termen de plată</strong> — alege din listă: fără termen, la data emiterii, 5/7/10/15/30/60/90 zile,
        ultima zi a lunii curente, sau o dată calendaristică anume. Data scadenței se calculează / completează corespunzător.</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-create',
    'label' => 'Figura 3',
    'caption' => 'Zona de antet: dată emitere, termen de plată și previzualizarea seriei.',
])

<h3>3. Serie și număr</h3>
<ol class="help-steps">
    <li>Selectează seria de factură (prefixul tău, ex. FCT).</li>
    <li>La deschiderea formularului se <strong>rezervă</strong> automat cel mai mic număr liber — inclusiv golurile rămase libere (ex. FCT-0003 dacă 0001–0002 sunt emise și 0003 a fost eliberat).</li>
    <li>Poți alege din listă un alt număr liber („liber (gol)” sau „următorul”). Rezervarea ține cont de alte sesiuni (web sau app).</li>
    <li>La emitere se folosește numărul rezervat; la renunțare (ciornă goală) sau după ~60 min fără activitate, numărul revine în lista de libere.</li>
</ol>

<div class="help-warn">
    Dacă apare mesajul că nu există serii active, oprește-te și creează seria din Setări → Serii pentru anul corect.
    Nu forța emiterea fără serie.
</div>

<h3>4. Monedă și curs valutar</h3>
<ul>
    <li>Alege moneda facturii: RON, EUR, USD, GBP (sau cele disponibile în formular).</li>
    <li>Dacă moneda ≠ RON, completează <strong>Curs valutar (RON)</strong>. Poți folosi sugestia de curs (ex. BNR) și o poți edita.</li>
    <li>Cursul este obligatoriu pentru monede străine; fără el nu poți finaliza corect documentul.</li>
</ul>

<div class="help-note">
    Totalurile pe PDF apar în moneda facturii; cursul asigură echivalentul în RON unde este necesar în evidență / e-Factura.
</div>

<h3>5. Limba documentului (PDF)</h3>
<p>
    Câmpul <strong>Limbă document</strong> controlează limba etichetelor și textelor din PDF.
    Este independent de limba interfeței (meniuri). Româna este mereu disponibilă; alte limbi depind de
    Setări → Limbi pentru societate.
</p>

@include('help._figure', [
    'shot' => 'ui-locale',
    'label' => 'Figura 4',
    'caption' => 'Limba UI (meniuri) nu înlocuiește limba documentului aleasă pe factură.',
])

<h3>6. Linii: produse și servicii</h3>
<ol class="help-steps">
    <li>Adaugă o linie nouă.</li>
    <li>În câmpul <strong>Produs</strong>, tastează și alege din autocomplete (sau introduci un nume nou).</li>
    <li>Completează cantitatea (≠ 0) și prețul unitar (tastare liberă). Pentru <strong>cota TVA</strong> alegi din listă una dintre cele 4 cote: <strong>21%</strong>, <strong>11%</strong>, <strong>5%</strong> sau <strong>0%</strong>.</li>
    <li><strong>Unitatea de măsură (UM)</strong> — listă live (buc, kg, m³…). Poți scrie o UM nouă (ex. palet); se adaugă în catalogul firmei. La XML e-Factura se folosește corespondența UNECE (H87, KGM…).</li>
    <li>Opțional: completează <strong>Descrierea</strong> liniei pentru detalii suplimentare.</li>
    <li>Repetă pentru fiecare poziție. Șterge liniile goale.</li>
    <li>Verifică subtotalul, TVA-ul și totalul din rezumatul formularului.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-lines',
    'label' => 'Figura 5',
    'caption' => 'Liniile facturii — produs, UM (listă live sau text nou), cantitate/preț, TVA, totaluri.',
])

<div class="help-warn">
    Validarea cere cel puțin o linie completă. Mesaje tipice: „Linia N: produsul e obligatoriu…” sau cerința
    de a completa / șterge liniile incomplete. Produsul nu poate lipsi; descrierea da.
</div>

<h3>7. Întocmit de și delegat</h3>
<ul>
    <li><strong>Întocmit de</strong> — text liber; poți scrie un nume sau alege din sugestii (utilizatori ai contului și valori folosite anterior pe documente).</li>
    <li><strong>Delegat</strong> — la fel, cu listă din istoric; dacă ai completat înainte și buletinul (CI), se poate precompleta.</li>
</ul>

<div class="help-note">
    Data emiterii folosește fusul orar <strong>Europe/Bucharest</strong> — o factură nouă nu mai apare cu o zi în urmă față de calendarul României.
</div>

<h3>8. Salvare draft vs. emitere</h3>
<ol class="help-steps">
    <li><strong>Salvează draft</strong> — păstrează ciorna pentru editare ulterioară; util când aștepți confirmări.</li>
    <li><strong>Salvează și emite</strong> — alocă numărul pe serie, trece documentul în stare Emisă.</li>
    <li>După salvare/emitere revii în <strong>lista tipului</strong> (Facturi, Proforme etc.), cu documentul recent sus.</li>
    <li>De pe listă poți deschide documentul: PDF, email, încasare, e-Factura, storno (în condiții).</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-show',
    'label' => 'Figura 6',
    'caption' => 'Pagina facturii emise — acțiuni PDF, încasare, e-Factura, anulare / storno după reguli.',
])

<h3>După emitere: PDF și email</h3>
<ul>
    <li>Descarcă sau deschide PDF-ul A4 (machetă, logo, semnătură conform Personalizare PDF).</li>
    <li>În subsolul PDF, textul „Document generat cu DateConta Facturare” este un link către pagina principală a aplicației.</li>
    <li>Trimite pe email clientului dacă ai adresa completată. Textul și serverul SMTP se configurează la Setări → Email.</li>
    <li>Verifică vizual IBAN-urile, CUI-ul și totalurile înainte de a trimite clientului.</li>
</ul>

@include('help._figure', [
    'shot' => 'settings-personalizare',
    'label' => 'Figura 7',
    'caption' => 'Aspectul PDF-ului se configurează o singură dată la Personalizare — apoi se aplică la emitere.',
])

<h3>Plată cu cardul pe factură (opțional)</h3>
<p>
    Dacă ai configurat un procesator în Setări → Integrări, bifează
    <strong>Permite plata cu cardul online</strong> în subsolul facturii.
    Pe PDF apar linkurile; clientul plătește pe site-ul procesatorului.
    Ghid: <a href="{{ route('help.show', 'plata-card') }}">Plată cu cardul online</a>.
</p>

<h3>Încasarea facturii (manual)</h3>
<p>
    De pe factura emisă folosește <strong>Înregistrează încasare</strong>: sumă (implicit restul), dată, metodă
    (ordin de plată, numerar, chitanță, card, alta), referință. Starea de plată trece la Parțial achitată sau Achitată.
    Detalii: <a href="{{ route('help.show', 'incasari') }}">Încasări</a>.
</p>

@include('help._figure', [
    'shot' => 'payments',
    'label' => 'Figura 8',
    'caption' => 'Încasările înregistrate pe facturi apar și în lista globală de plăți.',
])

<h3>Trimitere e-Factura</h3>
<p>
    Pentru facturi emise (și storno, după caz), dacă SPV este autorizat, poți apăsa <strong>Trimite e-Factura</strong>
    sau poți lăsa modul automat (la salvare / după N zile) din setările societății.
    Nu edita o factură deja încărcată / acceptată în ANAF — folosește storno când legislația o cere.
</p>

@include('help._figure', [
    'shot' => 'settings-efactura',
    'label' => 'Figura 9',
    'caption' => 'Modul de trimitere e-Factura se setează pe societate; pe document rămâne acțiunea manuală când e cazul.',
])

<h3>Editare, anulare, storno (rezumat)</h3>
<ul>
    <li><strong>Editează</strong> — draft sau emisă, dar nu dacă e deja trimisă/în prelucrare/acceptată e-Factura. Dacă nu salvezi, apasă <strong>Renunță</strong> ca să te întorci la fișa documentului fără modificări.</li>
    <li><strong>Anulează</strong> — doar emisă și neîncărcată în e-Factura (în regulile aplicației).</li>
    <li><strong>Stornează</strong> — pentru factură emisă, creează document cu linii negative; permis și după e-Factura, cu trimitere ulterioară a storno. Storno-ul și factura originală apar ambele ca <strong>Achitată</strong>.</li>
</ul>
<p>
    Detalii complete în <a href="{{ route('help.show', 'liste-documente') }}">Liste documente</a>.
</p>

@include('help._figure', [
    'shot' => 'documents-list',
    'label' => 'Figura 10',
    'caption' => 'Lista facturilor — găsești rapid draft-uri, emise, stări de plată și e-Factura.',
])

<h3>Checklist emitere fără erori</h3>
<ol class="help-steps">
    <li>Client corect + CUI verificat.</li>
    <li>Dată emitere + serie pe anul potrivit.</li>
    <li>Monedă + curs (dacă nu e RON).</li>
    <li>Limbă PDF potrivită clientului.</li>
    <li>Linii complete: produs, cantitate, preț, TVA; descrieri doar unde ajută.</li>
    <li>Draft → verificare → Emitere → PDF → (opțional) e-Factura → încasare.</li>
</ol>

<div class="help-note">
    Pentru facturi care se repetă lunar, configurează o <a href="{{ route('help.show', 'recurente') }}">factură recurentă</a>
    în loc să completezi manual același formular de fiecare dată.
</div>

<p>
    Alte tipuri de documente: <a href="{{ route('help.show', 'alte-documente') }}">Proforme, avize, chitanțe</a>.
</p>
@endsection
