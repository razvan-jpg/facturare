<?php

return [
    'enabled' => filter_var(env('MOLLIE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /**
     * Cheie API Mollie: test_… (sandbox) sau live_… (producție).
     * @see https://my.mollie.com/dashboard/developers/api-keys
     */
    'key' => (string) env('MOLLIE_KEY', ''),

    /**
     * Metode acceptate pe checkout. Null = toate metodele activate în contul Mollie.
     * Pentru card: ['creditcard'] sau lăsați gol ca utilizatorul să aleagă în Mollie.
     *
     * @var list<string>|null
     */
    'methods' => null,
];
