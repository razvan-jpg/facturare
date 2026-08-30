@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Listele din meniul <strong>Liste</strong> afișează documentele societății active pe tip: Facturi, Proforme,
    Avize, Chitanțe. De aici filtrezi, deschizi un document, emiți draft-uri, descarci PDF, trimiți e-Factura
    sau aplici anulare / storno când regulile o permit.
</p>

@include('help._figure', [
    'shot' => 'documents-list',
    'label' => 'Figura 1',
    'caption' => 'Lista documentelor — filtre, stări și acces la fișa fiecărui document.',
])

<h3>Filtrare și căutare</h3>
<ol class="help-steps">
    <li>Deschide tipul dorit din Liste (ex. Facturi).</li>
    <li>Filtrează după perioadă, client, stare document, stare plată sau stare e-Factura (unde există).</li>
    <li>Lista e ordonată după ultima actualizare / dată / număr (cele recente apar sus).</li>
    <li>Deschide rândul pentru pagina de detaliu.</li>
</ol>
<p>
    Numărul de rânduri pe pagină se setează la Setări → Preferințe generale
    (<a href="{{ route('help.show', 'preferinte') }}">Preferințe și limbi</a>).
</p>

<h3>Stări document</h3>
<ul>
    <li><strong>Ciornă</strong> — neemis; poți edita liber și apoi Emite.</li>
    <li><strong>Emisă</strong> — are număr PREFIX-####; PDF disponibil.</li>
    <li><strong>Anulată</strong> — document anulat în aplicație.</li>
    <li><strong>Storno</strong> — factură de stornare (linii negative) legată de original.</li>
</ul>

<h3>Stări de plată (facturi)</h3>
<ul>
    <li>Neachitată · Parțial achitată · Achitată — actualizate de încasări.</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-show',
    'label' => 'Figura 2',
    'caption' => 'Fișa documentului — acțiunile disponibile depind de stare și de e-Factura.',
])

<h3>Editare</h3>
<ol class="help-steps">
    <li>Deschide documentul.</li>
    <li>Apasă <strong>Editează</strong> dacă este draft sau emisă și nu a fost trimisă / nu este în prelucrare / acceptată e-Factura. Din editare, <strong>Renunță</strong> te întoarce la fișa documentului fără a salva.</li>
    <li>Modifică liniile sau antetul și salvează.</li>
</ol>

<div class="help-warn">
    După încărcarea în e-Factura (uploaded / processing / ok), editarea este blocată.
    Corecțiile fiscale se fac prin storno (sau conform procedurii tale legale), nu prin rescrierea PDF-ului.
</div>

<h3>Emitere din draft</h3>
<ul>
    <li>Din fișa draft-ului: acțiunea <strong>Emite</strong> alocă numărul pe serie.</li>
    <li>Verifică seria și data înainte — anul datei trebuie să aibă serie activă.</li>
</ul>

<h3>Anulare</h3>
<ol class="help-steps">
    <li>Anularea este disponibilă pentru documente emise care nu au fost trimise în e-Factura (conform regulilor din aplicație).</li>
    <li>Confirmă dialogul „Anulezi documentul?”.</li>
    <li>În anumite cazuri, dacă anulezi ultimul număr emis, contorul seriei poate fi eliberat.</li>
</ol>

<h3>Storno și notă de creditare</h3>
<ol class="help-steps">
    <li>Din Emite / Document nou: <strong>Factură storno</strong> sau <strong>Notă de creditare</strong> — alegi factura emisă.</li>
    <li>Sau din fișa / lista facturii: <strong>Stornează</strong> / <strong>Notă credit</strong>.</li>
    <li>Se creează un document cu linii negative, emis (storno pe serie factură; NC pe serie notă de creditare).</li>
    <li>La storno, storno-ul și factura originală primesc automat starea de plată <strong>Achitată</strong>.</li>
    <li>Trimite corecția în e-Factura când este cazul.</li>
</ol>

<div class="help-note">
    Storno și nota de creditare sunt permise și dacă factura originală a fost deja transmisă în e-Factura.
    Nu poți emite ambele pe aceeași factură. După storno, ambele documente rămân Achitate (nu apar la „de încasat”).
</div>

<h3>Ștergere</h3>
<ul>
    <li>Ștergerea definitivă nu este disponibilă pentru storno sau pentru documente deja trimise în e-Factura.</li>
    <li>Confirmă „Ștergi definitiv documentul?” doar când ești sigur — acțiunea este ireversibilă.</li>
</ul>

@include('help._figure', [
    'shot' => 'documents-list',
    'label' => 'Figura 3',
    'caption' => 'Din listă poți urmări și starea e-Factura; acțiunile în masă (ex. trimitere) pot fi disponibile pe Facturi.',
])

@include('help._figure', [
    'shot' => 'dashboard',
    'label' => 'Figura 4',
    'caption' => 'Draft-urile și facturile de încasat de pe Dashboard trimit tot către aceste liste / fișe.',
])

<p>
    Următorul pas operațional: <a href="{{ route('help.show', 'incasari') }}">Încasări</a>.
</p>
@endsection
