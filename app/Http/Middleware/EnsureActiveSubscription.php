<?php

namespace App\Http\Middleware;

use App\Services\AccessGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(private AccessGate $accessGate) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $this->accessGate->hasAccess($user)) {
            if ($request->routeIs('billing.expired') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('billing.expired');
        }

        return $next($request);
    }
}
