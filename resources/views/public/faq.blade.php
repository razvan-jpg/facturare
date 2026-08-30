@extends('layouts.public-seo')

@section('meta_title', __($meta['title'] ?? 'Întrebări frecvente'))
@section('meta_description', __($meta['meta_description'] ?? ''))
@section('canonical', $canonical)

@section('heading', __($meta['title'] ?? 'Întrebări frecvente'))
@section('subheading', __($meta['subtitle'] ?? ''))

@push('head_jsonld')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqItems ?? [])->map(fn ($item) => [
        '@type' => 'Question',
        'name' => $item['question'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['answer'],
        ],
    ])->values()->all(),
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="help-shell">
    @include('public._toc', ['guides' => $guides, 'current' => null])

    <article class="help-article dc-card">
        <h2>{{ __($meta['title'] ?? 'Întrebări frecvente') }}</h2>
        <p class="help-lead">{{ __($meta['subtitle'] ?? '') }}</p>

        <p>
            {{ __('Mai jos găsești răspunsuri la cele mai frecvente întrebări despre DateConta Facturare. Pentru detalii după autentificare, folosește manualul Ajutor din aplicație.') }}
            {{ __('Contact:') }}
            <a href="mailto:{{ config('dateconta.contact_email') }}" class="text-teal-800 hover:underline">{{ config('dateconta.contact_email') }}</a>.
        </p>

        <div class="help-note">
            {{ __('Vezi și ghidurile:') }}
            <a href="{{ route('guides.show', 'e-factura') }}">{{ __('Cum emiți e-Factura') }}</a>
            ·
            <a href="{{ route('guides.show', 'proforma-vs-factura') }}">{{ __('Proformă vs factură') }}</a>
        </div>

        <h3>{{ __('Cont și prețuri') }}</h3>

        <h4>{{ __('Ce este DateConta Facturare?') }}</h4>
        <p>
            {{ __('Soft de facturare online pentru firme din România: facturi, proforme, avize, chitanțe, PDF, email, încasări și e-Factura (SPV ANAF). Poți lucra cu una sau mai multe societăți din același cont.') }}
        </p>

        <h4>{{ __('Cât costă? Este gratuit?') }}</h4>
        <p>
            {!! __('Accesul este <strong>gratuit până la 31.03.2027</strong>. După această dată, conturile noi pe web primesc 6 luni de probă, apoi se aplică abonamentul. Detalii pe pagina :link.', [
                'link' => '<a href="'.e(route('pricing')).'">'.e(__('Prețuri')).'</a>',
            ]) !!}
        </p>

        <h4>{{ __('Cum funcționează codul promoțional?') }}</h4>
        <p>
            {{ __('Fiecare societate are un cod unic. Cine îl folosește la crearea unei societăți noi primește +2 săptămâni; tu primești +1 lună la fiecare 2 societăți aduse. Codul se introduce doar la „Adaugă societate”.') }}
        </p>

        <h4>{{ __('Pot folosi aplicația pe mai multe dispozitive?') }}</h4>
        <p>
            {{ __('Da. Sesiunea de pe un dispozitiv nu blochează autentificarea pe altul. Dacă vezi „Emailul sau parola nu sunt corecte”, verifică parola sau folosește resetarea de pe ecranul de login.') }}
        </p>

        <h3>{{ __('Emitere documente') }}</h3>

        <h4>{{ __('Care e diferența dintre proformă și factură?') }}</h4>
        <p>
            {!! __('Proforma este ofertă / solicitare de plată anticipată și <strong>nu</strong> înlocuiește factura fiscală; nu se trimite în e-Factura. Factura fiscală e documentul de vânzare pe serie proprie și poate fi trimisă în e-Factura. La încasarea integrală a unei proforme, aplicația poate emite automat factura. :link', [
                'link' => '<a href="'.e(route('guides.show', 'proforma-vs-factura')).'">'.e(__('Citește ghidul')).'</a>',
            ]) !!}
        </p>

        <h4>{{ __('Ce tipuri de documente pot emite?') }}</h4>
        <p>
            {{ __('Factură, proformă, aviz de însoțire, chitanță / OP (prin Încasare), factură storno, notă de creditare și facturi recurente. Fiecare tip folosește serii separate (ex. FCT, PRF).') }}
        </p>

        <h4>{{ __('Mesaj: nu există serii active') }}</h4>
        <ol class="help-steps">
            <li>{{ __('Setări → Serii.') }}</li>
            <li>{{ __('Creează o serie pentru tipul Factură (sau Proformă) și anul datei de emitere.') }}</li>
            <li>{{ __('Marcheaz-o Activă (și Implicită dacă e singura).') }}</li>
        </ol>

        <h4>{{ __('De ce numărul arată FCT-0001?') }}</h4>
        <p>
            {{ __('Formatul standard este PREFIX + „-” + număr pe 4 cifre. Prefixul îl alegi tu; zerourile sunt doar aliniere.') }}
        </p>

        <h4>{{ __('„Linia N: produsul e obligatoriu”') }}</h4>
        <p>
            {{ __('Completează numele produsului pe linie sau șterge linia goală. Descrierea poate rămâne goală; produsul nu.') }}
        </p>

        <h3>{{ __('e-Factura ANAF') }}</h3>

        <h4>{{ __('Cum trimit e-Factura către ANAF?') }}</h4>
        <p>
            {!! __('Autorizezi SPV din Setări → e-Factura (certificat digital pe CUI), emiți factura, apoi o trimiți manual sau automat (la salvare sau după N zile). Urmărești starea până la Acceptată ANAF. Proformele, avizele și chitanțele nu se trimit în e-Factura. :link', [
                'link' => '<a href="'.e(route('guides.show', 'e-factura')).'">'.e(__('Ghid pas cu pas')).'</a>',
            ]) !!}
        </p>

        <h4>{{ __('Rămâne pe „așteaptă validare”') }}</h4>
        <p>
            {{ __('Lista se reîmprospătează ~la 30s; poți apăsa Actualizează stare ANAF. Platforma verifică automat până la Acceptată ANAF. La respingere apare mesajul pe factură și se reîncearcă trimiterea (cu corectări automate unde e posibil).') }}
        </p>

        <h4>{{ __('e-Factura respinsă BR-RO-100 (București / SECTOR)') }}</h4>
        <p>
            {{ __('Pentru clienți din București, județul/localitatea trebuie să includă Sector 1–6 (nu doar „București”). Completează sectorul la client, salvează, apoi retrimite factura în e-Factura.') }}
        </p>

        <h4>{{ __('Nu pot edita factura') }}</h4>
        <p>
            {{ __('Documentul este probabil trimis / în prelucrare / acceptat în e-Factura. Folosește storno sau notă de creditare pentru corecții.') }}
        </p>

        <h3>{{ __('PDF, limbi, plăți') }}</h3>

        <h4>{{ __('Am schimbat limba UI, dar factura e tot în română') }}</h4>
        <p>
            {{ __('Este comportamentul corect. Schimbă Limbă document pe factură (și activează limba la Setări → Limbi).') }}
        </p>

        <h4>{{ __('Nu apar IBAN-urile pe PDF') }}</h4>
        <p>
            {{ __('Setări → Conturi bancare: bifează „Pe factură” (maximum 3) și salvează. Regenerează PDF-ul.') }}
        </p>

        <h4>{{ __('Cum încep?') }}</h4>
        <ol class="help-steps">
            <li>{{ __('Creezi contul gratuit.') }}</li>
            <li>{{ __('Adaugi societatea (poți prelua datele din ANAF după CUI).') }}</li>
            <li>{{ __('Configurezi serii și conturi bancare.') }}</li>
            <li>{{ __('Adaugi un client și emiți prima factură sau proformă.') }}</li>
        </ol>
        <p>
            <a href="{{ route('register') }}" class="dc-btn-primary text-sm px-4 py-2 inline-flex">{{ __('Începe gratuit acum') }}</a>
        </p>

        <div class="help-warn">
            {{ __('Acest FAQ nu este consultanță fiscală. Pentru interpretări legale privind e-Factura sau TVA, consultă un specialist; pentru erori tehnice ale aplicației, scrie la') }}
            {{ config('dateconta.contact_email') }}.
        </div>
    </article>
</div>

<style>
.help-shell {
    display: grid;
    grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 960px) {
    .help-shell { grid-template-columns: 1fr; }
}
.help-toc { padding: 1rem; position: sticky; top: 0.75rem; }
.help-toc-title {
    font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    color: #627d98; margin-bottom: .65rem;
}
.help-toc-nav { display: flex; flex-direction: column; gap: .15rem; max-height: 65vh; overflow: auto; }
.help-toc-nav a {
    display: block; padding: .45rem .55rem; border-radius: .45rem;
    font-size: 13px; color: #243b53; text-decoration: none; line-height: 1.35;
}
.help-toc-nav a:hover { background: #f0f4f8; }
.help-toc-nav a.is-active { background: #e6fffa; color: #0f766e; font-weight: 600; }
.help-toc-meta { margin-top: 1rem; padding-top: .75rem; border-top: 1px solid #e2e8f0; font-size: 11px; color: #829ab1; line-height: 1.5; }
.help-article { padding: 1.5rem 1.75rem; }
.help-article h2 { font-family: 'Source Serif 4', Georgia, serif; font-size: 1.65rem; color: #102a43; margin: 0 0 .35rem; }
.help-article .help-lead { color: #486581; margin-bottom: 1.25rem; font-size: .95rem; }
.help-article h3 { font-size: 1.1rem; font-weight: 700; color: #243b53; margin: 1.6rem 0 .55rem; }
.help-article h4 { font-size: .95rem; font-weight: 700; color: #334e68; margin: 1.15rem 0 .4rem; }
.help-article p, .help-article li { font-size: .92rem; line-height: 1.65; color: #334e68; }
.help-article p { margin: 0 0 .75rem; }
.help-article ul, .help-article ol { margin: 0 0 1rem 1.15rem; }
.help-article li { margin-bottom: .35rem; }
.help-article ol.help-steps { list-style: decimal; }
.help-article .help-note {
    border-left: 3px solid #14b8a6; background: #f0fdfa; padding: .75rem 1rem;
    border-radius: 0 .5rem .5rem 0; margin: 1rem 0; font-size: .88rem; color: #115e59;
}
.help-article .help-warn {
    border-left: 3px solid #f59e0b; background: #fffbeb; padding: .75rem 1rem;
    border-radius: 0 .5rem .5rem 0; margin: 1rem 0; font-size: .88rem; color: #92400e;
}
</style>
@endsection
