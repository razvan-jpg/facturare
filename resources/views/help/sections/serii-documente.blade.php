@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Fiecare tip de document (factură, proformă, aviz, chitanță) folosește o <strong>serie</strong> cu prefix
    și un număr care crește la emitere. Numărul afișat pe document are forma <strong>PREFIX-####</strong>
    (numărul este completat cu zerouri la 4 cifre), de exemplu <code class="help-kbd">FCT-0001</code>,
    <code class="help-kbd">PRF-0012</code>.
</p>

@include('help._figure', [
    'shot' => 'settings-serii',
    'label' => 'Figura 1',
    'caption' => 'Setări → Serii — tip, prefix, an, primul număr DateConta, următorul de emis, implicită și activă.',
])

<div class="help-note">
    La migrare din alt soft (ex. SmartBill): setează <strong>Primul număr folosit în DateConta</strong> și
    <strong>Următorul număr de emis</strong> la același număr (ex. 306). Golurile libere se caută doar de la
    acest prag în sus — nu se reiau SM-0001…SM-0305 emise în afara DateConta.
</div>

<h3>Serii implicite</h3>
<p>
    La crearea societății (sau când un tip de document nu are nicio serie pe anul curent), aplicația creează
    automat serii de start:
</p>
<ul>
    <li><strong>FCT</strong> — facturi</li>
    <li><strong>PRF</strong> — proforme</li>
    <li><strong>AVZ</strong> — avize</li>
    <li><strong>CHT</strong> — chitanțe</li>
    <li><strong>NC</strong> — note de creditare</li>
</ul>
<p>
    Poți adăuga propriile serii, marca una ca implicită și <strong>șterge</strong> seriile create automat
    (ex. FCT), dacă ai deja cel puțin o altă serie pe același tip și an. Nu poți rămâne fără nicio serie
    pe un tip de document: ultima serie de pe tip+an nu se șterge.
</p>

<h3>Cum adaugi sau editezi o serie</h3>
<ol class="help-steps">
    <li>Deschide Setări → <strong>Serii</strong> pentru societatea activă.</li>
    <li>Alege tipul de document (Factură, Proformă, Aviz, Chitanță).</li>
    <li>Completează <strong>Prefix / denumire serie</strong> (ex. SM, FCT).</li>
    <li>Setează <strong>Anul</strong> seriei (emiterea filtrează seriile după anul datei de emitere).</li>
    <li><strong>Primul număr folosit în DateConta</strong> — pragul de la care se caută goluri libere.</li>
    <li><strong>Următorul număr de emis</strong> — ce se alocă când nu există goluri (≥ primul număr).</li>
    <li>Bifează <strong>Implicită</strong> dacă vrei ca formularul de emitere să o preselecteze.</li>
    <li>Lasă seria <strong>Activă</strong>. O serie inactivă nu apare la emitere.</li>
    <li>Salvează.</li>
</ol>

@include('help._figure', [
    'shot' => 'documents-create',
    'label' => 'Figura 2',
    'caption' => 'La emitere, câmpul Serie și număr arată previzualizarea PREFIX-#### („se va emite”).',
])

<h3>Ce se întâmplă la emitere</h3>
<ul>
    <li>La <strong>Salvează draft</strong>, numărul final poate rămâne rezervat doar ca previzualizare — emiterea propriu-zisă alocă numărul.</li>
    <li>La <strong>Salvează și emite</strong> / acțiunea Emite, documentul primește <code>number_full</code> = prefix + „-” + număr pe 4 cifre.</li>
    <li>Se preferă cel mai mic <strong>gol liber ≥ primul număr DateConta</strong>; altfel se folosește următorul număr de emis.</li>
    <li>Contorul seriei avansează la următorul număr liber.</li>
    <li>Dacă anulezi sau ștergi ultimul document emis (în condițiile permise), numărul poate fi eliberat înapoi pe serie (doar dacă e ≥ primul număr).</li>
</ul>

<div class="help-warn">
    Nu scade manual „Următorul număr” sau „Primul număr DateConta” sub documente deja emise aici — riști coliziuni.
    Crește contoarele doar înainte (sau creează o serie nouă pe alt prefix/an).
</div>

<h3>Serii pe an</h3>
<p>
    Formularul de emitere listează seriile active pentru <strong>anul datei de emitere</strong>.
    Dacă schimbi data facturii într-un an fără serii, vei vedea mesajul că nu există serii active —
    creează-le din Setări → Serii pentru acel an.
</p>

<div class="help-note">
    Pentru început de an: duplică logica (același prefix, an nou, următorul număr = 1) sau creează serii noi
    înainte de 1 ianuarie, ca să nu blochezi emiterea.
</div>

<h3>Mai multe serii pe același tip</h3>
<ul>
    <li>Poți avea FCT pentru clienți interni și ALT pentru export, pe același tip Factură.</li>
    <li>Doar una ar trebui marcată Implicită per tip (pentru selecție rapidă).</li>
    <li>Alege seria corectă pe fiecare document înainte de emitere.</li>
    <li>După ce ai adăugat seria ta și ai marcat-o implicită, poți șterge seria de start (FCT/PRF/…) dacă nu are documente emise pe ea.</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-show',
    'label' => 'Figura 3',
    'caption' => 'Pe documentul emis, numărul complet PREFIX-#### apare în antetul paginii și pe PDF.',
])

<p>
    Cu seriile pregătite, treci la <a href="{{ route('help.show', 'clienti') }}">Clienți</a>.
</p>
@endsection
