<?php

namespace App\Http\Controllers;

use App\Support\UiLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UiLocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = UiLocales::normalize((string) $request->input('ui_locale', 'ro'));

        $request->user()?->update(['ui_locale' => $locale]);
        $request->session()->put('ui_locale', $locale);
        app()->setLocale($locale);

        return back();
    }
}
