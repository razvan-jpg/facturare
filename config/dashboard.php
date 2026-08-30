<?php

/**
 * Catalog widget-uri dashboard (personalizabile, tip SmartBill).
 */
return [
    'max_slots' => 12,

    'categories' => [
        'all' => 'Toate',
        'sales' => 'Analiza vânzărilor',
        'collections' => 'Gestiunea încasărilor',
        'misc' => 'Diverse',
    ],

    'widgets' => [
        'sales' => [
            'category' => 'sales',
            'title' => 'Vânzări',
            'description' => 'Valoarea totală a facturilor emise în luna curentă, număr emise, medie/zi și grafic zilnic.',
            'thumb' => 'chart',
            'span' => 1,
            'configure' => [],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Totalul facturilor emise în luna curentă (RON).',
                    'Numărul de facturi emise și valoarea medie pe zi.',
                    'Graficul evoluției zilnice a vânzărilor.',
                ],
                'Care este utilitatea?' => [
                    'Vezi rapid ritmul vânzărilor din luna curentă.',
                    'Compară zilele cu vârfuri față de zilele liniștite.',
                ],
                'Bine de știut!' => [
                    'Valorile țin de societatea activă.',
                    'Reîmprospătarea reîncarcă datele de pe server.',
                ],
            ],
        ],
        'top_clients' => [
            'category' => 'sales',
            'title' => 'Top clienți',
            'description' => 'Primii clienți după valoarea facturilor emise în luna curentă.',
            'thumb' => 'rank',
            'span' => 1,
            'configure' => ['sort'],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Primii clienți după valoarea facturilor emise în luna curentă.',
                    'Bara arată ponderea relativă față de liderul din listă.',
                ],
                'Care este utilitatea?' => [
                    'Identifici rapid clienții care generează cele mai mari vânzări.',
                    'Poți prioritiza loializarea și urmărirea acestora.',
                ],
                'Bine de știut!' => [
                    'Poți schimba sortarea crescătoare/descrescătoare din Configurări.',
                ],
            ],
        ],
        'top_products' => [
            'category' => 'sales',
            'title' => 'Top produse',
            'description' => 'Produsele/serviciile cu cele mai mari vânzări în luna curentă.',
            'thumb' => 'rank',
            'span' => 1,
            'configure' => ['sort_by', 'sort'],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Top produse/servicii din luna curentă.',
                    'Poți sorta după valoare sau după cantitate.',
                ],
                'Care este utilitatea?' => [
                    'Vezi ce se vinde cel mai bine și ajustezi oferta.',
                ],
                'Bine de știut!' => [
                    'Sortarea se setează din Configurări și se salvează pe contul tău.',
                ],
            ],
        ],
        'payments' => [
            'category' => 'collections',
            'title' => 'Încasări',
            'description' => 'Total încasări înregistrate luna curentă, număr, medie/zi și grafic zilnic.',
            'thumb' => 'chart',
            'span' => 1,
            'configure' => [],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Totalul plăților înregistrate în luna curentă.',
                    'Numărul de încasări și media pe zi, plus grafic zilnic.',
                ],
                'Care este utilitatea?' => [
                    'Urmărești ritmul cash-flow-ului din lună.',
                ],
                'Bine de știut!' => [
                    'Include toate metodele de plată înregistrate în aplicație.',
                ],
            ],
        ],
        'payments_chart' => [
            'category' => 'collections',
            'title' => 'Grafic încasări',
            'description' => 'Evoluția grafică a încasărilor pe zile în luna curentă (vizualizare mărită).',
            'thumb' => 'chart',
            'span' => 1,
            'configure' => [],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Grafic mărit al încasărilor pe zile în luna curentă.',
                ],
                'Care este utilitatea?' => [
                    'Vizualizare rapidă a trendului de încasări.',
                ],
                'Bine de știut!' => [
                    'Datele sunt aceleași ca la widget-ul Încasări, pe un format mai mare.',
                ],
            ],
        ],
        'unpaid' => [
            'category' => 'collections',
            'title' => 'Facturi neîncasate',
            'description' => 'Facturi și proforme cu rest de plată, scadență și zile de întârziere.',
            'thumb' => 'table',
            'span' => 2,
            'configure' => ['only_overdue', 'ignore_before'],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Documente cu rest de încasat, scadență și zile de întârziere.',
                    'Totalul restant afișat în antetul listei.',
                ],
                'Care este utilitatea?' => [
                    'Prioritizezi încasările și contactezi clienții restanțieri.',
                ],
                'Bine de știut!' => [
                    'Din Configurări poți afișa doar scadențele depășite sau ignora documente vechi.',
                    'Sumele în altă monedă sunt afișate în moneda documentului.',
                ],
            ],
        ],
        'client_balances' => [
            'category' => 'collections',
            'title' => 'Sold clienți',
            'description' => 'Clienții cu cele mai mari solduri deschise la data de azi.',
            'thumb' => 'rank',
            'span' => 1,
            'configure' => ['sort', 'ignore_before'],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Clienții cu cele mai mari solduri deschise.',
                ],
                'Care este utilitatea?' => [
                    'Vezi rapid cine datorează cele mai mari sume.',
                ],
                'Bine de știut!' => [
                    'Poți sorta crescător/descrescător și ignora documente emise înainte de o dată.',
                ],
            ],
        ],
        'unbilled_penalties' => [
            'category' => 'collections',
            'title' => 'Penalități nefacturate',
            'description' => 'Clienții cu penalități calculate până azi și încă nefacturate, sortați descrescător după sumă.',
            'thumb' => 'rank',
            'span' => 1,
            'configure' => [],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Doar clienții care au penalități calculate și încă nefacturate, până la data curentă.',
                    'Lista e sortată descrescător după sumă; clienții fără penalități nu apar.',
                ],
                'Care este utilitatea?' => [
                    'Vezi rapid cine acumulează penalități de facturat.',
                    'Poți deschide fișa clientului din listă.',
                ],
                'Bine de știut!' => [
                    'Sumele țin de calculul zilnic (și de procentul setat pe client), indiferent dacă toggle-ul de facturare e ON sau OFF.',
                    'La emiterea următoarei facturi, liniile de penalizare apar doar dacă facturarea e activată pe client.',
                ],
            ],
        ],
        'receivables' => [
            'category' => 'collections',
            'title' => 'Sume de încasat',
            'description' => 'Total de recuperat, depășit vs în termen și defalcare pe intervale de restanță.',
            'thumb' => 'bars',
            'span' => 1,
            'configure' => ['ignore_before'],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Total de încasat, împărțit în Depășit / În termen.',
                    'Defalcare pe intervale: azi, 1–7, 8–14, 15–30, peste 30 zile.',
                ],
                'Care este utilitatea?' => [
                    'Radar operațional pentru recuperarea creanțelor.',
                ],
                'Bine de știut!' => [
                    'Include și soldurile inițiale ale clienților, unde există.',
                ],
            ],
        ],
        'cash' => [
            'category' => 'collections',
            'title' => 'Numerar & scadențe',
            'description' => 'Numerar luna curentă, total încasări azi/lună și sume scadente azi / în 7 zile.',
            'thumb' => 'cash',
            'span' => 1,
            'configure' => ['currency'],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Numerar (cash/chitanță) pe luna curentă.',
                    'Total încasări azi / lună și scadente azi / în 7 zile.',
                ],
                'Care este utilitatea?' => [
                    'Ai o privire rapidă asupra lichidităților și a scadențelor apropiate.',
                ],
                'Bine de știut!' => [
                    'Moneda afișată se alege din Configurări (implicit RON).',
                ],
            ],
        ],
        'activity' => [
            'category' => 'misc',
            'title' => 'Activități efectuate',
            'description' => 'Sumar al celor mai recente acțiuni din aplicație (emiteri și încasări).',
            'thumb' => 'list',
            'span' => 1,
            'configure' => ['activity_filters'],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Ultimele acțiuni din aplicație: emiteri, încasări etc.',
                ],
                'Care este utilitatea?' => [
                    'Vezi ce s-a întâmplat recent pe societatea activă.',
                ],
                'Bine de știut!' => [
                    'Din Configurări alegi tipurile de evenimente afișate.',
                ],
            ],
        ],
        'upcoming_recurring' => [
            'category' => 'misc',
            'title' => 'Activități viitoare',
            'description' => 'Abonamentele recurente programate: următoarea emitere, client, valoare și frecvență.',
            'thumb' => 'list',
            'span' => 1,
            'configure' => [],
            'details' => [
                'Ce informații afișează elementul?' => [
                    'Abonamentele recurente active, ordonate după următoarea emitere.',
                    'Client, tip document, valoare estimată și frecvență.',
                ],
                'Care este utilitatea?' => [
                    'Anticipezi emiterile automate și verifici ce e scadent.',
                ],
                'Bine de știut!' => [
                    'Linkul din listă deschide abonamentul; din meniu poți merge la lista de recurente.',
                ],
            ],
        ],
    ],

    'default_settings' => [
        'sort' => 'desc',
        'sort_by' => 'value',
        'only_overdue' => false,
        'ignore_before_enabled' => false,
        'ignore_before' => null,
        'currency' => 'RON',
        'show_issues' => true,
        'show_payments' => true,
        'show_edits' => false,
        'show_cancels' => false,
        'show_deletes' => false,
    ],

    'default' => [
        'receivables',
        'top_clients',
        'activity',
        'upcoming_recurring',
        'cash',
        'sales',
        'payments',
        'payments_chart',
        'client_balances',
        'unbilled_penalties',
        'unpaid',
        'top_products',
    ],
];
