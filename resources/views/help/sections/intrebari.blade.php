@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Mai jos găsești răspunsuri la problemele întâlnite cel mai des. Pentru detalii complete, urmează
    linkurile către capitolele dedicate. Contact suport: {{ config('dateconta.contact_email') }}.
</p>

<div class="help-note">
    Există și un <a href="{{ route('faq') }}"><strong>FAQ public</strong></a> (fără autentificare), util de partajat
    clienților sau colegilor — plus ghidurile
    <a href="{{ route('guides.show', 'e-factura') }}">Cum emiți e-Factura</a> și
    <a href="{{ route('guides.show', 'proforma-vs-factura') }}">Proformă vs factură</a>.
</div>

@include('help._figure', [
    'shot' => 'dashboard',
    'label' => 'Figura 1',
    'caption' => 'Dacă „nu vezi date”, verifică mai întâi societatea activă din antet, apoi filtrele din liste.',
])

<h3>Cont și acces</h3>
<h4>Unde e codul meu promoțional?</h4>
<p>
    În meniul contului (click pe numele societății din antet), la Setări → Date generale sau în lista societăților.
    Click pe cod îl copiază. Detalii complete: <a href="{{ route('help.show', 'cod-promotional') }}">Cod promoțional</a>.
</p>

<h4>Cum funcționează recompensa la recomandare?</h4>
<p>
    Cine folosește codul tău la crearea unei societăți primește +2 săptămâni; tu primești +1 lună la fiecare
    2 societăți aduse. Codul se introduce doar la „Adaugă societate”.
</p>

<h4>De ce am ecranul „Acces suspendat”?</h4>
<p>
    Perioada gratuită sau de probă s-a încheiat. Promoția curentă oferă acces gratuit până la
    <strong>31.03.2027</strong>; după aceea se aplică regulile de trial / abonament. Scrie la
    {{ config('dateconta.contact_email') }}.
</p>

<h4>Nu găsesc facturile / clienții</h4>
<p>
    Cel mai frecvent: societatea activă este alta. Comută firma din selector și reîncarcă listele.
    Dacă ești subuser, este posibil să nu ai dreptul bifată pentru liste / clienți — întreabă proprietarul contului
    (Setări → Utilizatori).
</p>

<h4>Pot folosi aplicația pe două calculatoare în același timp?</h4>
<p>
    Da. Sesiunea de pe un dispozitiv nu blochează autentificarea pe altul. Dacă vezi „Emailul sau parola nu sunt corecte”,
    verifică parola (sau folosește <em>Ai uitat parola?</em> pe ecranul de login) — nu e din cauza celuilalt calculator.
</p>

<h4>Cum adaug un coleg pe firmele mele?</h4>
<p>
    Setări → <strong>Utilizatori</strong>: introduci emailul. Dacă e nou → subuser (cu parolă); dacă există deja
    → invitație; dacă e admin de platformă → invitație de admin (acces complet, fără revocare ulterioară).
    Detalii: <a href="{{ route('help.show', 'utilizatori') }}">Utilizatori (subuseri)</a>.
</p>

<h4>Care e diferența dintre subuser și utilizator invitat?</h4>
<p>
    <strong>Subuserul</strong> e un cont nou creat de tine (tu setezi parola). <strong>Invitatul</strong> are deja
    cont: pe firmele tale lucrează cu drepturile pe care i le dai, pe firmele lui rămâne proprietar.
    Poți șterge doar subuserii creați; pe invitați doar le revoci accesul (excepție: adminul invitat nu se mai scoate).
</p>

<h4>Subuserul nu vede meniul Utilizatori / Abonament</h4>
<p>
    Este intenționat: doar proprietarul le gestionează. Nimeni nu își poate șterge singur contul din Contul meu.
</p>

@include('help._figure', [
    'shot' => 'login',
    'label' => 'Figura 2',
    'caption' => 'Problemele de parolă se rezolvă din login / resetare; nu crea un al doilea cont pe același CUI fără nevoie.',
])

<h3>Emitere și serii</h3>
<h4>Mesaj: nu există serii active</h4>
<ol class="help-steps">
    <li>Setări → Serii.</li>
    <li>Creează o serie pentru tipul Factură și anul datei de emitere.</li>
    <li>Marcheaz-o Activă (și Implicită dacă e singura).</li>
</ol>

<h4>De ce numărul arată FCT-0001?</h4>
<p>
    Formatul standard este PREFIX + „-” + număr pe 4 cifre. Prefixul îl alegi tu; zerourile sunt doar aliniere.
</p>

<h4>Nu pot șterge seria FCT / PRF creată automat</h4>
<ol class="help-steps">
    <li>Adaugă mai întâi seria ta pe același tip de document și an, și marcheaz-o Implicită dacă vrei.</li>
    <li>Apoi Șterge pe FCT/PRF/… — e permis, atâta timp cât nu e ultima serie pe tip+an și nu are documente emise.</li>
</ol>

<h4>„Linia N: produsul e obligatoriu”</h4>
<p>
    Completează numele produsului pe linie sau șterge linia goală. Descrierea poate rămâne goală; produsul nu.
</p>

@include('help._figure', [
    'shot' => 'documents-lines',
    'label' => 'Figura 3',
    'caption' => 'Linii incomplete — cel mai des lipsește produsul sau cantitatea/prețul/TVA.',
])

<h3>ANAF și adrese</h3>
<h4>Preluarea CUI a umplut greșit adresa</h4>
<p>
    Câmpul Adresă trebuie să conțină strada/numărul, iar Localitate și Județ rămân separate.
    Mută manual orașul/județul dacă au fost lipite greșit și salvează.
    Străzile care conțin „București” în denumire (ex. în alte județe) nu trebuie să umple Localitate/Județ cu București —
    aplicația le separă după județul ANAF.
</p>
<h4>e-Factura respinsă BR-RO-100 (București / SECTOR)</h4>
<p>
    Pentru clienți din București, județul/localitatea trebuie să includă Sector 1–6 (nu doar „București”).
    Completează sectorul la client, salvează, apoi retrimite factura în e-Factura.
</p>

<h3>Date și listă</h3>
<h4>Data facturii e cu o zi în urmă</h4>
<p>
    Aplicația folosește fusul orar Europe/Bucharest. Dacă vezi încă o dată greșită, reîncarcă pagina sau
    contactează suportul — pe server trebuie setat același fus.
</p>

<h4>După salvare nu mai ajung pe ecranul de încasare</h4>
<p>
    Normal: după salvare/emitere revii în lista tipului (Facturi, Proforme…). Deschizi documentul din listă
    dacă vrei PDF, încasare sau e-Factura. Câte rânduri pe pagină: Setări → Preferințe.
</p>

<h3>e-Factura</h3>
<h4>Rămâne pe „așteaptă validare”</h4>
<p>
    Lista se reîmprospătează ~la 30s; poți apăsa Actualizează stare ANAF. Platforma verifică automat până la
    Acceptată ANAF. La respingere apare mesajul pe factură și se reîncearcă trimiterea (cu corectări automate unde e posibil).
    Detalii: <a href="{{ route('help.show', 'efactura') }}">e-Factura ANAF</a>.
</p>
<h4>Văd „În reîncercare automată”</h4>
<p>
    Factura a fost respinsă sau a eșuat la upload; sistemul o tratează ca netrimisă, corectează cauze tipice
    (ex. adresă București/sector) și retrimite de până la 5 ori pe zi. Dacă tot eșuează, rămâne mesajul ANAF —
    corectează datele clientului și lasă automatizarea să reîncerce, sau apasă Retrimite.
</p>

<h4>Butonul Autorizează SPV nu finalizează (pagina BIG-IP / „Logged out successfully”)</h4>
<p>
    Dacă după parola semnăturii apari pe <em>logincert.anaf.ro</em> cu „Your session is finished” / hangup,
    ANAF a închis sesiunea <strong>fără</strong> a trimite autorizarea înapoi în DateConta — nu e un refuz din aplicație.
</p>
<ol class="help-steps">
    <li>Închide toate tab-urile ANAF / SPV, apoi închide și redeschide browserul (sau fereastră privată cu certificatul disponibil).</li>
    <li>Asigură-te că certificatul e pentru CUI-ul din Date generale și are drept SPV PJ (reprezentant legal / desemnat / împuternicit).</li>
    <li>La alegerea certificatului, selectează exact tokenul firmei (nu alt certificat din listă).</li>
    <li>Apasă din nou <strong>Autorizează SPV</strong> din Setări → e-Factura (același tab, fără „Deschide în tab nou”).</li>
    <li>Dacă certificatul e la contabil: folosește <strong>Invită contabilul pe email</strong> și deschide linkul din emailul cel mai recent (valabil 7 zile).</li>
    <li>Dacă vezi „Invitație invalidă”, trimite din nou invitația din Setări → e-Factura (nu refolosi un link vechi).</li>
</ol>

<h4>Nu pot edita factura</h4>
<p>
    Documentul este probabil trimis / în prelucrare / acceptat în e-Factura. Folosește storno pentru corecții.
</p>

@include('help._figure', [
    'shot' => 'settings-efactura',
    'label' => 'Figura 4',
    'caption' => 'Starea Autorizat / Neautorizat și modul de trimitere se verifică aici înainte de a semnala o eroare.',
])

<h3>PDF, limbi, plăți</h3>
<h4>Am schimbat limba UI, dar factura e tot în română</h4>
<p>
    Este comportamentul corect. Schimbă <strong>Limbă document</strong> pe factură (și activează limba la Setări → Limbi).
</p>

<h4>Nu apar IBAN-urile pe PDF</h4>
<p>
    Setări → Conturi bancare: bifează „Pe factură” (maximum 3) și salvează. Regenerează PDF-ul.
</p>

<h4>Am înregistrat plata, dar factura e tot neachitată</h4>
<ol class="help-steps">
    <li>Verifică că încasarea e pe factura corectă și suma e suficientă.</li>
    <li>Reîncarcă fișa documentului.</li>
    <li>Consultă lista din Emite → Înregistrează încasare.</li>
</ol>

@include('help._figure', [
    'shot' => 'payments',
    'label' => 'Figura 5',
    'caption' => 'Lista încasărilor — verifică suma și documentul legat când starea de plată pare greșită.',
])

<div class="help-note">
    Pentru fluxuri complete vezi: 
    <a href="{{ route('help.show', 'emitere-factura') }}">Emitere factură</a>,
    <a href="{{ route('help.show', 'efactura') }}">e-Factura</a>,
    <a href="{{ route('help.show', 'societate') }}">Societatea</a>.
</div>

<div class="help-warn">
    Acest FAQ nu este consultanță fiscală. Pentru interpretări legale privind e-Factura sau TVA,
    consultă un specialist; pentru erori tehnice ale aplicației, scrie la {{ config('dateconta.contact_email') }}.
</div>

@include('help._figure', [
    'shot' => 'landing',
    'label' => 'Figura 6',
    'caption' => 'Pagina publică DateConta Facturare — punct de reîntoarcere dacă ai nevoie să te autentifici din nou.',
])

<p>
    Revino la <a href="{{ route('help.index') }}">prezentarea generală</a> a manualului sau folosește cuprinsul din stânga.
</p>
@endsection
