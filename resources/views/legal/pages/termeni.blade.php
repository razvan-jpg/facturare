@extends(($embed ?? false) ? 'legal.embed' : 'legal._layout')

@section('legal')
<h2>{{ __($meta['title']) }}</h2>
<p class="help-lead">{{ __($meta['subtitle']) }}</p>
<p class="help-meta-line">{{ __('Ultima actualizare: :date · Versiune document: :version', ['date' => \Illuminate\Support\Carbon::parse($meta['updated'])->format('d.m.Y'), 'version' => '2.0']) }}</p>

<div class="help-note">
    {!! __('Prezentele <strong>Termeni și condiții de folosire</strong> („Termenii”) constituie acordul legal dintre <strong>:operator</strong> („Operatorul”, „noi”, „nouă”) și orice persoană fizică sau juridică („Utilizatorul”, „tu”) care accesează sau folosește platforma <strong>:brand</strong> („Platforma”, „Serviciul”, „Aplicația”), disponibilă online, inclusiv la domeniul factura.dateconta.ro și subdomeniile aferente.', ['operator' => e($operator['name']), 'brand' => e($brand)]) !!}
</div>

<p>
    {!! __('<strong>Date Operator:</strong> :name, CUI :cui, :reg_com, sediu social: :address, :city, :county, :country. Email: <a href="mailto::email">:email</a>.', [
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

<h3>{{ __('1. Acceptarea și forța obligatorie a Termenilor') }}</h3>
<h4>{{ __('1.1. Cum se acceptă') }}</h4>
<p>{{ __('Acceptarea Termenilor intervine, fără a se limita la, în oricare dintre situațiile următoare:') }}</p>
<ul>
    <li>{{ __('bifarea casetei de acceptare la înregistrare sau la plasarea unei comenzi de abonament;') }}</li>
    <li>{{ __('crearea unui cont și prima autentificare;') }}</li>
    <li>{{ __('continuarea utilizării Platformei după publicarea unei versiuni actualizate a Termenilor;') }}</li>
    <li>{{ __('plata unui abonament sau activarea unei perioade promoționale / de probă.') }}</li>
</ul>
<p>
    {{ __('Termenii se completează cu') }}
    <a href="{{ route('legal.show', 'confidentialitate') }}">{{ __('Politica de confidențialitate') }}</a>,
    <a href="{{ route('legal.show', 'gdpr') }}">{{ __('Politica GDPR') }}</a>,
    <a href="{{ route('legal.show', 'livrare') }}">{{ __('Politica de livrare comandă') }}</a> {{ __('și') }}
    <a href="{{ route('legal.show', 'anulare') }}">{{ __('Politica de anulare comandă') }}</a>,
    {{ __('care fac parte integrantă din relația contractuală.') }}
</p>

<h4>{{ __('1.2. Capacitate și reprezentare') }}</h4>
<p>
    {{ __('Declari că ai capacitate deplină de exercițiu. Dacă folosești Platforma în numele unei societăți (SRL, SA, PFA, II etc.), garantzi că ești împuternicit să obligi acea entitate. Toate acțiunile din cont (emitere facturi, autorizare SPV, comenzi) se consideră făcute cu acordul entității reprezentate.') }}
</p>

<h4>{{ __('1.3. Limba') }}</h4>
<p>
    {{ __('Documentul de referință este în limba română. Traducerile UI în alte limbi (PDF documente etc.) nu modifică interpretarea acestor Termeni, care prevalează în limba română.') }}
</p>

<h3>{{ __('2. Definiții') }}</h3>
<table class="legal-table">
    <thead><tr><th>{{ __('Termen') }}</th><th>{{ __('Semnificație') }}</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Cont') }}</td><td>{{ __('Contul de utilizator creat pe Platformă, asociat unei adrese de email.') }}</td></tr>
        <tr><td>{{ __('Societate') }}</td><td>{{ __('Firma (emitent) configurată în nomenclatorul multi-firmă al Contului.') }}</td></tr>
        <tr><td>{{ __('Document') }}</td><td>{{ __('Factură, proformă, aviz, chitanță, storno, notă de creditare sau alt înscris generat în app.') }}</td></tr>
        <tr><td>{{ __('Abonament') }}</td><td>{{ __('Dreptul de acces plătit sau promoțional la funcțiile Platformei, pe o perioadă determinată.') }}</td></tr>
        <tr><td>{{ __('Comandă') }}</td><td>{{ __('Solicitarea de activare/prelungire a Abonamentului, plătită cu cardul sau OP.') }}</td></tr>
        <tr><td>{{ __('Conținut Utilizator') }}</td><td>{{ __('Date, fișiere și documente introduse sau generate de Utilizator în Cont.') }}</td></tr>
        <tr><td>{{ __('Terți') }}</td><td>{{ __('ANAF/SPV, NETOPIA Payments, furnizori de hosting, email, browsere etc.') }}</td></tr>
    </tbody>
</table>

<h3>{{ __('3. Descrierea detaliată a Serviciului') }}</h3>
<h4>{{ __('3.1. Funcționalități principale') }}</h4>
<p>{{ __(':brand este un software ca serviciu (SaaS) care poate include, în funcție de versiune și configurare:', ['brand' => $brand]) }}</p>
<ul>
    <li>{{ __('gestionarea uneia sau mai multor Societăți (date fiscale, sedii, conturi bancare, serii, cote TVA, limbi PDF);') }}</li>
    <li>{{ __('nomenclatoare de clienți și produse/servicii, inclusiv preluare date firme din ANAF după CUI;') }}</li>
    <li>{{ __('emitere Documente, draft / emis, PDF, email către client, încasări și rapoarte;') }}</li>
    <li>{{ __('facturi recurente, storno, note de creditare;') }}</li>
    <li>{{ __('integrare e-Factura (autorizare OAuth SPV, trimitere UBL, actualizare stare), când este configurată și autorizată;') }}</li>
    <li>{{ __('abonamente Platformă, plăți card (NETOPIA) sau OP, notificări de expirare, coduri promoționale.') }}</li>
</ul>

<h4>{{ __('3.2. Ce nu este Serviciul') }}</h4>
<ul>
    <li>{{ __('nu este consultanță fiscală, juridică sau contabilă personalizată;') }}</li>
    <li>{{ __('nu înlocuiește obligațiile tale de arhivare, raportare și conformare față de ANAF / ANAF e-Factura / alte autorități;') }}</li>
    <li>{{ __('nu garantează acceptarea unei facturi de către SPV, dacă datele sau structura UBL sunt incomplete/incorecte din partea ta;') }}</li>
    <li>{{ __('nu este un serviciu de gestiune stocuri / NIR / magazin online (în afara scopului anunțat al produsului).') }}</li>
</ul>

<h4>{{ __('3.3. Evoluția produsului') }}</h4>
<p>
    {{ __('Operatorul poate introduce funcții noi, modifica interfața, retrage module experimental sau corecta erori fără a constitui o modificare esențială a obiectului contractului. Schimbările care afectează esențial prețul sau natura Abonamentului vor fi comunicate în mod rezonabil (în app, pe site sau pe email).') }}
</p>

<h3>{{ __('4. Contul de utilizator') }}</h3>
<h4>{{ __('4.1. Înregistrare') }}</h4>
<p>
    {{ __('Pentru a folosi Platforma ai nevoie de un Cont valid (nume, email, parolă). Emailul trebuie să fie real și sub controlul tău. Este interzisă crearea de Conturi false, automate (bot) sau în scop de abuz de promoții.') }}
</p>

<h4>{{ __('4.2. Securitatea Contului') }}</h4>
<ul>
    <li>{{ __('ești responsabil pentru confidențialitatea parolei și a sesiunilor;') }}</li>
    <li>{{ __('anunță imediat :contact dacă suspectezi acces neautorizat;', ['contact' => $contact]) }}</li>
    <li>{{ __('recomandăm deconectarea pe dispozitive partajate și parole unice, puternice;') }}</li>
    <li>{{ __('Operatorul poate forța resetarea parolei sau blocarea Contului în caz de risc.') }}</li>
</ul>

<h4>{{ __('4.3. Roluri și Societăți') }}</h4>
<p>
    {{ __('Un Cont poate deține sau fi asociat cu una sau mai multe Societăți. Accesul la Abonament este, de regulă, legat de Contul titular (proprietar). Invitațiile către colaboratori (ex. flux contabil e-Factura), unde există, se supun acelorași reguli de confidențialitate și responsabilitate.') }}
</p>

<h4>{{ __('4.4. Suspendare / închidere Cont') }}</h4>
<p>{{ __('Putem suspenda sau închide Contul, total sau parțial, dacă:') }}</p>
<ul>
    <li>{{ __('încalci Termenii sau legea;') }}</li>
    <li>{{ __('există indicii de fraudă, chargeback abuziv, spam sau atac asupra infrastructurii;') }}</li>
    <li>{{ __('Abonamentul a expirat și nu a fost reînnoit (restricționare funcțională);') }}</li>
    <li>{{ __('ne este cerut de o autoritate competentă printr-un act legal.') }}</li>
</ul>

<h3>{{ __('5. Abonamente, prețuri și plăți') }}</h3>
<h4>{{ __('5.1. Modele de acces') }}</h4>
<ul>
    <li>{!! __('<strong>Promoție platformă</strong> — acces gratuit până la o dată anunțată public (ex. 31.03.2027);') !!}</li>
    <li>{!! __('<strong>Trial / perioadă gratuită</strong> — pentru Conturi noi după promoție (ex. 6 luni de la înregistrare);') !!}</li>
    <li>{!! __('<strong>Abonament plătit</strong> — perioade 1 / 3 / 6 luni sau 1 an, în EUR + TVA, conform paginii de Comandă.') !!}</li>
</ul>

<h4>{{ __('5.2. Prețuri și TVA') }}</h4>
<p>
    {{ __('Prețurile afișate la Comandă sunt cele aplicabile la momentul plasării. TVA se calculează conform legislației române (cotă standard afișată în app, ex. 21%). Bonusurile de perioadă (săptămâni/luni) apar explicit la selectarea pachetului.') }}
</p>

<h4>{{ __('5.3. Metode de plată') }}</h4>
<ul>
    <li>{!! __('<strong>Card</strong> — prin NETOPIA Payments; datele complete de card nu sunt stocate pe serverele noastre;') !!}</li>
    <li>{!! __('<strong>OP</strong> — în contul Operatorului afișat; activarea după confirmarea încasării.') !!}</li>
</ul>
<p>{{ __('Detalii:') }} <a href="{{ route('legal.show', 'livrare') }}">{{ __('Politica de livrare') }}</a> {{ __('și') }} <a href="{{ route('legal.show', 'anulare') }}">{{ __('Politica de anulare') }}</a>.</p>

<h4>{{ __('5.4. Coduri promoționale') }}</h4>
<p>
    {{ __('Codurile de tip XXXX-XXXX-XXXX pot acorda bonusuri de perioadă conform regulilor din aplicație. Abuzul (conturi fictive, automătizări) poate duce la anularea bonusului și/sau a Contului.') }}
</p>

<h4>{{ __('5.5. Neplată și expirare') }}</h4>
<p>
    {{ __('La expirarea accesului, funcțiile pot fi blocate (ecran „Acces suspendat”). Datele nu sunt șterse automat doar pentru expirare; totuși, retenția pe termen lung urmează politica de date și legea. Primești notificări (email și/sau in-app) înainte de expirare, când sistemul este configurat astfel.') }}
</p>

<h3>{{ __('6. Obligațiile Utilizatorului (detaliat)') }}</h3>
<ol>
    <li>{!! __('<strong>Legalitate:</strong> folosești Platforma doar pentru activități licite; nu emiți documente fictive în scop de fraudă.') !!}</li>
    <li>{!! __('<strong>Acuratețe:</strong> datele Societății, clienților și Documentelor trebuie să fie corecte în măsura cunoștințelor tale.') !!}</li>
    <li>{!! __('<strong>Conformare fiscală:</strong> tu răspunzi pentru seria/numărul, TVA, termene, e-Factura și arhivare.') !!}</li>
    <li>{!! __('<strong>Interdicții tehnice:</strong> fără scraping agresiv, reverse engineering abuziv, injectare de malware, încercări de escaladare a privilegiilor sau perturbarea serviciului altor clienți.') !!}</li>
    <li>{!! __('<strong>Conținut:</strong> nu încarci materiale ilegale, tip malware, sau date sensibile inutile (ex. dosare medicale).') !!}</li>
    <li>{!! __('<strong>Terți:</strong> respecți termenii ANAF, NETOPIA și ai furnizorilor de email pe care îi configurezi.') !!}</li>
    <li>{!! __('<strong>Backup:</strong> menții copii ale Documentelor esențiale; Platforma nu înlocuiește arhiva ta legală.') !!}</li>
</ol>

<h3>{{ __('7. Obligațiile și garanțiile Operatorului') }}</h3>
<ul>
    <li>{{ __('furnizăm accesul la Platformă cu diligență profesională rezonabilă;') }}</li>
    <li>{{ __('aplicăm măsuri de securitate proporționale (vezi Politica de confidențialitate / GDPR);') }}</li>
    <li>{{ __('oferim suport la :contact, în zile lucrătoare, fără SLA garantat de tip „99,99%” decât dacă este contractat separat în scris;', ['contact' => $contact]) }}</li>
    <li>{{ __('nu garantăm compatibilitatea cu toate browserele, plugin-urile sau rețelele corporative restrictive;') }}</li>
    <li>{{ __('serviciile Terților pot avea întreruperi pe care nu le controlăm integral.') }}</li>
</ul>

<div class="help-warn">
    {{ __('Platforma este furnizată „ca atare” („as is”) și „după disponibilitate” („as available”), în afara garanțiilor pe care legea le consideră imperative și pe care nu le putem exclude.') }}
</div>

<h3>{{ __('8. Proprietate intelectuală și licență de folosire') }}</h3>
<h4>{{ __('8.1. Drepturile Operatorului') }}</h4>
<p>
    {{ __('Software-ul, UI, textele de ajutor, brandul DateConta, logo-urile, bazele de date tehnice și documentația aparțin Operatorului sau licențiatorilor. Orice drept neacordat expres este rezervat.') }}
</p>
<h4>{{ __('8.2. Licența ta') }}</h4>
<p>
    {{ __('Îți acordăm o licență limitată, personală/organizațională, neexclusivă, netransmisibilă, revocabilă, de a folosi Platforma pe durata accesului valabil, exclusiv pentru emiterea și gestionarea Documentelor aferente activității tale. Este interzisă sublicențierea, revânzarea accesului ca „white-label” fără acord scris, sau extragerea sistematică a conținutului Platformei.') }}
</p>
<h4>{{ __('8.3. Conținutul Utilizatorului') }}</h4>
<p>
    {{ __('Păstrezi drepturile asupra Conținutului Utilizator. Ne acorzi o licență mondială, neexclusivă, de a stoca, copia tehnic, transmite și procesa acest Conținut exclusiv pentru a-ți furniza Serviciul (inclusiv backup, generare PDF/XML, trimitere email/e-Factura la cererea ta).') }}
</p>

<h3>{{ __('9. e-Factura și ANAF') }}</h3>
<ul>
    <li>{{ __('autorizarea SPV se face cu certificatul tău / al societății; tu controlezi consimțământul OAuth;') }}</li>
    <li>{{ __('Operatorul nu deține și nu îți cere certificatul digital; token-urile se stochează securizat pentru reînnoire;') }}</li>
    <li>{{ __('o factură „acceptată ANAF” poate bloca editări ulterioare — comportament intenționat de conformare;') }}</li>
    <li>{{ __('respingerile ANAF din cauze de date greșite nu angajează răspunderea Operatorului.') }}</li>
</ul>

<h3>{{ __('10. Confidențialitate și date personale') }}</h3>
<p>
    {{ __('Prelucrarea este detaliată în Politica de confidențialitate și Politica GDPR. Pe scurt: datele Contului le prelucrăm ca operator; datele clienților tăi le prelucrăm de regulă ca împuternicit, la instrucțiunile tale.') }}
</p>

<h3>{{ __('11. Limitarea răspunderii') }}</h3>
<ol>
    <li>{{ __('Nu răspundem pentru daune indirecte, pierderi de profit, oportunitate, reputație, date sau întreruperea afacerii.') }}</li>
    <li>{{ __('Nu răspundem pentru acte/omisiuni ale Terților (ANAF, bănci, NETOPIA, ISP, furnizori email ai tăi).') }}</li>
    <li>{{ __('Nu răspundem pentru Documente emise greșit de tine sau pentru amenzile/fiscalizările rezultate din utilizarea ta.') }}</li>
    <li>{{ __('Răspunderea totală agregată a Operatorului față de tine pe orice temei, pe 12 luni calendaristice, este plafonată la sumele efectiv plătite de tine pentru Abonament în acele 12 luni (sau 100 EUR dacă ai folosit doar acces gratuit), exceptând dolul, culpa gravă sau alte cazuri în care legea interzice plafonarea.') }}</li>
</ol>

<h3>{{ __('12. Forță majoră') }}</h3>
<p>
    {{ __('Nicio parte nu răspunde pentru neexecutare cauzată de evenimente de forță majoră / caz fortuit (inclusiv întreruperi majore de energie, cutremur, război, pandemie cu restricții, atacuri cibernetice de amploare asupra infrastructurii publice, decizii ANAF care blochează API-urile), pe durata evenimentului.') }}
</p>

<h3>{{ __('13. Cesionarea') }}</h3>
<p>
    {{ __('Poți cesiona Contul doar cu acordul nostru scris. Operatorul poate cesiona contractul unei entități afiliate sau unui succesor în drepturi (ex. fuziune), cu notificare.') }}
</p>

<h3>{{ __('14. Modificarea Termenilor') }}</h3>
<p>
    {{ __('Publicăm versiunea actualizată pe această pagină, cu data. Pentru modificări esențiale putem afișa un avertisment în app. Continuarea utilizării după data actualizării echivalează cu acceptarea, exceptând cazurile în care legea cere consimțământ expres separat.') }}
</p>

<h3>{{ __('15. Legea aplicabilă, mediție, litigii') }}</h3>
<ol>
    <li>{{ __('Legea română guvernează Termenii.') }}</li>
    <li>{{ __('Preferăm soluționarea amiabilă pe email, în maximum 30 de zile de la sesizare.') }}</li>
    <li>{!! __('Consumatorii pot folosi SAL ANPC: <a href=":url" target="_blank" rel="noopener">reclamatiisal.anpc.ro</a>.', ['url' => 'https://reclamatiisal.anpc.ro']) !!}</li>
    <li>{{ __('Litigiile nerezolvate revin instanțelor competente din România, conform normelor de competență.') }}</li>
</ol>

<h3>{{ __('16. Dispoziții finale') }}</h3>
<ul>
    <li>{{ __('Dacă o clauză este invalidă, restul rămâne în vigoare.') }}</li>
    <li>{{ __('Neexercitarea unui drept nu constituie renunțare.') }}</li>
    <li>{{ __('Termenii, împreună cu politicile legate și detaliile Comenzii, formează întregul acord privind folosirea Platformei și înlocuiesc înțelegerile anterioare pe același obiect (fără a afecta drepturile imperative ale consumatorilor).') }}</li>
</ul>

<h3>{{ __('17. Contact') }}</h3>
<p>
    <strong>{{ $operator['name'] }}</strong><br>
    {{ __('CUI') }} {{ $operator['cui'] }} · {{ $operator['reg_com'] }}<br>
    {{ $operator['address'] }}, {{ $operator['city'] }}, {{ $operator['county'] }}, {{ $operator['country'] }}<br>
    {{ __('Email') }}: <a href="mailto:{{ $contact }}">{{ $contact }}</a>
</p>
@endsection
