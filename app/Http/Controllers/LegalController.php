<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function index(): View
    {
        return view('legal.index', [
            'pages' => config('legal', []),
            'meta' => [
                'title' => 'Legal',
                'subtitle' => 'Documente legale pentru utilizarea DateConta Facturare',
            ],
            'operator' => config('dateconta.platform_operator'),
            'contact' => config('dateconta.contact_email'),
            'brand' => config('dateconta.brand_name', 'DateConta Facturare'),
        ]);
    }

    public function show(string $page): View
    {
        $pages = config('legal', []);
        if (! array_key_exists($page, $pages)) {
            abort(404);
        }

        $keys = array_keys($pages);
        $index = array_search($page, $keys, true);
        $prev = $index > 0 ? $keys[$index - 1] : null;
        $next = $index < count($keys) - 1 ? $keys[$index + 1] : null;

        $view = "legal.pages.{$page}";
        if (! view()->exists($view)) {
            abort(404);
        }

        return view($view, [
            'pages' => $pages,
            'current' => $page,
            'meta' => $pages[$page],
            'prev' => $prev,
            'next' => $next,
            'operator' => config('dateconta.platform_operator'),
            'contact' => config('dateconta.contact_email'),
            'brand' => config('dateconta.brand_name', 'DateConta Facturare'),
        ]);
    }
}
