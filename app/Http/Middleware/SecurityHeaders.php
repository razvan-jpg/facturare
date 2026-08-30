<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        // Fără COOP strict: Google Tag Assistant / Ads debug nu se poate conecta (timeout „Not Connected”).
        // CORP cross-origin: permite uneltele Google să citească resursele publice.
        $response->headers->remove('Cross-Origin-Opener-Policy');
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
        // X-Frame-Options conflictă cu frame-ancestors (Tag Assistant); folosim doar CSP.
        $response->headers->remove('X-Frame-Options');
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // CSP permisivă pentru stiluri/fonturi/vite existente — blochează obiecte și framing străin.
        // form-action trebuie să permită Netopia (POST cross-origin către gateway-ul de plată).
        if (! $response->headers->has('Content-Security-Policy')) {
            $paymentHosts = [
                'https://sandboxsecure.mobilpay.ro',
                'https://secure.mobilpay.ro',
                'https://secure.euplatesc.ro',
                'https://www.euplatesc.ro',
                'https://www.mollie.com',
                'https://mollie.com',
                'https://checkout.stripe.com',
                'https://billing.stripe.com',
            ];
            foreach (['netopia.payment_url', 'euplatesc.payment_url'] as $paymentConfigKey) {
                $paymentUrl = (string) config($paymentConfigKey, '');
                if ($paymentUrl === '') {
                    continue;
                }
                $origin = parse_url($paymentUrl, PHP_URL_SCHEME).'://'.parse_url($paymentUrl, PHP_URL_HOST);
                if ($origin !== '://' && ! in_array($origin, $paymentHosts, true)) {
                    $paymentHosts[] = $origin;
                }
            }

            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self' ".implode(' ', $paymentHosts),
                "frame-ancestors 'self' https://tagassistant.google.com https://www.googletagmanager.com https://ads.google.com https://www.google.com",
                "object-src 'none'",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://mny.ro https://atrafic.ro https://ts.trafic.ro https://www.googletagmanager.com https://www.googleadservices.com https://googleads.g.doubleclick.net https://www.google.com https://tagassistant.google.com",
                "frame-src 'self' https://atrafic.ro https://www.googletagmanager.com https://tagassistant.google.com https://www.google.com https:",
                "connect-src 'self' https:",
                "upgrade-insecure-requests",
            ]));
        }

        return $response;
    }
}
