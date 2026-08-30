<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\User;
use App\Support\DocumentFooterFields;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RecurringInvoiceService
{
    public function __construct(
        private DocumentService $documents,
        private DocumentTemplateVariables $variables,
    ) {}

    public function save(Company $company, User $user, array $data, array $items, ?RecurringInvoice $existing = null): RecurringInvoice
    {
        $items = $this->normalizeItems($items, $company->id);
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Adaugă cel puțin o linie de produs/serviciu.',
            ]);
        }

        abort_unless(
            $company->clients()->where('id', $data['client_id'])->exists(),
            403
        );

        return DB::transaction(function () use ($company, $user, $data, $items, $existing) {
            $this->ensureNextRunDateNullable();

            $payload = array_merge([
                'company_id' => $company->id,
                'client_id' => $data['client_id'],
                'title' => $data['title'] ?? null,
                'subscription_number' => $data['subscription_number'] ?? null,
                'frequency' => $data['frequency'],
                'start_date' => $data['start_date'],
                'next_run_date' => array_key_exists('next_run_date', $data)
                    ? $data['next_run_date']
                    : ($data['start_date'] ?? null),
                'end_date' => $data['end_date'] ?? null,
                'due_days' => (int) ($data['due_days'] ?? 15),
                'payment_term' => $data['payment_term'] ?? null,
                'currency' => strtoupper($data['currency'] ?? 'RON'),
                'series' => $data['series'] ?? null,
                'document_type' => in_array(($data['document_type'] ?? 'invoice'), ['invoice', 'proforma'], true)
                    ? $data['document_type']
                    : 'invoice',
                'document_language' => $data['document_language'] ?? 'ro',
                'max_documents' => array_key_exists('max_documents', $data) ? $data['max_documents'] : null,
                'auto_issue' => (bool) ($data['auto_issue'] ?? true),
                'active' => (bool) ($data['active'] ?? true),
            ], DocumentFooterFields::persistable($data));

            if (! ($payload['active'] ?? false)) {
                $payload['next_run_date'] = null;
            } elseif (blank($payload['next_run_date'])) {
                $payload['next_run_date'] = $payload['start_date'];
            }

            if ($existing) {
                $existing->update($payload);
                $recurring = $existing;
                $recurring->items()->delete();
            } else {
                $payload['created_by'] = $user->id;
                $recurring = RecurringInvoice::create($payload);
            }

            foreach ($items as $index => $row) {
                RecurringInvoiceItem::create([
                    'recurring_invoice_id' => $recurring->id,
                    'product_id' => $row['product_id'] ?? null,
                    'position' => $index,
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'unit' => app(\App\Services\MeasureUnitService::class)->ensure($company, $row['unit'] ?? null),
                    'quantity' => round((float) $row['quantity'], 2),
                    'unit_price' => round((float) $row['unit_price'], 2),
                    'vat_rate' => round((float) $row['vat_rate'], 2),
                ]);
            }

            return $recurring->fresh(['items', 'client']);
        });
    }

    public function generate(RecurringInvoice $recurring, ?User $actor = null): ?Document
    {
        $recurring->loadMissing(['items', 'client', 'company']);

        if ($recurring->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Abonamentul nu are linii de facturat.',
            ]);
        }

        if ($recurring->end_date && $recurring->next_run_date && $recurring->next_run_date->gt($recurring->end_date)) {
            $recurring->update(['active' => false, 'next_run_date' => null]);

            return null;
        }

        if ($recurring->reachedDocumentLimit()) {
            $recurring->update(['active' => false, 'next_run_date' => null]);

            return null;
        }

        if (! $recurring->next_run_date) {
            throw ValidationException::withMessages([
                'next_run_date' => 'Abonamentul nu are dată pentru următoarea emitere. Reactivează-l și completează data.',
            ]);
        }

        $issueDate = $recurring->next_run_date->copy();
        $dueDate = $issueDate->copy()->addDays(max(0, (int) $recurring->due_days));
        $user = $actor ?: $recurring->creator ?: $recurring->company->owner;

        $items = $recurring->items->map(fn (RecurringInvoiceItem $item) => [
            'product_id' => $item->product_id,
            'name' => $this->variables->expand($item->name, $issueDate),
            'description' => $this->variables->expand($item->description, $issueDate),
            'unit' => $item->unit,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'vat_rate' => (float) $item->vat_rate,
        ])->all();

        $docType = $recurring->documentType();
        $notes = $this->variables->expand($recurring->notes, $issueDate);
        if (filled($recurring->subscription_number) && blank($notes)) {
            $label = $docType === 'proforma' ? 'Proformă recurentă' : 'Factură recurentă';
            $notes = $label.' emisă pentru abonamentul nr. '.$recurring->subscription_number;
        }

        $document = $this->documents->createDraft(
            $recurring->company,
            $user,
            $docType,
            array_merge([
                'client_id' => $recurring->client_id,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'payment_term' => $recurring->payment_term,
                'currency' => $recurring->currency,
                'series' => $recurring->series,
                'document_language' => $recurring->document_language ?: 'ro',
            ], DocumentFooterFields::persistable([
                'notes' => $notes,
                'allow_card_payment' => $recurring->allow_card_payment,
                'contract_number' => $recurring->contract_number,
                'despatch_advice' => $recurring->despatch_advice,
                'prepared_by' => $recurring->prepared_by,
                'prepared_by_cnp' => $recurring->prepared_by_cnp,
                'delegate_name' => $recurring->delegate_name,
                'delegate_id_card' => $recurring->delegate_id_card,
                'vehicle_reg' => $recurring->vehicle_reg,
                'auto_email_client' => $recurring->auto_email_client,
                'auto_email_cc' => $recurring->auto_email_cc,
                'auto_email_cc_address' => $recurring->auto_email_cc_address,
            ])),
            $items
        );

        $document->update(['recurring_invoice_id' => $recurring->id]);

        if ($recurring->auto_issue) {
            $document = $this->documents->issueAndMaybeSendEfactura($document->fresh());
        }

        $recurring->forceFill([
            'last_generated_at' => now(),
            'last_document_id' => $document->id,
            'generated_count' => $recurring->generated_count + 1,
        ])->save();

        $recurring->advanceNextRunDate();

        if ($recurring->fresh()->reachedDocumentLimit()) {
            $recurring->update(['active' => false, 'next_run_date' => null]);
        }

        return $document->fresh(['items', 'client', 'company']);
    }

    /**
     * Construiește un draft temporar (în tranzacție) pentru preview PDF — include penalități dacă toggle ON.
     * Apelantul trebuie să facă rollback după generarea PDF-ului.
     */
    public function buildPreviewDraft(RecurringInvoice $recurring, ?User $actor = null): Document
    {
        $recurring->loadMissing(['items', 'client', 'company']);

        if ($recurring->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Abonamentul nu are linii de facturat.',
            ]);
        }

        $issueDate = $recurring->next_run_date
            ? $recurring->next_run_date->copy()
            : now('Europe/Bucharest')->startOfDay();
        $dueDate = $issueDate->copy()->addDays(max(0, (int) $recurring->due_days));
        $user = $actor ?: $recurring->creator ?: $recurring->company->owner;

        $items = $recurring->items->map(fn (RecurringInvoiceItem $item) => [
            'product_id' => $item->product_id,
            'name' => $this->variables->expand($item->name, $issueDate),
            'description' => $this->variables->expand($item->description, $issueDate),
            'unit' => $item->unit,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'vat_rate' => (float) $item->vat_rate,
        ])->all();

        $docType = $recurring->documentType();
        $notes = $this->variables->expand($recurring->notes, $issueDate);
        if (filled($recurring->subscription_number) && blank($notes)) {
            $label = $docType === 'proforma' ? 'Proformă recurentă' : 'Factură recurentă';
            $notes = $label.' emisă pentru abonamentul nr. '.$recurring->subscription_number;
        }

        $document = $this->documents->createDraft(
            $recurring->company,
            $user,
            $docType,
            array_merge([
                'client_id' => $recurring->client_id,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'payment_term' => $recurring->payment_term,
                'currency' => $recurring->currency,
                'series' => $recurring->series,
                'document_language' => $recurring->document_language ?: 'ro',
            ], DocumentFooterFields::persistable([
                'notes' => trim(($notes ? $notes."\n" : '').'PREVIEW — document temporar, nu se salvează'),
                'allow_card_payment' => $recurring->allow_card_payment,
                'contract_number' => $recurring->contract_number,
                'despatch_advice' => $recurring->despatch_advice,
                'prepared_by' => $recurring->prepared_by,
                'prepared_by_cnp' => $recurring->prepared_by_cnp,
                'delegate_name' => $recurring->delegate_name,
                'delegate_id_card' => $recurring->delegate_id_card,
                'vehicle_reg' => $recurring->vehicle_reg,
                'auto_email_client' => false,
                'auto_email_cc' => false,
                'auto_email_cc_address' => null,
            ])),
            $items
        );

        $document->update(['recurring_invoice_id' => $recurring->id]);

        return $document->fresh(['items', 'client', 'company']);
    }

    public function processDue(int $limit = 100, ?Company $company = null): int
    {
        $count = 0;
        $maxDocuments = 5000;

        do {
            $query = RecurringInvoice::query()
                ->with(['items', 'client', 'company', 'creator'])
                ->where('active', true)
                ->whereDate('next_run_date', '<=', now()->toDateString())
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhereColumn('next_run_date', '<=', 'end_date');
                })
                ->orderBy('next_run_date')
                ->limit($limit);

            if ($company) {
                $query->where('company_id', $company->id);
            }

            $batch = $query->get();
            if ($batch->isEmpty()) {
                break;
            }

            $batchGenerated = 0;
            foreach ($batch as $recurring) {
                try {
                    // Catch up missed periods (max 12 iterations)
                    $loops = 0;
                    while ($recurring->fresh()->isDue() && $loops < 12) {
                        $this->generate($recurring->fresh());
                        $count++;
                        $batchGenerated++;
                        $loops++;
                        $recurring = $recurring->fresh();
                        if (! $recurring || ! $recurring->active) {
                            break;
                        }
                        if ($count >= $maxDocuments) {
                            break 3;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Recurring invoice generation failed', [
                        'id' => $recurring->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } while ($batchGenerated > 0 && $count < $maxDocuments);

        return $count;
    }

    private function normalizeItems(array $items, ?int $companyId = null): array
    {
        $normalized = [];
        foreach (array_values($items) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $unit = $row['unit'] ?? null;
            if ($companyId) {
                $company = Company::query()->find($companyId);
                if ($company) {
                    $unit = app(MeasureUnitService::class)->ensure($company, $unit);
                } else {
                    $unit = \App\Support\MeasureUnits::canonicalName($unit);
                }
            } else {
                $unit = \App\Support\MeasureUnits::canonicalName($unit);
            }
            $description = filled($row['description'] ?? null) ? trim((string) $row['description']) : null;
            $qty = round((float) ($row['quantity'] ?? 1), 2);
            $price = round((float) ($row['unit_price'] ?? 0), 2);
            $vatRate = round((float) ($row['vat_rate'] ?? 21), 2);
            $productId = $row['product_id'] ?? null;

            if ($companyId) {
                if ($productId) {
                    $exists = Product::query()
                        ->where('company_id', $companyId)
                        ->where('id', $productId)
                        ->exists();
                    if (! $exists) {
                        $productId = null;
                    }
                }

                if (! $productId) {
                    $product = Product::query()
                        ->where('company_id', $companyId)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])
                        ->first();

                    if (! $product) {
                        $product = Product::create([
                            'company_id' => $companyId,
                            'name' => $name,
                            'unit' => $unit,
                            'price' => $price,
                            'vat_rate' => $vatRate,
                            'description' => $description,
                            'type' => 'service',
                            'active' => true,
                        ]);
                    } else {
                        $product->update([
                            'unit' => $unit,
                            'price' => $price,
                            'vat_rate' => $vatRate,
                            'description' => $description ?: $product->description,
                            'active' => true,
                        ]);
                    }
                    $productId = $product->id;
                }
            }

            $normalized[] = [
                'product_id' => $productId,
                'name' => $name,
                'description' => $description,
                'unit' => $unit,
                'quantity' => $qty,
                'unit_price' => $price,
                'vat_rate' => $vatRate,
            ];
        }

        return $normalized;
    }

    /** Asigură next_run_date nullable (abonament inactiv fără dată de emitere). */
    private function ensureNextRunDateNullable(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        try {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $done = true;

                return;
            }

            if (! Schema::hasTable('recurring_invoices')) {
                return;
            }

            $col = DB::selectOne(
                "SELECT IS_NULLABLE AS is_nullable FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'recurring_invoices' AND COLUMN_NAME = 'next_run_date'"
            );
            if ($col && strtoupper((string) $col->is_nullable) === 'NO') {
                DB::statement('ALTER TABLE `recurring_invoices` MODIFY `next_run_date` DATE NULL');
            }

            $done = true;
        } catch (\Throwable $e) {
            Log::warning('Could not make recurring_invoices.next_run_date nullable', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
