<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'billing/netopia/confirm',
        'billing/netopia/return/*',
        'billing/mollie/webhook',
        'billing/euplatesc/silent',
        'billing/stripe/webhook',
        'plata/netopia/confirm',
        'plata/mollie/webhook',
        'plata/euplatesc/silent',
        'plata/stripe/webhook',
        'plata-return/*',
    ];
}
