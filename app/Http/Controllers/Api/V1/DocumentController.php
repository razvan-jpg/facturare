<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentService;
use App\Services\EfacturaService;
use App\Services\InvoicePdfService;
use App\Support\DocumentFooterFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentController extends Controller
{
    use ResolvesApiCompany;

    public function index(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_view');
        $query = $company->documents()->with(['items', 'client'])->latest('id');

        if ($type = $request->query('type')) {
            if ($type === 'storno') {
                $query->where('status', 'storno');
            } else {
                $query->where('type', $type)->where('status', '!=', 'storno');
            }
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($since = $request->query('since')) {
            $query->where('updated_at', '>', $since);
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('number_full', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 50)));
        $page = $query->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn (Document $d) => $this->serialize($d))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(Request $request, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        $data = $this->validated($request, $company);
        $document = $service->createDraft($company, $request->user(), $data['type'], $data, $data['items']);

        if (($data['action'] ?? 'draft') === 'issue') {
            $document = $service->issueAndMaybeSendEfactura($document);
        }

        return response()->json(['data' => $this->serialize($document->fresh(['items', 'client']))], 201);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_view');
        abort_unless($document->company_id === $company->id, 404);
        $document->load(['items', 'client', 'payments']);

        return response()->json(['data' => $this->serialize($document, true)]);
    }

    public function update(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);
        abort_unless($document->status === 'draft', 422, 'Doar ciornele pot fi editate.');

        $data = $this->validated($request, $company, true);
        $document->fill(collect($data)->except(['items', 'action', 'type'])->all());
        if (! empty($data['client_id'])) {
            $service->syncClientSnapshot($document, $company->clients()->find($data['client_id']));
        }
        $document->save();
        if (isset($data['items'])) {
            $service->replaceItems($document, $data['items']);
        }

        try {
            $document = $service->reserveNumber($document->fresh(), $data['series'] ?? $document->series);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (($data['action'] ?? null) === 'issue') {
            $document = $service->issueAndMaybeSendEfactura($document->fresh('items'));
        }

        return response()->json(['data' => $this->serialize($document->fresh(['items', 'client']))]);
    }

    public function issue(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);

        try {
            $document = $service->issueAndMaybeSendEfactura($document);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($document->fresh(['items', 'client']))]);
    }

    public function reserveNumber(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);
        abort_unless($document->status === 'draft', 422, 'Doar ciornele pot rezerva un număr.');

        $data = $request->validate([
            'series' => ['nullable', 'string', 'max:20'],
            'issue_date' => ['nullable', 'date'],
            'number' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! empty($data['issue_date'])) {
            $document->update(['issue_date' => $data['issue_date']]);
        }
        if (array_key_exists('series', $data) && filled($data['series'])) {
            $document->update(['series' => $data['series']]);
        }

        try {
            $document = $service->reserveNumber(
                $document->fresh(),
                $data['series'] ?? null,
                isset($data['number']) ? (int) $data['number'] : null
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $availability = $service->availableNumbers(
            $company,
            $document->type,
            (string) $document->series,
            (int) ($document->issue_year ?: $document->issue_date->format('Y')),
            $document->id
        );

        return response()->json([
            'data' => $this->serialize($document),
            'available_numbers' => $availability['available'],
            'gap_numbers' => $availability['gaps'],
            'next_number' => $availability['next'],
        ]);
    }

    public function availableNumbers(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);

        $data = $request->validate([
            'series' => ['nullable', 'string', 'max:20'],
            'issue_date' => ['nullable', 'date'],
        ]);

        $year = ! empty($data['issue_date'])
            ? (int) date('Y', strtotime($data['issue_date']))
            : (int) ($document->issue_year ?: $document->issue_date->format('Y'));
        $prefix = $data['series'] ?? $document->series;
        if (! filled($prefix)) {
            return response()->json(['available_numbers' => [], 'gap_numbers' => [], 'next_number' => 1]);
        }

        $availability = $service->availableNumbers($company, $document->type, (string) $prefix, $year, $document->id);

        return response()->json([
            'available_numbers' => $availability['available'],
            'gap_numbers' => $availability['gaps'],
            'next_number' => $availability['next'],
            'reserved_number' => $document->hasNumberReservation() ? (int) $document->number : null,
        ]);
    }

    public function releaseNumber(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);

        $service->releaseReservation($document);

        return response()->json(['data' => $this->serialize($document->fresh(['items', 'client']))]);
    }

    public function touchNumber(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);
        abort_unless($document->status === 'draft', 422, 'Doar ciornele pot prelungi rezervarea.');

        try {
            $document = $service->touchReservation($document);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($document)]);
    }

    public function cancel(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);
        abort_unless($document->status === 'draft' || $document->status === 'issued', 422, 'Documentul nu poate fi anulat.');

        try {
            $document = $service->cancelDocument($document);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($document->fresh(['items', 'client']))]);
    }

    public function destroy(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);
        abort_unless($document->status === 'draft', 422, 'Doar ciornele pot fi șterse.');

        $id = $document->id;
        $service->deleteDocument($document);

        return response()->json(['message' => 'Șters.', 'id' => $id]);
    }

    public function storno(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);

        try {
            $storno = $service->createStorno($document, $request->user());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($storno->fresh(['items', 'client']))], 201);
    }

    public function creditNote(Request $request, Document $document, DocumentService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'documents_manage');
        abort_unless($document->company_id === $company->id, 404);

        try {
            $note = $service->createCreditNote($document, $request->user());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($note->fresh(['items', 'client']))], 201);
    }

    public function pdf(Request $request, Document $document, InvoicePdfService $invoicePdf): Response
    {
        $company = $this->authorizeAbility($request, 'documents_view');
        abort_unless($document->company_id === $company->id, 404);
        $document->load(['items', 'client', 'company']);

        return $invoicePdf->make($document)
            ->download($document->pdfFileName())
            ->withHeaders([
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }

    public function sendEfactura(Request $request, Document $document, EfacturaService $efactura): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'efactura_manage');
        abort_unless($document->company_id === $company->id, 404);

        try {
            $document = $efactura->send($document);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($document->fresh(['items', 'client']))]);
    }

    public function refreshEfactura(Request $request, Document $document, EfacturaService $efactura): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'efactura_manage');
        abort_unless($document->company_id === $company->id, 404);

        try {
            $document = $efactura->refreshStatus($document);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($document->fresh(['items', 'client']))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, $company, bool $partial = false): array
    {
        $currencies = array_keys(config('currencies', ['RON' => 'RON']));
        $req = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'type' => [$req, 'in:invoice,proforma,delivery,receipt,credit_note'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'issue_date' => [$req, 'date'],
            'due_date' => ['nullable', 'date'],
            'payment_term' => ['nullable', 'string', Rule::in(array_keys(config('payment_terms', [])))],
            'currency' => [$req, 'string', 'size:3', Rule::in($currencies)],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'series' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'document_language' => ['nullable', 'string', Rule::in(array_keys(config('document_languages', ['ro' => 'Română'])))],
            'items' => [$partial ? 'sometimes' : 'required', 'array', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['nullable', 'numeric'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.details' => ['nullable', 'array'],
            'action' => ['nullable', 'in:draft,issue'],
            'client_uuid' => ['nullable', 'uuid'],
            ...DocumentFooterFields::rules(),
        ]);

        unset($data['client_uuid']);

        if (($data['currency'] ?? 'RON') === 'RON') {
            $data['exchange_rate'] = 1;
        } elseif (! empty($data['currency']) && empty($data['exchange_rate'])) {
            abort(response()->json(['message' => 'Completează cursul valutar.', 'errors' => ['exchange_rate' => ['Completează cursul valutar.']]], 422));
        } elseif (! empty($data['exchange_rate'])) {
            $data['exchange_rate'] = round((float) $data['exchange_rate'], 4);
        }

        $data = DocumentFooterFields::fromRequest($request, $data, $company);

        if (! empty($data['client_id'])) {
            abort_unless($company->clients()->where('id', $data['client_id'])->exists(), 403);
        }

        if (isset($data['items'])) {
            $data['items'] = array_values(array_filter($data['items'], function ($item) {
                $name = trim((string) ($item['name'] ?? ''));

                return $name !== '' || ! empty($item['product_id']);
            }));
            abort_if(count($data['items']) < 1 && ! $partial, 422, 'Adaugă cel puțin o linie.');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Document $document, bool $detailed = false): array
    {
        $payload = [
            'id' => $document->id,
            'company_id' => $document->company_id,
            'client_id' => $document->client_id,
            'type' => $document->type,
            'status' => $document->status,
            'series' => $document->series,
            'number' => $document->number,
            'number_full' => $document->number_full,
            'number_reserved_at' => optional($document->number_reserved_at)?->toIso8601String(),
            'issue_date' => optional($document->issue_date)?->toDateString(),
            'issue_year' => $document->issue_year,
            'due_date' => optional($document->due_date)?->toDateString(),
            'payment_term' => $document->payment_term,
            'currency' => $document->currency,
            'exchange_rate' => (float) $document->exchange_rate,
            'subtotal' => (float) $document->subtotal,
            'vat_total' => (float) $document->vat_total,
            'total' => (float) $document->total,
            'paid_amount' => (float) $document->paid_amount,
            'payment_status' => $document->payment_status,
            'notes' => $document->notes,
            'document_language' => $document->document_language,
            'client_name' => $document->client_name,
            'client_cui' => $document->client_cui,
            'client_reg_com' => $document->client_reg_com,
            'client_address' => $document->client_address,
            'client_email' => $document->client_email,
            'related_document_id' => $document->related_document_id,
            'efactura_status' => $document->efactura_status,
            'efactura_upload_id' => $document->efactura_upload_id,
            'efactura_error' => $document->efactura_error,
            'efactura_sent_at' => optional($document->efactura_sent_at)?->toIso8601String(),
            'efactura_scheduled_at' => optional($document->efactura_scheduled_at)?->toIso8601String(),
            'prepared_by' => $document->prepared_by,
            'updated_at' => optional($document->updated_at)?->toIso8601String(),
            'created_at' => optional($document->created_at)?->toIso8601String(),
            'items' => ($document->relationLoaded('items') ? $document->items : $document->items()->get())->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'vat_rate' => (float) $item->vat_rate,
                'subtotal' => (float) $item->subtotal,
                'vat_amount' => (float) $item->vat_amount,
                'total' => (float) $item->total,
                'position' => $item->position,
                'details' => $item->details,
            ])->values(),
        ];

        if ($detailed && $document->relationLoaded('payments')) {
            $payload['payments'] = $document->payments->map(fn ($p) => [
                'id' => $p->id,
                'method' => $p->method,
                'paid_at' => optional($p->paid_at)?->toDateString(),
                'amount' => (float) $p->amount,
                'currency' => $p->currency,
                'reference' => $p->reference,
            ])->values();
        }

        return $payload;
    }
}
