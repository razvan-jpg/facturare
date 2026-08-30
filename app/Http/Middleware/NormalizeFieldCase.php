<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeFieldCase
{
    public function handle(Request $request, Closure $next): Response
    {
        $payload = $request->all();

        foreach (['iban', 'bank_name'] as $field) {
            if (isset($payload[$field]) && is_string($payload[$field])) {
                $payload[$field] = mb_strtoupper($payload[$field], 'UTF-8');
            }
        }

        if (isset($payload['email']) && is_string($payload['email'])) {
            $payload['email'] = mb_strtolower(trim($payload['email']), 'UTF-8');
        }

        if (isset($payload['banks']) && is_array($payload['banks'])) {
            foreach ($payload['banks'] as $bi => $bank) {
                if (! is_array($bank)) {
                    continue;
                }
                if (isset($bank['name']) && is_string($bank['name'])) {
                    $payload['banks'][$bi]['name'] = mb_strtoupper($bank['name'], 'UTF-8');
                }
                if (! empty($bank['accounts']) && is_array($bank['accounts'])) {
                    foreach ($bank['accounts'] as $ai => $account) {
                        if (isset($account['iban']) && is_string($account['iban'])) {
                            $payload['banks'][$bi]['accounts'][$ai]['iban'] = mb_strtoupper($account['iban'], 'UTF-8');
                        }
                    }
                }
            }
        }

        $request->merge($payload);

        return $next($request);
    }
}
