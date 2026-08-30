<?php

return [
    'enabled' => filter_var(env('STRIPE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /** Publishable key (pk_test_… / pk_live_…) */
    'key' => (string) env('STRIPE_KEY', ''),

    /** Secret key (sk_test_… / sk_live_…) */
    'secret' => (string) env('STRIPE_SECRET', ''),

    /** Webhook signing secret (whsec_…) */
    'webhook_secret' => (string) env('STRIPE_WEBHOOK_SECRET', ''),

    /**
     * Opțional: Price ID-uri Stripe pe period_key (1m, 3m, 6m, 1y).
     * Dacă lipsesc, serviciul creează/reutilizează Prices după lookup_key.
     */
    'price_ids' => [
        '1m' => (string) env('STRIPE_PRICE_1M', ''),
        '3m' => (string) env('STRIPE_PRICE_3M', ''),
        '6m' => (string) env('STRIPE_PRICE_6M', ''),
        '1y' => (string) env('STRIPE_PRICE_1Y', ''),
    ],
];
