@extends(($embed ?? false) ? 'legal.embed' : 'legal._layout')

@section('legal')
<h2>{{ __($meta['title']) }}</h2>
<p class="help-lead">{{ __($meta['subtitle']) }}</p>
<p class="help-meta-line">{{ __('Ultima actualizare: :date · Versiune document: :version', ['date' => \Illuminate\Support\Carbon::parse($meta['updated'])->format('d.m.Y'), 'version' => '2.0']) }}</p>

<div class="help-note">
    {!! __('Prezenta <strong>Politică de anulare comandă</strong> reglementează anularea, retragerea și eventualele rambursări pentru Abonamentele <strong>:brand</strong>, comercializate de <strong>:operator</strong> (CUI :cui). Contact:', [
        'brand' => e($brand),
        'operator' => e($operator['name']),
        'cui' => e($operator['cui']),
    ]) !!}
    <a href="mailto:{{ $contact }}">{{ $contact }}</a>.
</div>

<h3>{{ __('1. Domeniu') }}</h3>
<p>{{ __('Se aplică:') }}</p>
<ul>
    <li>{{ __('Comenzilor de Abonament plasate în aplicație (card NETOPIA sau OP);') }}</li>
    <li>{{ __('solicitărilor de renunțare la reînnoire;') }}</li>
    <li>{{ __('contestațiilor de plată și corectărilor de eroare.') }}</li>
</ul>
<p>{{ __('Nu se aplică relației tale cu clienții finali cărora le emiți facturi din Platformă (aceea e treaba ta comercială).') }}</p>

<h3>{{ __('2. Anularea înainte de finalizarea plății') }}</h3>
<ul>
    <li>{{ __('Poți închide pagina NETOPIA sau abandona OP-ul — Comanda rămâne neplătită.') }}</li>
    <li>{{ __('Comenzile OP neîncasate pot fi marcate eșuate/anulate administrativ după un interval rezonabil (ex. 14–30 zile), fără penalități.') }}</li>
    <li>{{ __('O Comandă neplătită nu activează Abonament.') }}</li>
</ul>

<h3>{{ __('3. Dreptul de retragere al consumatorului (14 zile)') }}</h3>
<h4>{{ __('3.1. Cine este consumator') }}</h4>
<p>
    {{ __('Persoana fizică ce acționează în scopuri din afara activității sale comerciale, industriale sau profesionale (conform OUG 34/2014 și legislației de protecție a consumatorilor).') }}
</p>
<h4>{{ __('3.2. Regula generală') }}</h4>
<p>
    {{ __('Consumatorul are, în principiu, 14 zile calendaristice pentru a se retrage din contractele la distanță, fără a invoca un motiv, cu restituirea sumelor, în condițiile legii.') }}
</p>
<h4>{{ __('3.3. Excepție — servicii digitale începute') }}</h4>
<p>
    {{ __('Conform art. 16 din OUG 34/2014 (și corelativelor), dreptul de retragere se poate pierde pentru:') }}
</p>
<ul>
    <li>{{ __('prestarea de servicii după executare completă, dacă executarea a început cu acordul expres al consumatorului și după ce a confirmat că își pierde dreptul odată ce contractul a fost executat integral;') }}</li>
    <li>{{ __('furnizarea de conținut digital care nu este livrat pe un suport material, dacă prestarea a început cu acordul expres prealabil și după confirmarea pierderii dreptului de retragere.') }}</li>
</ul>
<p>
    {!! __('Abonamentul :brand este serviciu digital. Prin plasarea Comenzii, acceptarea Termenilor și folosirea accesului după activare, soliciți începerea executării imediat. După activarea accesului și utilizare, retragerea poate fi <strong>limitată sau exclusă</strong>, în măsura permisă de lege.', ['brand' => e($brand)]) !!}
</p>
<div class="help-warn">
    {{ __('Dacă plata s-a făcut dar nu ai folosit deloc Serviciul și te afli în termenul legal, scrie imediat la :contact. Analizăm cazul și, dacă legea o cere, rambursăm.', ['contact' => $contact]) }}
</div>

<h3>{{ __('4. Anulare / rambursare pentru profesioniști (B2B)') }}</h3>
<p>
    {{ __('Pentru societăți / PFA care cumpără Abonamentul în scop profesional, dreptul de retragere de 14 zile al consumatorului nu se aplică în același regim. Anularea după activare nu generează automat rambursare pro-rata, exceptând:') }}
</p>
<ul>
    <li>{{ __('neactivarea din culpa dovedită a Operatorului;') }}</li>
    <li>{{ __('plată dublă / eroare de sumă;') }}</li>
    <li>{{ __('acord comercial scris separat;') }}</li>
    <li>{{ __('gesturi comerciale voluntare ale Operatorului (credit de zile).') }}</li>
</ul>

<h3>{{ __('5. Renunțarea la reînnoire') }}</h3>
<p>
    {{ __('Nu există obligație de reînnoire automată forțată dincolo de perioada plătită, decât dacă ai activat explicit o opțiune de plată recurentă (unde este oferită) și nu ai renunțat ulterior. La expirare, accesul se poate bloca; Contul și datele nu se șterg automat doar din acest motiv.') }}
</p>

<h3>{{ __('6. Cum soliciți anularea sau rambursarea') }}</h3>
<ol>
    <li>{!! __('Email la <a href="mailto::email">:email</a>, subiect: „Anulare / rambursare Abonament”.', ['email' => e($contact)]) !!}</li>
    <li>{{ __('Conținut minim:') }}
        <ul>
            <li>{{ __('nume Cont și email de înregistrare;') }}</li>
            <li>{{ __('număr Comandă;') }}</li>
            <li>{{ __('dată și metodă plată (card / OP);') }}</li>
            <li>{{ __('motivul solicitării;') }}</li>
            <li>{{ __('IBAN pentru rambursare OP (dacă e cazul).') }}</li>
        </ul>
    </li>
    <li>{{ __('Confirmăm înregistrarea cererii și solicităm clarificări dacă e nevoie.') }}</li>
    <li>{{ __('Răspuns de fond: de regulă în 5–15 zile lucrătoare (mai rapid pentru erori evidente).') }}</li>
</ol>

<h3>{{ __('7. Modalități de rambursare (dacă se aprobă)') }}</h3>
<table class="legal-table">
    <thead><tr><th>{{ __('Plata originală') }}</th><th>{{ __('Canal rambursare') }}</th><th>{{ __('Termen tipic după aprobare') }}</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Card NETOPIA') }}</td><td>{{ __('Același canal / card') }}</td><td>{{ __('3–10 zile lucrătoare (depinde de bancă)') }}</td></tr>
        <tr><td>{{ __('OP') }}</td><td>{{ __('Transfer în contul plătitor sau IBAN comunicat') }}</td><td>{{ __('3–7 zile lucrătoare') }}</td></tr>
        <tr><td>{{ __('Acord de credit') }}</td><td>{{ __('Zile adăugate la access_until') }}</td><td>{{ __('Imediat după operare în sistem') }}</td></tr>
    </tbody>
</table>

<h3>{{ __('8. Situații în care rambursarea poate fi refuzată') }}</h3>
<ul>
    <li>{{ __('accesul a fost activat și folosit substanțial pe perioada plătită;') }}</li>
    <li>{{ __('solicitarea este în afara termenelor legale aplicabile;') }}</li>
    <li>{{ __('există fraudă, chargeback abuziv, abuz de promoții sau încălcarea Termenilor;') }}</li>
    <li>{{ __('suma a fost deja restituită / creditată;') }}</li>
    <li>{{ __('nu putem verifica identitatea solicitantului ca titular al Contului.') }}</li>
</ul>

<h3>{{ __('9. Chargeback / contestare la bancă') }}</h3>
<p>
    {{ __('Dacă deschizi un chargeback fără a ne contacta mai întâi, putem:') }}
</p>
<ul>
    <li>{{ __('suspenda Contul pe durata investigației;') }}</li>
    <li>{{ __('furniza dovezi procesatorului (Comandă, IPN, loguri de activare);') }}</li>
    <li>{{ __('refuza comenzi viitoare în caz de abuz repetat.') }}</li>
</ul>
<p>{{ __('Te încurajăm să scrii întâi la :contact — rezolvăm mai rapid majoritatea cazurilor.', ['contact' => $contact]) }}</p>

<h3>{{ __('10. Efectele anulării asupra datelor') }}</h3>
<ul>
    <li>{{ __('anularea Abonamentului nu șterge automat Societățile și Documentele;') }}</li>
    <li>{{ __('pentru ștergere Cont / date, vezi Politica GDPR (cerere expresă);') }}</li>
    <li>{{ __('exportă PDF/XML înainte dacă ai nevoie de arhivă proprie.') }}</li>
</ul>

<h3>{{ __('11. Modificarea Comenzii') }}</h3>
<p>
    {{ __('Schimbarea pachetului după plată nu este un drept absolut. Poți:') }}
</p>
<ul>
    <li>{{ __('aștepta expirarea și comanda alt pachet;') }}</li>
    <li>{{ __('solicita upgrade cu regularizare (la discreția Operatorului);') }}</li>
    <li>{{ __('corecta datele de facturare înainte de emiterea documentului fiscal al Operatorului.') }}</li>
</ul>

<h3>{{ __('12. Reclamații și SAL') }}</h3>
<p>
    {{ __('Reclamații: :contact. Consumatorii pot apela', ['contact' => $contact]) }}
    <a href="https://reclamatiisal.anpc.ro" target="_blank" rel="noopener">{{ __('ANPC SAL') }}</a>
    {{ __('sau alte structuri competente. Păstrăm evidența cererilor de anulare în scopul soluționării.') }}
</p>

<h3>{{ __('13. Documente conexe') }}</h3>
<ul>
    <li><a href="{{ route('legal.show', 'termeni') }}">{{ __('Termeni și condiții') }}</a></li>
    <li><a href="{{ route('legal.show', 'livrare') }}">{{ __('Politica de livrare') }}</a></li>
    <li><a href="{{ route('legal.show', 'confidentialitate') }}">{{ __('Politica de confidențialitate') }}</a></li>
    <li><a href="{{ route('legal.show', 'gdpr') }}">{{ __('Politica GDPR') }}</a></li>
</ul>
@endsection
