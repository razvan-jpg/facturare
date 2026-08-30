<?php

namespace App\Http\Middleware;

use App\Models\VisitorSession;
use App\Services\GeoIpLookup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /** Debounce writes for returning visitors (seconds). */
    private const TOUCH_INTERVAL = 180;

    public function __construct(private GeoIpLookup $geoIp) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request)) {
            return $response;
        }

        $key = $request->cookie('dc_vid');
        $setCookie = false;

        if (! $key || ! preg_match('/^[a-f0-9\-]{36}$/i', $key)) {
            $key = (string) Str::uuid();
            $setCookie = true;
        }

        if ($setCookie) {
            $response = $response->withCookie(cookie(
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

        // Scrie în DB după ce răspunsul a fost trimis — TTFB mai mic.
        $snapshot = [
            'key' => $key,
            'path' => '/'.ltrim($request->path(), '/'),
            'ip' => $request->ip(),
            'ua' => Str::limit((string) $request->userAgent(), 500, ''),
            'user_id' => $request->user()?->id,
            'header_country' => $request->headers->get('CF-IPCountry')
                ?: $request->headers->get('X-Country-Code'),
            'set_cookie' => $setCookie,
        ];

        app()->terminating(function () use ($snapshot) {
            $this->persist($snapshot);
        });

        return $response;
    }

    /**
     * @param  array{key:string,path:string,ip:?string,ua:string,user_id:?int,header_country:?string,set_cookie:bool}  $snapshot
     */
    private function persist(array $snapshot): void
    {
        try {
            $key = $snapshot['key'];
            $path = $snapshot['path'] === '//' ? '/' : $snapshot['path'];
            $now = now();
            $ip = $snapshot['ip'];

            $visitor = VisitorSession::query()->where('visitor_key', $key)->first();

            // Nu sări debounce-ul dacă tocmai s-a autentificat (trebuie legat user_id).
            $sameUser = (int) ($visitor?->user_id ?? 0) === (int) ($snapshot['user_id'] ?? 0);

            if (
                $visitor
                && $sameUser
                && ! $snapshot['set_cookie']
                && $visitor->last_seen_at
                && $visitor->last_seen_at->gt(now()->subSeconds(self::TOUCH_INTERVAL))
                && (string) $visitor->ip === (string) $ip
            ) {
                return;
            }

            if (! $visitor && ! $snapshot['user_id'] && $ip) {
                $visitor = VisitorSession::query()
                    ->where('ip', $ip)
                    ->where('user_agent', $snapshot['ua'])
                    ->where('last_seen_at', '>=', now()->subHours(6))
                    ->orderByDesc('last_seen_at')
                    ->first();

                if ($visitor) {
                    $key = $visitor->visitor_key;
                }
            }

            $geo = null;
            $needsGeo = ! $visitor || blank($visitor->country_code) || ($visitor->ip && $visitor->ip !== $ip);

            if ($needsGeo) {
                // Preferă header CDN; GeoIP HTTP doar dacă nu există țară deloc.
                $header = $snapshot['header_country'];
                if (filled($header) && strtoupper((string) $header) !== 'XX') {
                    $geo = $this->geoIp->resolve($ip, $header);
                } elseif (! $visitor || blank($visitor->country_code)) {
                    $geo = $this->geoIp->resolve($ip, $header);
                }
            }

            if ($visitor) {
                $payload = [
                    'ip' => $ip,
                    'user_agent' => $snapshot['ua'],
                    'user_id' => $snapshot['user_id'] ?? $visitor->user_id,
                    'page_views' => $visitor->page_views + 1,
                    'last_path' => Str::limit($path, 250, ''),
                    'last_seen_at' => $now,
                ];
                if ($geo && ($geo['code'] || $geo['name'])) {
                    $payload['country_code'] = $geo['code'];
                    $payload['country'] = $geo['name'];
                }
                $visitor->update($payload);
            } else {
                VisitorSession::create([
                    'visitor_key' => $key,
                    'ip' => $ip,
                    'country_code' => $geo['code'] ?? null,
                    'country' => $geo['name'] ?? null,
                    'user_agent' => $snapshot['ua'],
                    'user_id' => $snapshot['user_id'],
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

    private function shouldTrack(Request $request): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        $path = $request->path();

        if ($path === 'health' || $path === 'up' || str_starts_with($path, 'cron/')) {
            return false;
        }

        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            return false;
        }

        // Adminii nu apar în statistici de activitate clienți.
        if ($request->user()?->is_admin) {
            return false;
        }

        if (str_starts_with($path, 'build/') || str_starts_with($path, 'livewire')) {
            return false;
        }

        if (preg_match('/\.(css|js|map|ico|png|jpg|jpeg|gif|svg|webp|woff2?)$/i', $path)) {
            return false;
        }

        if (str_starts_with($path, '_')) {
            return false;
        }

        $ua = (string) $request->userAgent();
        if ($ua !== '' && preg_match('/bot|spider|crawl|slurp|facebookexternalhit|preview|uptime|pingdom|statuscake|monitor/i', $ua)) {
            return false;
        }

        return true;
    }
}
