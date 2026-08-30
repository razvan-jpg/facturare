<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Models\RecurringInvoice;
use App\Services\RecurringInvoiceService;
use App\Support\DocumentFooterFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class RecurringInvoiceController extends Controller
{
    use ResolvesApiCompany;

    public function index(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'recurring_view');
        $items = $company->recurringInvoices()->with(['client', 'items'])->orderByDesc('id')->get();

        return response()->json([
            'data' => $items->map(fn (RecurringInvoice $r) => $this->serialize($r))->values(),
        ]);
    }

    public function store(Request $request, RecurringInvoiceService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'recurring_manage');
        $data = $this->validated($request, $company);
        $recurring = $service->save($company, $request->user(), $data, $data['items']);

        return response()->json(['data' => $this->serialize($recurring->fresh(['items', 'client']))], 201);
    }

    public function show(Request $request, RecurringInvoice $recurring): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'recurring_view');
        abort_unless($recurring->company_id === $company->id, 404);

        return response()->json(['data' => $this->serialize($recurring->load(['items', 'client']))]);
    }

    public function update(Request $request, RecurringInvoice $recurring, RecurringInvoiceService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'recurring_manage');
        abort_unless($recurring->company_id === $company->id, 404);
        $data = $this->validated($request, $company);
        $service->save($company, $request->user(), $data, $data['items'], $recurring);

        return response()->json(['data' => $this->serialize($recurring->fresh(['items', 'client']))]);
    }

    public function destroy(Request $request, RecurringInvoice $recurring): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'recurring_manage');
        abort_unless($recurring->company_id === $company->id, 404);
        $id = $recurring->id;
        $recurring->delete();

        return response()->json(['deleted_id' => $id]);
    }

    public function toggle(Request $request, RecurringInvoice $recurring): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'recurring_manage');
        abort_unless($recurring->company_id === $company->id, 404);

        $willActivate = ! $recurring->active;
        if ($willActivate) {
            $next = $recurring->next_run_date
                ?: ($recurring->start_date?->copy() ?: now('Europe/Bucharest')->startOfDay());
            if ($next->lt(now('Europe/Bucharest')->startOfDay())) {
                $next = now('Europe/Bucharest')->startOfDay();
            }
            $recurring->update([
                'active' => true,
                'next_run_date' => $next->toDateString(),
            ]);
        } else {
            $recurring->update([
                'active' => false,
                'next_run_date' => null,
            ]);
        }

        return response()->json(['data' => $this->serialize($recurring->fresh(['items', 'client']))]);
    }

    public function generateNow(Request $request, RecurringInvoice $recurring, RecurringInvoiceService $service): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'recurring_manage');
        abort_unless($recurring->company_id === $company->id, 404);

        try {
            $doc = $service->generate($recurring->fresh(['items', 'client', 'company']), $request->user());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $doc) {
            return response()->json(['message' => 'Abonamentul a expirat și a fost dezactivat.'], 422);
        }

        return response()->json([
            'document_id' => $doc->id,
            'data' => $this->serialize($recurring->fresh(['items', 'client'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, $company): array
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'subscription_number' => ['nullable', 'string', 'max:40'],
            'frequency' => ['required', Rule::in(array_keys(RecurringInvoice::FREQUENCIES))],
            'start_date' => ['required', 'date'],
            'next_run_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'due_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'payment_term' => ['nullable', 'string', Rule::in(array_keys(config('payment_terms', [])))],
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys(config('currencies', ['RON' => 'RON'])))],
            'document_type' => ['nullable', Rule::in(array_keys(RecurringInvoice::DOCUMENT_TYPES))],
            'series' => ['nullable', 'string', 'max:20'],
            'document_language' => ['nullable', 'string', Rule::in(array_keys(config('document_languages', ['ro' => 'Română'])))],
            'max_documents' => ['nullable', 'integer', 'min:-1', 'max:9999'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['nullable', 'numeric'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.product_id' => ['nullable', 'integer'],
            ...DocumentFooterFields::rules(),
        ]);

        abort_unless($company->clients()->where('id', $data['client_id'])->exists(), 403);
        $data = DocumentFooterFields::fromRequest($request, $data, $company);
        $data['document_type'] = $data['document_type'] ?? 'invoice';
        if ($request->has('active')) {
            $data['active'] = $request->boolean('active');
        }
        if ($request->has('auto_issue')) {
            $data['auto_issue'] = $request->boolean('auto_issue');
        }
        if (array_key_exists('active', $data) && ! $data['active']) {
            $data['next_run_date'] = null;
        } elseif (blank($data['next_run_date'] ?? null)) {
            $data['next_run_date'] = $data['start_date'];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(RecurringInvoice $r): array
    {
        $r->loadMissing(['items', 'client']);

        return [
            'id' => $r->id,
            'company_id' => $r->company_id,
            'client_id' => $r->client_id,
            'client_name' => $r->client?->name,
            'title' => $r->title,
            'subscription_number' => $r->subscription_number,
            'frequency' => $r->frequency,
            'start_date' => optional($r->start_date)?->toDateString(),
            'next_run_date' => optional($r->next_run_date)?->toDateString(),
            'end_date' => optional($r->end_date)?->toDateString(),
            'due_days' => $r->due_days,
            'currency' => $r->currency,
            'document_type' => $r->documentType(),
            'series' => $r->series,
            'active' => (bool) $r->active,
            'max_documents' => $r->max_documents,
            'updated_at' => optional($r->updated_at)?->toIso8601String(),
            'items' => $r->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'name' => $i->name,
                'unit' => $i->unit,
                'quantity' => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'vat_rate' => (float) $i->vat_rate,
            ])->values(),
        ];
    }
}
