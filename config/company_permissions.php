<?php

/**
 * Drepturi pe societate (Setări → Utilizatori).
 * Pe fiecare categorie: vizualizare (_view) + creare/editare (_manage).
 * `access` = membru pe societate.
 */
return [
    'categories' => [
        'documents' => 'Documente',
        'clients' => 'Clienți',
        'products' => 'Produse și servicii',
        'payments' => 'Încasări',
        'recurring' => 'Facturi recurente',
        'reports' => 'Rapoarte',
        'efactura' => 'e-Factura ANAF',
        'settings' => 'Setări firmă',
    ],

    'abilities' => [
        'access' => 'Acces societate',
        'documents_view' => 'Documente — vizualizare',
        'documents_manage' => 'Documente — creare/editare',
        'clients_view' => 'Clienți — vizualizare',
        'clients_manage' => 'Clienți — creare/editare',
        'products_view' => 'Produse — vizualizare',
        'products_manage' => 'Produse — creare/editare',
        'payments_view' => 'Încasări — vizualizare',
        'payments_manage' => 'Încasări — creare/editare',
        'recurring_view' => 'Recurente — vizualizare',
        'recurring_manage' => 'Recurente — creare/editare',
        'reports_view' => 'Rapoarte — vizualizare',
        'reports_manage' => 'Rapoarte — creare/editare',
        'efactura_view' => 'e-Factura — vizualizare',
        'efactura_manage' => 'e-Factura — creare/editare',
        'settings_view' => 'Setări — vizualizare',
        'settings_manage' => 'Setări — creare/editare',
    ],

    /** Mapare meniu → abilități (oricare). */
    'nav' => [
        'emite' => ['documents_manage', 'payments_view', 'payments_manage', 'recurring_manage'],
        'liste' => ['documents_view', 'documents_manage', 'recurring_view', 'recurring_manage'],
        'catalog' => ['clients_view', 'clients_manage', 'products_view', 'products_manage'],
        'rapoarte' => ['reports_view', 'reports_manage'],
    ],

    /**
     * Chei vechi → noi (compatibilitate JSON salvat înainte de split view/manage).
     */
    'legacy_map' => [
        'documents_create' => ['documents_view', 'documents_manage'],
        'documents_view' => ['documents_view'],
        'clients' => ['clients_view', 'clients_manage'],
        'products' => ['products_view', 'products_manage'],
        'payments' => ['payments_view', 'payments_manage'],
        'recurring' => ['recurring_view', 'recurring_manage'],
        'reports' => ['reports_view', 'reports_manage'],
        'efactura' => ['efactura_view', 'efactura_manage'],
        'settings' => ['settings_view', 'settings_manage'],
    ],
];
