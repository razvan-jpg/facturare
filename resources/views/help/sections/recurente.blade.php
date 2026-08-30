@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Abonamentele recurente programează emiterea automată (sau generarea de draft) pe o frecvență aleasă:
    săptămânală, lunară, trimestrială, semestrială sau anuală. Ideal pentru abonamente, chirii și servicii lunare.
    La creare alegi dacă emiți <strong>facturi fiscale</strong> sau <strong>proforme</strong>.
</p>

@include('help._figure', [
    'shot' => 'recurring',
    'label' => 'Figura 1',
    'caption' => 'Lista abonamentelor — tip document (factură/proformă), serie (următorul număr se rezervă la emitere), stare și următoarea emitere.',
])

<h3>Creare abonament recurent</h3>
<ol class="help-steps">
    <li>Emite → <strong>Factură recurentă</strong> (sau Liste → Recurente → creare).</li>
    <li>Completează <strong>Denumire abonament</strong> (ex. „Mentenanță lunară — Client X”).</li>
    <li>Alege <strong>clientul</strong> (obligatoriu).</li>
    <li>Alege <strong>Tip document emis</strong>: Factură fiscală sau Proformă — și seria aferentă.</li>
    <li>Selectează frecvența: Săptămânală, Lunară, Trimestrială, Semestrială, Anuală.</li>
    <li>Setează <strong>Data emiterii primei facturi</strong> și <strong>Data emiterii următoarei facturi</strong>
        (la creare se aliniază automat; la editare poți muta doar următoarea emitere).
        Dacă debifezi <strong>Abonament activ</strong>, data următoarei emiteri și scadența se golesc;
        abonamentul se salvează în listă cu status <strong>Inactiv</strong> și nu emite facturi.</li>
    <li>Configurează termenul de plată — scadența afișată e calculată față de
        <strong>data emiterii următoarei facturi</strong> (nu față de data primei).</li>
    <li>Completează liniile de produse ca pe o factură (denumire + descriere opțională).</li>
    <li>Opțional: folosește <strong>Variabile</strong> (#luna#, #an#) în denumire, descriere sau observații.</li>
    <li>Bifează <strong>Emite automat</strong> dacă vrei document emis (nu doar draft).</li>
    <li>Lasă <strong>Abonament activ</strong> bifat și salvează.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-lines',
    'label' => 'Figura 2',
    'caption' => 'Liniile abonamentului definesc ce se va copia pe fiecare document generat.',
])

<h3>Variabile (#luna#, #an#)</h3>
<p>
    Variabilele se actualizează automat la emiterea facturii, după data de emitere.
    Apasă <strong>Variabile</strong> lângă linii / observații, plasează cursorul în câmp și alege
    <strong>[Luna]</strong> sau <strong>[An]</strong>, ori scrie manual.
</p>
<ul>
    <li><code>Abonament #luna# #an#</code> → <em>Abonament august 2026</em></li>
    <li><code>Servicii #luna-2# - #luna# #an#</code> → <em>Servicii iunie - august 2026</em></li>
    <li><code>Avans luna #luna+1#</code> → <em>Avans luna septembrie</em></li>
</ul>
<div class="help-note">
    Funcționează și offset-uri: <code>#luna+1#</code>, <code>#luna-2#</code>, <code>#an+1#</code>.
</div>

<h3>Pauză, generare manuală, ștergere</h3>
<ul>
    <li><strong>Dezactivează / Activează</strong> (sau debifează <strong>Abonament activ</strong> la editare) —
        oprește emiterea automată, golește „Următoarea emitere” și afișează status <strong>Inactiv</strong> (roșu) în listă.
        La reactivare se completează din nou o dată (azi sau data de start, dacă e în viitor).</li>
    <li><strong>Preview factură</strong> — PDF al următorului document (inclusiv penalități nefacturate dacă pe client e activat „Se calculeaza / factureaza”), fără salvare și fără avansarea datei.</li>
    <li><strong>Generează acum</strong> — forțează o generare imediată (util pentru teste sau emisii anticipate).</li>
    <li><strong>Șterge abonamentul</strong> — elimină programarea; facturile deja emise rămân în liste.</li>
</ul>

<div class="help-note">
    După salvare (creare sau editare) revii în lista de abonamente. Dacă erai pe pagina 2 (sau alta),
    rămâi pe aceeași pagină. Când lista are mai multe pagini, selectorul de pagini apare atât sus, cât și jos.
</div>

<div class="help-note">
    Verifică „Următoarea emitere” după salvare. Dacă data a trecut și abonamentul e activ, generarea
    (manuală sau automată, după job-urile serverului) ar trebui să producă documentul.
</div>

<div class="help-note">
    Dacă abonamentul emite <strong>proforme</strong>, la marcarea proformei ca încasată (integral) se emite automat
    factura fiscală, iar e-Factura urmează termenul din Setări → e-Factura.
</div>

<div class="help-note">
    În listă vezi <strong>Tip</strong> (factură fiscală / proformă) și <strong>Serie</strong>, cu preview
    „urm. SERIE-0001”. Numărul nu e blocat pe abonament — se rezervă abia când se generează documentul.
</div>

<div class="help-warn">
    Abonamentele folosesc societatea activă, seriile și setările curente la momentul generării.
    Dacă schimbi prețul în catalog, actualizează și liniile abonamentului dacă vrei noul preț pe viitor.
</div>

@include('help._figure', [
    'shot' => 'documents-list',
    'label' => 'Figura 3',
    'caption' => 'Documentele generate din recurente apar în Liste (Facturi sau Proforme), după tipul ales.',
])

@include('help._figure', [
    'shot' => 'nav-emite',
    'label' => 'Figura 4',
    'caption' => 'Acces rapid: Emite → Factură recurentă pentru un abonament nou.',
])

<h3>Când se emit automat</h3>
<p>
    În zilele în care există abonamente scadente, emiterea automată rulează <strong>între 04:00 și 10:00</strong>
    (ora României). Facturile și proformele programate pentru ziua respectivă sunt generate în acest interval.
</p>
<div class="help-note">
    După închiderea intervalului (~10:25, ora României), platforma:
    <ol style="margin:8px 0 0 18px;padding:0;">
        <li>verifică dacă emailurile către beneficiari (unde e bifat pe recurentă) au fost trimise;</li>
        <li>la netrimitere: anunță cauza (echipa DateConta) și reîncearcă de până la 3 ori, cu CC <strong>facturare@fly-david.ro</strong>;</li>
        <li>trimite apoi raportul PDF (toate societățile) cu starea email + e-Factura.</li>
    </ol>
</div>

<h3>e-Factura și recurente</h3>
<p>
    Dacă modul de trimitere e-Factura este „la salvarea facturii” sau după N zile, facturile generate automat
    urmează aceleași reguli ca cele manuale — cu condiția ca SPV să fie autorizat pe societate.
</p>

<p>
    Capitol esențial pentru obligația de raportare: <a href="{{ route('help.show', 'efactura') }}">e-Factura ANAF</a>.
</p>
@endsection
