@extends(($embed ?? false) ? 'legal.embed' : 'legal._layout')

@section('legal')
<h2>{{ __($meta['title']) }}</h2>
<p class="help-lead">{{ __($meta['subtitle']) }}</p>
<p class="help-meta-line">{{ __('Ultima actualizare: :date · Versiune document: :version', ['date' => \Illuminate\Support\Carbon::parse($meta['updated'])->format('d.m.Y'), 'version' => '2.0']) }}</p>

<div class="help-note">
    {!! __('Această <strong>Politică de confidențialitate</strong> descrie modul în care <strong>:operator</strong> („Operatorul”, „noi”) prelucrează date cu caracter personal în legătură cu platforma <strong>:brand</strong>. Se citește împreună cu', ['operator' => e($operator['name']), 'brand' => e($brand)]) !!}
    <a href="{{ route('legal.show', 'gdpr') }}">{{ __('Politica GDPR') }}</a> {{ __('și') }}
    <a href="{{ route('legal.show', 'termeni') }}">{{ __('Termenii de folosire') }}</a>.
</div>

<p>
    {!! __('<strong>Identitate Operator:</strong> :name, CUI :cui, :reg_com, :address, :city, :county, :country. Contact confidențialitate: <a href="mailto::email">:email</a>.', [
        'name' => e($operator['name']),
        'cui' => e($operator['cui']),
        'reg_com' => e($operator['reg_com']),
        'address' => e($operator['address']),
        'city' => e($operator['city']),
        'county' => e($operator['county']),
        'country' => e($operator['country']),
        'email' => e($contact),
    ]) !!}
</p>

<h3>{{ __('1. Domeniul de aplicare') }}</h3>
<p>{{ __('Politica acoperă:') }}</p>
<ul>
    <li>{{ __('vizitarea paginilor publice (landing, legal, înregistrare);') }}</li>
    <li>{{ __('crearea și folosirea Contului pe factura.dateconta.ro;') }}</li>
    <li>{{ __('comenzile de abonament și plățile aferente;') }}</li>
    <li>{{ __('comunicările de suport și notificările operaționale;') }}</li>
    <li>{{ __('prelucrarea tehnică a datelor pe care le introduci despre clienții / partenerii tăi în Aplicație.') }}</li>
</ul>
<p>
    {{ __('Nu reglementează site-urile Terților (ANAF, NETOPIA, bănci) pe care le accesezi prin redirect — acelea au propriile politici.') }}
</p>

<h3>{{ __('2. Categorii de date prelucrate') }}</h3>
<h4>{{ __('2.1. Date de Cont (Utilizator)') }}</h4>
<table class="legal-table">
    <thead><tr><th>{{ __('Categorie') }}</th><th>{{ __('Exemple') }}</th><th>{{ __('Sursă') }}</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Identificare') }}</td><td>{{ __('nume și prenume, email') }}</td><td>{{ __('furnizate de tine la înregistrare / profil') }}</td></tr>
        <tr><td>{{ __('Autentificare') }}</td><td>{{ __('parolă (hash), token sesiune, „remember me”') }}</td><td>{{ __('generate / derivate tehnic') }}</td></tr>
        <tr><td>{{ __('Abonament') }}</td><td>{{ __('plan, access_until, trial_ends_at, istoric comenzi') }}</td><td>{{ __('sistem + plăți') }}</td></tr>
        <tr><td>{{ __('Preferințe') }}</td><td>{{ __('limbă UI, societate activă, setări notificare') }}</td><td>{{ __('setări în app') }}</td></tr>
        <tr><td>{{ __('Suport') }}</td><td>{{ __('conținutul mesajelor trimise la :contact', ['contact' => $contact]) }}</td><td>{{ __('tu') }}</td></tr>
    </tbody>
</table>

<h4>{{ __('2.2. Date de Societate (emitent)') }}</h4>
<ul>
    <li>{{ __('denumire, CUI/CIF, reg. com., adresă, județ, localitate, țară;') }}</li>
    <li>{{ __('telefon, email(uri), IBAN, bancă, sedii;') }}</li>
    <li>{{ __('serii documente, cote TVA, șabloane PDF, limbi;') }}</li>
    <li>{{ __('date e-Factura: stare autorizare SPV, token OAuth (criptat/protejat), CIF asociat, mod trimitere;') }}</li>
    <li>{{ __('cod promoțional al Societății, legături de recomandare.') }}</li>
</ul>

<h4>{{ __('2.3. Date ale clienților tăi finali (introduse de tine)') }}</h4>
<ul>
    <li>{{ __('persoane juridice: denumire, CUI, reg. com., adresă, contacte, IBAN;') }}</li>
    <li>{{ __('persoane fizice: nume, CNP (dacă îl introduci) sau marcaj fără CNP, adresă, email, telefon;') }}</li>
    <li>{{ __('istoric Documente, solduri, restanțe, adrese de email pentru trimitere factură.') }}</li>
</ul>
<p>
    {!! __('<strong>Important:</strong> pentru aceste date ești, de regulă, <em>operator</em> în sens GDPR, iar noi suntem <em>împuternicit</em>. Tu stabilesti ce date introduci și cui trimiți Documentele.') !!}
</p>

<h4>{{ __('2.4. Date de Document și financiare în app') }}</h4>
<ul>
    <li>{{ __('linii de produse/servicii, cantități, prețuri, TVA, monedă, curs;') }}</li>
    <li>{{ __('PDF-uri, XML UBL, status e-Factura, încasări, mențiuni, delegat, contract, aviz;') }}</li>
    <li>{{ __('nu stocăm numărul complet al cardului bancar folosit la plata Abonamentului Platformei.') }}</li>
</ul>

<h4>{{ __('2.5. Date tehnice și de jurnal') }}</h4>
<ul>
    <li>{{ __('adresă IP, user-agent, timestamp-uri de acces;') }}</li>
    <li>{{ __('jurnale erori aplicație, rate-limiting, semnale de securitate;') }}</li>
    <li>{{ __('cookie-uri / stocare locală esențiale pentru sesiune.') }}</li>
</ul>

<h3>{{ __('3. Scopuri, temeiuri și necesitate') }}</h3>
<table class="legal-table">
    <thead><tr><th>{{ __('Scop') }}</th><th>{{ __('Temei GDPR') }}</th><th>{{ __('De ce e necesar') }}</th></tr></thead>
    <tbody>
        <tr>
            <td>{{ __('Creare Cont și autentificare') }}</td>
            <td>{{ __('Art. 6 (1) lit. b — contract') }}</td>
            <td>{{ __('Fără aceste date nu putem furniza Serviciul') }}</td>
        </tr>
        <tr>
            <td>{{ __('Furnizare SaaS, stocare Documente') }}</td>
            <td>{{ __('Art. 6 (1) lit. b') }}</td>
            <td>{{ __('Executarea funcțiilor facturare') }}</td>
        </tr>
        <tr>
            <td>{{ __('Prelucrare ca împuternicit a datelor clienților tăi') }}</td>
            <td>{{ __('Art. 6 (1) lit. b (față de tine) + instrucțiunile tale') }}</td>
            <td>{{ __('Emitere / trimitere Documente') }}</td>
        </tr>
        <tr>
            <td>{{ __('Plăți abonament, facturare Operator') }}</td>
            <td>{{ __('Art. 6 (1) lit. b și lit. c') }}</td>
            <td>{{ __('Contract + obligații fiscale') }}</td>
        </tr>
        <tr>
            <td>{{ __('Notificări expirare, status comenzi') }}</td>
            <td>{{ __('Art. 6 (1) lit. b / f') }}</td>
            <td>{{ __('Informare operațională esențială') }}</td>
        </tr>
        <tr>
            <td>{{ __('Securitate, prevenirea fraudei') }}</td>
            <td>{{ __('Art. 6 (1) lit. f — interes legitim') }}</td>
            <td>{{ __('Protejarea Platformei și a utilizatorilor') }}</td>
        </tr>
        <tr>
            <td>{{ __('Îmbunătățiri produs (agregate/anonimizate)') }}</td>
            <td>{{ __('Art. 6 (1) lit. f') }}</td>
            <td>{{ __('Stabilitate și UX — fără profilare de marketing invazivă') }}</td>
        </tr>
        <tr>
            <td>{{ __('Marketing opțional (dacă există)') }}</td>
            <td>{{ __('Art. 6 (1) lit. a — consimțământ') }}</td>
            <td>{{ __('Doar dacă bifezi / te înscrii explicit') }}</td>
        </tr>
        <tr>
            <td>{{ __('Răspuns la autorități') }}</td>
            <td>{{ __('Art. 6 (1) lit. c') }}</td>
            <td>{{ __('Obligație legală') }}</td>
        </tr>
    </tbody>
</table>

<h3>{{ __('4. Interese legitime') }}</h3>
<p>{{ __('Când ne bazăm pe art. 6 (1) lit. f, interesul legitim include:') }}</p>
<ul>
    <li>{{ __('asigurarea securității IT și a continuității Serviciului;') }}</li>
    <li>{{ __('prevenirea abuzului de Conturi / promoții;') }}</li>
    <li>{{ __('apărarea drepturilor în eventualitatea unui litigiu;') }}</li>
    <li>{{ __('statistici agregate de utilizare (fără a vinde profiluri individuale).') }}</li>
</ul>
<p>{{ __('Poți obiecta la prelucrările bazate pe interes legitim scriind la :contact, subiect „Opoziție GDPR”.', ['contact' => $contact]) }}</p>

<h3>{{ __('5. Destinatari și categorii de destinatari') }}</h3>
<ol>
    <li>{!! __('<strong>NETOPIA Payments</strong> — procesare plăți card; primește datele necesare tranzacției.') !!}</li>
    <li>{!! __('<strong>Furnizorul de hosting / infrastructură</strong> — stocare server, backup.') !!}</li>
    <li>{!! __('<strong>Servicii de email</strong> — livrare mesaje operaționale (și SMTP-ul tău, dacă îl configurezi pentru Documente).') !!}</li>
    <li>{!! __('<strong>ANAF / SPV</strong> — doar când tu inițiezi e-Factura și ești autorizat.') !!}</li>
    <li>{!! __('<strong>Consultanți</strong> (contabil / avocat) — sub confidențialitate, dacă e necesar pentru Operator.') !!}</li>
    <li>{!! __('<strong>Autorități publice</strong> — când există temei legal.') !!}</li>
</ol>
<p>{{ __('Nu vindem și nu închiriem liste de emailuri sau baze de clienți către brokeri de date.') }}</p>

<h3>{{ __('6. Transferuri internaționale') }}</h3>
<p>
    {{ __('Infrastructura este orientată către SEE. Dacă un subprocessator transferă date în afara SEE, ne asigurăm că există garanții adecvate (decizie de adecvare, clauze contractuale standard ale Comisiei Europene sau alte mecanisme GDPR). La cerere, putem indica categoriile de garanții folosite.') }}
</p>

<h3>{{ __('7. Durate de stocare') }}</h3>
<table class="legal-table">
    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Durată orientativă') }}</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Cont activ') }}</td><td>{{ __('Pe durata relației + termene legale ulterioare') }}</td></tr>
        <tr><td>{{ __('Comenzi / plăți abonament') }}</td><td>{{ __('Conform Codului fiscal / arhivare (de regulă 5–10 ani pentru evidențe)') }}</td></tr>
        <tr><td>{{ __('Documente emise de tine') }}</td><td>{{ __('Cât timp Contul/Societatea există; tu poți șterge/exporta; arhivarea ta legală rămâne responsabilitatea ta') }}</td></tr>
        <tr><td>{{ __('Jurnale tehnice') }}</td><td>{{ __('De la câteva săptămâni la maxim ~12 luni, dacă nu există incident') }}</td></tr>
        <tr><td>{{ __('Cereri suport') }}</td><td>{{ __('Până la 36 luni de la închiderea tichetului, dacă nu există litigiu') }}</td></tr>
        <tr><td>{{ __('Cont șters la cerere') }}</td><td>{{ __('Ștergere / anonimizare în termen rezonabil, exceptând datele pe care legea ne obligă să le păstrăm') }}</td></tr>
    </tbody>
</table>

<h3>{{ __('8. Cookie-uri și tehnologii similare') }}</h3>
<h4>{{ __('8.1. Esențiale') }}</h4>
<ul>
    <li>{{ __('sesiune Laravel / autentificare;') }}</li>
    <li>{{ __('protecție CSRF;') }}</li>
    <li>{{ __('preferință limbă UI;') }}</li>
    <li>{{ __('stare interfață (meniuri) — unde e cazul.') }}</li>
</ul>
<h4>{{ __('8.2. Analiză / marketing') }}</h4>
<p>
    {{ __('Putem folosi instrumente de măsurare și publicitate (ex. Google Ads / gtag, Trafic.ro, bannere partener) doar după ce îți exprimi consimțământul prin bannerul de cookie-uri („Accept toate”). Poți alege „Doar esențiale” — atunci aceste scripturi rămân dezactivate. Consimțământul e stocat local (localStorage / cookie dc_consent) și poate fi resetat ștergând datele site-ului din browser.') }}
</p>
<p>
    {{ __('Pentru utilizatorii din Spațiul Economic European folosim Google Consent Mode: până la accept, stocarea publicitară și personalizarea anunțurilor rămân pe „denied”.') }}
</p>
<h4>{{ __('8.3. Control') }}</h4>
<p>
    {{ __('Poți șterge cookie-urile din browser. Blocarea celor esențiale poate împiedica login-ul.') }}
</p>

<h3>{{ __('9. Securitatea datelor') }}</h3>
<ul>
    <li>{{ __('HTTPS / TLS pe traficul web;') }}</li>
    <li>{{ __('parole hashuite (algoritmi moderni ai framework-ului);') }}</li>
    <li>{{ __('separare logică pe Societăți (autorizare pe company_id);') }}</li>
    <li>{{ __('antete de securitate (CSP, HSTS în producție etc.);') }}</li>
    <li>{{ __('acces intern limitat;') }}</li>
    <li>{{ __('backup-uri conform politicii de hosting;') }}</li>
    <li>{{ __('monitorizare erori și limitare request-uri abuzive.') }}</li>
</ul>
<p>
    {{ __('Nicio măsură nu elimină total riscul. Tu contribuie prin parole puternice, actualizarea browserului și neanunțarea imediată a incidentelor la :contact.', ['contact' => $contact]) }}
</p>

<h3>{{ __('10. Decizii automate și profilare') }}</h3>
<p>
    {{ __('Nu efectuăm profilare în scop de scoring de credit sau decizii automate cu efecte juridice semnificative. Logica de Abonament (expirare, promoție, trial) este bazată pe reguli transparente afișate în Cont.') }}
</p>

<h3>{{ __('11. Copii') }}</h3>
<p>
    {{ __('Serviciul se adresează profesioniștilor și persoanelor cu capacitate deplină. Nu colectăm în mod intenționat date ale minorilor sub 16 ani. Dacă aflăm că am colectat astfel de date, le vom șterge.') }}
</p>

<h3>{{ __('12. Drepturile tale — rezumat') }}</h3>
<p>
    {{ __('Acces, rectificare, ștergere, restricționare, opoziție, portabilitate, retragere consimțământ (unde e cazul), plângere la ANSPDCP. Procedura detaliată:') }}
    <a href="{{ route('legal.show', 'gdpr') }}">{{ __('Politica GDPR') }}</a>.
</p>

<h3>{{ __('13. Actualizări ale Politicii') }}</h3>
<p>
    {{ __('Putem modifica Politica pentru a reflecta schimbări de produs sau de lege. Data de la începutul paginii indică versiunea. Pentru modificări majore putem notifica în app sau pe emailul Contului.') }}
</p>

<h3>{{ __('14. Contact') }}</h3>
<p>
    {{ __('Solicitări confidențialitate / GDPR:') }}<br>
    {{ __('Email') }}: <a href="mailto:{{ $contact }}">{{ $contact }}</a><br>
    {{ __('Poștă') }}: {{ $operator['name'] }}, {{ $operator['address'] }}, {{ $operator['city'] }}, {{ $operator['county'] }}
</p>
@endsection
