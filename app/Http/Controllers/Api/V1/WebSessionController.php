<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebLoginToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'redirect' => ['nullable', 'string', 'max:500'],
        ]);

        WebLoginToken::ensureSchema();

        $redirect = $this->sanitizeRedirect($data['redirect'] ?? '/dashboard');
        $plain = Str::random(48);

        WebLoginToken::query()->create([
            'user_id' => $request->user()->id,
            'token_hash' => hash('sha256', $plain),
            'redirect' => $redirect,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Curăță token-uri vechi.
        WebLoginToken::query()
            ->where(function ($q) {
                $q->where('expires_at', '<', now())->orWhereNotNull('used_at');
            })
            ->where('created_at', '<', now()->subDay())
            ->delete();

        return response()->json([
            'url' => url('/auth/mobile-login?token='.$plain),
            'expires_in' => 300,
        ]);
    }

    private function sanitizeRedirect(string $redirect): string
    {
        $redirect = trim($redirect);
        if ($redirect === '' || ! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return '/dashboard';
        }

        return $redirect;
    }
}
