<?php

namespace App\Services;

use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorActivityService
{
    /**
     * Leagă / actualizează sesiunea de vizită la utilizatorul autentificat (ex. după login).
     */
    public function touchAuthenticated(Request $request): void
    {
        $user = $request->user();
        if (! $user || $user->is_admin) {
            return;
        }

        try {
            $key = $request->cookie('dc_vid');
            if (! $key || ! preg_match('/^[a-f0-9\-]{36}$/i', $key)) {
                $key = (string) Str::uuid();
                cookie()->queue(cookie(
                    'dc_vid',
                    $key,
                    60 * 24 * 365 * 2,
                    '/',
                    null,
                    $request->secure(),
                    true,
                    false,
                    'Lax'
                ));
            }

            $now = now();
            $path = '/'.ltrim($request->path(), '/');
            if ($path === '//') {
                $path = '/';
            }

            $visitor = VisitorSession::query()->where('visitor_key', $key)->first();
            $ua = Str::limit((string) $request->userAgent(), 500, '');

            if ($visitor) {
                $visitor->update([
                    'ip' => $request->ip(),
                    'user_agent' => $ua,
                    'user_id' => $user->id,
                    'page_views' => max(1, (int) $visitor->page_views) + 1,
                    'last_path' => Str::limit($path, 250, ''),
                    'last_seen_at' => $now,
                ]);
            } else {
                VisitorSession::query()->create([
                    'visitor_key' => $key,
                    'ip' => $request->ip(),
                    'user_agent' => $ua,
                    'user_id' => $user->id,
                    'page_views' => 1,
                    'landing_path' => Str::limit($path, 250, ''),
                    'last_path' => Str::limit($path, 250, ''),
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
