<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\AccessGate;
use App\Services\CollectionService;
use App\Services\DocumentService;
use App\Services\RecurringInvoiceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MobileDemoSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@dateconta.ro';

    public const DEMO_PASSWORD = 'DemoDateConta1!';

    public const DEMO_COMPANY_CUI = '90000001';

    public function run(): void
    {
        $gate = app(AccessGate::class);
        $documents = app(DocumentService::class);
        $collections = app(CollectionService::class);
        $recurring = app(RecurringInvoiceService::class);

        $demo = User::query()->updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'Utilizator Demo',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        $gate->applyOnRegister($demo);
        $demo->forceFill([
            'plan' => 'paid',
            'access_until' => null,
            'trial_ends_at' => null,
            'is_admin' => false,
        ])->save();

        $company = Company::query()->firstOrCreate(
            ['cui' => self::DEMO_COMPANY_CUI],
            [
                'owner_id' => $demo->id,
                'name' => 'Firma Demo DateConta',
                'reg_com' => 'J40/DEMO/2026',
                'address' => 'Str. Demo nr. 10',
                'city' => 'București',
                'county' => 'București',
                'country' => 'România',
                'vat_payer' => true,
                'default_vat_rate' => 21,
                'iban' => 'RO49AAAA1B31007593840000',
                'bank_name' => 'Banca Demo',
                'invoice_notes' => 'Vă mulțumim pentru colaborare!',
            ]
        );

        if ((int) $company->owner_id !== (int) $demo->id) {
            $company->forceFill(['owner_id' => $demo->id])->save();
        }

        // Doar Firma Demo — nu lăsa demo pe FLY DAVID / alte societăți de platformă.
        $demo->companies()->sync([
            $company->id => ['role' => 'owner', 'permissions' => null],
        ]);
        $demo->forceFill(['current_company_id' => $company->id])->save();

        $documents->ensureDefaultSeries($company);

        $client = Client::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'cui' => '12345678',
            ],
            [
                'name' => 'Client Demo SRL',
                'type' => 'company',
                'reg_com' => 'J40/1234/2020',
                'address' => 'Str. Exemplu nr. 1',
                'city' => 'București',
                'county' => 'București',
                'country' => 'România',
                'email' => 'client@example.com',
                'iban' => 'RO49AAAA1B31007593840001',
                'bank_name' => 'Banca Client',
            ]
        );

        $consulting = Product::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Servicii consultanță',
            ],
            [
                'unit' => 'ore',
                'type' => 'service',
                'price' => 250,
                'vat_rate' => 21,
            ]
        );

        $hosting = Product::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Abonament hosting',
            ],
            [
                'unit' => 'lună',
                'type' => 'service',
                'price' => 120,
                'vat_rate' => 21,
            ]
        );

        $support = Product::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Suport tehnic',
            ],
            [
                'unit' => 'ore',
                'type' => 'service',
                'price' => 180,
                'vat_rate' => 21,
            ]
        );

        $issuedInvoices = $company->documents()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->count();

        if ($issuedInvoices === 0) {
            $this->seedDocumentsAndPayments(
                $company,
                $demo,
                $client,
                $consulting,
                $hosting,
                $support,
                $documents,
                $collections
            );
        }

        if ($company->recurringInvoices()->count() === 0) {
            $this->seedRecurring(
                $company,
                $demo,
                $client,
                $hosting,
                $support,
                $recurring
            );
        }

        $this->command?->info(
            'Demo mobil: '.self::DEMO_EMAIL.' / '.self::DEMO_PASSWORD
            .' pe firma #'.$company->id.' ('.$company->name.')'
            .' · client: '.$client->name
            .' · facturi: '.$company->documents()->where('type', 'invoice')->count()
            .' · proforme: '.$company->documents()->where('type', 'proforma')->count()
            .' · recurente: '.$company->recurringInvoices()->count()
        );
    }

    private function seedDocumentsAndPayments(
        Company $company,
        User $demo,
        Client $client,
        Product $consulting,
        Product $hosting,
        Product $support,
        DocumentService $documents,
        CollectionService $collections,
    ): void {
        // Factură 1 — parțial încasată cu card
        $invoiceCard = $documents->issue($documents->createDraft($company, $demo, 'invoice', [
            'client_id' => $client->id,
            'issue_date' => now()->subDays(45)->toDateString(),
            'due_date' => now()->subDays(30)->toDateString(),
            'currency' => 'RON',
            'notes' => 'Consultanță Q1 — plată parțială card',
        ], [[
            'product_id' => $consulting->id,
            'name' => $consulting->name,
            'unit' => $consulting->unit,
            'quantity' => 10,
            'unit_price' => 250,
            'vat_rate' => 21,
        ]])->fresh('items'));

        Payment::create([
            'company_id' => $company->id,
            'document_id' => $invoiceCard->id,
            'client_id' => $client->id,
            'method' => 'card',
            'paid_at' => now()->subDays(40)->toDateString(),
            'amount' => round((float) $invoiceCard->total * 0.4, 2),
            'currency' => 'RON',
            'reference' => 'CARD-DEMO-001',
            'notes' => 'Plată parțială cu card',
        ]);
        $invoiceCard->refreshPaymentStatus();

        // Factură 2 — parțial încasată cu OP
        $invoiceOp = $documents->issue($documents->createDraft($company, $demo, 'invoice', [
            'client_id' => $client->id,
            'issue_date' => now()->subDays(28)->toDateString(),
            'due_date' => now()->subDays(13)->toDateString(),
            'currency' => 'RON',
            'notes' => 'Hosting + suport — avans OP',
        ], [
            [
                'product_id' => $hosting->id,
                'name' => $hosting->name,
                'unit' => $hosting->unit,
                'quantity' => 3,
                'unit_price' => 120,
                'vat_rate' => 21,
            ],
            [
                'product_id' => $support->id,
                'name' => $support->name,
                'unit' => $support->unit,
                'quantity' => 4,
                'unit_price' => 180,
                'vat_rate' => 21,
            ],
        ])->fresh('items'));

        $collections->collect(
            $company,
            $demo,
            $client,
            'op',
            round((float) $invoiceOp->total * 0.5, 2),
            now()->subDays(20)->toDateString(),
            'RON',
            'Avans factură '.$invoiceOp->number_full,
            null,
            'ro',
            [$invoiceOp->id],
            false
        );

        // Factură 3 — parțial încasată cu chitanță
        $invoiceReceipt = $documents->issue($documents->createDraft($company, $demo, 'invoice', [
            'client_id' => $client->id,
            'issue_date' => now()->subDays(18)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
            'currency' => 'RON',
            'notes' => 'Servicii punctuale — chitanță parțială',
        ], [[
            'product_id' => $consulting->id,
            'name' => $consulting->name,
            'unit' => $consulting->unit,
            'quantity' => 6,
            'unit_price' => 250,
            'vat_rate' => 21,
        ]])->fresh('items'));

        $collections->collect(
            $company,
            $demo,
            $client,
            'receipt',
            round((float) $invoiceReceipt->total * 0.35, 2),
            now()->subDays(10)->toDateString(),
            'RON',
            'Încasare parțială '.$invoiceReceipt->number_full,
            null,
            'ro',
            [$invoiceReceipt->id],
            false
        );

        // Factură 4 — mixt (card + OP), încă parțială
        $invoiceMixed = $documents->issue($documents->createDraft($company, $demo, 'invoice', [
            'client_id' => $client->id,
            'issue_date' => now()->subDays(12)->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'currency' => 'RON',
            'notes' => 'Pachet mix — card + OP',
        ], [
            [
                'product_id' => $consulting->id,
                'name' => $consulting->name,
                'unit' => $consulting->unit,
                'quantity' => 8,
                'unit_price' => 250,
                'vat_rate' => 21,
            ],
            [
                'product_id' => $hosting->id,
                'name' => $hosting->name,
                'unit' => $hosting->unit,
                'quantity' => 1,
                'unit_price' => 120,
                'vat_rate' => 21,
            ],
        ])->fresh('items'));

        Payment::create([
            'company_id' => $company->id,
            'document_id' => $invoiceMixed->id,
            'client_id' => $client->id,
            'method' => 'card',
            'paid_at' => now()->subDays(8)->toDateString(),
            'amount' => round((float) $invoiceMixed->total * 0.25, 2),
            'currency' => 'RON',
            'reference' => 'CARD-DEMO-002',
            'notes' => 'Avans card',
        ]);
        $invoiceMixed->refreshPaymentStatus();

        $collections->collect(
            $company,
            $demo,
            $client,
            'op',
            round((float) $invoiceMixed->total * 0.3, 2),
            now()->subDays(5)->toDateString(),
            'RON',
            'Tranșă OP '.$invoiceMixed->number_full,
            null,
            'ro',
            [$invoiceMixed->id],
            false
        );

        // Factură 5 — neîncasată
        $documents->issue($documents->createDraft($company, $demo, 'invoice', [
            'client_id' => $client->id,
            'issue_date' => now()->subDays(4)->toDateString(),
            'due_date' => now()->addDays(11)->toDateString(),
            'currency' => 'RON',
            'notes' => 'Factură recentă — neîncasată',
        ], [[
            'product_id' => $support->id,
            'name' => $support->name,
            'unit' => $support->unit,
            'quantity' => 5,
            'unit_price' => 180,
            'vat_rate' => 21,
        ]])->fresh('items'));

        // Proforme
        foreach ([
            [
                'days_ago' => 20,
                'due_in' => 10,
                'items' => [[
                    'product_id' => $consulting->id,
                    'name' => $consulting->name,
                    'unit' => $consulting->unit,
                    'quantity' => 12,
                    'unit_price' => 250,
                    'vat_rate' => 21,
                ]],
                'notes' => 'Proformă consultanță proiect nou',
            ],
            [
                'days_ago' => 7,
                'due_in' => 23,
                'items' => [
                    [
                        'product_id' => $hosting->id,
                        'name' => $hosting->name,
                        'unit' => $hosting->unit,
                        'quantity' => 12,
                        'unit_price' => 120,
                        'vat_rate' => 21,
                    ],
                    [
                        'product_id' => $support->id,
                        'name' => $support->name,
                        'unit' => $support->unit,
                        'quantity' => 2,
                        'unit_price' => 180,
                        'vat_rate' => 21,
                    ],
                ],
                'notes' => 'Proformă abonament anual hosting + suport',
            ],
            [
                'days_ago' => 2,
                'due_in' => 28,
                'items' => [[
                    'product_id' => $consulting->id,
                    'name' => $consulting->name,
                    'unit' => $consulting->unit,
                    'quantity' => 4,
                    'unit_price' => 250,
                    'vat_rate' => 21,
                ]],
                'notes' => 'Proformă draft emisă recent',
            ],
        ] as $proforma) {
            $documents->issue($documents->createDraft($company, $demo, 'proforma', [
                'client_id' => $client->id,
                'issue_date' => now()->subDays($proforma['days_ago'])->toDateString(),
                'due_date' => now()->addDays($proforma['due_in'])->toDateString(),
                'currency' => 'RON',
                'notes' => $proforma['notes'],
            ], $proforma['items'])->fresh('items'));
        }
    }

    private function seedRecurring(
        Company $company,
        User $demo,
        Client $client,
        Product $hosting,
        Product $support,
        RecurringInvoiceService $recurring,
    ): void {
        $recurring->save($company, $demo, [
            'client_id' => $client->id,
            'title' => 'Hosting lunar Client Demo',
            'subscription_number' => 'ABO-DEMO-001',
            'frequency' => 'monthly',
            'start_date' => now()->subMonths(2)->startOfMonth()->toDateString(),
            'next_run_date' => now()->addMonth()->startOfMonth()->toDateString(),
            'due_days' => 15,
            'currency' => 'RON',
            'auto_issue' => true,
            'active' => true,
            'notes' => 'Factură recurentă hosting lunar',
        ], [[
            'product_id' => $hosting->id,
            'name' => $hosting->name,
            'unit' => $hosting->unit,
            'quantity' => 1,
            'unit_price' => 120,
            'vat_rate' => 21,
        ]]);

        $recurring->save($company, $demo, [
            'client_id' => $client->id,
            'title' => 'Suport trimestrial Client Demo',
            'subscription_number' => 'ABO-DEMO-002',
            'frequency' => 'quarterly',
            'start_date' => now()->subMonths(1)->startOfMonth()->toDateString(),
            'next_run_date' => now()->addMonths(2)->startOfMonth()->toDateString(),
            'due_days' => 30,
            'currency' => 'RON',
            'auto_issue' => false,
            'active' => true,
            'notes' => 'Pachet suport trimestrial',
        ], [[
            'product_id' => $support->id,
            'name' => $support->name,
            'unit' => $support->unit,
            'quantity' => 10,
            'unit_price' => 180,
            'vat_rate' => 21,
        ]]);

        $recurring->save($company, $demo, [
            'client_id' => $client->id,
            'title' => 'Pachet consultanță + hosting',
            'subscription_number' => 'ABO-DEMO-003',
            'frequency' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'next_run_date' => now()->addMonth()->startOfMonth()->toDateString(),
            'due_days' => 10,
            'currency' => 'RON',
            'auto_issue' => true,
            'active' => true,
            'notes' => 'Abonament mixt lunar',
        ], [
            [
                'product_id' => $hosting->id,
                'name' => $hosting->name,
                'unit' => $hosting->unit,
                'quantity' => 1,
                'unit_price' => 120,
                'vat_rate' => 21,
            ],
            [
                'product_id' => $support->id,
                'name' => 'Retainer suport',
                'unit' => $support->unit,
                'quantity' => 2,
                'unit_price' => 180,
                'vat_rate' => 21,
            ],
        ]);
    }
}
