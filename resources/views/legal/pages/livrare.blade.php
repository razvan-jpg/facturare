@extends(($embed ?? false) ? 'legal.embed' : 'legal._layout')

@section('legal')
<h2>{{ __($meta['title']) }}</h2>
<p class="help-lead">{{ __($meta['subtitle']) }}</p>
<p class="help-meta-line">{{ __('Ultima actualizare: :date · Versiune document: :version', ['date' => \Illuminate\Support\Carbon::parse($meta['updated'])->format('d.m.Y'), 'version' => '2.0']) }}</p>

<div class="help-note">
    {!! __('Prezenta <strong>Politică de livrare comandă</strong> explică modul în care <strong>:operator</strong> livrează (activează) Abonamentul <strong>:brand</strong> după plasarea unei Comenzi. Contact:', ['operator' => e($operator['name']), 'brand' => e($brand)]) !!}
    <a href="mailto:{{ $contact }}">{{ $contact }}</a>.
</div>

<h3>{{ __('1. Obiectul livrării') }}</h3>
<p>
    {!! __('Produsul comandat este un <strong>serviciu digital</strong> (acces SaaS), nu un bun corporal. Livrarea înseamnă:') !!}
</p>
<ul>
    <li>{{ __('înregistrarea Comenzii cu număr unic;') }}</li>
    <li>{{ __('confirmarea plății (card sau OP);') }}</li>
    <li>{{ __('actualizarea dreptului de acces al Contului (dată de expirare / plan);') }}</li>
    <li>{{ __('posibilitatea imediată sau ulterioară de a folosi funcțiile incluse pe perioada plătită.') }}</li>
</ul>
<p>{{ __('Nu se emit AWB-uri, nu există livrare curier, nu există ambalaje fizice.') }}</p>

<h3>{{ __('2. Pașii Comenzii (flux detaliat)') }}</h3>
<ol>
    <li>{!! __('<strong>:auth</strong> în Contul DateConta Facturare.', ['auth' => e(__('Autentificare'))]) !!}</li>
    <li>{!! __('<strong>Selectare Societate</strong> din „Societățile mele” (unde e cazul) și apăsare „Comandă”.') !!}</li>
    <li>{!! __('<strong>Alegere perioadă</strong>: 1 lună, 3 luni (+ bonus), 6 luni (+ bonus) sau 1 an (+ bonus), conform afișajului curent din pagină.') !!}</li>
    <li>{!! __('<strong>Date facturare</strong>: denumire, CUI (opțional pentru PF), email, adresă, telefon.') !!}</li>
    <li>{!! __('<strong>Metodă plată</strong>: card (NETOPIA) sau OP bancar.') !!}</li>
    <li>{!! __('<strong>Acceptare termeni</strong> (Termeni, Confidențialitate, GDPR).') !!}</li>
    <li>{!! __('<strong>Plasare Comandă</strong> → număr tip DC-AAMMZZ-XXXXXX.') !!}</li>
    <li>{!! __('<strong>:plata</strong> și <strong>activare acces</strong> conform secțiunilor de mai jos.', ['plata' => e(__('Plată'))]) !!}</li>
</ol>

<h3>{{ __('3. Livrare la plata cu cardul (NETOPIA)') }}</h3>
<h4>{{ __('3.1. Redirect și autentificare pe gateway') }}</h4>
<p>
    {{ __('Ești redirecționat către mediul securizat NETOPIA. Introduci datele cardului acolo. Operatorul nu vede și nu stochează PAN-ul complet, CVV-ul sau date echivalente.') }}
</p>
<h4>{{ __('3.2. Confirmare tehnică') }}</h4>
<ul>
    <li>{{ __('gateway-ul procesează autorizarea;') }}</li>
    <li>{{ __('Platforma primește confirmare (IPN / return URL);') }}</li>
    <li>{{ __('la succes, Comanda trece în stare plătită și accesul Contului se prelungește automat cu lunile/bonusurile aferente pachetului.') }}</li>
</ul>
<h4>{{ __('3.3. Termen de livrare card') }}</h4>
<table class="legal-table">
    <thead><tr><th>{{ __('Situație') }}</th><th>{{ __('Termen tipic') }}</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Plată aprobată, IPN primit') }}</td><td>{{ __('Imediat — câteva minute') }}</td></tr>
        <tr><td>{{ __('Plată aprobată, întârziere IPN') }}</td><td>{{ __('De regulă sub 2 ore; maxim investigăm în 24h lucrătoare') }}</td></tr>
        <tr><td>{{ __('Plată respinsă / abandonată') }}</td><td>{{ __('Fără livrare; poți reîncerca o Comandă nouă') }}</td></tr>
        <tr><td>{{ __('3-D Secure eșuat') }}</td><td>{{ __('Fără livrare până la o plată reușită') }}</td></tr>
    </tbody>
</table>

<h3>{{ __('4. Livrare la plata prin OP') }}</h3>
<ol>
    <li>{{ __('După Comandă vezi instrucțiunile: IBAN Operator, sumă, moneda, detaliile de plată recomandate (inclusiv număr Comandă).') }}</li>
    <li>{{ __('Efectuezi transferul din contul tău.') }}</li>
    <li>{!! __('Comanda rămâne <strong>în așteptare</strong> până la identificarea încasării.') !!}</li>
    <li>{{ __('Operatorul confirmă OP-ul (panou admin); sistemul activează / prelungește accesul automat.') }}</li>
</ol>
<p>
    {!! __('<strong>Termen tipic:</strong> 1–3 zile lucrătoare după ce plata apare în extras, uneori în aceeași zi. Pentru accelerare, trimite dovada plății (PDF/foto) la :contact cu numărul Comenzii.', ['contact' => e($contact)]) !!}
</p>
<div class="help-warn">
    {{ __('Menționează numărul Comenzii în detaliile plății. Fără acest reper, identificarea poate întârzia.') }}
</div>

<h3>{{ __('5. Ce se livrează concret în Cont') }}</h3>
<ul>
    <li>{!! __('actualizare <em>access_until</em> (și trial_ends_at unde e cazul);') !!}</li>
    <li>{{ __('plan marcat ca plătit (paid), când fluxul o prevede;') }}</li>
    <li>{{ __('afișare dată expirare în Societățile mele / meniul Cont;') }}</li>
    <li>{{ __('deblocarea funcțiilor dacă erai pe ecranul „Acces suspendat”.') }}</li>
</ul>
<p>{{ __('Nu se livrează: instalare pe serverul tău, cod sursă, licențe perpetue offline, training on-site.') }}</p>

<h3>{{ __('6. Promoții, trial și bonusuri') }}</h3>
<ul>
    <li>{!! __('<strong>Promoție platformă</strong> (ex. până la 31.03.2027): accesul se „livrează” la înregistrare, fără Comandă.') !!}</li>
    <li>{!! __('<strong>Trial 6 luni</strong> pentru Conturi noi după promoție: livrare automată la creare Cont.') !!}</li>
    <li>{!! __('<strong>Bonusuri de pachet</strong> (ex. +1 săptămână la 3 luni): se adaugă la livrarea Abonamentului plătit.') !!}</li>
    <li>{!! __('<strong>:cod</strong> la creare Societate: bonus conform regulilor afișate, independent de Comanda de Abonament.', ['cod' => e(__('Cod promoțional'))]) !!}</li>
</ul>

<h3>{{ __('7. Factura / documentul fiscal al Operatorului') }}</h3>
<p>
    {{ __('După încasare, Operatorul poate emite documentul fiscal aferent Abonamentului pe datele de facturare din Comandă. Verifică CUI-ul și denumirea înainte de plată. Corecțiile ulterioare se solicită la :contact.', ['contact' => $contact]) }}
</p>

<h3>{{ __('8. Neconcordanțe și întârzieri') }}</h3>
<table class="legal-table">
    <thead><tr><th>{{ __('Problemă') }}</th><th>{{ __('Ce faci') }}</th><th>{{ __('Ce facem') }}</th></tr></thead>
    <tbody>
        <tr>
            <td>{{ __('Card plătit, acces neschimbat') }}</td>
            <td>{{ __('Email cu nr. Comandă + oră plată') }}</td>
            <td>{{ __('Verificăm IPN / stare Comandă și activăm manual dacă e cazul') }}</td>
        </tr>
        <tr>
            <td>{{ __('OP trimis, neconfirmat') }}</td>
            <td>{{ __('Trimiți extras / dovadă') }}</td>
            <td>{{ __('Identificăm încasarea și confirmăm') }}</td>
        </tr>
        <tr>
            <td>{{ __('Sumă greșită') }}</td>
            <td>{{ __('Nu forța activarea; contactează-ne') }}</td>
            <td>{{ __('Corectăm Comanda sau restituim diferența') }}</td>
        </tr>
        <tr>
            <td>{{ __('Plată dublă') }}</td>
            <td>{{ __('Semnalezi ambele referințe') }}</td>
            <td>{{ __('Rambursare sau credit perioadă, la alegere') }}</td>
        </tr>
    </tbody>
</table>

<h3>{{ __('9. Indisponibilitate Platformă la momentul livrării') }}</h3>
<p>
    {{ __('Dacă Platforma sau NETOPIA sunt temporar indisponibile după ce plata a fost reușită, livrarea (activarea) se face imediat ce sistemele permit reconcilierea, fără a anula dreptul tău obținut prin plată.') }}
</p>

<h3>{{ __('10. Livrare către consumator vs. profesionist') }}</h3>
<p>
    {{ __('Fluxul tehnic este același. Drepturile suplimentare ale consumatorilor privind retragerea sunt tratate în') }}
    <a href="{{ route('legal.show', 'anulare') }}">{{ __('Politica de anulare') }}</a>.
</p>

<h3>{{ __('11. Dovada livrării') }}</h3>
<p>{{ __('Se consideră dovadă a livrării oricare dintre:') }}</p>
<ul>
    <li>{{ __('starea Comenzii „plătită” în sistem și data access_until actualizată;') }}</li>
    <li>{{ __('mesajul de succes după return NETOPIA;') }}</li>
    <li>{{ __('confirmarea pe email (dacă este trimisă) sau răspunsul suportului;') }}</li>
    <li>{{ __('posibilitatea efectivă de a folosi Platforma după plată.') }}</li>
</ul>

<h3>{{ __('12. Contact livrări / comenzi') }}</h3>
<p>
    <strong>{{ $operator['name'] }}</strong> — {{ __('CUI') }} {{ $operator['cui'] }}<br>
    {{ __('Email') }}: <a href="mailto:{{ $contact }}">{{ $contact }}</a><br>
    {{ __('Menționează întotdeauna: email Cont, număr Comandă, metodă de plată, data.') }}
</p>
@endsection
