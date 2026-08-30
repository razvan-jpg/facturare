@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Contul DateConta Facturare îți oferă acces la toate societățile pe care le administrezi.
    Fără autentificare nu poți emite documente, modifica setările sau trimite e-Factura.
</p>

@include('help._figure', [
    'shot' => 'login',
    'label' => 'Figura 1',
    'caption' => 'Ecranul de autentificare — panou brand + formular cu email și parolă.',
])

<h3>Înregistrare cont nou</h3>
<ol class="help-steps">
    <li>Deschide pagina de înregistrare din site-ul DateConta Facturare.</li>
    <li>Completează numele, adresa de email și o parolă sigură.</li>
    <li>Confirmă datele și creează contul. Vei fi redirecționat către autentificare sau direct în aplicație.</li>
    <li>La primul login, creează sau activează o societate — fără societate activă nu poți emite documente.</li>
</ol>

<div class="help-note">
    Folosește o adresă de email la care ai acces permanent. Pe aceasta pot ajunge invitații pentru autorizarea
    e-Factura (contabil) și eventual notificări legate de cont.
</div>

<h3>Autentificare</h3>
<ol class="help-steps">
    <li>Accesează pagina de login.</li>
    <li>Introdu emailul și parola contului.</li>
    <li>După autentificare ajungi pe Dashboard-ul societății active.</li>
</ol>

@include('help._figure', [
    'shot' => 'landing',
    'label' => 'Figura 2',
    'caption' => 'Pagina publică de unde poți trece la login sau la crearea contului.',
])

<h3>Abonament și acces</h3>
<p>
    Perioada promoțională actuală oferă utilizare <strong>gratuită până la 31.03.2027</strong>.
    Conturile noi create <strong>după 31.03.2027</strong> primesc automat
    <strong>{{ (int) config('dateconta.trial_months_after_promo', 6) }} luni gratuite</strong>
    de la data înregistrării; apoi se aplică abonamentul.
    Poți prelungi accesul și prin <a href="{{ route('help.show', 'cod-promotional') }}">codul promoțional</a>
    (recomandări).
</p>
<ul>
    <li>În perioada gratuită / de probă ai acces complet la funcțiile aplicației (în limitele configurării serverului, ex. chei ANAF).</li>
    <li>Dacă perioada expiră și nu există un plan activ, vei vedea ecranul „Acces suspendat”.</li>
    <li>Conturile de tip administrativ sau abonamentele plătite păstrează accesul fără întrerupere.</li>
    <li>La plata abonamentului cu card <strong>NETOPIA</strong>, încasarea și factura fiscală sunt în <strong>RON</strong>,
        la cursul <strong>BNR + {{ (int) round((((float) config('dateconta.subscription.netopia_ron_markup', 1.02)) - 1) * 100) }}%</strong>.
        La întoarcerea din plată, statusul e sincronizat automat (nu e nevoie să marchezi manual încasarea).</li>
</ul>

<h3>Aplicația iPhone / iPad (App Store)</h3>
<p>
    Aplicația mobilă este <strong>gratuită până la 31.03.2027</strong> pentru toți.
    Din <strong>01.04.2027</strong>:
</p>
<ul>
    <li><strong>Conturile existente</strong> (create în perioada gratuită) trec pe <strong>abonament App Store</strong>.</li>
    <li><strong>Conturile noi</strong> primesc <strong>1 lună de test</strong> pe iOS de la înregistrare, apoi abonament App Store.</li>
</ul>
<p>
    Abonamentul App Store: 1 lună, 3 luni, 6 luni sau 1 an (de la ~0,99&nbsp;USD/lună),
    reînnoibil automat (poți anula din Setări Apple).
</p>
<div class="help-note">
    Abonamentul din App Store deblochează <strong>doar aplicația iOS</strong>.
    Abonamentul web (card / OP pe factura.dateconta.ro) este <strong>separat</strong> și nu se înlocuiesc reciproc.
    Pe web, conturile noi după 31.03.2027 primesc
    <strong>{{ (int) config('dateconta.trial_months_after_promo', 6) }} luni</strong> de probă (nu 1 lună ca pe iOS).
</div>
<ul>
    <li>În app: <strong>Setări → Abonament aplicație</strong> — status (gratuit / test / activ), Abonează-te (alegi perioada), Restaurează cumpărăturile, Gestionează în App Store.</li>
    <li>Dacă accesul iOS expiră, apare ecranul de abonament; datele rămân pe cont (web și sync după reactivare).</li>
    <li><strong>Ștergere cont</strong>: în Setări (app) → <strong>Șterge contul</strong> (confirmare cu parolă). Contul nu mai poate fi folosit la autentificare; datele de business rămân arhivate pe server.</li>
</ul>

<div class="help-warn">
    Dacă vezi mesajul că perioada gratuită sau de probă s-a încheiat, contactează
    {{ config('dateconta.contact_email') }}. Nu șterge societățile sau documentele — datele rămân pe cont.
</div>

<h3>Sesiune și deconectare</h3>
<ul>
    <li>Rămâi autentificat cât timp sesiunea browserului este validă.</li>
    <li>La finalul lucrului pe un calculator partajat, folosește deconectarea din meniul contului.</li>
    <li>Nu partaja parola. Pentru colaborare pe e-Factura există fluxul de invitație contabil (vezi capitolul e-Factura).</li>
</ul>

@include('help._figure', [
    'shot' => 'dashboard',
    'label' => 'Figura 3',
    'caption' => 'După login reușit, panoul principal confirmă că ai acces la societatea activă.',
])

<h3>Probleme frecvente la acces</h3>
<ul>
    <li><strong>Parolă uitată</strong> — din login, „Ai uitat parola?”: introduci emailul și primești link de resetare; sau contactează suportul.</li>
    <li><strong>Nu văd documente</strong> — verifică societatea activă din meniul superior.</li>
    <li><strong>Acces suspendat</strong> — perioada promo / trial a expirat; vezi mai sus.</li>
    <li><strong>Ștergere cont (iOS)</strong> — Setări → Șterge contul, cu confirmare prin parolă.</li>
</ul>

<p>
    După ce ai acces, continuă cu <a href="{{ route('help.show', 'navigare') }}">Navigare și interfață</a>
    pentru a înțelege meniul Emite, Liste, Catalog, Rapoarte, Ajutor și Setări.
</p>
@endsection
