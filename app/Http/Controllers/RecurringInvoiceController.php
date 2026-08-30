<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCompanyPermission;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\RecurringInvoice;
use App\Services\ClientPenaltyService;
use App\Services\CompanyContext;
use App\Services\DocumentService;
use App\Services\InvoicePdfService;
use App\Services\RecurringInvoiceService;
use App\Support\DocumentFooterFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RecurringInvoiceController extends Controller
{
    use ChecksCompanyPermission;

    public function index(CompanyContext $context, DocumentService $documents): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'recurring_view');

        $recurring = $company->recurringInvoices()
            ->with(['client', 'lastDocument', 'items'])
            ->latest()
            ->paginate(25);

        $seriesNextByRecurringId = [];
        foreach ($recurring as $row) {
            $seriesNextByRecurringId[$row->id] = $this->seriesNextPreview($company, $row, $documents);
        }

        return view('recurring.index', compact('company', 'recurring', 'seriesNextByRecurringId'));
    }

    public function create(CompanyContext $context): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'recurring_manage');

        return view('recurring.create', $this->formPayload($company));
    }

    public function store(Request $request, CompanyContext $context, RecurringInvoiceService $service): RedirectResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'recurring_manage');
        try {
            $data = $this->validated($request, $company);
            $recurring = $service->save($company, $request->user(), $data, $data['items']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'active' => 'Nu am putut salva abonamentul: '.$e->getMessage(),
            ]);
        }

        return redirect()
            ->route('recurring.index')
            ->with('status', $recurring->active
                ? 'Factura recurentă a fost creată.'
                : 'Abonament salvat ca inactiv (fără emitere automată).');
    }

    public function show(Request $request, RecurringInvoice $recurring, CompanyContext $context, ClientPenaltyService $penalties): View
    {
        $this->authorizeRecurring($recurring, $context, 'recurring_view');
        $recurring->load(['items', 'client', 'lastDocument', 'documents' => fn ($q) => $q->latest()->limit(20)]);

        $pendingPenalties = 0.0;
        if ($recurring->client && $recurring->documentType() === 'invoice' && $penalties->isBillingEnabled($recurring->client)) {
            $pendingPenalties = (float) ($penalties->summaryForClient($recurring->client)['unbilled'] ?? 0);
        }

        return view('recurring.show', [
            'company' => $context->current(),
            'recurring' => $recurring,
            'returnPage' => $this->listPageFromRequest($request),
            'pendingPenalties' => $pendingPenalties,
        ]);
    }

    public function edit(Request $request, RecurringInvoice $recurring, CompanyContext $context): View
    {
        $this->authorizeRecurring($recurring, $context, 'recurring_manage');
        $company = $context->current();
        $recurring->load('items');

        return view('recurring.edit', array_merge($this->formPayload($company), [
            'recurring' => $recurring,
            'returnPage' => $this->listPageFromRequest($request),
        ]));
    }

    public function update(Request $request, RecurringInvoice $recurring, CompanyContext $context, RecurringInvoiceService $service): RedirectResponse
    {
        $this->authorizeRecurring($recurring, $context, 'recurring_manage');
        $company = $context->current();
        try {
            $data = $this->validated($request, $company);
            $service->save($company, $request->user(), $data, $data['items'], $recurring);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'active' => 'Nu am putut salva abonamentul: '.$e->getMessage(),
            ]);
        }

        return redirect()
            ->to($this->listUrl($request))
            ->with('status', ! empty($data['active'])
                ? 'Factura recurentă a fost actualizată.'
                : 'Abonament salvat ca inactiv (fără emitere automată).');
    }

    public function destroy(Request $request, RecurringInvoice $recurring, CompanyContext $context): RedirectResponse
    {
        $this->authorizeRecurring($recurring, $context, 'recurring_manage');
        $recurring->delete();

        return redirect()
            ->to($this->listUrl($request))
            ->with('status', 'Factura recurentă a fost ștearsă.');
    }

    public function toggle(RecurringInvoice $recurring, CompanyContext $context): RedirectResponse
    {
        $this->authorizeRecurring($recurring, $context, 'recurring_manage');
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

        return back()->with('status', $recurring->fresh()->active ? 'Abonament activat.' : 'Abonament dezactivat.');
    }

    public function generateNow(RecurringInvoice $recurring, CompanyContext $context, RecurringInvoiceService $service): RedirectResponse
    {
        $this->authorizeRecurring($recurring, $context, 'recurring_manage');

        try {
            $document = $service->generate($recurring->fresh(['items', 'client', 'company']), auth()->user());
            if (! $document) {
                return back()->with('status', 'Abonamentul a expirat și a fost dezactivat.');
            }

            $label = $document->type === 'proforma' ? 'Proformă' : 'Factură';

            return redirect()
                ->route('documents.show', $document)
                ->with('status', $label.' generată din abonament: '.($document->number_full ?: '#'.$document->id));
        } catch (\Throwable $e) {
            return back()->with('status', 'Nu am putut genera documentul: '.$e->getMessage());
        }
    }

    /**
     * Preview PDF al următorului document (include penalități dacă toggle ON) — fără salvare / fără avansare.
     */
    public function previewNext(
        RecurringInvoice $recurring,
        CompanyContext $context,
        RecurringInvoiceService $service,
        InvoicePdfService $invoicePdf,
    ): Response|RedirectResponse {
        $this->authorizeRecurring($recurring, $context, 'recurring_view');

        $pdfBinary = null;
        $fileName = 'preview-recurenta-'.$recurring->id.'.pdf';

        try {
            DB::beginTransaction();
            try {
                $document = $service->buildPreviewDraft(
                    $recurring->fresh(['items', 'client', 'company']),
                    auth()->user()
                );
                $pdfBinary = $invoicePdf->output($document->fresh(['items', 'client', 'company']));
                $typeLabel = $document->type === 'proforma' ? 'proforma' : 'factura';
                $fileName = 'preview-'.$typeLabel.'-abonament-'.($recurring->subscription_number ?: $recurring->id).'.pdf';
            } finally {
                DB::rollBack();
            }
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return back()->with('status', 'Preview eșuat: '.$e->getMessage());
        }

        if ($pdfBinary === null || $pdfBinary === '') {
            return back()->with('status', 'Preview eșuat: PDF gol.');
        }

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function validated(Request $request, Company $company): array
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
            'document_type' => ['required', Rule::in(array_keys(RecurringInvoice::DOCUMENT_TYPES))],
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

        $data = DocumentFooterFields::fromRequest($request, $data, $company);
        // Checkbox cu hidden 0: boolean pe "0"/"1".
        $data['auto_issue'] = $request->boolean('auto_issue');
        $data['active'] = $request->boolean('active');
        if ($data['active']) {
            $next = $data['next_run_date'] ?? null;
            $data['next_run_date'] = filled($next) ? $next : $data['start_date'];
        } else {
            $data['next_run_date'] = null;
        }
        $data['document_type'] = $data['document_type'] ?? 'invoice';

        $term = (string) ($data['payment_term'] ?? '');
        if (ctype_digit($term)) {
            $data['due_days'] = (int) $term;
        } elseif ($term === 'issue' || $term === 'none') {
            $data['due_days'] = 0;
        } else {
            $data['due_days'] = (int) ($data['due_days'] ?? 15);
        }

        $max = $data['max_documents'] ?? null;
        if ($max === '' || $max === null || (int) $max < 0) {
            $data['max_documents'] = null;
        } else {
            $data['max_documents'] = (int) $max;
        }

        $data['document_language'] = $data['document_language'] ?? 'ro';
        $data['subscription_number'] = filled($data['subscription_number'] ?? null)
            ? trim((string) $data['subscription_number'])
            : null;
        $data['series'] = filled($data['series'] ?? null) ? trim((string) $data['series']) : null;

        if (filled($data['series'])) {
            $seriesOk = $company->series()
                ->where('type', $data['document_type'])
                ->where('active', true)
                ->where('prefix', $data['series'])
                ->exists();
            if (! $seriesOk) {
                throw ValidationException::withMessages([
                    'series' => 'Alege o serie activă pentru tipul de document selectat.',
                ]);
            }
        }

        $data['items'] = $this->assertCompleteItems($data['items'] ?? []);

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function assertCompleteItems(array $items): array
    {
        $complete = [];
        $errors = [];

        foreach (array_values($items) as $index => $row) {
            $line = $index + 1;
            $name = trim((string) ($row['name'] ?? ''));
            $qty = $row['quantity'] ?? null;
            $price = $row['unit_price'] ?? null;
            $vat = $row['vat_rate'] ?? null;
            $productId = $row['product_id'] ?? null;

            $isEmpty = $name === ''
                && blank($productId)
                && ($price === null || $price === '' || (float) $price == 0.0);

            // Linii goale (rânduri rezervă din UI) — le ignorăm, nu blocăm salvarea.
            if ($isEmpty) {
                continue;
            }

            if (
                $name === ''
                || $qty === null || $qty === '' || (float) $qty == 0.0
                || $price === null || $price === ''
                || $vat === null || $vat === ''
            ) {
                $errors["items.$index.name"] = "Linia {$line}: produsul e obligatoriu (cantitate ≠ 0, preț, TVA). Descrierea e opțională.";
                continue;
            }

            $row['name'] = $name;
            $complete[] = $row;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($complete === []) {
            throw ValidationException::withMessages([
                'items' => 'Adaugă cel puțin o linie completă pe abonament.',
            ]);
        }

        return $complete;
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload($company): array
    {
        return [
            'company' => $company,
            'clients' => $company->clients()->orderBy('name')->get(),
            'products' => $company->products()->where('active', true)->orderBy('name')->get(),
            'seriesList' => $company->series()
                ->whereIn('type', ['invoice', 'proforma'])
                ->where('active', true)
                ->orderByDesc('year')
                ->orderByDesc('is_default')
                ->orderBy('prefix')
                ->get(),
            'currencies' => config('currencies'),
            'paymentTerms' => config('payment_terms'),
        ];
    }

    private function listPageFromRequest(Request $request): int
    {
        $page = (int) $request->input('return_page', $request->query('page', 1));

        return max(1, $page);
    }

    private function listUrl(Request $request): string
    {
        $page = $this->listPageFromRequest($request);

        return route('recurring.index', $page > 1 ? ['page' => $page] : []);
    }

    private function authorizeRecurring(RecurringInvoice $recurring, CompanyContext $context, string $ability = 'recurring_view'): void
    {
        abort_unless($recurring->company_id === $context->current()?->id, 403);
        $this->authorizeCompanyAbility($context->current(), $ability);
    }

    /**
     * Prefix + următorul număr care se rezervă la emitere (nu e blocat pe abonament).
     *
     * @return array{prefix: string, next_full: string|null}
     */
    private function seriesNextPreview(Company $company, RecurringInvoice $recurring, DocumentService $documents): array
    {
        $type = $recurring->documentType();
        $year = (int) (optional($recurring->next_run_date)->year ?: now('Europe/Bucharest')->year);
        $prefix = filled($recurring->series) ? (string) $recurring->series : null;

        if (! $prefix) {
            $default = DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $type)
                ->where('year', $year)
                ->where('active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
            $prefix = $default?->prefix;
        }

        if (! filled($prefix)) {
            return ['prefix' => 'implicită', 'next_full' => null];
        }

        $available = $documents->availableNumbers($company, $type, $prefix, $year);
        $next = (int) ($available['next'] ?? 0);

        return [
            'prefix' => $prefix,
            'next_full' => $next > 0
                ? $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT)
                : null,
        ];
    }
}
