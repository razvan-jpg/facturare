<?php

return [
    'enabled' => filter_var(env('EUPLATESC_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'sandbox' => filter_var(env('EUPLATESC_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),

    /** Merchant ID (MID) din panoul EuPlătesc. */
    'mid' => (string) env('EUPLATESC_MID', ''),

    /** Cheie secretă hex (KEY) din panoul EuPlătesc. */
    'key' => (string) env('EUPLATESC_KEY', ''),

    'payment_url' => 'https://secure.euplatesc.ro/tdsprocess/tranzactd.php',
];
