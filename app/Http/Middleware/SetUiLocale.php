<?php

namespace App\Http\Middleware;

use App\Support\UiLocales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'ro';

        if ($request->user()?->ui_locale) {
            $locale = (string) $request->user()->ui_locale;
        } elseif ($request->session()->has('ui_locale')) {
            $locale = (string) $request->session()->get('ui_locale');
        }

        $locale = UiLocales::normalize($locale);

        // Persistă aliasuri vechi (ex. en → en_US) pe utilizator.
        if ($request->user() && (string) $request->user()->ui_locale !== $locale) {
            $request->user()->forceFill(['ui_locale' => $locale])->saveQuietly();
        }

        App::setLocale($locale);

        if ($request->session()->get('ui_locale') !== $locale) {
            $request->session()->put('ui_locale', $locale);
        }

        return $next($request);
    }
}
