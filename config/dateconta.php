<?php

$anafEnv = env('ANAF_ENV', 'prod');

// Versiunea curentă = prima (cea mai recentă) intrare din „Ce este nou…” (config/changelog.php).
$changelog = require __DIR__.'/changelog.php';
$appVersion = (is_array($changelog) && isset($changelog[0]['version']) && $changelog[0]['version'] !== '')
    ? (string) $changelog[0]['version']
    : '1.0.000';

return [
    'version' => $appVersion,
    'promo_free_until' => '2027-03-31',
    'trial_months_after_promo' => 6,
    /** Notificări email înainte de expirarea abonamentului (zile rămase). */
    'subscription_reminders' => [
        'days_before' => [10, 5],
    ],
    'referral' => [
        /** Bonus pentru societatea nou creată (aplicat pe contul creatorului). */
        'invitee_bonus_days' => 14,
        /** Bonus pentru societatea care a recomandat, la fiecare N societăți aduse. */
        'referrer_bonus_months' => 1,
        'referrer_every' => 2,
    ],
    'platform_operator' => [
        'name' => 'FLY DAVID SRL',
        'cui' => '38254880',
        'reg_com' => 'J40/16280/2017',
        'address' => 'Str. Popa Stoica Farcaș nr. 86-88A',
        'city' => 'București',
        'county' => 'București - Sector 3',
        'country' => 'România',
        /** Cont pentru plăți OP ale abonamentului platformă. */
        'iban' => env('PLATFORM_IBAN', ''),
        'bank_name' => env('PLATFORM_BANK_NAME', ''),
    ],
    'contact_email' => 'contact.facturare@dateconta.ro',

    /**
     * Raport zilnic PDF după emiterea recurentelor (toate firmele).
     * Listă separată prin virgulă: RECURRING_DAILY_REPORT_EMAILS (preferat) sau RECURRING_DAILY_REPORT_EMAIL.
     */
    'recurring_daily_report_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) (
            env('RECURRING_DAILY_REPORT_EMAILS')
            ?: env('RECURRING_DAILY_REPORT_EMAIL')
            ?: 'razvan@fly-david.ro,razvan@dateconta.ro'
        ))
    ))),
    /** @deprecated Folosește recurring_daily_report_emails */
    'recurring_daily_report_email' => env('RECURRING_DAILY_REPORT_EMAIL', 'razvan@fly-david.ro'),

    /** Alertă cauze netrimitere email documente recurente. */
    'recurring_email_alert_to' => env('RECURRING_EMAIL_ALERT_TO', 'razvan@dateconta.ro'),

    /** CC MIME pe emailurile de document din fluxul recurent (beneficiar). */
    'recurring_document_email_cc' => env('RECURRING_DOCUMENT_EMAIL_CC', 'facturare@fly-david.ro'),

    /** Mail reclamă din Admin (fără cod promo) — Razvan Ivan / FLY DAVID. */
    'admin_promo' => [
        'from_name' => 'Razvan Ivan — FLY DAVID SRL',
        'sender_name' => 'Razvan Ivan',
        'reply_to' => env('ADMIN_PROMO_REPLY_TO', 'contact.facturare@dateconta.ro'),
    ],

    /**
     * Abonament DateConta Facturare (prețuri fără TVA).
     * TVA RO standard 21%.
     */
    'subscription' => [
        'vat_rate' => 21,
        'currency' => 'EUR',
        /** Fallback EUR→RON pe pagina Prețuri dacă BNR/API nu răspund. */
        'eur_ron_approx' => (float) env('EUR_RON_APPROX', 5.0),
        /**
         * Plăți NETOPIA + factură fiscală: conversie catalog EUR → RON cu curs BNR × acest factor.
         * Ex. 1.02 = BNR + 2%.
         */
        'netopia_ron_markup' => (float) env('NETOPIA_RON_MARKUP', 1.02),
        'product_name' => 'DateConta Facturare',
        'periods' => [
            '1m' => ['label' => '1 lună', 'months' => 1, 'price' => 1.99, 'bonus_days' => 0, 'bonus_months' => 0, 'bonus_label' => null],
            '3m' => ['label' => '3 luni', 'months' => 3, 'price' => 5.97, 'bonus_days' => 7, 'bonus_months' => 0, 'bonus_label' => '+1 săptămână bonus'],
            '6m' => ['label' => '6 luni', 'months' => 6, 'price' => 11.94, 'bonus_days' => 14, 'bonus_months' => 0, 'bonus_label' => '+2 săptămâni bonus'],
            '1y' => ['label' => '1 an', 'months' => 12, 'price' => 23.88, 'bonus_days' => 0, 'bonus_months' => 1, 'bonus_label' => '+1 lună bonus'],
        ],
    ],

    /**
     * Locuri subuser (achiziționate de proprietar).
     * Gratuit / nelimitat până la ajunul billable_from; din billable_from = 1 EUR/loc/lună.
     */
    'subuser_seats' => [
        'billable_from' => '2027-04-01',
        'price_per_seat_month' => 1.00,
        'vat_rate' => 21,
        'currency' => 'EUR',
        'product_name' => 'DateConta Facturare — locuri utilizatori',
        'periods' => [
            '1m' => ['label' => '1 lună', 'months' => 1],
            '3m' => ['label' => '3 luni', 'months' => 3],
            '6m' => ['label' => '6 luni', 'months' => 6],
            '1y' => ['label' => '1 an', 'months' => 12],
        ],
    ],
    'brand_name' => 'DateConta Facturare',
    'logo_path' => 'images/brand/dateconta-logo.png',
    'logo_url' => rtrim((string) env('APP_URL'), '/').'/images/brand/dateconta-logo.png',
    /** Token pentru GET /cron/run?token=… (cron HTTP pe hosting fără shell). */
/** Notificări email înainte de expirarea abonamentului (zile rămase). */
    'subscription_reminders' => [
        'days_before' => [10, 5],
    ],
    'referral' => [
        /** Bonus pentru societatea nou creată (aplicat pe contul creatorului). */
        'invitee_bonus_days' => 14,
        /** Bonus pentru societatea care a recomandat, la fiecare N societăți aduse. */
        'referrer_bonus_months' => 1,
        'referrer_every' => 2,
    ],
    'platform_operator' => [
        'name' => 'FLY DAVID SRL',
        'cui' => '38254880',
        'reg_com' => 'J40/16280/2017',
        'address' => 'Str. Popa Stoica Farcaș nr. 86-88A',
        'city' => 'București',
        'county' => 'București - Sector 3',
        'country' => 'România',
        /** Cont pentru plăți OP ale abonamentului platformă. */
        'iban' => env('PLATFORM_IBAN', ''),
        'bank_name' => env('PLATFORM_BANK_NAME', ''),
    ],
    'contact_email' => 'contact.facturare@dateconta.ro',

    /**
     * Raport zilnic PDF după emiterea recurentelor (toate firmele).
     * Listă separată prin virgulă: RECURRING_DAILY_REPORT_EMAILS (preferat) sau RECURRING_DAILY_REPORT_EMAIL.
     */
    'recurring_daily_report_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) (
            env('RECURRING_DAILY_REPORT_EMAILS')
            ?: env('RECURRING_DAILY_REPORT_EMAIL')
            ?: 'razvan@fly-david.ro,razvan@dateconta.ro'
        ))
    ))),
    /** @deprecated Folosește recurring_daily_report_emails */
    'recurring_daily_report_email' => env('RECURRING_DAILY_REPORT_EMAIL', 'razvan@fly-david.ro'),

    /**
     * Raport recapitular zilnic (23:55) — activitate pe toată platforma.
     * Implicit aceleași adrese ca raportul de recurente.
     */
    'daily_ops_report_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) (
            env('DAILY_OPS_REPORT_EMAILS')
            ?: env('RECURRING_DAILY_REPORT_EMAILS')
            ?: env('RECURRING_DAILY_REPORT_EMAIL')
            ?: 'razvan@fly-david.ro,razvan@dateconta.ro'
        ))
    ))),

    'schedule_token' => env('SCHEDULE_TOKEN'),

    /**
     * Abonament App Store (doar client iOS). Separat de abonamentul web EUR.
     * Gratis până la free_until; apoi: conturile noi primesc trial_months_after_promo luni,
     * după care StoreKit auto-renewable (1 / 3 / 6 / 12 luni). Conturile din promo → IAP imediat.
     * Web rămâne pe trial_months_after_promo (global, de obicei 6).
     */
    'ios_subscription' => [
        'free_until' => env('IOS_SUBSCRIPTION_FREE_UNTIL', '2027-03-31'),
        /** Conturi create după free_until: luni gratuite pe iOS înainte de App Store. */
        'trial_months_after_promo' => max(1, (int) env('IOS_SUBSCRIPTION_TRIAL_MONTHS', 1)),
        /**
         * Conturi App Review: ignoră perioada gratuită / trial și cer entitlement activ
         * (paywall + flux cumpărare). Poți marca și user.ios_force_paywall = true.
         */
        'review_force_paywall_emails' => array_values(array_filter(array_map(
            'strtolower',
            array_map('trim', explode(',', (string) env(
                'IOS_REVIEW_FORCE_PAYWALL_EMAILS',
                'review-expired@dateconta.ro'
            )))
        ))),
        /** @deprecated Folosește product_ids; păstrat pentru compat. */
        'product_id' => env('IOS_SUBSCRIPTION_PRODUCT_ID', 'ro.dateconta.facturare.premium.monthly'),
        'product_ids' => [
            'ro.dateconta.facturare.premium.monthly',
            'ro.dateconta.facturare.premium.3months',
            'ro.dateconta.facturare.premium.6months',
            'ro.dateconta.facturare.premium.yearly',
        ],
        'bundle_id' => env('IOS_SUBSCRIPTION_BUNDLE_ID', 'com.dateconta.Facturare-by-DateConta.Facturare-by-DateConta'),
        'apple_root_ca_path' => env(
            'IOS_APPLE_ROOT_CA_PATH',
            storage_path('certs/AppleRootCA-G3.pem')
        ),
        'apple_root_ca_sha256' => env(
            'IOS_APPLE_ROOT_CA_SHA256',
            '63343ABFB89A6A03EBB57E9B3F5FA7BE7C4F5C756F3017B3A8C488C3653E9179'
        ),
        /** Doar local/testing — NU activa în producție. */
        'allow_unverified' => (bool) env('IOS_IAP_ALLOW_UNVERIFIED', false),
    ],

    'anaf' => [
        'env' => $anafEnv,
        'client_id' => env('ANAF_CLIENT_ID'),
        'client_secret' => env('ANAF_CLIENT_SECRET'),
        'authorize_url' => 'https://logincert.anaf.ro/anaf-oauth2/v1/authorize',
        'token_url' => 'https://logincert.anaf.ro/anaf-oauth2/v1/token',
        'callback_url' => env('ANAF_CALLBACK_URL', rtrim((string) env('APP_URL'), '/').'/anaf/oauth/callback'),
        'api_base' => $anafEnv === 'test'
            ? 'https://api.anaf.ro/test/FCTEL/rest'
            : 'https://api.anaf.ro/prod/FCTEL/rest',
        'token_content_type' => 'jwt',
    ],
];
