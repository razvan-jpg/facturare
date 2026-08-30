<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Services\CompanyPermission;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class SyncController extends Controller
{
    use ResolvesApiCompany;

    public function pull(Request $request, CompanyPermission $permissions): JsonResponse
    {
        $company = $this->apiCompany($request);
        $permissions->authorize($request->user(), $company, 'access');

        $since = $request->query('since');
        // iOS/URL: „+” din timezone (…+03:00) ajunge ca spațiu (… 03:00) dacă nu e %2B.
        $sinceAt = $this->parseSince($since);
        $afterDocumentId = max(0, (int) $request->query('after_document_id', 0));
        $afterPaymentId = max(0, (int) $request->query('after_payment_id', 0));
        $pageLimit = 500;

        // Prima pagină: entități mici + documente/plăți.
        // Continuare: doar fluxul cerut (after_document_id SAU after_payment_id).
        $isFirstPage = $afterDocumentId === 0 && $afterPaymentId === 0;
        $fetchDocuments = $isFirstPage || $afterDocumentId > 0;
        $fetchPayments = $isFirstPage || $afterPaymentId > 0;

        $clients = $isFirstPage
            ? $this->scoped($company->clients(), $sinceAt)->orderBy('id')->get()
            : collect();
        $products = $isFirstPage
            ? $this->scoped($company->products(), $sinceAt)->orderBy('id')->get()
            : collect();
        $series = $isFirstPage
            ? $this->scoped($company->series(), $sinceAt)->orderBy('id')->get()
            : collect();
        $recurring = $isFirstPage
            ? $this->scoped($company->recurringInvoices()->with('items'), $sinceAt)->orderBy('id')->get()
            : collect();

        $documents = $fetchDocuments
            ? $this->scoped($company->documents()->with('items'), $sinceAt)
                ->when($afterDocumentId > 0, fn ($q) => $q->where('id', '>', $afterDocumentId))
                ->orderBy('id')
                ->limit($pageLimit)
                ->get()
            : collect();
        $payments = $fetchPayments
            ? $this->scoped($company->payments(), $sinceAt)
                ->when($afterPaymentId > 0, fn ($q) => $q->where('id', '>', $afterPaymentId))
                ->orderBy('id')
                ->limit($pageLimit)
                ->get()
            : collect();

        return response()->json([
            'server_time' => now()->utc()->toIso8601ZuluString(),
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'cui' => $company->cui,
                'updated_at' => optional($company->updated_at)?->toIso8601String(),
            ],
            'clients' => $clients->map(fn (Client $c) => $this->clientArray($c))->values(),
            'products' => $products->map(fn (Product $p) => $this->productArray($p))->values(),
            'documents' => $documents->map(fn (Document $d) => $this->documentArray($d))->values(),
            'payments' => $payments->map(fn (Payment $p) => $this->paymentArray($p))->values(),
            'has_more_documents' => $documents->count() >= $pageLimit,
            'has_more_payments' => $payments->count() >= $pageLimit,
            'series' => $series->map(fn ($s) => [
                'id' => $s->id,
                'type' => $s->type,
                'prefix' => $s->prefix,
                'first_number' => (int) ($s->first_number ?? 1),
                'next_number' => $s->next_number,
                'year' => $s->year,
                'active' => (bool) $s->active,
                'is_default' => (bool) $s->is_default,
                'updated_at' => optional($s->updated_at)?->toIso8601String(),
            ])->values(),
            'recurring' => $recurring->map(fn (RecurringInvoice $r) => $this->recurringArray($r))->values(),
        ]);
    }

    public function push(Request $request, DocumentService $documents, CompanyPermission $permissions): JsonResponse
    {
        $company = $this->apiCompany($request);
        $permissions->authorize($request->user(), $company, 'access');

        $payload = $request->validate([
            'operations' => ['required', 'array', 'max:100'],
            'operations.*.op_id' => ['required', 'string', 'max:64'],
            'operations.*.entity' => ['required', 'in:client,product,document,payment'],
            'operations.*.action' => ['required', 'in:create,update,delete,issue'],
            'operations.*.client_uuid' => ['nullable', 'uuid'],
            'operations.*.server_id' => ['nullable', 'integer'],
            'operations.*.payload' => ['nullable', 'array'],
        ]);

        $results = [];
        foreach ($payload['operations'] as $op) {
            $results[] = $this->processOperation($request, $company, $op, $documents, $permissions);
        }

        return response()->json([
            'server_time' => now()->utc()->toIso8601ZuluString(),
            'results' => $results,
        ]);
    }

    /**
     * @param  array<string, mixed>  $op
     * @return array<string, mixed>
     */
    private function processOperation(
        Request $request,
        $company,
        array $op,
        DocumentService $documents,
        CompanyPermission $permissions,
    ): array {
        $base = [
            'op_id' => $op['op_id'],
            'entity' => $op['entity'],
            'action' => $op['action'],
            'client_uuid' => $op['client_uuid'] ?? null,
        ];

        try {
            return match ($op['entity']) {
                'client' => array_merge($base, $this->syncClient($request, $company, $op, $permissions)),
                'product' => array_merge($base, $this->syncProduct($request, $company, $op, $permissions)),
                'document' => array_merge($base, $this->syncDocument($request, $company, $op, $documents, $permissions)),
                'payment' => array_merge($base, $this->syncPayment($request, $company, $op, $permissions)),
                default => array_merge($base, ['ok' => false, 'error' => 'Entitate necunoscută.']),
            };
        } catch (Throwable $e) {
            return array_merge($base, ['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $op
     * @return array<string, mixed>
     */
    private function syncClient(Request $request, $company, array $op, CompanyPermission $permissions): array
    {
        $permissions->authorize($request->user(), $company, 'clients_manage');
        $data = $op['payload'] ?? [];

        if ($op['action'] === 'delete') {
            $client = $company->clients()->whereKey($op['server_id'] ?? 0)->firstOrFail();
            $id = $client->id;
            $client->delete();

            return ['ok' => true, 'server_id' => $id, 'deleted' => true];
        }

        if ($op['action'] === 'create') {
            $client = $company->clients()->create([
                'name' => $data['name'] ?? 'Client',
                'type' => $data['type'] ?? 'company',
                'cui' => $data['cui'] ?? null,
                'reg_com' => $data['reg_com'] ?? null,
                'cnp' => $data['cnp'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'county' => $data['county'] ?? null,
                'country' => $data['country'] ?? 'România',
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'iban' => $data['iban'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'opening_balance_date' => $data['opening_balance_date'] ?? null,
            ]);

            return ['ok' => true, 'server_id' => $client->id, 'data' => $this->clientArray($client)];
        }

        $client = $company->clients()->whereKey($op['server_id'] ?? 0)->firstOrFail();
        $client->update(collect($data)->only([
            'name', 'type', 'cui', 'reg_com', 'cnp', 'address', 'city', 'county', 'country',
            'phone', 'email', 'iban', 'bank_name', 'notes', 'opening_balance', 'opening_balance_date',
            'admin_last_name', 'admin_first_name',
        ])->all());

        return ['ok' => true, 'server_id' => $client->id, 'data' => $this->clientArray($client->fresh())];
    }

    /**
     * @param  array<string, mixed>  $op
     * @return array<string, mixed>
     */
    private function syncProduct(Request $request, $company, array $op, CompanyPermission $permissions): array
    {
        $permissions->authorize($request->user(), $company, 'products_manage');
        $data = $op['payload'] ?? [];

        if ($op['action'] === 'delete') {
            $product = $company->products()->whereKey($op['server_id'] ?? 0)->firstOrFail();
            $id = $product->id;
            $product->delete();

            return ['ok' => true, 'server_id' => $id, 'deleted' => true];
        }

        if ($op['action'] === 'create') {
            $product = $company->products()->create([
                'name' => $data['name'] ?? 'Produs',
                'sku' => $data['sku'] ?? null,
                'unit' => $data['unit'] ?? 'buc',
                'type' => $data['type'] ?? 'service',
                'price' => round((float) ($data['price'] ?? 0), 2),
                'vat_rate' => round((float) ($data['vat_rate'] ?? 21), 2),
                'description' => $data['description'] ?? null,
                'active' => (bool) ($data['active'] ?? true),
            ]);

            return ['ok' => true, 'server_id' => $product->id, 'data' => $this->productArray($product)];
        }

        $product = $company->products()->whereKey($op['server_id'] ?? 0)->firstOrFail();
        $product->update(collect($data)->only([
            'name', 'sku', 'unit', 'type', 'price', 'vat_rate', 'description', 'active',
        ])->all());

        return ['ok' => true, 'server_id' => $product->id, 'data' => $this->productArray($product->fresh())];
    }

    /**
     * @param  array<string, mixed>  $op
     * @return array<string, mixed>
     */
    private function syncDocument(
        Request $request,
        $company,
        array $op,
        DocumentService $documents,
        CompanyPermission $permissions,
    ): array {
        $permissions->authorize($request->user(), $company, 'documents_manage');
        $data = $op['payload'] ?? [];

        if ($op['action'] === 'issue') {
            $document = $company->documents()->whereKey($op['server_id'] ?? 0)->firstOrFail();
            if (! $document->hasNumberReservation()) {
                $documents->reserveNumber($document, $data['series'] ?? $document->series);
                $document->refresh();
            }
            $document = $documents->issueAndMaybeSendEfactura($document);

            return ['ok' => true, 'server_id' => $document->id, 'data' => $this->documentArray($document->fresh('items'))];
        }

        if ($op['action'] === 'delete') {
            $document = $company->documents()->whereKey($op['server_id'] ?? 0)->firstOrFail();
            abort_unless($document->status === 'draft', 422, 'Doar ciornele pot fi șterse.');
            $id = $document->id;
            $documents->deleteDocument($document);

            return ['ok' => true, 'server_id' => $id, 'deleted' => true];
        }

        $items = $data['items'] ?? [];
        if ($op['action'] === 'create') {
            $document = $documents->createDraft(
                $company,
                $request->user(),
                $data['type'] ?? 'invoice',
                $data,
                $items
            );
            if (($data['action'] ?? 'draft') === 'issue') {
                $document = $documents->issueAndMaybeSendEfactura($document);
            }

            return ['ok' => true, 'server_id' => $document->id, 'data' => $this->documentArray($document->fresh('items'))];
        }

        $document = $company->documents()->whereKey($op['server_id'] ?? 0)->firstOrFail();
        abort_unless($document->status === 'draft', 422, 'Doar ciornele pot fi editate.');
        $document->fill(collect($data)->only([
            'client_id', 'issue_date', 'due_date', 'payment_term', 'currency', 'exchange_rate',
            'series', 'notes', 'document_language', 'prepared_by',
        ])->all());
        if (! empty($data['client_id'])) {
            $documents->syncClientSnapshot($document, $company->clients()->find($data['client_id']));
        }
        $document->save();
        if (is_array($items) && $items !== []) {
            $documents->replaceItems($document, $items);
        }
        if (($data['action'] ?? null) === 'issue') {
            $document = $documents->issueAndMaybeSendEfactura($document->fresh('items'));
        }

        return ['ok' => true, 'server_id' => $document->id, 'data' => $this->documentArray($document->fresh('items'))];
    }

    /**
     * @param  array<string, mixed>  $op
     * @return array<string, mixed>
     */
    private function syncPayment(Request $request, $company, array $op, CompanyPermission $permissions): array
    {
        $permissions->authorize($request->user(), $company, 'payments_manage');
        $data = $op['payload'] ?? [];

        if ($op['action'] !== 'create') {
            return ['ok' => false, 'error' => 'Doar crearea plăților este suportată în sync push.'];
        }

        $document = $company->documents()->whereKey($data['document_id'] ?? 0)->firstOrFail();
        $payment = Payment::create([
            'company_id' => $company->id,
            'document_id' => $document->id,
            'client_id' => $document->client_id,
            'method' => $data['method'] ?? 'op',
            'paid_at' => $data['paid_at'] ?? now()->toDateString(),
            'amount' => round((float) ($data['amount'] ?? 0), 2),
            'currency' => $document->currency,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $document->refreshPaymentStatus();

        return ['ok' => true, 'server_id' => $payment->id, 'data' => $this->paymentArray($payment)];
    }

    private function scoped($query, ?Carbon $sinceAt)
    {
        if ($sinceAt) {
            $query->where('updated_at', '>', $sinceAt);
        }

        return $query;
    }

    private function parseSince(mixed $since): ?Carbon
    {
        if (! is_string($since) || trim($since) === '') {
            return null;
        }

        $value = trim($since);
        // „2026-08-09T01:19:24 03:00” → „…+03:00” (plus pierdut în query string).
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}) (\d{2}:\d{2})$/', $value, $m)) {
            $value = $m[1].'+'.$m[2];
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            try {
                return Carbon::createFromFormat(DATE_ATOM, $value);
            } catch (Throwable) {
                return null;
            }
        }
    }

    /** @return array<string, mixed> */
    private function clientArray(Client $c): array
    {
        return [
            'id' => $c->id,
            'company_id' => $c->company_id,
            'name' => $c->name,
            'type' => $c->type,
            'cui' => $c->cui,
            'reg_com' => $c->reg_com,
            'cnp' => $c->cnp,
            'address' => $c->address,
            'city' => $c->city,
            'county' => $c->county,
            'country' => $c->country,
            'phone' => $c->phone,
            'email' => $c->email,
            'iban' => $c->iban,
            'bank_name' => $c->bank_name,
            'notes' => $c->notes,
            'opening_balance' => (float) $c->opening_balance,
            'opening_balance_date' => optional($c->opening_balance_date)?->toDateString(),
            'updated_at' => optional($c->updated_at)?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function productArray(Product $p): array
    {
        return [
            'id' => $p->id,
            'company_id' => $p->company_id,
            'name' => $p->name,
            'sku' => $p->sku,
            'unit' => $p->unit,
            'type' => $p->type,
            'price' => (float) $p->price,
            'vat_rate' => (float) $p->vat_rate,
            'description' => $p->description,
            'active' => (bool) $p->active,
            'updated_at' => optional($p->updated_at)?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function documentArray(Document $d): array
    {
        $d->loadMissing('items');

        return [
            'id' => $d->id,
            'company_id' => $d->company_id,
            'client_id' => $d->client_id,
            'type' => $d->type,
            'status' => $d->status,
            'series' => $d->series,
            'number' => $d->number,
            'number_full' => $d->number_full,
            'number_reserved_at' => optional($d->number_reserved_at)?->toIso8601String(),
            'issue_date' => optional($d->issue_date)?->toDateString(),
            'issue_year' => $d->issue_year,
            'due_date' => optional($d->due_date)?->toDateString(),
            'currency' => $d->currency,
            'subtotal' => (float) $d->subtotal,
            'vat_total' => (float) $d->vat_total,
            'total' => (float) $d->total,
            'paid_amount' => (float) $d->paid_amount,
            'payment_status' => $d->payment_status,
            'client_name' => $d->client_name,
            'notes' => $d->notes,
            'efactura_status' => $d->efactura_status,
            'efactura_error' => $d->efactura_error,
            'updated_at' => optional($d->updated_at)?->toIso8601String(),
            'items' => $d->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'name' => $i->name,
                'unit' => $i->unit,
                'quantity' => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'vat_rate' => (float) $i->vat_rate,
                'subtotal' => (float) $i->subtotal,
                'vat_amount' => (float) $i->vat_amount,
                'total' => (float) $i->total,
            ])->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function paymentArray(Payment $p): array
    {
        return [
            'id' => $p->id,
            'company_id' => $p->company_id,
            'document_id' => $p->document_id,
            'client_id' => $p->client_id,
            'method' => $p->method,
            'paid_at' => optional($p->paid_at)?->toDateString(),
            'amount' => (float) $p->amount,
            'currency' => $p->currency,
            'reference' => $p->reference,
            'updated_at' => optional($p->updated_at)?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function recurringArray(RecurringInvoice $r): array
    {
        $r->loadMissing('items');

        return [
            'id' => $r->id,
            'client_id' => $r->client_id,
            'title' => $r->title,
            'frequency' => $r->frequency,
            'start_date' => optional($r->start_date)?->toDateString(),
            'next_run_date' => optional($r->next_run_date)?->toDateString(),
            'end_date' => optional($r->end_date)?->toDateString(),
            'currency' => $r->currency,
            'document_type' => $r->documentType(),
            'series' => $r->series,
            'active' => (bool) $r->active,
            'updated_at' => optional($r->updated_at)?->toIso8601String(),
            'items' => $r->items->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'quantity' => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'vat_rate' => (float) $i->vat_rate,
            ])->values(),
        ];
    }
}
