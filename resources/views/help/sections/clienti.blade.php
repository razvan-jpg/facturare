@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Nomenclatorul de clienți stochează datele partenerilor pe societatea activă. Poți adăuga firme (CIF)
    sau persoane (CNP / „-”), prelua date din ANAF și reutiliza clientul la fiecare factură.
</p>

@include('help._figure', [
    'shot' => 'clients-list',
    'label' => 'Figura 1',
    'caption' => 'Catalog → Clienți — lista clienților societății active, cu căutare și acces la fișă.',
])

<h3>Lista de clienți</h3>
<ol class="help-steps">
    <li>Deschide <strong>Catalog → Clienți</strong>.</li>
    <li>Sus: <strong>Ascunde sold 0</strong> / <strong>Afișează sold 0</strong> — filtrează clienții fără rest de încasat (paginarea ține cont de filtru).</li>
    <li>Caută după denumire, CIF/CNP sau fragment de adresă.</li>
    <li>Coloana <strong>Sold</strong> arată soldul real: rest sold inițial + rest facturi − încasări pe sold.</li>
    <li>Coloana <strong>Penalități</strong> arată sumele calculate până azi și încă nefacturate (apar și cu comutatorul OFF; pe factură ajung doar când e ON).</li>
    <li><strong>Fișă</strong> deschide situația clientului; <strong>Editează</strong> modifică datele.</li>
    <li>La editare, jos: <strong>Actualizează</strong> salvează; <strong>Renunță</strong> te întoarce la listă fără a salva.</li>
</ol>

<h3>Solduri inițiale</h3>
<p>
    Dacă migrezi din alt soft sau ai datorii înainte de facturile din DateConta, poți seta
    <strong>soldul inițial</strong> (sumă + dată) pe fiecare client.
</p>
<ol class="help-steps">
    <li>Din listă, apasă <strong>Solduri inițiale</strong>.</li>
    <li>Opțional: setează o <strong>dată implicită</strong> și apasă <strong>Aplică data la toți</strong>.</li>
    <li>Completează soldul inițial pe fiecare rând; coloana Sold curent previzualizează totalul (inițial + facturi deschise).</li>
    <li>Salvează. Poți modifica soldul și pe fișa de editare a clientului.</li>
</ol>
<div class="help-note">
    Dacă nu completezi soldul inițial, este <strong>0</strong>, iar data implicită este <strong>data creării clientului</strong>.
    Sold real = sold inițial + restul facturilor deschise − încasările alocate pe sold (fără factură).
    Din Emite → Încasare poți încasa întâi soldul inițial, apoi facturile.
    Nu pune în soldul inițial sume deja acoperite de facturi importate/emise aici — altfel dublezi datoria.
</div>
<div class="help-note">
    Pe <strong>Fișă client</strong> (și PDF) vezi soldul inițial, facturile deschise și soldul total.
    Cardul <strong>Neîncasat</strong> din Rapoarte include și soldurile inițiale.
    Tot pe fișă apar <strong>Procent penalizare cf contract</strong>, comutatorul
    <strong>Se calculeaza / factureaza</strong> (ON/OFF — îl poți schimba direct pe fișă), sumarul penalități
    (nefacturate / facturate neîncasate / încasate) și lista detaliată: cele nefacturate apar cu roșu
    („Nefacturate”), iar cele deja facturate arată factura pe care au fost puse.
</div>

<h3>Adăugare client (manual)</h3>
<ol class="help-steps">
    <li>Apasă adăugare client.</li>
    <li>Completează denumirea (obligatoriu în practică pentru facturare).</li>
    <li>Pentru firme: tip identificator CIF și CUI-ul. Pentru persoane: CNP sau „-” dacă nu colectezi CNP.</li>
    <li>Completează adresa, localitatea, județul și țara pe câmpuri separate — la fel ca la societate.</li>
    <li>Opțional: IBAN/cont + <strong>Bancă</strong> (se poate completa automat din IBAN), email, telefon.</li>
    <li>Pentru persoane fizice / administratori: <strong>Nume administrator</strong>, <strong>Prenume administrator</strong>, <strong>CNP administrator</strong>.</li>
    <li>Opțional: <strong>Procent penalizare cf contract</strong> (pe zi) și comutatorul <strong>Se calculeaza / factureaza</strong> (implicit OFF).</li>
    <li>Salvează. Clientul apare imediat la căutarea din formularul de factură.</li>
</ol>
<div class="help-note">
    Penalitățile se calculează pe soldul inițial (scadență 11.08.2026) și pe facturile cu scadență ≥ 11.08.2026,
    pe principalul restant (fără linii de tip penalizare). Cu comutatorul <strong>ON</strong>, sumele nefacturate
    apar automat pe următoarea factură emisă (linie fără TVA). Cu <strong>OFF</strong>, calculul continuă, dar
    liniile nu mai sunt adăugate pe facturi până reactivezi.
    Dacă există abonament <strong>lunar</strong> la client, soldul inițial e împărțit automat în tranșe egale cu valoarea recurentă
    (restul care nu e multiplu exact = tranșă parțială mai veche); ultima scadență 11.08.2026, celelalte lunar pe data de 11.
    Opțional poți forța <strong>Tranșă lunară</strong> + <strong>Nr. tranșe</strong> pe fișa clientului.
    Plățile pe sold acoperă întâi cea mai veche tranșă.
</div>

@include('help._figure', [
    'shot' => 'clients-form',
    'label' => 'Figura 2',
    'caption' => 'Formularul de client — date de identificare, adresă structurată și contact.',
])

<h3>Preluare client din ANAF</h3>
<p>
    Poți completa sau actualiza rapid datele unei firme după CUI din:
</p>
<ul>
    <li><strong>Lista Clienți</strong> — buton <strong>Actualizare ANAF</strong> (sus): actualizează odată toți clienții cu CUI din societatea curentă;</li>
    <li><strong>Client nou</strong> și <strong>Editează client</strong> — zona „Caută după CUI (ANAF)” / <strong>Preluare date</strong>
        (la editare, CUI-ul existent e precompletat);</li>
    <li>formularul de factură — buton <strong>+ ANAF</strong> / Preluare ANAF.</li>
</ul>
<ol class="help-steps">
    <li>Pentru un singur client: introdu CUI-ul (sau folosește-l pe cel din fișă la editare) și apasă <strong>Preluare date</strong>.</li>
    <li>Pentru toți clienții cu CUI: din listă, apasă <strong>Actualizare ANAF</strong> și confirmă. La final apare o fereastră cu statistica (actualizați / fișe modificate / ignorați), care se închide automat în 30 de secunde.</li>
    <li>Verifică denumirea, Reg. Com., adresa, localitatea și județul; la nevoie corectează pe fișa clientului.</li>
    <li>Reține: câmpul Adresă primește strada/numărul — localitatea și județul rămân separate.</li>
    <li>Email, IBAN și notele locale nu sunt suprascrise la actualizarea în masă.</li>
    <li>Din factură poți alege „Adaugă pe factură” pentru a atașa imediat clientul documentului.</li>
</ol>

<div class="help-note">
    Preluarea ANAF funcționează pentru persoane juridice / entități din Registrul ANAF.
    Persoanele fizice (CNP / fără CUI) și CUI-urile negăsite în ANAF sunt omise automat la actualizarea în masă — fără mesaj de eroare.
</div>

<h3>Clientul pe factură</h3>
<ul>
    <li>În formularul de emitere, câmpul client acceptă căutare după „Nume sau CIF/CNP”.</li>
    <li>Selectează din sugestii pentru a evita duplicatele.</li>
    <li>Dacă clientul nu există, îl poți crea din fluxul ANAF sau din Catalog și apoi îl selectezi.</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-create',
    'label' => 'Figura 3',
    'caption' => 'La emitere, selectarea clientului din autocomplete folosește nomenclatorul societății.',
])

<div class="help-warn">
    Clienții aparțin societății active. Dacă lucrezi pe o a doua firmă, nomenclatorul este separat —
    nu vei găsi clienții celeilalte societăți.
</div>

<h3>Bune practici</h3>
<ul>
    <li>Unifică duplicatele (aceeași firmă cu denumiri diferite) editând o singură fișă.</li>
    <li>Păstrează emailul clientului actualizat dacă trimiți PDF pe email din aplicație.</li>
    <li>Pentru clienți UE/export, verifică țara și datele de TVA cerute de tipul de operațiune.</li>
</ul>

<p>
    Continuă cu <a href="{{ route('help.show', 'produse') }}">Produse și servicii</a>.
</p>
@endsection
