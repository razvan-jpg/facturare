<?php

$sandbox = filter_var(env('NETOPIA_SANDBOX', true), FILTER_VALIDATE_BOOLEAN);

return [
    'enabled' => filter_var(env('NETOPIA_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'sandbox' => $sandbox,
    /** Semnătura merchant din contul Netopia (ex. XXXX-XXXX-XXXX-XXXX-XXXX). */
    'signature' => env('NETOPIA_SIGNATURE', ''),
    'public_key_path' => env('NETOPIA_PUBLIC_KEY_PATH', storage_path('app/netopia/public.cer')),
    'private_key_path' => env('NETOPIA_PRIVATE_KEY_PATH', storage_path('app/netopia/private.key')),
    'payment_url' => $sandbox
        ? 'https://sandboxsecure.mobilpay.ro'
        : 'https://secure.mobilpay.ro',
];
