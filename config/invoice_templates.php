<?php

/**
 * Machete PDF. Cheile restricționate au allowed_cui.
 * forced_by_cui: societățile listate pot folosi DOAR macheta indicată (fără selector).
 */
return [
    /*
     * FIRST CONSULTING BEST MANAGEMENT SRL → DateConta A
     * FLY DAVID SRL → DateConta B
     */
    'forced_by_cui' => [
        '40094365' => 'dateconta',
        '38254880' => 'dateconta_b',
    ],

    'classic' => [
        'name' => 'Clasic',
        'description' => 'Antet clar, tabel simplu — stil contabil tradițional.',
    ],
    'modern' => [
        'name' => 'Modern',
        'description' => 'Bandă colorată sus, spațiu aerisit, accent pe total.',
    ],
    'compact' => [
        'name' => 'Compact',
        'description' => 'Dens pe o pagină, fonturi mai mici, fără spații mari.',
    ],
    'bold' => [
        'name' => 'Bold',
        'description' => 'Header plin cu culoarea firmei, tipografie puternică.',
    ],
    'elegant' => [
        'name' => 'Elegant',
        'description' => 'Linii fine, antet centrat, aspect premium.',
    ],
    'stripe' => [
        'name' => 'Stripe',
        'description' => 'Dungă laterală colorată și casetă de totaluri.',
    ],
    'nord' => [
        'name' => 'Nord',
        'description' => 'Minimal scandinav — aerisit, linii subțiri, total discret.',
    ],
    'ledger' => [
        'name' => 'Ledger',
        'description' => 'Aspect contabil RO — chenare, casete furnizor/client.',
    ],
    'studio' => [
        'name' => 'Studio',
        'description' => 'Titlu tipografic mare, impact editorial.',
    ],
    'frame' => [
        'name' => 'Frame',
        'description' => 'Ramă fină pe pagină, antet simetric, premium.',
    ],
    'swiss' => [
        'name' => 'Swiss',
        'description' => 'Coloană meta stânga, conținut dreapta — grid elvețian.',
    ],
    'folio' => [
        'name' => 'Folio',
        'description' => 'Letterhead corporate, clar pentru IMM-uri.',
    ],
    'split' => [
        'name' => 'Split',
        'description' => 'Jumătate brand colorată cu total, jumătate tabel.',
    ],
    'ticket' => [
        'name' => 'Ticket',
        'description' => 'Compact tip chitanță — focus pe sumă și plată.',
    ],
    'dateconta' => [
        'name' => 'DateConta A',
        'description' => 'Antet teal + bandă amber — brand DateConta (firme autorizate).',
        'restricted' => true,
        'allowed_cui' => ['38254880', '40094365'],
    ],
    'dateconta_b' => [
        'name' => 'DateConta B',
        'description' => 'Coloană teal stânga + total accent amber — brand DateConta (firme autorizate).',
        'restricted' => true,
        'allowed_cui' => ['38254880', '40094365'],
    ],
];
