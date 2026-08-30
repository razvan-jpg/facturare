@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Modulul e-Factura conectează societatea ta la SPV ANAF prin OAuth (autorizare cu certificat digital).
    După autorizare poți trimite facturile emise (și storno) în sistemul național, urmări starea
    (în coadă, trimisă, acceptată, respinsă) și actualiza răspunsul ANAF din aplicație.
</p>

@include('help._figure', [
    'shot' => 'settings-efactura',
    'label' => 'Figura 1',
    'caption' => 'Setări → e-Factura ANAF — stare autorizare SPV, reautorizare, revocare și mod de trimitere.',
])

<h3>Condiții preliminare</h3>
<ul>
    <li>Societatea are <strong>CUI</strong> corect completat în Date generale.</li>
    <li>Ai acces la <strong>certificatul digital SPV</strong> asociat CUI-ului (token / browser configurat pentru ANAF).</li>
    <li>Serverul DateConta are configurate cheile aplicației ANAF (client id/secret). Dacă lipsește configurarea, contactează suportul.</li>
    <li>Factura pe care o trimiți este <strong>emisă</strong> (are număr) sau este un storno emis.</li>
</ul>

<div class="help-warn">
    Fără CUI pe societate nu poți asocia autorizarea SPV. Completează și salvează Date generale înainte de „Autorizează SPV”.
</div>

<h3>Autorizare SPV (OAuth ANAF) — pas cu pas</h3>
<ol class="help-steps">
    <li>Activează societatea corectă.</li>
    <li>Deschide <strong>Setări → e-Factura ANAF</strong>.</li>
    <li>Verifică starea: <strong>Neautorizat</strong> înseamnă că trebuie să legi SPV; mesajul tipic amintește de certificatul digital pe CUI.</li>
    <li>Apasă <strong>Autorizează SPV</strong> (sau <strong>Reautorizează SPV</strong> dacă tokenul a expirat).</li>
    <li>Ești redirecționat către portalul ANAF (logincert.anaf.ro). Autentifică-te cu certificatul SPV.</li>
    <li>Aprobă accesul aplicației DateConta Facturare.</li>
    <li>După callback, revii în aplicație cu mesaj de succes („SPV ANAF autorizat…”) și starea <strong>Autorizat</strong>.</li>
</ol>

<div class="help-warn">
    Dacă după parola semnăturii vezi pagina BIG-IP („Logged out successfully” / hangup) și nu revii în DateConta:
    închide toate tab-urile ANAF, redeschide browserul, verifică că certificatul are SPV pe CUI-ul firmei,
    apoi reîncearcă din același tab (fără tab nou). Detalii în FAQ: „Autorizează SPV nu finalizează”.
</div>

@include('help._figure', [
    'shot' => 'settings-efactura',
    'label' => 'Figura 2',
    'caption' => 'După OAuth reușit, fila e-Factura afișează starea Autorizat și acțiunile de revocare / reautorizare.',
])

<div class="help-note">
    Autorizarea este pe societate (CUI). Dacă administrezi mai multe firme, repetă fluxul pentru fiecare CUI
    care trebuie să trimită e-Factura.
</div>

<h3>Revocare și reautorizare</h3>
<ul>
    <li><strong>Prelungește conectarea</strong> — reîmprospătează tokenul ANAF și prelungește valabilitatea cu 90 de zile de la apăsare.</li>
    <li><strong>Revocă conectarea</strong> — șterge tokenurile din aplicație; nu mai poți trimite până la o nouă autorizare.</li>
    <li><strong>Reautorizează SPV</strong> — flux OAuth complet, când refresh-ul eșuează sau după schimbarea certificatului.</li>
</ul>

<h3>Invitarea contabilului</h3>
<p>
    Dacă certificatul SPV este la contabil, folosește „Invită contabilul pe email”. După trimitere vezi confirmarea
    cu data/ora și un link de rezervă pe care îl poți copia. Contabilul deschide linkul, parcurge OAuth cu certificatul
    firmei și leagă SPV de societatea ta
    fără a-ți partaja parola DateConta.
</p>
<ol class="help-steps">
    <li>Din fila e-Factura, inițiază invitația pe emailul contabilului.</li>
    <li>Contabilul deschide linkul primit și se autentifică la ANAF cu certificatul pe CUI.</li>
    <li>La final, în aplicație starea societății devine Autorizat.</li>
</ol>

<div class="help-note">
    Linkul de invitație e valabil 7 zile și e de unică folosință după autorizare reușită.
    Dacă apare „Invitație invalidă”, trimite o invitație nouă din Setări → e-Factura (folosește linkul din emailul cel mai recent).
</div>

<div class="help-warn">
    Nu trimite pe email fișierele certificatului. Folosește doar linkul de invitație OAuth din aplicație.
</div>

<h3>Moduri de trimitere</h3>
<p>
    În aceeași filă alegi când se trimit facturile către ANAF:
</p>
<ul>
    <li><strong>La salvarea facturii</strong> — după emitere, documentul intră în fluxul de trimitere.</li>
    <li><strong>La 1 / 2 / 3 zile după emitere</strong> — programare întârziată (util dacă mai corectezi rapid după emitere).</li>
    <li><strong>Manual</strong> — trimiți tu din fișa facturii sau din listă (buton Trimite e-Factura).</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-show',
    'label' => 'Figura 3',
    'caption' => 'Pe factura emisă: Trimite e-Factura și Actualizează stare ANAF, plus eticheta stării curente.',
])

<h3>Trimitere de pe o factură</h3>
<ol class="help-steps">
    <li>Emite factura (număr PREFIX-####) și verifică datele clientului (CUI, adresă structurată).</li>
    <li>Deschide fișa documentului.</li>
    <li>Apasă <strong>Trimite e-Factura</strong> dacă modul este manual sau dacă vrei trimitere imediată.</li>
    <li>Așteaptă trecerea prin stările: programată / în coadă → trimisă → în prelucrare → <strong>Acceptată ANAF</strong>.</li>
    <li>Sistemul verifică automat starea și, la respingere/eroare, încearcă corectări (ex. adresă) și retrimite — până la acceptare.</li>
    <li>Poți folosi <strong>Actualizează stare ANAF</strong> manual dacă vrei un refresh imediat.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-list',
    'label' => 'Figura 4',
    'caption' => 'Lista de facturi: stare e-Factura, ID încărcare / ID descărcare; refresh automat la ~30s când aștepți răspuns SPV.',
])

<h3>Urmărire pe listă</h3>
<ul>
    <li>În lista de facturi vezi <strong>ID încărcare</strong> și <strong>ID descărcare</strong> ANAF (utile la suport / verificare în SPV).</li>
    <li>Cât timp starea e „așteaptă validare” / în prelucrare, lista se <strong>reîmprospătează automat la ~30 de secunde</strong>.</li>
    <li>Poți apăsa oricând <strong>Actualizează stare ANAF</strong> pe fișa documentului.</li>
    <li>La <strong>respingere</strong>, aplicația descarcă și afișează mesajul de eroare din ZIP-ul ANAF (nu rămâne doar pe „așteaptă validare”).</li>
</ul>

<div class="help-note">
    XML-ul e-Factura folosește reguli CIUS-RO (prefix RO pe CUI TVA, coduri adresă București/SECTOR, scadență când există sumă de plată, UM cu coduri UNECE).
    După orice trimitere (manuală sau automată), platforma poll-uiește ANAF până la <strong>Acceptată</strong>.
    La respingere încearcă corectări automate (adresă/sector etc.) și retrimite; erorile nerezolvabile apar pe factură și pot genera alertă către suport.
</div>

<h3>Stări e-Factura în aplicație</h3>
<ul>
    <li><strong>Netrimisă</strong> — nu a intrat în coadă.</li>
    <li><strong>Programată / în coadă</strong> — așteaptă procesare locală sau fereastra de N zile.</li>
    <li><strong>Trimisă (așteaptă validare)</strong> — încărcată, răspuns ANAF în așteptare (verificare automată).</li>
    <li><strong>În prelucrare ANAF</strong> — în curs la ANAF.</li>
    <li><strong>Acceptată ANAF</strong> — validată; editarea / anularea clasică sunt blocate.</li>
    <li><strong>Respinsă ANAF</strong> — considerată netrimisă pentru automatizare; se reîncearcă după corectare. Citește motivul; dacă persistă, corectează datele / storno după caz.</li>
    <li><strong>Eroare trimitere</strong> — problemă tehnică; reîncercare automată + eventual reautorizare SPV.</li>
</ul>

<div class="help-warn">
    După trimisă / în prelucrare / acceptată, nu mai poți edita sau anula factura ca pe un draft.
    Pentru corectarea unei facturi deja în SPV folosește <strong>Storno</strong> și trimite și documentul de stornare.
</div>

<h3>Storno, note de creditare și e-Factura</h3>
<ol class="help-steps">
    <li>Din factura originală emisă: <strong>Stornează</strong> sau emite o <strong>Notă de creditare</strong>.</li>
    <li>Se creează documentul de corecție (linii negative), emis. La storno, atât storno-ul cât și factura originală apar ca <strong>Achitată</strong> și rămân închise.</li>
    <li>În listele <strong>Facturi storno</strong> și <strong>Note de creditare</strong> vezi coloana <strong>e-Factura</strong>, poți selecta documente și apăsa <strong>Trimite în e-Factura</strong> (ca la facturi).</li>
    <li>Poți trimite și din fișa documentului; urmărește acceptarea ANAF.</li>
</ol>

<div class="help-note">
    Proformele, avizele și chitanțele nu se trimit în e-Factura. Doar facturile (inclusiv storno) și notele de creditare participă la flux — în listă apar statusul, ID-urile și acțiunile de trimitere/XML.
</div>

<div class="help-note">
    Automat: după trimitere, sistemul verifică starea până la <strong>Acceptată</strong>. La respingere/eroare încearcă corectări (ex. adresă/sector) și <strong>retrimite</strong> storno-urile și notele de creditare la fel ca facturile (max. 5 încercări/zi, apoi alertă).
</div>

<h3>Date care trebuie să fie corecte înainte de trimitere</h3>
<ul>
    <li>CUI emitent (societate) și CUI/date client complete.</li>
    <li>Adresă, localitate, județ pe câmpuri separate (nu totul lipit în „Adresă”).</li>
    <li>Linii cu produs, UM din listă, cantitate, preț, cotă TVA coerente.</li>
    <li>Serie și număr alocate (document emis).</li>
    <li>Monedă și curs valutar dacă factura nu este în RON.</li>
</ul>

@include('help._figure', [
    'shot' => 'settings-generale',
    'label' => 'Figura 5',
    'caption' => 'Datele generale ale firmei (CUI, adresă) trebuie să fie corecte — ele alimentează XML-ul e-Factura.',
])

@include('help._figure', [
    'shot' => 'clients-form',
    'label' => 'Figura 6',
    'caption' => 'Fișa clientului cu CUI și adresă structurată reduce riscul de respingere ANAF.',
])

<h3>Probleme frecvente</h3>
<ul>
    <li><strong>Neautorizat după ce era ok</strong> — reautorizează SPV; certificatul sau refresh token-ul a expirat.</li>
    <li><strong>Eroare la redirect ANAF</strong> — verifică certificatul în browser și că CUI-ul din aplicație corespunde certificatului.</li>
    <li><strong>Respinsă ANAF</strong> — citește detaliul; corectează datele pe o nouă factură sau storno, după caz.</li>
    <li><strong>Butonul Trimite lipsește</strong> — documentul nu e factură emisă, e deja ok/uploaded, sau tipul nu e eligibil.</li>
    <li><strong>Trimisă dar starea nu se mișcă</strong> — așteaptă refresh-ul automat (~30s) sau Actualizează stare ANAF; verifică ID-urile pe listă. Dacă persistă, contactează {{ config('dateconta.contact_email') }}.</li>
    <li><strong>Respinsă cu cod BR-…</strong> — mesajul ANAF apare pe factură; tipic: CUI fără prefix RO, lipsă scadență, județ/sector București greșit, UM invalidă.</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-show',
    'label' => 'Figura 7',
    'caption' => 'Fișa facturii după trimitere — urmărește eticheta de stare și acțiunea de refresh ANAF.',
])

<h3>Checklist e-Factura</h3>
<ol class="help-steps">
    <li>CUI firmă + date ANAF verificate.</li>
    <li>SPV Autorizat pe societatea activă.</li>
    <li>Mod de trimitere ales conștient (manual vs. automat).</li>
    <li>Factură emisă, date client ok, linii complete.</li>
    <li>Trimite → actualizează stare → arhivează PDF / răspuns.</li>
    <li>Pentru corecții după acceptare: doar storno (+ trimitere storno).</li>
</ol>

<p>
    Aspectul documentului pentru client: <a href="{{ route('help.show', 'pdf-personalizare') }}">PDF și personalizare</a>.
</p>
@endsection
