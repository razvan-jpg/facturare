@extends(($embed ?? false) ? 'help.embed' : 'help._layout')

@section('heading', $meta['title'])
@section('subheading', $meta['subtitle'])

@section('help')
<h2>{{ $meta['title'] }}</h2>
<p class="help-lead">{{ $meta['subtitle'] }}</p>

<p>
    Pe DateConta Facturare există mai multe tipuri de conturi care pot lucra pe o societate.
    Mai jos: ce înseamnă fiecare, cum îi adaugi și ce poți (sau nu) șterge.
</p>

<div class="help-note">
    Meniul <strong>Setări → Utilizatori</strong> (și <strong>Abonament utilizatori</strong>) apare doar pentru
    <strong>proprietarul</strong> societății. Subuserii și invitații obișnuiți nu văd aceste opțiuni și nu pot
    adăuga societăți noi în numele tău.
</div>

@include('help._figure', [
    'shot' => 'users-nav-setari',
    'label' => 'Figura 1',
    'caption' => 'Setări → Utilizatori — vizibil doar pentru proprietarul firmelor.',
])

<h3>Tipuri de utilizatori</h3>

<h4>1. Utilizator principal (proprietar)</h4>
<p>
    Contul cu care ai creat societatea (sau societățile). Administrezi firmele, abonamentul platformei,
    locurile de colaboratori și meniul Utilizatori. Nu ești „subuser” pe propriile firme.
</p>

<h4>2. Subuser creat</h4>
<p>
    Cont <strong>nou</strong>, creat de tine din Setări → Utilizatori (email + parolă pe care le setezi tu).
    Se autentifică la <a href="https://factura.dateconta.ro">https://factura.dateconta.ro</a> cu acele date
    și vede doar firmele și drepturile pe care i le aloci. Moștenește accesul tău la platformă; nu poate crea
    alți subuseri și nu plătește abonamentul.
</p>
<p>
    La salvarea societăților/drepturilor primește un email: cine l-a creat, de la ce societate, datele de login
    și lista firmelor cu drepturi. Semnătură: <em>Cu drag, Echipa DateConta</em>.
</p>

<h4>3. Utilizator invitat</h4>
<p>
    Un cont <strong>deja existent</strong> în DateConta Facturare. Îl adaugi introducând același email la
    „Adaugă utilizator” — fără parolă nouă. Pe firmele tale se comportă ca un colaborator (drepturile pe care
    i le bifezi); pe firmele <em>lui</em> rămâne proprietar ca înainte.
</p>
<p>
    Primește email de <strong>invitație</strong> (nu de „cont creat”). Poți <strong>Revoca accesul</strong>
    la societățile tale — contul lui nu se șterge.
</p>

<h4>4. Administrator invitat</h4>
<p>
    Poți invita și un cont de <strong>administrator</strong> al platformei (de exemplu pentru suport).
    Odată invitat pe o societate a ta:
</p>
<ul>
    <li>păstrează <strong>tot timpul</strong> comportamentul și drepturile complete de admin;</li>
    <li><strong>nu mai poate fi scos</strong> de pe societățile pe care l-ai alocat (poți doar adăuga altele);</li>
    <li>nu consumă un loc din abonamentul de utilizatori.</li>
</ul>

@include('help._figure', [
    'shot' => 'users-list',
    'label' => 'Figura 2',
    'caption' => 'Lista colaboratorilor: subuser creat, invitat sau admin invitat.',
])

<h3>Cum adaugi un colaborator</h3>
<ol class="help-steps">
    <li>Setări → <strong>Utilizatori</strong> → <strong>Adaugă utilizator</strong>.</li>
    <li>Introdu <strong>emailul</strong>. Dacă există deja, formularul se completează cu numele din cont
        și câmpurile de parolă se dezactivează (invitație). Dacă e liber, completezi nume + parolă
        pentru un <strong>subuser nou</strong>.</li>
    <li>Alocă societățile și drepturile, apoi <strong>Salvează</strong> (se trimite emailul).</li>
</ol>

@include('help._figure', [
    'shot' => 'users-create',
    'label' => 'Figura 3',
    'caption' => 'Email nou = subuser; email existent = invitație (inclusiv admin).',
])

<h3>Drepturi pe societate</h3>
<p>
    Pentru subuseri și invitați (nu admin): bifezi accesul pe firmă, apoi pe categorii
    <strong>Vizualizare</strong> și/sau <strong>Creare / editare</strong>.
    Fără nicio bifă pe o categorie = fără acces acolo.
</p>
<p>
    Categorii: Documente, Clienți, Produse și servicii, Încasări, Facturi recurente, Rapoarte,
    e-Factura ANAF, Setări firmă.
</p>

@include('help._figure', [
    'shot' => 'users-permissions',
    'label' => 'Figura 4',
    'caption' => 'Matricea de drepturi pe firmă (nu se aplică adminului invitat — are acces complet).',
])

<h3>Profil, parolă și ștergere</h3>
<ul>
    <li><strong>Contul meu:</strong> oricine își poate schimba numele, emailul și parola.</li>
    <li><strong>Nimeni nu își șterge singur contul</strong> din Contul meu.</li>
    <li><strong>Subuser creat:</strong> tu poți <strong>Șterge utilizator</strong> (închizi contul lui de subuser)
        și îi poți reseta parola.</li>
    <li><strong>Utilizator invitat:</strong> doar <strong>Revocă accesul</strong> de pe firmele tale.</li>
    <li><strong>Admin invitat:</strong> nu se revocă și nu se șterge de pe societățile alocate.</li>
</ul>

@include('help._figure', [
    'shot' => 'users-profile',
    'label' => 'Figura 5',
    'caption' => 'Contul meu — profil și parolă; fără auto-ștergere a contului.',
])

<h3>Abonament locuri (de la 01.04.2027)</h3>
<p>
    Din <strong>1 aprilie 2027</strong>, fiecare <strong>subuser creat</strong> și fiecare
    <strong>utilizator invitat</strong> (non-admin) pe firmele tale necesită un loc cumpărat de tine
    la <strong>1 EUR / loc / lună</strong> (+ TVA). Adminii invitați nu ocupă loc.
</p>

@include('help._figure', [
    'shot' => 'users-seats',
    'label' => 'Figura 6',
    'caption' => 'Comandă locuri (1 EUR / loc / lună + TVA) — pentru subuseri și invitați obișnuiți.',
])

<p>
    Vezi și <a href="{{ route('help.show', 'navigare') }}">Navigare și interfață</a>
    sau <a href="{{ route('help.show', 'intrebari') }}">Întrebări frecvente</a>.
</p>
@endsection
