<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function index(): View
    {
        $sections = config('help', []);

        return view('help.index', [
            'sections' => $sections,
        ]);
    }

    public function whatsNew(): View
    {
        $changelog = collect(config('changelog', []))
            ->sort(function (array $a, array $b) {
                return version_compare($b['version'] ?? '0.0.0', $a['version'] ?? '0.0.0');
            })
            ->values()
            ->all();

        return view('help.whats-new', [
            'sections' => config('help', []),
            'changelog' => $changelog,
            'currentVersion' => config('dateconta.version'),
        ]);
    }

    public function show(string $section): View
    {
        $sections = config('help', []);

        if (! array_key_exists($section, $sections)) {
            abort(404);
        }

        $keys = array_keys($sections);
        $index = array_search($section, $keys, true);
        $prev = $index > 0 ? $keys[$index - 1] : null;
        $next = $index < count($keys) - 1 ? $keys[$index + 1] : null;

        $view = "help.sections.{$section}";
        if (! view()->exists($view)) {
            abort(404);
        }

        return view($view, [
            'sections' => $sections,
            'current' => $section,
            'meta' => $sections[$section],
            'prev' => $prev,
            'next' => $next,
        ]);
    }
}
