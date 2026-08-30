<?php

namespace App\Http\Middleware;

use App\Services\AccessGate;
use App\Services\IosSubscriptionGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiSubscription
{
    public function __construct(
        private AccessGate $accessGate,
        private IosSubscriptionGate $iosGate,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($this->isIosClient($request)) {
            if (! $this->iosGate->hasIosAccess($user)) {
                return response()->json([
                    'message' => 'Abonamentul iOS a expirat. Reactivează-l din aplicație (0.99 USD/lună).',
                    'code' => 'ios_subscription_required',
                ], 402);
            }

            return $next($request);
        }

        if (! $this->accessGate->hasAccess($user)) {
            return response()->json([
                'message' => 'Abonamentul a expirat. Reînnoiește accesul din aplicația web.',
                'code' => 'subscription_expired',
            ], 402);
        }

        return $next($request);
    }

    private function isIosClient(Request $request): bool
    {
        $client = strtolower((string) $request->header('X-Client', ''));
        if ($client === 'ios') {
            return true;
        }

        // Fallback: device_name trimis la login (unele request-uri vechi).
        return strtolower((string) $request->header('X-Device-Name', '')) === 'ios';
    }
}
