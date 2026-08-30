<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function helpIndex(): JsonResponse
    {
        $sections = collect(config('help', []))->map(function (array $meta, string $key) {
            return [
                'key' => $key,
                'title' => $meta['title'] ?? $key,
                'subtitle' => $meta['subtitle'] ?? '',
                'icon' => $meta['icon'] ?? null,
            ];
        })->values();

        return response()->json(['data' => $sections]);
    }

    public function helpWhatsNew(): JsonResponse
    {
        $changelog = collect(config('changelog', []))
            ->sort(fn (array $a, array $b) => version_compare($b['version'] ?? '0', $a['version'] ?? '0'))
            ->values()
            ->all();

        return response()->json([
            'current_version' => config('dateconta.version'),
            'data' => $changelog,
        ]);
    }

    public function helpShow(string $section): JsonResponse
    {
        $sections = config('help', []);
        abort_unless(array_key_exists($section, $sections), 404);

        $view = "help.sections.{$section}";
        abort_unless(view()->exists($view), 404);

        $keys = array_keys($sections);
        $index = array_search($section, $keys, true);

        $html = view($view, [
            'embed' => true,
            'sections' => $sections,
            'current' => $section,
            'meta' => $sections[$section],
            'prev' => $index > 0 ? $keys[$index - 1] : null,
            'next' => $index < count($keys) - 1 ? $keys[$index + 1] : null,
        ])->render();

        return response()->json([
            'key' => $section,
            'title' => $sections[$section]['title'] ?? $section,
            'subtitle' => $sections[$section]['subtitle'] ?? '',
            'html' => $this->absolutizeHtml($html),
        ]);
    }

    public function legalIndex(): JsonResponse
    {
        $pages = collect(config('legal', []))->map(function (array $meta, string $key) {
            return [
                'key' => $key,
                'title' => $meta['title'] ?? $key,
                'subtitle' => $meta['subtitle'] ?? '',
                'updated' => $meta['updated'] ?? null,
            ];
        })->values();

        return response()->json(['data' => $pages]);
    }

    public function legalShow(string $page): JsonResponse
    {
        $pages = config('legal', []);
        abort_unless(array_key_exists($page, $pages), 404);

        $view = "legal.pages.{$page}";
        abort_unless(view()->exists($view), 404);

        $html = view($view, [
            'embed' => true,
            'pages' => $pages,
            'current' => $page,
            'meta' => $pages[$page],
            'operator' => config('dateconta.platform_operator', []),
            'contact' => config('dateconta.contact_email'),
            'brand' => 'DateConta Facturare',
        ])->render();

        return response()->json([
            'key' => $page,
            'title' => $pages[$page]['title'] ?? $page,
            'subtitle' => $pages[$page]['subtitle'] ?? '',
            'updated' => $pages[$page]['updated'] ?? null,
            'html' => $this->absolutizeHtml($html),
        ]);
    }

    private function absolutizeHtml(string $html): string
    {
        $base = rtrim((string) config('app.url'), '/');

        $html = preg_replace('#(src|href)=([\'"])/(?!/)#', '$1=$2'.$base.'/', $html) ?? $html;

        return $html;
    }
}
