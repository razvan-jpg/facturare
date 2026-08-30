<?php

/**
 * Meniu superior DateConta + dropdown-uri.
 * Doar funcții livrate.
 */
return [
    'emite' => [
        'label' => 'Emite',
        'icon' => 'emite',
        'match' => ['documents.create', 'documents.corrections.*', 'payments.*', 'recurring.create'],
        'groups' => [
            [
                'title' => 'Documente noi',
                'items' => [
                    ['label' => 'Factură', 'url' => '/documents/create?type=invoice', 'match' => 'documents.create', 'type' => 'invoice'],
                    ['label' => 'Proformă', 'url' => '/documents/create?type=proforma', 'match' => 'documents.create', 'type' => 'proforma'],
                    ['label' => 'Aviz de însoțire', 'url' => '/documents/create?type=delivery', 'match' => 'documents.create', 'type' => 'delivery'],
                    ['label' => 'Încasare', 'url' => '/payments/create', 'match' => 'payments.create'],
                    ['label' => 'Factură storno', 'url' => '/documents/corrections/storno', 'match' => 'documents.corrections.create'],
                    ['label' => 'Notă de creditare', 'url' => '/documents/corrections/credit_note', 'match' => 'documents.corrections.create'],
                    ['label' => 'Factură recurentă', 'url' => '/recurring/create', 'match' => 'recurring.create'],
                ],
            ],
            [
                'title' => 'Bani',
                'items' => [
                    ['label' => 'Încasare nouă', 'url' => '/payments/create', 'match' => 'payments.create'],
                    ['label' => 'Listă încasări', 'url' => '/payments', 'match' => 'payments.index'],
                ],
            ],
        ],
    ],

    'liste' => [
        'label' => 'Liste',
        'icon' => 'liste',
        'match' => ['documents.index', 'documents.show', 'documents.edit', 'recurring.index', 'recurring.show', 'recurring.edit'],
        'groups' => [
            [
                'title' => 'Emise',
                'items' => [
                    ['label' => 'Facturi', 'url' => '/documents?type=invoice', 'match' => 'documents.index', 'type' => 'invoice'],
                    ['label' => 'Proforme', 'url' => '/documents?type=proforma', 'match' => 'documents.index', 'type' => 'proforma'],
                    ['label' => 'Avize', 'url' => '/documents?type=delivery', 'match' => 'documents.index', 'type' => 'delivery'],
                    ['label' => 'Chitanțe', 'url' => '/documents?type=receipt', 'match' => 'documents.index', 'type' => 'receipt'],
                    ['label' => 'Facturi storno', 'url' => '/documents?type=storno', 'match' => 'documents.index', 'type' => 'storno'],
                    ['label' => 'Note de creditare', 'url' => '/documents?type=credit_note', 'match' => 'documents.index', 'type' => 'credit_note'],
                    ['label' => 'Recurente', 'url' => '/recurring', 'match' => 'recurring.*', 'except' => 'recurring.create'],
                ],
            ],
        ],
    ],

    'catalog' => [
        'label' => 'Catalog',
        'icon' => 'catalog',
        'match' => ['clients.*', 'products.*'],
        'groups' => [
            [
                'title' => null,
                'items' => [
                    ['label' => 'Clienți', 'url' => '/clients', 'match' => 'clients.*'],
                    ['label' => 'Produse și servicii', 'url' => '/products', 'match' => 'products.*'],
                ],
            ],
        ],
    ],

    'rapoarte' => [
        'label' => 'Rapoarte',
        'icon' => 'rapoarte',
        'match' => ['reports.*'],
        'groups' => [
            [
                'title' => null,
                'items' => [
                    ['label' => 'Vânzări și încasări', 'url' => '/reports', 'match' => 'reports.index'],
                    ['label' => 'Clienți (solduri)', 'url' => '/reports/clients', 'match' => 'reports.clients'],
                    ['label' => 'Export CSV', 'url' => '/reports/export', 'match' => 'reports.export'],
                ],
            ],
        ],
    ],

    'ajutor' => [
        'label' => 'Ajutor',
        'icon' => 'help',
        'match' => ['help.*'],
        'groups' => [
            [
                'title' => 'Noutăți',
                'items' => [
                    ['label' => 'Ce este nou…', 'url' => '/ajutor/ce-este-nou', 'match' => 'help.whats-new'],
                ],
            ],
            [
                'title' => 'Manual',
                'items' => [
                    ['label' => 'Cuprins', 'url' => '/ajutor', 'match' => 'help.index'],
                    ['label' => 'Introducere', 'url' => '/ajutor/intro', 'match' => 'help.show'],
                    ['label' => 'Navigare', 'url' => '/ajutor/navigare', 'match' => 'help.show'],
                    ['label' => 'Cod promoțional', 'url' => '/ajutor/cod-promotional', 'match' => 'help.show'],
                    ['label' => 'Emitere factură', 'url' => '/ajutor/emitere-factura', 'match' => 'help.show'],
                    ['label' => 'Clienți și produse', 'url' => '/ajutor/clienti', 'match' => 'help.show'],
                    ['label' => 'e-Factura ANAF', 'url' => '/ajutor/efactura', 'match' => 'help.show'],
                    ['label' => 'Utilizatori', 'url' => '/ajutor/utilizatori', 'match' => 'help.show'],
                    ['label' => 'Întrebări frecvente', 'url' => '/ajutor/intrebari', 'match' => 'help.show'],
                ],
            ],
        ],
    ],

    'legal' => [
        'label' => 'Legal',
        'icon' => 'legal',
        'match' => ['legal.*'],
        'groups' => [
            [
                'title' => 'Documente',
                'items' => [
                    ['label' => 'Termeni și condiții de folosire', 'url' => '/legal/termeni', 'match' => 'legal.show'],
                    ['label' => 'Politica de confidențialitate', 'url' => '/legal/confidentialitate', 'match' => 'legal.show'],
                    ['label' => 'Politica de livrare comandă', 'url' => '/legal/livrare', 'match' => 'legal.show'],
                    ['label' => 'Politica de anulare comandă', 'url' => '/legal/anulare', 'match' => 'legal.show'],
                    ['label' => 'Politica GDPR', 'url' => '/legal/gdpr', 'match' => 'legal.show'],
                ],
            ],
        ],
    ],

    'setari' => [
        'label' => 'Setări',
        'icon' => 'setari',
        'match' => ['companies.*', 'admin.integrari.*', 'company-users.*'],
        'groups' => [
            [
                'title' => 'Firmă',
                'items' => [
                    ['label' => 'Date generale', 'tab' => 'generale'],
                    ['label' => 'Sedii', 'tab' => 'sedii'],
                    ['label' => 'Conturi bancare', 'tab' => 'conturi'],
                ],
            ],
            [
                'title' => 'Documente',
                'items' => [
                    ['label' => 'Serii', 'tab' => 'serii'],
                    ['label' => 'Personalizare PDF', 'tab' => 'personalizare'],
                    ['label' => 'Cote TVA', 'tab' => 'cote-tva'],
                    ['label' => 'e-Factura ANAF', 'tab' => 'efactura'],
                    ['label' => 'Limbi', 'tab' => 'limbi'],
                ],
            ],
            [
                'title' => 'Integrări',
                'items' => [
                    ['label' => 'Plată cu cardul', 'tab' => 'integrari'],
                ],
            ],
            [
                'title' => 'Cont',
                'items' => [
                    ['label' => 'Preferințe personale', 'tab' => 'preferinte-personale'],
                    ['label' => 'Preferințe', 'tab' => 'preferinte-generale'],
                    ['label' => 'Email', 'tab' => 'email'],
                    ['label' => 'Notificări', 'tab' => 'notificari'],
                    ['label' => 'Utilizatori', 'url' => '/utilizatori', 'match' => 'company-users.*', 'owners_only' => true],
                    ['label' => 'Abonament utilizatori', 'url' => '/billing/locuri', 'match' => 'billing.seats*', 'owners_only' => true],
                    ['label' => 'Societățile mele', 'url' => '/companies?all=1', 'match' => 'companies.index'],
                ],
            ],
        ],
    ],
];
