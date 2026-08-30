@extends(($embed ?? false) ? 'legal.embed' : 'legal._layout')

@section('legal')
<h2>{{ __($meta['title']) }}</h2>
<p class="help-lead">{{ __($meta['subtitle']) }}</p>
<p class="help-meta-line">{{ __('Ultima actualizare: :date · Versiune document: :version', ['date' => \Illuminate\Support\Carbon::parse($meta['updated'])->format('d.m.Y'), 'version' => '2.0']) }}</p>

<div class="help-note">
    {!! __('Prezenta <strong>Politică GDPR</strong> descrie cadrul de conformare al <strong>:operator</strong> pentru platforma <strong>:brand</strong>, drepturile persoanelor vizate și modul de exercitare. Se completează cu', ['operator' => e($operator['name']), 'brand' => e($brand)]) !!}
    <a href="{{ route('legal.show', 'confidentialitate') }}">{{ __('Politica de confidențialitate') }}</a>.
    {{ __('Contact:') }} <a href="mailto:{{ $contact }}">{{ $contact }}</a>.
</div>

<h3>{{ __('1. Cadru normativ') }}</h3>
<ul>
    <li>{{ __('Regulamentul (UE) 2016/679 (GDPR);') }}</li>
    <li>{{ __('Legea nr. 190/2018 privind măsuri de punere în aplicare a GDPR;') }}</li>
    <li>{{ __('Legea nr. 506/2004 (prelucrări în sectorul comunicațiilor electronice), unde e aplicabilă;') }}</li>
    <li>{{ __('alte acte speciale (fiscalitate, e-Factura) care impun păstrarea unor evidențe.') }}</li>
</ul>
<p>{!! __('Autoritatea de supraveghere: <strong>ANSPDCP</strong> — <a href=":url" target="_blank" rel="noopener">www.dataprotection.ro</a>.', ['url' => 'https://www.dataprotection.ro']) !!}</p>

<h3>{{ __('2. Identitatea operatorului (pentru datele de Cont)') }}</h3>
<p>
    <strong>{{ $operator['name'] }}</strong><br>
    {{ __('CUI') }} {{ $operator['cui'] }} · {{ $operator['reg_com'] }}<br>
    {{ $operator['address'] }}, {{ $operator['city'] }}, {{ $operator['county'] }}, {{ $operator['country'] }}<br>
    {{ __('Email') }}: {{ $contact }}
</p>
<p>
    {{ __('Nu am desemnat în mod obligatoriu un DPO public separat; cererile GDPR se trimit la adresa de mai sus cu subiectul „GDPR — cerere persoană vizată”. Dacă legislația sau volumul prelucrărilor o vor impune, vom actualiza datele de contact DPO pe această pagină.') }}
</p>

<h3>{{ __('3. Hartă a rolurilor') }}</h3>
<table class="legal-table">
    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Tu') }}</th><th>{{ __('Noi (:name)', ['name' => $operator['name']]) }}</th></tr></thead>
    <tbody>
        <tr>
            <td>{{ __('Nume, email Cont, abonament, IP login') }}</td>
            <td>{{ __('Persoană vizată') }}</td>
            <td>{!! __('<strong>Operator</strong>') !!}</td>
        </tr>
        <tr>
            <td>{{ __('Date Societate pe care o administrezi') }}</td>
            <td>{{ __('Operator / persoană împuternicită a firmei tale') }}</td>
            <td>{{ __('Împuternicit față de tine pentru stocare în SaaS') }}</td>
        </tr>
        <tr>
            <td>{{ __('Clienți, CNP/CUI, facturi pe care le emiți') }}</td>
            <td>{!! __('<strong>Operator</strong> față de clienții finali') !!}</td>
            <td>{!! __('<strong>Împuternicit (procesator)</strong>') !!}</td>
        </tr>
        <tr>
            <td>{{ __('XML trimis la ANAF la cererea ta') }}</td>
            <td>{{ __('Operator / responsabil conținut') }}</td>
            <td>{{ __('Facilitator tehnic / împuternicit') }}</td>
        </tr>
        <tr>
            <td>{{ __('Date card la NETOPIA') }}</td>
            <td>{{ __('Persoană vizată față de procesator') }}</td>
            <td>{{ __('Nu stocăm PAN; NETOPIA are rol propriu de operator/procesator conform politicii lor') }}</td>
        </tr>
    </tbody>
</table>

<h3>{{ __('4. Principiile GDPR pe care le urmărim') }}</h3>
<ol>
    <li>{!! __('<strong>Legalitate, echitate, transparență</strong> — informare prin politici + UI.') !!}</li>
    <li>{!! __('<strong>Limitarea scopului</strong> — nu folosim datele Contului pentru scopuri incompatibile.') !!}</li>
    <li>{!! __('<strong>Minimizarea datelor</strong> — cerem doar ce e necesar Contului; tu controlezi ce introduci despre clienți.') !!}</li>
    <li>{!! __('<strong>Exactitate</strong> — poți edita profilul și nomenclatoarele.') !!}</li>
    <li>{!! __('<strong>Limitarea stocării</strong> — vezi duratele din Politica de confidențialitate.') !!}</li>
    <li>{!! __('<strong>Integritate și confidențialitate</strong> — măsuri tehnice/organizatorice.') !!}</li>
    <li>{!! __('<strong>Responsabilitate (accountability)</strong> — evidențe, politici, proceduri de răspuns la cereri.') !!}</li>
</ol>

<h3>{{ __('5. Temeiuri legale (rezumat operațional)') }}</h3>
<ul>
    <li>{!! __('<strong>Contract</strong> — Cont, Abonament, furnizare SaaS;') !!}</li>
    <li>{!! __('<strong>Obligație legală</strong> — evidențe fiscale ale Operatorului, răspunsuri la autorități;') !!}</li>
    <li>{!! __('<strong>Interes legitim</strong> — securitate, prevenirea fraudei, suport, îmbunătățiri agregate;') !!}</li>
    <li>{!! __('<strong>Consimțământ</strong> — doar unde e necesar (ex. marketing opțional, cookie-uri neesențiale dacă apar).') !!}</li>
</ul>

<h3>{{ __('6. Drepturile persoanelor vizate — detaliere') }}</h3>

<h4>{{ __('6.1. Dreptul de acces (art. 15)') }}</h4>
<p>
    {{ __('Poți obține confirmarea dacă te prelucrăm și o copie a datelor de Cont, plus informații despre scopuri, destinatari, durate, drepturi. Vom evita să prejudiciem drepturile altora (ex. date ale altor utilizatori).') }}
</p>

<h4>{{ __('6.2. Dreptul la rectificare (art. 16)') }}</h4>
<p>
    {{ __('Corectează din Cont (profil, Societate, clienți) sau cere corectarea pe email dacă nu poți edita singur.') }}
</p>

<h4>{{ __('6.3. Dreptul la ștergere (art. 17)') }}</h4>
<p>{{ __('Poți cere ștergerea când, de exemplu:') }}</p>
<ul>
    <li>{{ __('datele nu mai sunt necesare scopurilor;') }}</li>
    <li>{{ __('îți retragi consimțământul și nu există alt temei;') }}</li>
    <li>{{ __('te opui și nu există motive legitime prevalente;') }}</li>
    <li>{{ __('datele au fost prelucrate ilegal.') }}</li>
</ul>
<p>{!! __('<strong>Excepții frecvente:</strong> păstrare pentru obligații fiscale, constatarea/exercitarea unui drept în instanță, arhivare în interes public (dacă ar fi cazul). Ștergerea Contului poate fi amânată până la clarificarea acestor excepții; te informăm.') !!}</p>

<h4>{{ __('6.4. Restricționarea prelucrării (art. 18)') }}</h4>
<p>
    {{ __('Poți cere restricționarea când contești exactitatea, prelucrarea e ilegală și preferi restricția în locul ștergerii, sau aștepți verificarea interesului legitim după opoziție.') }}
</p>

<h4>{{ __('6.5. Portabilitatea (art. 20)') }}</h4>
<p>
    {{ __('Pentru datele pe care ni le-ai furnizat și pe care le prelucrăm automat pe bază de contract/consimțământ, poți cere un export într-un format structurat (ex. CSV/JSON al nomenclatoarelor), în măsura fezabilității tehnice. Documentele PDF/XML le poți descărca deja din aplicație.') }}
</p>

<h4>{{ __('6.6. Opoziția (art. 21)') }}</h4>
<p>
    {{ __('Te poți opune prelucrărilor bazate pe interes legitim. Vom opri prelucrarea, exceptând motive legitime care prevalează sau exercitarea unor drepturi legale.') }}
</p>

<h4>{{ __('6.7. Decizii automate (art. 22)') }}</h4>
<p>
    {{ __('Nu luăm decizii bazate exclusiv pe prelucrare automată care să producă efecte juridice semnificative asupra ta în afara aplicării transparente a regulilor de Abonament (expirare / promoție).') }}
</p>

<h4>{{ __('6.8. Plângere') }}</h4>
<p>
    {{ __('Ai dreptul să depui plângere la ANSPDCP. Te rugăm totuși să ne contactezi întâi — rezolvăm majoritatea cererilor direct.') }}
</p>

<h3>{{ __('7. Procedura de exercitare a drepturilor') }}</h3>
<ol>
    <li>{!! __('Trimite email la <a href="mailto::email">:email</a>.', ['email' => e($contact)]) !!}</li>
    <li>{{ __('Subiect recomandat: „GDPR — [acces / rectificare / ștergere / portabilitate / opoziție]”.') }}</li>
    <li>{{ __('Include: nume, email Cont, descrierea cererii, eventuale documente utile.') }}</li>
    <li>{{ __('Verificare identitate: putem cere confirmări suplimentare pentru a nu divulga date unor terți.') }}</li>
    <li>{{ __('Termen de răspuns: fără întârzieri nejustificate, maximum o lună; prelungibil cu încă două luni în cazuri complexe (te informăm).') }}</li>
    <li>{{ __('Gratuit: da, de regulă. Cereri vădit nefondate sau repetitive excesiv pot fi taxate sau refuzate (art. 12).') }}</li>
</ol>

<div class="help-warn">
    {{ __('Dacă cererea privește datele unui client final pe care doar tu le-ai introdus, este posibil să te îndrumăm să acționezi ca operator (sau să ne dai instrucțiuni clare de ștergere din Contul tău). Nu putem „ghici” dacă ai temei să ștergi datele unui terț fără context.') }}
</div>

<h3>{{ __('8. Obligațiile tale când ești operator (clienții tăi)') }}</h3>
<p>{{ __('Dacă introduci date personale ale clienților în Platformă, tu trebuie, între altele:') }}</p>
<ul>
    <li>{{ __('să ai temei legal (contract, obligație legală, interes legitim etc.);') }}</li>
    <li>{{ __('să informezi persoanele vizate (notă de informare pe facturi / site / contract);') }}</li>
    <li>{{ __('să nu introduci date excesive (ex. CNP doar dacă e necesar);') }}</li>
    <li>{{ __('să răspunzi cererilor lor GDPR; noi te asistăm tehnic (export/ștergere din Cont) pe baza instrucțiunilor tale;') }}</li>
    <li>{{ __('să configurezi corect emailurile și destinatarii Documentelor.') }}</li>
</ul>

<h3>{{ __('9. Măsuri de securitate (detaliere)') }}</h3>
<table class="legal-table">
    <thead><tr><th>{{ __('Zonă') }}</th><th>{{ __('Măsuri') }}</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Transport') }}</td><td>{{ __('HTTPS, HSTS în producție') }}</td></tr>
        <tr><td>{{ __('Autentificare') }}</td><td>{{ __('Parole hashuite, sesiuni, protecție CSRF') }}</td></tr>
        <tr><td>{{ __('Autorizare') }}</td><td>{{ __('Izolare pe Societate, middleware de acces') }}</td></tr>
        <tr><td>{{ __('Aplicație') }}</td><td>{{ __('Validări, antete CSP, limitări request') }}</td></tr>
        <tr><td>{{ __('Organizațional') }}</td><td>{{ __('Acces intern limitat, proceduri suport') }}</td></tr>
        <tr><td>{{ __('Continuitate') }}</td><td>{{ __('Backup-uri hosting, monitorizare erori') }}</td></tr>
    </tbody>
</table>

<h3>{{ __('10. Încălcarea securității datelor (data breach)') }}</h3>
<ol>
    <li>{{ __('Detectare / semnalare internă sau de la utilizator.') }}</li>
    <li>{{ __('Evaluare risc pentru drepturile persoanelor vizate.') }}</li>
    <li>{{ __('Notificare ANSPDCP în 72 de ore când e necesar (art. 33).') }}</li>
    <li>{{ __('Informarea persoanelor vizate când riscul este ridicat (art. 34).') }}</li>
    <li>{{ __('Documentarea incidentului și măsuri de remediere.') }}</li>
</ol>
<p>{{ __('Dacă observi un incident: :contact, subiect „Securitate / breach”.', ['contact' => $contact]) }}</p>

<h3>{{ __('11. Subprocesatori') }}</h3>
<p>
    {{ __('Folosim subprocessatori tip hosting, email, plăți (NETOPIA). Selectăm furnizori care oferă garanții contractuale adecvate. Lista categoriilor este în Politica de confidențialitate; modificările materiale vor fi reflectate în politici.') }}
</p>

<h3>{{ __('12. Transferuri în afara SEE') }}</h3>
<p>
    {{ __('Preferăm SEE. Transferurile, dacă există, se bazează pe mecanisme GDPR (adecvare, SCC etc.).') }}
</p>

<h3>{{ __('13. Categorii speciale de date (art. 9)') }}</h3>
<p>
    {{ __('Nu solicităm și nu dorim date privind sănătatea, originea etnică, orientarea sexuală, biometria etc. Te rugăm să nu le introduci în câmpurile Platformei. Dacă apar accidental, le vom șterge la sesizare.') }}
</p>

<h3>{{ __('14. Copii') }}</h3>
<p>
    {{ __('Serviciul nu se adresează minorilor sub 16 ani. Nu creăm Conturi pentru copii în mod intenționat.') }}
</p>

<h3>{{ __('15. Păstrare după închiderea Contului') }}</h3>
<ul>
    <li>{{ __('la cerere de ștergere: eliminăm / anonimizăm datele de Cont care nu mai sunt necesare;') }}</li>
    <li>{{ __('păstrăm evidențele pe care legea le impune Operatorului (ex. facturare abonament);') }}</li>
    <li>{{ __('backup-urile pot conține residual date pentru o perioadă scurtă, până la rotația lor.') }}</li>
</ul>

<h3>{{ __('16. Relația cu Termenii') }}</h3>
<p>
    {{ __('Folosirea Platformei implică acceptarea Termenilor. Politica GDPR nu diminuează drepturile imperative ale persoanelor vizate și nu înlocuiește obligațiile tale ca operator față de clienții finali.') }}
</p>

<h3>{{ __('17. Actualizări') }}</h3>
<p>
    {{ __('Actualizăm acest document când se schimbă prelucrările sau legea. Versiunea curentă este cea publicată aici, cu data de la începutul paginii.') }}
</p>

<h3>{{ __('18. Contact și autoritate') }}</h3>
<p>
    {!! __('<strong>Operator Cont:</strong> :name — :contact', ['name' => e($operator['name']), 'contact' => e($contact)]) !!}<br>
    {!! __('<strong>ANSPDCP:</strong> B-dul Gheorghe Magheru 28-30, București (verifică datele actuale pe dataprotection.ro)') !!}<br>
    {!! __('<strong>SAL consumatori (contracte):</strong> <a href=":url" target="_blank" rel="noopener">reclamatiisal.anpc.ro</a>', ['url' => 'https://reclamatiisal.anpc.ro']) !!}
</p>
@endsection
