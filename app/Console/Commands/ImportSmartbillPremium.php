<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyBank;
use App\Models\CompanyBankAccount;
use App\Models\DocumentSeries;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\User;
use App\Services\DocumentService;
use App\Support\MeasureUnits;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSmartbillPremium extends Command
{
    protected $signature = 'app:import-smartbill-premium
        {--payload= : Path to import_payload.json}
        {--owner=razvan.ivan@icloud.com : Owner email}';

    protected $description = 'Import company data scraped from SmartBill (payload JSON)';

    public function handle(DocumentService $documents): int
    {
        $path = $this->option('payload')
            ?: storage_path('app/smartbill_premium/import_payload.json');

        if (! is_file($path)) {
            $this->error('Payload missing: '.$path);

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $ownerEmail = (string) $this->option('owner');
        $owner = User::query()->where('email', $ownerEmail)->first();
        if (! $owner) {
            $this->error('Owner user not found: '.$ownerEmail);

            return self::FAILURE;
        }

        $stats = [
            'company' => 'unchanged',
            'banks' => 0,
            'series' => 0,
            'clients_created' => 0,
            'clients_updated' => 0,
            'products_created' => 0,
            'products_updated' => 0,
            'recurring_created' => 0,
            'recurring_updated' => 0,
        ];

        $company = DB::transaction(function () use ($payload, $owner, $documents, &$stats) {
            $companyData = $payload['company'];
            $cuiDigits = preg_replace('/\D+/', '', (string) ($companyData['cui'] ?? '')) ?: '34633694';

            $company = Company::query()
                ->whereRaw("REPLACE(REPLACE(UPPER(cui),'RO',''),' ','') = ?", [$cuiDigits])
                ->first();

            $attrs = [
                'name' => $companyData['name'],
                'cui' => dc_format_cui($cuiDigits, (bool) ($companyData['vat_payer'] ?? true)),
                'reg_com' => $companyData['reg_com'] ?? null,
                'address' => $companyData['address'] ?? null,
                'city' => $companyData['city'] ?? null,
                'county' => dc_normalize_county($companyData['county'] ?? null),
                'country' => $companyData['country'] ?? 'România',
                'capital_social' => $companyData['capital_social'] ?? null,
                'phone' => $companyData['phone'] ?? null,
                'email' => $companyData['email'] ?? null,
                'website' => $companyData['website'] ?? null,
                'iban' => $companyData['iban'] ?? null,
                'bank_name' => $companyData['bank_name'] ?? null,
                'vat_payer' => (bool) ($companyData['vat_payer'] ?? true),
                'vat_on_collection' => (bool) ($companyData['vat_on_collection'] ?? false),
                'default_vat_rate' => (float) ($companyData['default_vat_rate'] ?? 21),
            ];

            if (! $company) {
                $attrs['owner_id'] = $owner->id;
                $attrs['document_languages'] = ['ro'];
                $attrs['preferences'] = [
                    'show_cui_on_docs' => true,
                    'show_reg_com_on_docs' => true,
                    'show_bank_on_docs' => true,
                    'show_product_code' => false,
                    'default_due_days' => 15,
                    'documents_per_page' => 25,
                ];
                $company = Company::create($attrs);
                $company->users()->syncWithoutDetaching([$owner->id => ['role' => 'owner']]);
                $documents->ensureDefaultSeries($company);
                $stats['company'] = 'created';
            } else {
                $company->fill($attrs)->save();
                $company->users()->syncWithoutDetaching([$owner->id => ['role' => 'owner']]);
                $documents->ensureDefaultSeries($company);
                $stats['company'] = 'updated';
            }

            $stats['banks'] = $this->syncBanks($company, $payload['banks'] ?? []);
            $stats['series'] = $this->syncSeries($company, $payload['series'] ?? []);

            foreach ($payload['clients'] ?? [] as $row) {
                $created = $this->upsertClient($company, $row);
                $stats[$created ? 'clients_created' : 'clients_updated']++;
            }

            foreach ($payload['products'] ?? [] as $row) {
                $created = $this->upsertProduct($company, $row);
                $stats[$created ? 'products_created' : 'products_updated']++;
            }

            foreach ($payload['recurring'] ?? [] as $row) {
                $created = $this->upsertRecurring($company, $owner, $row);
                $stats[$created ? 'recurring_created' : 'recurring_updated']++;
            }

            return $company;
        });

        $this->info(json_encode([
            'ok' => true,
            'company_id' => $company->id,
            'company_name' => $company->name,
            'cui' => $company->cui,
            'stats' => $stats,
            'notes' => $payload['notes'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function syncBanks(Company $company, array $banks): int
    {
        if ($banks === []) {
            return 0;
        }

        CompanyBank::query()->where('company_id', $company->id)->delete();

        $count = 0;
        $order = 0;
        foreach ($banks as $row) {
            $iban = strtoupper(preg_replace('/\s+/', '', (string) ($row['iban'] ?? '')) ?: '');
            if ($iban === '') {
                continue;
            }
            $bank = CompanyBank::create([
                'company_id' => $company->id,
                'name' => $row['bank_name'] ?: 'Bancă',
                'sort_order' => $order,
            ]);
            CompanyBankAccount::create([
                'company_bank_id' => $bank->id,
                'iban' => $iban,
                'currency' => strtoupper((string) ($row['currency'] ?? 'RON')) ?: 'RON',
                'show_on_invoice' => $order === 0,
                'sort_order' => 0,
            ]);
            $order++;
            $count++;
        }

        return $count;
    }

    private function syncSeries(Company $company, array $series): int
    {
        $year = (int) now()->format('Y');
        $count = 0;

        foreach ($series as $row) {
            $type = (string) ($row['type'] ?? '');
            $prefix = (string) ($row['prefix'] ?? '');
            if ($type === '' || $prefix === '') {
                continue;
            }

            $model = DocumentSeries::query()->firstOrNew([
                'company_id' => $company->id,
                'type' => $type,
                'prefix' => $prefix,
                'year' => $year,
            ]);

            $model->fill([
                'description' => $row['description'] ?? null,
                'first_number' => (int) ($row['first_number'] ?? 1),
                'next_number' => (int) ($row['next_number'] ?? 1),
                'active' => (bool) ($row['active'] ?? true),
                'is_default' => (bool) ($row['is_default'] ?? false),
            ]);
            $model->save();
            $count++;

            if ($model->is_default) {
                DocumentSeries::query()
                    ->where('company_id', $company->id)
                    ->where('type', $type)
                    ->where('year', $year)
                    ->where('id', '!=', $model->id)
                    ->update(['is_default' => false]);
            }
        }

        return $count;
    }

    private function upsertClient(Company $company, array $row): bool
    {
        $name = trim((string) ($row['name'] ?? ''));
        $cuiDigits = preg_replace('/\D+/', '', (string) ($row['cui'] ?? '')) ?: null;
        $cnp = $row['cnp'] ?? null;
        $type = ($row['type'] ?? 'company') === 'person' ? 'person' : 'company';

        $query = Client::query()->where('company_id', $company->id);
        $existing = null;
        if ($cuiDigits) {
            $existing = (clone $query)
                ->whereRaw("REPLACE(REPLACE(UPPER(COALESCE(cui,'')),'RO',''),' ','') = ?", [$cuiDigits])
                ->first();
        }
        if (! $existing && $cnp) {
            $existing = (clone $query)->where('cnp', $cnp)->first();
        }
        if (! $existing) {
            $existing = (clone $query)->where('name', $name)->first();
        }

        $attrs = [
            'name' => $name,
            'type' => $type,
            'cui' => $type === 'company' && $cuiDigits ? $cuiDigits : null,
            'cnp' => $type === 'person' ? ($cnp ?: $cuiDigits) : null,
            'reg_com' => $row['reg_com'] ?? null,
            'address' => $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'county' => dc_normalize_county($row['county'] ?? null) ?: ($row['county'] ?? null),
            'country' => $row['country'] ?? 'România',
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'iban' => $row['iban'] ?? null,
            'bank_name' => $row['bank_name'] ?? null,
            'opening_balance' => (float) ($row['opening_balance'] ?? 0),
            'opening_balance_date' => $row['opening_balance_date'] ?? now()->toDateString(),
        ];

        if ($existing) {
            $existing->fill($attrs)->save();

            return false;
        }

        Client::create(array_merge($attrs, ['company_id' => $company->id]));

        return true;
    }

    private function upsertProduct(Company $company, array $row): bool
    {
        $name = trim((string) ($row['name'] ?? ''));
        $sku = filled($row['sku'] ?? null) ? trim((string) $row['sku']) : null;
        $existing = Product::query()
            ->where('company_id', $company->id)
            ->where('name', $name)
            ->when($sku, fn ($q) => $q->where('sku', $sku))
            ->first();

        $vat = $this->parseVat($row['vat_rate'] ?? 21);
        $unit = MeasureUnits::canonicalName($row['unit'] ?? 'buc');
        $description = filled($row['description'] ?? null) ? (string) $row['description'] : null;
        if (mb_strlen($name) > 255) {
            $description = trim(($description ? $description.' · ' : '').$name);
            $name = mb_substr($name, 0, 252).'…';
        }
        if (is_string($sku) && mb_strlen($sku) > 64) {
            $description = trim(($description ? $description.' · ' : '').'SKU: '.$sku);
            $sku = mb_substr($sku, 0, 64);
        }

        $attrs = [
            'name' => $name,
            'sku' => $sku,
            'unit' => $unit,
            'type' => ($row['type'] ?? 'service') === 'product' ? 'product' : 'service',
            'price' => (float) ($row['price'] ?? 0),
            'vat_rate' => $vat,
            'description' => $description,
            'active' => (bool) ($row['active'] ?? true),
        ];

        if ($existing) {
            $existing->fill($attrs)->save();

            return false;
        }

        Product::create(array_merge($attrs, ['company_id' => $company->id]));

        return true;
    }

    private function upsertRecurring(Company $company, User $owner, array $row): bool
    {
        $clientCui = preg_replace('/\D+/', '', (string) ($row['client_cui'] ?? '')) ?: null;
        $client = null;
        if ($clientCui) {
            $client = Client::query()
                ->where('company_id', $company->id)
                ->whereRaw("REPLACE(REPLACE(UPPER(COALESCE(cui,'')),'RO',''),' ','') = ?", [$clientCui])
                ->first();
        }
        if (! $client) {
            $client = Client::query()
                ->where('company_id', $company->id)
                ->where('name', $row['client_name'] ?? '')
                ->first();
        }
        if (! $client) {
            $clientName = trim((string) ($row['client_name'] ?? ''));
            if ($clientName === '') {
                throw new \RuntimeException('Recurring client missing: '.($row['client_name'] ?? '?'));
            }
            $client = Client::create([
                'company_id' => $company->id,
                'name' => $clientName,
                'type' => 'company',
                'cui' => $clientCui,
                'country' => 'România',
                'opening_balance' => 0,
            ]);
        }

        $subNo = (string) ($row['subscription_number'] ?? '0001');
        $existing = RecurringInvoice::query()
            ->where('company_id', $company->id)
            ->where('subscription_number', $subNo)
            ->first();

        $attrs = [
            'client_id' => $client->id,
            'created_by' => $owner->id,
            'title' => $row['title'] ?? null,
            'subscription_number' => $subNo,
            'frequency' => $row['frequency'] ?? 'monthly',
            'start_date' => $row['start_date'] ?? now()->toDateString(),
            'next_run_date' => $row['next_run_date'] ?? now()->toDateString(),
            'due_days' => (int) ($row['due_days'] ?? 7),
            'currency' => $row['currency'] ?? 'RON',
            'series' => $row['series'] ?? null,
            'document_type' => ($row['document_type'] ?? 'invoice') === 'proforma' ? 'proforma' : 'invoice',
            'document_language' => $row['document_language'] ?? 'ro',
            'notes' => $row['notes'] ?? null,
            'auto_email_client' => (bool) ($row['auto_email_client'] ?? true),
            'auto_email_cc' => (bool) ($row['auto_email_cc'] ?? false),
            'auto_email_cc_address' => $row['auto_email_cc_address'] ?? null,
            'auto_issue' => (bool) ($row['auto_issue'] ?? false),
            'active' => (bool) ($row['active'] ?? true),
            'prepared_by' => $row['prepared_by'] ?? null,
            'prepared_by_cnp' => $row['prepared_by_cnp'] ?? null,
            'delegate_name' => $row['delegate_name'] ?? null,
        ];

        if ($existing) {
            $existing->fill($attrs)->save();
            $existing->items()->delete();
            $recurring = $existing;
            $created = false;
        } else {
            $recurring = RecurringInvoice::create(array_merge($attrs, [
                'company_id' => $company->id,
            ]));
            $created = true;
        }

        $pos = 0;
        foreach ($row['items'] ?? [] as $item) {
            RecurringInvoiceItem::create([
                'recurring_invoice_id' => $recurring->id,
                'position' => $pos++,
                'name' => $item['name'],
                'unit' => MeasureUnits::canonicalName($item['unit'] ?? 'buc'),
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'vat_rate' => $this->parseVat($item['vat_rate'] ?? 21),
            ]);
        }

        return $created;
    }

    private function parseVat(mixed $vat): float
    {
        if (is_numeric($vat)) {
            return (float) $vat;
        }
        $s = (string) $vat;
        if (preg_match('/(\d+(?:[.,]\d+)?)/', $s, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        return 21.0;
    }
}
