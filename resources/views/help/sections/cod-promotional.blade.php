@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Fiecare societate din DateConta Facturare primește automat un <strong>cod promoțional unic</strong>
    în formatul <code>XXXX-XXXX-XXXX</code>. Îl poți partaja cu alți antreprenori: când ei creează o firmă
    folosind codul tău, amândoi câștigați timp de acces în plus.
</p>

@include('help._figure', [
    'shot' => 'promo-account-menu-v2',
    'label' => 'Figura 1',
    'caption' => 'Click pe numele societății din antet → se deschide meniul contului; rândul „Cod promoțional” (ex. FJ7U-…) se copiază cu un click.',
])

<h3>Unde găsești codul</h3>
<p>Codul aparține societății active (nu contului personal). Îl poți vedea în trei locuri:</p>
<ol class="help-steps">
    <li>
        <strong>Meniul contului</strong> — click pe numele societății din antet. În panou apare rândul
        „Cod promoțional”; un click pe cod îl copiază în clipboard (mesaj scurt „Copiat!”).
        De aici poți apăsa și <strong>Trimite mail recomandare</strong>.
    </li>
    <li>
        <strong>Setări → Date generale</strong> — câmpul „Cod promoțional”, tot cu click pentru copiere,
        plus butonul <strong>Trimite mail recomandare</strong>.
        Textul nu se poate modifica manual.
    </li>
    <li>
        <strong>Lista societăților</strong> (Setări → Societățile mele) — coloana „Cod promoțional”,
        din nou click pentru copiere.
    </li>
</ol>

@include('help._figure', [
    'shot' => 'promo-settings-generale',
    'label' => 'Figura 2',
    'caption' => 'Setări → Date generale — codul promoțional al firmei, generat automat și unic.',
])

@include('help._figure', [
    'shot' => 'promo-companies-list',
    'label' => 'Figura 3',
    'caption' => 'Lista societăților — fiecare firmă din cont are propriul cod, pe care îl poți copia cu un click.',
])

<div class="help-note">
    Dacă administrezi mai multe firme, fiecare are un cod diferit. Partajează codul societății pe care
    vrei să o „recomanzi” — bonusul pentru recomandări se aplică pe contul proprietarului acelei societăți.
</div>

<h3>Trimite mail recomandare</h3>
<ol class="help-steps">
    <li>Deschide meniul societății (sau Setări → Date generale) și apasă <strong>Trimite mail recomandare</strong>.</li>
    <li>Introdu una sau mai multe adrese (separate prin virgulă) și trimite.</li>
    <li>Destinatarul primește un email personalizat de la firma ta, cu <strong>codul promoțional mare</strong>
        și instrucțiuni: la înregistrare / creare societate folosește codul — câștigați amândoi perioadă promoțională.</li>
    <li>Emailul e în <strong>limba de lucru</strong> a destinatarului (dacă are cont) sau a ta (dacă adresa e nouă).</li>
</ol>

<h3>Cum funcționează recomandarea</h3>
<p>
    Cineva care își creează o societate nouă poate bifa „Ai un cod promoțional?” și introduce codul tău
    (forma <code>XXXX-XXXX-XXXX</code>). Codul trebuie să fie valid și să nu aparțină unei societăți pe care
    o deține deja același utilizator.
</p>

@include('help._figure', [
    'shot' => 'promo-company-create',
    'label' => 'Figura 4',
    'caption' => 'Formularul „Adaugă societate” — bifează că ai un cod, apoi introdu-l înainte de a salva firma.',
])

<h3>Recompense</h3>
<ul>
    <li>
        <strong>Cine folosește codul</strong> (societatea nou creată) — proprietarul contului primește
        <strong>+{{ (int) config('dateconta.referral.invitee_bonus_days', 14) }} zile</strong>
        (2 săptămâni) la perioada de acces.
    </li>
    <li>
        <strong>Cine a recomandat</strong> (proprietarul societății cu codul partajat) — la fiecare
        <strong>{{ (int) config('dateconta.referral.referrer_every', 2) }} societăți</strong> aduse prin
        codul său primește
        <strong>+{{ (int) config('dateconta.referral.referrer_bonus_months', 1) }} lună</strong>
        la acces. Exemplu: 2 firme aduse → +1 lună; 4 firme → încă +1 lună (în total 2 luni acordate).
    </li>
</ul>

<div class="help-note">
    Bonusurile prelungesc data de acces a contului (<em>access_until</em>), peste perioada promoțională
    a platformei (gratuit până la 31.03.2027) sau peste trial. Le vezi și în meniul contului, la
    „Promoții primite” / numărul de societăți aduse.
</div>

@include('help._figure', [
    'shot' => 'promo-landing',
    'label' => 'Figura 5',
    'caption' => 'Pe pagina publică există și secțiunea „Recomandă & câștigă” — același mecanism, explicat pe scurt pentru invitați.',
])

<h3>Pași practici (tu recomanzi)</h3>
<ol class="help-steps">
    <li>Deschide meniul contului sau Setări → Date generale și copiază codul.</li>
    <li>Trimite-l pe email, WhatsApp etc. împreună cu linkul {{ url('/') }}.</li>
    <li>Persoana își creează contul (dacă nu are), apoi o societate nouă și introduce codul tău.</li>
    <li>Tu primești bonusul la fiecare 2 societăți validate; el primește +2 săptămâni imediat.</li>
</ol>

<h3>Pași practici (ți s-a dat un cod)</h3>
<ol class="help-steps">
    <li>Autentifică-te și alege <strong>Adaugă societate</strong>.</li>
    <li>Completează datele firmei (poți prelua din ANAF după CUI).</li>
    <li>Bifează „Ai un cod promoțional?”, introdu codul și salvează.</li>
    <li>Bonusul de +2 săptămâni se aplică pe contul tău la creare.</li>
</ol>

<div class="help-warn">
    Codul se folosește <strong>doar la crearea societății</strong>. Nu îl poți atașa ulterior unei firme
    deja existente. Nu poți folosi codul unei societăți pe care o deții deja din același cont.
</div>

<h3>Întrebări scurte</h3>
<ul>
    <li><strong>Pot schimba codul?</strong> — Nu. Este generat automat și rămâne fix.</li>
    <li><strong>Câte firme pot aduce?</strong> — Nelimitat; bonusul de +1 lună se acordă din 2 în 2.</li>
    <li><strong>Unde văd câte am adus?</strong> — În meniul contului, la promoții / societăți aduse.</li>
</ul>

<p>
    Legat de acces și abonament: <a href="{{ route('help.show', 'cont-acces') }}">Cont și autentificare</a>.
    Pentru alte întrebări: <a href="{{ route('help.show', 'intrebari') }}">Întrebări frecvente</a>
    sau {{ config('dateconta.contact_email') }}.
</p>
@endsection
