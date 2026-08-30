@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Dashboard-ul este prima pagină după autentificare. Oferă o privire de ansamblu asupra societății active
    și poți personaliza ce widget-uri apar pe panou.
</p>

@include('help._figure', [
    'shot' => 'dashboard',
    'label' => 'Figura 1',
    'caption' => 'Panoul principal — widget-uri personalizabile pentru societatea activă.',
])

<h3>Personalizare panou</h3>
<ol class="help-steps">
    <li>Apasă tile-ul <strong>Adaugă element nou</strong> (cu +).</li>
    <li>Alege o categorie (Toate, Analiza vânzărilor, Gestiunea încasărilor, Diverse) și selectează widgetul.</li>
    <li>În <strong>Gestiunea încasărilor</strong> găsești și <strong>Penalități nefacturate</strong> (clienți cu sume calculate până azi, încă nefacturate).</li>
    <li>În <strong>Diverse</strong> găsești <strong>Activități efectuate</strong> (emiteri/încasări recente) și <strong>Activități viitoare</strong> (abonamente recurente programate).</li>
    <li>Apasă <strong>Adaugă</strong>. Poți avea maxim 12 widget-uri.</li>
    <li>Pe bara de sus a fiecărui tile: <strong>mută</strong> (trage de punctele din stânga sau de bară), <strong>reîmprospătează</strong>, meniu ⋮ cu Configurează / Detalii / Șterge.</li>
    <li><strong>Configurează</strong> deschide setările tile-ului (sortare, filtre, monedă etc.); salvezi cu ✓.</li>
    <li><strong>Detalii</strong> arată explicații despre ce afișează widget-ul; închizi cu ✓.</li>
    <li>Opțional: <strong>Resetează layout</strong> din modal pentru a reveni la aranjamentul implicit.</li>
</ol>

<h3>Widget-uri disponibile</h3>
<ul>
    <li><strong>Sume de încasat</strong> — total de recuperat; bară Depășit vs În termen și defalcare pe intervale.</li>
    <li><strong>Top clienți / Top produse</strong> — ranking pe luna curentă.</li>
    <li><strong>Activități efectuate</strong> — ultimele documente emise și încasări.</li>
    <li><strong>Activități viitoare</strong> — recurente active, ordonate după următoarea emitere.</li>
    <li><strong>Numerar &amp; scadențe</strong> — cash luna curentă + încasări și scadente azi / 7 zile.</li>
    <li><strong>Vânzări / Încasări / Grafic încasări</strong> — totaluri și grafice zilnice pe luna curentă.</li>
    <li><strong>Sold clienți</strong> — cele mai mari solduri deschise.</li>
    <li><strong>Penalități nefacturate</strong> — clienții cu penalități calculate până azi și încă nefacturate (doar cei cu sumă &gt; 0), descrescător; click pe nume deschide fișa clientului.</li>
    <li><strong>Facturi neîncasate</strong> — listă scurtă cu rest, scadență și întârzieri.</li>
</ul>

<h3>Cum folosești panoul zilnic</h3>
<ol class="help-steps">
    <li>Verifică societatea activă din antet — toate widget-urile aparțin firmei selectate.</li>
    <li>Urmărește sumele de încasat și facturile restante; deschide documentul pentru a înregistra o plată.</li>
    <li>Consultă graficele de vânzări/încasări pentru ritmul lunii curente.</li>
    <li>Apasă <strong>Document nou</strong> pentru emitere rapidă sau mergi la <a href="{{ route('help.show', 'rapoarte') }}">Rapoarte</a> pentru export și filtre pe perioadă.</li>
</ol>

<div class="help-warn">
    Layout-ul ales e salvat pe contul tău (nu pe societate). Datele din widget-uri țin totuși de societatea activă.
</div>

<p>
    Continuă cu configurarea firmei: <a href="{{ route('help.show', 'societate') }}">Societatea (firma ta)</a>.
</p>
@endsection
