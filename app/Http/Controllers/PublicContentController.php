<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicContentController extends Controller
{
    public function faq(): View
    {
        $faq = config('public_guides.faq', []);
        $guides = config('public_guides.guides', []);

        return view('public.faq', [
            'meta' => $faq,
            'guides' => $guides,
            'canonical' => url('/intrebari-frecvente'),
            'faqItems' => $this->faqSchemaItems(),
        ]);
    }

    public function guide(string $slug): View
    {
        $guides = config('public_guides.guides', []);
        if (! array_key_exists($slug, $guides)) {
            abort(404);
        }

        $view = "public.guides.{$slug}";
        if (! view()->exists($view)) {
            abort(404);
        }

        return view($view, [
            'meta' => $guides[$slug],
            'guides' => $guides,
            'current' => $slug,
            'canonical' => url('/ghid/'.$slug),
        ]);
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function faqSchemaItems(): array
    {
        return [
            [
                'question' => __('Ce este DateConta Facturare?'),
                'answer' => __('Soft de facturare online pentru firme din România: facturi, proforme, avize, chitanțe, PDF, email, încasări și e-Factura (SPV ANAF). Poți lucra cu una sau mai multe societăți din același cont.'),
            ],
            [
                'question' => __('Cât costă? Este gratuit?'),
                'answer' => __('Accesul este gratuit până la 31.03.2027. După această dată, conturile noi primesc o perioadă de probă (pe web: 6 luni), apoi se aplică abonamentul. Detalii pe pagina Prețuri.'),
            ],
            [
                'question' => __('Care e diferența dintre proformă și factură?'),
                'answer' => __('Proforma este ofertă / solicitare de plată anticipată și nu înlocuiește factura fiscală; nu se trimite în e-Factura. Factura fiscală e documentul de vânzare pe serie proprie și poate fi trimisă în e-Factura. La încasarea integrală a unei proforme, aplicația poate emite automat factura.'),
            ],
            [
                'question' => __('Cum trimit e-Factura către ANAF?'),
                'answer' => __('Autorizezi SPV din Setări → e-Factura (certificat digital pe CUI), emiți factura, apoi o trimiți manual sau automat (la salvare sau după N zile). Urmărești starea până la Acceptată ANAF. Proformele, avizele și chitanțele nu se trimit în e-Factura.'),
            ],
            [
                'question' => __('Ce fac dacă factura e respinsă de ANAF?'),
                'answer' => __('Citești mesajul de pe factură, corectezi datele (ex. adresă București/sector) și lași reîncercarea automată sau apeși Retrimite. După acceptare, corecțiile se fac prin storno / notă de creditare, nu prin editare clasică.'),
            ],
            [
                'question' => __('Cum încep?'),
                'answer' => __('Creezi contul, adaugi societatea (poți prelua datele din ANAF după CUI), configurezi serii și conturi bancare, adaugi un client și emiți prima factură sau proformă.'),
            ],
        ];
    }
}
