@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Poți lăsa clientul să plătească factura online cu cardul (NETOPIA, Eu Plătesc, Mollie sau Stripe).
    Credențialele se configurează <strong>per firmă</strong> — fiecare societate își pune propriile chei de merchant.
    Nu confunda cu plățile de abonament DateConta: acelea folosesc cheile FLY DAVID din meniul Admin și nu îți încasează facturile.
</p>

<div class="help-note">
    „Card” ca metodă la <a href="{{ route('help.show', 'incasari') }}">Încasări</a> înseamnă doar că ai înregistrat manual o plată cu cardul.
    Această pagină descrie <strong>plata online</strong>: link pe factură → clientul plătește → aplicația marchează încasarea.
</div>

@include('help._figure', [
    'shot' => 'settings-integrari',
    'label' => 'Figura 1',
    'caption' => 'Setări → Integrări — configurația NETOPIA / Eu Plătesc / Mollie / Stripe pentru firma activă.',
])

<h3>1. Configurează procesatorul (o dată per firmă)</h3>
<ol class="help-steps">
    <li>Selectează societatea activă din meniu.</li>
    <li>Deschide <strong>Setări → Integrări</strong> (tabul „Integrări” / „Plată cu cardul”).</li>
    <li>Alege procesatorul: <strong>NETOPIA</strong>, <strong>Eu Plătesc</strong>, <strong>Mollie</strong> sau <strong>Stripe</strong>.</li>
    <li>Completează credențialele din <strong>contul tău</strong> de merchant (semnătură / MID / API key / pk+sk, fișiere certificat dacă e NETOPIA).</li>
    <li>Bifează <strong>Activează</strong> și, dacă testezi, lasă modul sandbox / test.</li>
    <li>Salvează. Statusul trebuie să arate că procesatorul e <strong>activ</strong>.</li>
</ol>

<div class="help-warn">
    URL-urile de confirmare (IPN / silent / webhook) afișate în ecran trebuie setate și în panoul procesatorului,
    altfel plata poate reuși la bancă dar factura rămâne neîncasată în DateConta.
</div>

<h3>Ce date ai nevoie (pe scurt)</h3>
<ul>
    <li><strong>NETOPIA</strong> — semnătură merchant + certificat public (.cer) + cheie privată (.key); opțional sandbox.</li>
    <li><strong>Eu Plătesc</strong> — Merchant ID (MID) + cheie secretă (KEY); opțional sandbox.</li>
    <li><strong>Mollie</strong> — API key (<code class="help-kbd">test_…</code> sau <code class="help-kbd">live_…</code>).</li>
    <li><strong>Stripe</strong> — publishable key (<code class="help-kbd">pk_…</code>) + secret key (<code class="help-kbd">sk_…</code>); webhook opțional.</li>
</ul>

<h3>2. Permite plata pe factură</h3>
<ol class="help-steps">
    <li>Creează sau editează o factură / proformă (sau abonament recurent).</li>
    <li>În zona de jos (subsol), bifează <strong>Permite plata cu cardul online</strong>.</li>
    <li>Bifa e activă doar dacă ai cel puțin un procesator configurat pentru firma curentă.
        Altfel vezi mesajul că trebuie setat din Setări → Integrări.</li>
    <li>Salvează și emite documentul.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-card-payment',
    'label' => 'Figura 2',
    'caption' => 'Pe formularul de factură: bifa „Permite plata cu cardul online”. Dacă e gri, configurează mai întâi un procesator în Integrări.',
])

<h3>3. Cum plătește clientul</h3>
<ol class="help-steps">
    <li>Pe PDF și în email apar linkurile doar pentru procesatoarele <strong>active</strong> ale firmei.</li>
    <li>Clientul deschide linkul, completează datele cardului pe pagina procesatorului (nu pe DateConta).</li>
    <li>La întoarcerea din plată, DateConta sincronizează statusul (ca la Mollie): dacă procesatorul trimite confirmarea pe return sau IPN-ul ajunge imediat, documentul se marchează încasat fără intervenție manuală.</li>
    <li>După confirmare (IPN / webhook / sync la return), documentul e încasat (sau parțial, după sumă).</li>
    <li>La <strong>proformă</strong>: după încasare se emite automat factura fiscală cu data plății.</li>
    <li>La <strong>facturi recurente</strong>: bifa de pe șablon se moștenește pe facturile generate.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-pdf-card',
    'label' => 'Figura 3',
    'caption' => 'Pe PDF, zona de plată cu cardul — linkuri către procesator (când bifa e activă la emitere).',
])

<h3>Test vs. producție</h3>
<ul>
    <li>Cu sandbox / cheie <code class="help-kbd">test_</code> poți verifica fluxul fără bani reali.</li>
    <li>Pentru clienți reali: dezactivează sandbox și folosește chei live; verifică din nou URL-urile în panoul merchant.</li>
    <li>Fiecare firmă din contul tău trebuie configurată separat — setările nu se moștenesc de la o societate la alta.</li>
</ul>

<div class="help-note">
    <strong>Admin → Abonament DateConta (FLY DAVID)</strong> conține cheile platformei pentru încasarea abonamentelor DateConta.
    Nu le folosești și nu le vezi ca utilizator obișnuit — facturile tale se încasează doar cu cheile din Setări → Integrări.
</div>

<p>
    Vezi și: <a href="{{ route('help.show', 'emitere-factura') }}">Emitere factură</a>,
    <a href="{{ route('help.show', 'incasari') }}">Încasări (manuale)</a>,
    <a href="{{ route('help.show', 'societate') }}">Societatea</a>.
</p>
@endsection
