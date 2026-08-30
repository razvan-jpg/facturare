@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Societatea (firma) este unitatea de lucru din DateConta Facturare. Toate documentele, seriile, clienții,
    produsele, setările PDF și autorizarea e-Factura aparțin unei societăți. Poți avea mai multe firme în același
    cont și comuta între ele din selectorul din antet.
</p>

@include('help._figure', [
    'shot' => 'settings-generale',
    'label' => 'Figura 1',
    'caption' => 'Setări → Date generale — denumire, CUI, reg. com., adresă, localitate, județ și preluare ANAF.',
])

<h3>Multi-societate: creare și activare</h3>
<ol class="help-steps">
    <li>Deschide lista de societăți (Setări → Societățile mele).</li>
    <li>Apasă <strong>Adaugă societate</strong> pentru o firmă nouă.</li>
    <li>Completează cel puțin denumirea. Ideal, completează CUI-ul și folosește preluarea ANAF.</li>
    <li>Salvează. Folosește <strong>Activează</strong> pentru a lucra pe firma respectivă.</li>
    <li>Apasă <strong>Configurează</strong> pentru a deschide filele de setări (generale, sedii, conturi, serii etc.).</li>
</ol>

<div class="help-note">
    La crearea societății, aplicația pregătește de obicei serii implicite pe tipuri (ex. FCT, PRF, AVZ, CHT)
    pentru anul curent. Verifică-le totuși înainte de prima emitere.
</div>

<h3>Date generale și preluare ANAF după CUI</h3>
<p>
    În fila <strong>Date generale</strong> (Setări → Generale) completezi identitatea fiscală a firmei.
    Câmpurile tipice: denumire, CUI, număr Reg. Com., adresă, localitate, județ, țară, telefon, indicator plătitor TVA.
</p>

<h4>Cum folosești Caută după CUI (ANAF)</h4>
<ol class="help-steps">
    <li>Introdu CUI-ul (cu sau fără prefix RO, în funcție de ce acceptă formularul).</li>
    <li>Apasă <strong>Preluare date</strong> / Caută după CUI (ANAF).</li>
    <li>Aplicația completează denumirea, CUI-ul, Reg. Com., telefonul (dacă există) și indicatorul de TVA.</li>
    <li>
        <strong>Adresa</strong> primită din ANAF se pune în câmpul de stradă/adresă — <em>fără</em> localitate și județ
        în același text. Localitatea și județul se completează în câmpurile lor separate.
    </li>
    <li>Verifică județul din listă (select „— selectează județul —” dacă lipsește) și salvează.</li>
</ol>

@include('help._figure', [
    'shot' => 'settings-generale',
    'label' => 'Figura 2',
    'caption' => 'După preluarea ANAF: verifică adresa, localitatea și județul pe câmpuri distincte înainte de salvare.',
])

<div class="help-warn">
    Nu lipi județul sau orașul în câmpul Adresă. PDF-ul și e-Factura folosesc structura pe câmpuri;
    datele amestecate pot apărea greșit pe document sau în validări.
</div>

<h3>Sedii / puncte de lucru</h3>
<p>
    În fila <strong>Sedii</strong> poți defini sediul social și eventuale puncte de lucru, fiecare cu adresă,
    localitate și județ. Folosește sediile când ai mai multe locații de pe care livrezi sau facturezi.
</p>
<ul>
    <li>Completează denumirea sediului (ex. „Sediul social”, „Depozit Nord”).</li>
    <li>Păstrează localitatea și județul separate de linia de adresă, ca la datele generale.</li>
    <li>Salvează după fiecare adăugare sau modificare.</li>
</ul>

<h3>Conturi bancare</h3>
<ol class="help-steps">
    <li>Deschide Setări → <strong>Conturi bancare</strong>.</li>
    <li>Adaugă un cont: IBAN, monedă (RON/EUR etc.), denumire bancă (adesea se completează automat din IBAN).</li>
    <li>Bifează <strong>Pe factură</strong> pentru conturile care trebuie tipărite pe PDF.</li>
    <li>Poți marca maximum <strong>3</strong> conturi „Pe factură”. Dacă ai nevoie de mai multe în evidență, lasă restul nebifate.</li>
    <li>Salvează. Verifică pe o factură draft/PDF că apar IBAN-urile dorite.</li>
</ol>

<div class="help-note">
    Ordinea și selecția conturilor „Pe factură” influențează ce vede clientul. Păstrează contul principal RON bifat.
</div>

<h3>Cote TVA</h3>
<p>
    În fila <strong>Cote TVA</strong> indici dacă firma este plătitoare sau neplătitoare de TVA și cota implicită
    folosită pe linii. Cotele disponibile pe factură țin cont de această configurare. Ajustează înainte de a emite
    volume mari de documente, ca să nu corectezi linie cu linie.
</p>

<h3>Preferințe generale ale firmei</h3>
<p>
    Fila <strong>Preferințe</strong> (preferințe generale) controlează ce elemente apar pe documente
    (ex. afișare CUI, Reg. Com., bănci) și zilele implicite de scadență. Aceste setări se aplică la documentele noi
    ale societății; documentele deja emise nu se rescriu automat.
</p>

@include('help._figure', [
    'shot' => 'settings-preferinte',
    'label' => 'Figura 3',
    'caption' => 'Preferințe generale — ce apare pe documente și valori implicite de scadență.',
])

<h3>Limbi documente PDF</h3>
<p>
    La Setări → <strong>Limbi</strong> activezi limbile în care poți genera PDF-ul facturii (româna este mereu disponibilă).
    Limba aleasă pe document este independentă de limba interfeței (UI). Detalii în
    <a href="{{ route('help.show', 'preferinte') }}">Preferințe și limbi</a> și
    <a href="{{ route('help.show', 'pdf-personalizare') }}">PDF și personalizare</a>.
</p>

@include('help._figure', [
    'shot' => 'settings-limbi',
    'label' => 'Figura 4',
    'caption' => 'Setări → Limbi — limbile disponibile pentru PDF, nu pentru meniurile aplicației.',
])

<h3>Integrări — plată cu cardul</h3>
<p>
    În Setări → <strong>Integrări</strong> configurezi NETOPIA / Eu Plătesc / Mollie / Stripe <strong>pentru firma activă</strong>
    (fiecare societate are propriile chei). Detalii pas cu pas:
    <a href="{{ route('help.show', 'plata-card') }}">Plată cu cardul online</a>.
</p>

<h3>e-Factura — legătura cu societatea</h3>
<p>
    Autorizarea SPV se face pe CUI-ul societății. Completează CUI-ul corect în Date generale înainte de
    Setări → e-Factura ANAF. Fără CUI, fluxul OAuth nu poate fi asociat firmei. Capitol dedicat:
    <a href="{{ route('help.show', 'efactura') }}">e-Factura ANAF</a>.
</p>

@include('help._figure', [
    'shot' => 'settings-efactura',
    'label' => 'Figura 5',
    'caption' => 'Fila e-Factura — stare autorizare SPV și modul de trimitere pentru societatea curentă.',
])

<h3>Email — text și server propriu</h3>
<p>
    În Setări → <strong>Email</strong> configurezi mesajul trimis clienților și, opțional, serverul SMTP al firmei.
</p>
<ol class="help-steps">
    <li>La <strong>Text email</strong> setezi subiectul și mesajul. Folosește termenii variabili din panoul din dreapta (ex. Tip document, Total, Nume client) — se inserează ca <code>#tip document#</code>.</li>
    <li>La trimiterea documentului pe email, variabilele se înlocuiesc automat cu datele din factură.</li>
    <li>La <strong>Server email</strong> bifează „Vreau să folosesc serverul meu de email” dacă vrei ca mesajele să plece din contul tău SMTP (host, port, TLS, utilizator, parolă).</li>
    <li>Fără server propriu, aplicația folosește trimiterea DateConta.</li>
</ol>

<div class="help-note">
    Parola SMTP se păstrează criptat. La modificare ulterioară, lasă câmpul parolă gol dacă nu vrei să o schimbi.
</div>

<h3>Personalizare vizuală (rezumat)</h3>
<p>
    Logo, semnătură, ștampilă, culoare și machetă se configurează la <strong>Personalizare PDF</strong>.
    Fișierele acceptate sunt de tip imagine (JPEG/PNG/WebP), cu limită de dimensiune (de regulă 2 MB).
    Vezi capitolul PDF pentru pași detaliați.
</p>

@include('help._figure', [
    'shot' => 'settings-personalizare',
    'label' => 'Figura 6',
    'caption' => 'Personalizare PDF — logo, semnătură, ștampilă și alegerea machetei.',
])

<h3>Checklist înainte de prima factură</h3>
<ol class="help-steps">
    <li>Denumire + CUI + Reg. Com. corecte (verificate ANAF).</li>
    <li>Adresă / localitate / județ pe câmpuri separate.</li>
    <li>Cel puțin un IBAN „Pe factură”.</li>
    <li>Serii active pe anul curent pentru tipul Factură.</li>
    <li>Cotă TVA / regim TVA aliniat cu situația reală a firmei.</li>
    <li>Opțional: logo + machetă PDF; autorizare SPV dacă trimiți e-Factura.</li>
</ol>

<div class="help-warn">
    Modificările din setări afectează documentele noi și PDF-urile regenerate ulterior.
    Facturile deja trimise în e-Factura nu trebuie „cosmetizate” în loc să faci storno / corecție conform regulilor legale.
</div>

<p>
    Urmează configurarea numerelor: <a href="{{ route('help.show', 'serii-documente') }}">Serii și numerotare</a>.
</p>
@endsection
