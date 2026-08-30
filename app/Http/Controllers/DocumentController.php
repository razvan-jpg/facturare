<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCompanyPermission;
use App\Mail\DocumentSentMail;
use App\Models\Client;
use App\Models\Document;
use App\Services\CompanyContext;
use App\Services\DocumentService;
use App\Services\EfacturaService;
use App\Services\EfacturaUblBuilder;
use App\Services\ExchangeRateService;
use App\Services\InvoicePdfService;
use App\Services\ReliableMail;
use App\Support\DocumentFooterFields;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class DocumentController extends Controller
{
    use ChecksCompanyPermission;

    public function index(Request $request, CompanyContext $context, EfacturaService $efactura): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'documents_view');
        $type = $request->string('type', 'invoice')->toString();
        abort_unless(isset(Document::LIST_LABELS[$type]), 404);

        // Actualizează rapid statusurile în așteptare (înainte de listă / auto-refresh 30s).
        if (in_array($type, ['invoice', 'storno', 'credit_note'], true) && $company->isAnafAuthorized()) {
            $pending = $company->documents()
                ->when($type === 'storno', fn ($q) => $q->where('type', 'invoice')->where('status', 'storno'))
                ->when($type === 'credit_note', fn ($q) => $q->where('type', 'credit_note'))
                ->when($type === 'invoice', fn ($q) => $q->where('type', 'invoice')->where('status', '!=', 'storno'))
                ->whereIn('efactura_status', ['uploaded', 'processing'])
                ->whereNotNull('efactura_upload_id')
                ->limit(15)
                ->get();
            foreach ($pending as $pendingDoc) {
                try {
                    $efactura->refreshStatus($pendingDoc);
                } catch (Throwable) {
                    // lista trebuie să se afișeze oricum
                }
            }
        }

        $documents = $company->documents()
            ->select([
                'id', 'company_id', 'type', 'status', 'number_full', 'issue_date', 'due_date',
                'client_name', 'total', 'paid_amount', 'currency', 'payment_status',
                'efactura_status', 'efactura_upload_id', 'efactura_download_id', 'efactura_error',
                'related_document_id', 'created_at', 'updated_at',
            ])
            ->withCount([
                'relatedDocuments as storno_count' => fn ($q) => $q->where('status', 'storno'),
                'relatedDocuments as credit_note_count' => fn ($q) => $q->where('type', 'credit_note'),
            ])
            ->when($type === 'storno', fn ($q) => $q->where('type', 'invoice')->where('status', 'storno'))
            ->when($type === 'credit_note', fn ($q) => $q->where('type', 'credit_note'))
            ->when($type === 'invoice', fn ($q) => $q->where('type', 'invoice')->where('status', '!=', 'storno'))
            ->when(in_array($type, ['proforma', 'delivery', 'receipt'], true), fn ($q) => $q->where('type', $type))
            // Cele mai noi sus: data/ora salvării, apoi data documentului, număr, id.
            ->orderByDesc('updated_at')
            ->orderByDesc('issue_date')
            ->orderByDesc('number')
            ->orderByDesc('id')
            ->paginate($company->documentsPerPage())
            ->withQueryString();

        $seriesType = match ($type) {
            'storno' => 'invoice',
            default => $type,
        };
        $hasActiveSeries = in_array($seriesType, ['invoice', 'proforma', 'delivery', 'receipt', 'credit_note'], true)
            && $company->series()
                ->where('type', $seriesType)
                ->where('active', true)
                ->exists();

        $hasEfacturaOverdue = false;
        if (in_array($type, ['invoice', 'storno', 'credit_note'], true)) {
            $hasEfacturaOverdue = $company->documents()
                ->when($type === 'storno', fn ($q) => $q->where('type', 'invoice')->where('status', 'storno'))
                ->when($type === 'credit_note', fn ($q) => $q->where('type', 'credit_note')->where('status', 'issued'))
                ->when($type === 'invoice', fn ($q) => $q->where('type', 'invoice')->whereIn('status', ['issued', 'storno']))
                ->where(function ($q) {
                    $q->whereNull('efactura_status')
                        ->orWhereNotIn('efactura_status', ['uploaded', 'processing', 'ok']);
                })
                ->whereDate('issue_date', '<=', now()->subDays(6)->toDateString())
                ->exists();
        }

        return view('documents.index', compact(
            'documents',
            'company',
            'type',
            'hasActiveSeries',
            'hasEfacturaOverdue'
        ));
    }

    public function create(Request $request, CompanyContext $context, DocumentService $documents): View|RedirectResponse
    {
        $this->authorizeCompanyAbility($context->current(), 'documents_manage');

        $company = $context->current();
        $type = $request->string('type', 'invoice')->toString();

        // Chitanța se emite din formularul de Încasare (facturi neîncasate + OP/chitanță).
        if ($type === 'receipt') {
            return redirect()->route('payments.create');
        }

        if ($type === 'storno') {
            return redirect()->route('documents.corrections.create', ['kind' => 'storno']);
        }

        if ($type === 'credit_note') {
            return redirect()->route('documents.corrections.create', ['kind' => 'credit_note']);
        }

        abort_unless(isset(Document::TYPE_LABELS[$type]), 404);

        $documents->ensureDefaultSeries($company);

        $seriesList = $company->series()
            ->where('type', $type)
            ->where('active', true)
            ->orderByDesc('year')
            ->orderByDesc('is_default')
            ->orderBy('prefix')
            ->get();

        if ($seriesList->isEmpty()) {
            // Mesajul e afișat bold/centrat pe listă — fără bara roșie de erori.
            return redirect()->route('documents.index', ['type' => $type]);
        }

        // Ciornă + rezervare număr imediat la deschiderea formularului (evită duplicate pe multi-sesiune).
        $year = (int) now()->format('Y');
        $defaultSeries = $seriesList->firstWhere('year', $year)?->prefix
            ?? $seriesList->firstWhere('is_default', true)?->prefix
            ?? $seriesList->first()?->prefix;

        $document = Document::create([
            'company_id' => $company->id,
            'created_by' => $request->user()->id,
            'type' => $type,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'issue_year' => $year,
            'currency' => 'RON',
            'exchange_rate' => 1,
            'series' => $defaultSeries,
            'document_language' => ($company->document_languages ?: ['ro'])[0] ?? 'ro',
            'prepared_by' => $company->seriesResponsibleName(),
        ]);

        try {
            $documents->reserveNumber($document, $defaultSeries);
        } catch (\Throwable $e) {
            $document->delete();

            return redirect()
                ->route('documents.index', ['type' => $type])
                ->withErrors(['series' => $e->getMessage()]);
        }

        return redirect()->route('documents.edit', $document);
    }

    public function fxRate(Request $request, ExchangeRateService $fx): JsonResponse
    {
        $data = $request->validate([
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys(config('currencies', [])))],
        ]);

        try {
            $rate = $fx->rateToRon($data['currency']);

            return response()->json([
                'currency' => strtoupper($data['currency']),
                'rate' => $rate,
                'source' => 'BNR',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request, CompanyContext $context, DocumentService $service): RedirectResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'documents_manage');
        $currencies = array_keys(config('currencies', ['RON' => 'RON']));
        $data = $request->validate([
            'type' => ['required', 'in:invoice,proforma,delivery,receipt'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'payment_term' => ['nullable', 'string', Rule::in(array_keys(config('payment_terms', [])))],
            'currency' => ['required', 'string', 'size:3', Rule::in($currencies)],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'series' => ['nullable', 'string', 'max:20'],
            'document_language' => ['nullable', 'string', Rule::in(array_keys(config('document_languages', ['ro' => 'Română'])))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['nullable', 'numeric', 'decimal:0,2'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.details' => ['nullable', 'array'],
            'items.*.details.*' => ['nullable', 'string', 'max:2000'],
            'action' => ['nullable', 'in:draft,issue'],
            ...DocumentFooterFields::rules(),
        ]);

        if (($data['currency'] ?? 'RON') === 'RON') {
            $data['exchange_rate'] = 1;
        } elseif (empty($data['exchange_rate'])) {
            return back()->withInput()->withErrors(['exchange_rate' => 'Completează cursul valutar.']);
        } else {
            $data['exchange_rate'] = round((float) $data['exchange_rate'], 4);
        }

        $data = DocumentFooterFields::fromRequest($request, $data, $company);
        $data['document_language'] = $this->resolveDocumentLanguage($company, $data['document_language'] ?? null);
        $data = $this->resolvePaymentTermDates($data);

        $data['items'] = $this->assertCompleteItems($data['items'] ?? []);

        if (! empty($data['client_id'])) {
            abort_unless($company->clients()->where('id', $data['client_id'])->exists(), 403);
        }

        $document = $service->createDraft($company, $request->user(), $data['type'], $data, $data['items']);

        if (($data['action'] ?? 'draft') === 'issue') {
            $service->issueAndMaybeSendEfactura($document);
        }

        return $this->redirectAfterSave($document, $company, 'Document salvat.');
    }

    public function show(Document $document, CompanyContext $context, EfacturaService $efactura): View
    {
        $this->authorizeDocument($document, $context);
        $document->load(['items', 'client', 'company.banks.accounts', 'payments']);

        // Refresh e-Factura doar pentru documentul curent (cron-ul face restul).
        if (
            $document->type === 'invoice'
            && $document->status === 'issued'
            && in_array($document->efactura_status, ['uploaded', 'processing'], true)
            && $document->company?->isAnafAuthorized()
        ) {
            try {
                $document = $efactura->refreshStatus($document);
            } catch (\Throwable) {
                // keep current status if ANAF is unreachable
            }
        }

        return view('documents.show', compact('document'));
    }

    public function sendEfactura(Document $document, CompanyContext $context, EfacturaService $efactura): RedirectResponse
    {
        $this->authorizeDocument($document, $context, 'efactura_manage');

        try {
            $efactura->send($document);
            return back()->with('status', 'Factura a fost trimisă în e-Factura. Status: '.$document->fresh()->efacturaStatusLabel());
        } catch (\Throwable $e) {
            return back()->with('status', 'e-Factura: '.$e->getMessage());
        }
    }

    public function sendEfacturaBulk(Request $request, CompanyContext $context, EfacturaService $efactura): RedirectResponse
    {
        $company = $context->current();
        abort_unless($company, 403);
        $this->authorizeCompanyAbility($company, 'efactura_manage');

        $data = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer'],
        ], [
            'document_ids.required' => 'Selectează cel puțin o factură.',
            'document_ids.min' => 'Selectează cel puțin o factură.',
        ]);

        $documents = $company->documents()
            ->whereIn('type', ['invoice', 'credit_note'])
            ->whereIn('id', $data['document_ids'])
            ->get();

        $ok = 0;
        $errors = [];

        foreach ($documents as $document) {
            if (! $document->canSendEfactura()) {
                $errors[] = ($document->number_full ?: '#'.$document->id).': nu poate fi trimis.';
                continue;
            }

            try {
                $efactura->send($document);
                $ok++;
            } catch (\Throwable $e) {
                $errors[] = ($document->number_full ?: '#'.$document->id).': '.$e->getMessage();
            }
        }

        $parts = [];
        if ($ok > 0) {
            $parts[] = $ok === 1
                ? '1 document trimis în e-Factura.'
                : $ok.' documente trimise în e-Factura.';
        }
        if ($errors !== []) {
            $parts[] = 'Erori: '.implode(' · ', array_slice($errors, 0, 5)).(count($errors) > 5 ? '…' : '');
        }
        if ($parts === []) {
            $parts[] = 'Niciun document eligibil pentru e-Factura.';
        }

        return back()->with('status', implode(' ', $parts));
    }

    public function emailBulk(
        Request $request,
        CompanyContext $context,
        InvoicePdfService $invoicePdf,
        ReliableMail $mail,
    ): RedirectResponse {
        $company = $context->current();
        abort_unless($company, 403);
        $this->authorizeCompanyAbility($company, 'documents_manage');

        $data = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer'],
        ], [
            'document_ids.required' => 'Selectează cel puțin o factură.',
            'document_ids.min' => 'Selectează cel puțin o factură.',
        ]);

        $documents = $company->documents()
            ->with(['client', 'company'])
            ->where('type', 'invoice')
            ->whereIn('status', ['issued', 'storno'])
            ->whereIn('id', $data['document_ids'])
            ->get();

        $ok = 0;
        $errors = [];

        foreach ($documents as $document) {
            $recipients = dc_parse_emails($document->client_email ?: $document->client?->email);
            if ($recipients === []) {
                $errors[] = ($document->number_full ?: '#'.$document->id).': clientul nu are email.';
                continue;
            }

            try {
                $mail->send(
                    new DocumentSentMail($document, $invoicePdf->output($document)),
                    $recipients,
                    $document->company
                );
                $ok++;
            } catch (Throwable $e) {
                $errors[] = ($document->number_full ?: '#'.$document->id).': '.$e->getMessage();
            }
        }

        $parts = [];
        if ($ok > 0) {
            $parts[] = $ok === 1
                ? '1 factură retrimisă pe email.'
                : $ok.' facturi retrimise pe email.';
        }
        if ($errors !== []) {
            $parts[] = 'Erori: '.implode(' · ', array_slice($errors, 0, 5)).(count($errors) > 5 ? '…' : '');
        }
        if ($parts === []) {
            $parts[] = 'Nicio factură eligibilă pentru email (emisă/storno cu adresă client).';
        }

        return back()->with('status', implode(' ', $parts));
    }

    /**
     * Generează XML UBL (CIUS-RO) pentru depunere manuală în SPV.
     * JSON (fișiere individuale) pentru salvare pe disc din browser, sau ZIP ca rezervă.
     */
    public function exportEfacturaXml(Request $request, CompanyContext $context, EfacturaUblBuilder $ubl): JsonResponse|StreamedResponse|\Illuminate\Http\Response
    {
        $this->authorizeCompanyAbility($context->current(), 'efactura_view');

        $company = $context->current();
        abort_unless($company, 403);

        $data = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer'],
            'format' => ['nullable', 'in:json,zip,xml'],
        ], [
            'document_ids.required' => 'Selectează cel puțin o factură.',
            'document_ids.min' => 'Selectează cel puțin o factură.',
        ]);

        $documents = $company->documents()
            ->with(['items', 'client', 'company'])
            ->whereIn('type', ['invoice', 'credit_note'])
            ->whereIn('id', $data['document_ids'])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $files = [];
        $errors = [];

        foreach ($documents as $document) {
            if (! $document->canExportEfacturaXml()) {
                $errors[] = ($document->number_full ?: '#'.$document->id).': doar facturile emise/storno și notele de creditare pot genera XML.';
                continue;
            }

            try {
                $xml = $ubl->build($document);
                $files[] = [
                    'id' => $document->id,
                    'filename' => $document->efacturaXmlFileName(),
                    'content' => $xml,
                ];
            } catch (Throwable $e) {
                $errors[] = ($document->number_full ?: '#'.$document->id).': '.$e->getMessage();
            }
        }

        if ($files === []) {
            $message = $errors !== []
                ? implode(' ', $errors)
                : 'Nicio factură eligibilă pentru export XML.';

            if ($request->expectsJson() || ($data['format'] ?? null) === 'json') {
                return response()->json(['message' => $message, 'files' => [], 'errors' => $errors], 422);
            }

            return back()->withErrors(['xml' => $message]);
        }

        $format = $data['format']
            ?? ($request->expectsJson() ? 'json' : (count($files) === 1 ? 'xml' : 'zip'));

        if ($format === 'json') {
            return response()->json([
                'files' => array_map(fn (array $f) => [
                    'id' => $f['id'],
                    'filename' => $f['filename'],
                    'content' => $f['content'],
                ], $files),
                'errors' => $errors,
                'count' => count($files),
            ]);
        }

        if ($format === 'xml' || count($files) === 1) {
            $file = $files[0];

            return response($file['content'], 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"',
            ]);
        }

        if (! class_exists(ZipArchive::class)) {
            return response()->json([
                'message' => 'ZIP indisponibil pe server. Folosește exportul JSON din interfață.',
                'files' => array_map(fn (array $f) => [
                    'id' => $f['id'],
                    'filename' => $f['filename'],
                    'content' => $f['content'],
                ], $files),
                'errors' => $errors,
            ]);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'efactura_xml_');
        $zipPath = $tmp.'.zip';
        @unlink($tmp);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors(['xml' => 'Nu am putut crea arhiva ZIP.']);
        }

        $usedNames = [];
        foreach ($files as $file) {
            $name = $file['filename'];
            $i = 1;
            while (isset($usedNames[$name])) {
                $name = preg_replace('/\.xml$/i', '', $file['filename']).'_'.$i.'.xml';
                $i++;
            }
            $usedNames[$name] = true;
            $zip->addFromString($name, $file['content']);
        }
        $zip->close();

        return response()->download($zipPath, 'e-factura-xml-'.now()->format('Y-m-d-His').'.zip')->deleteFileAfterSend(true);
    }

    public function refreshEfactura(Document $document, CompanyContext $context, EfacturaService $efactura): RedirectResponse
    {
        $this->authorizeDocument($document, $context, 'efactura_manage');

        try {
            $efactura->refreshStatus($document);
            return back()->with('status', 'Stare e-Factura: '.$document->fresh()->efacturaStatusLabel());
        } catch (\Throwable $e) {
            return back()->with('status', 'e-Factura: '.$e->getMessage());
        }
    }

    public function edit(Document $document, CompanyContext $context, DocumentService $service): View
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        abort_unless($document->canEdit(), 403, 'Documentul nu poate fi editat (posibil trimis în e-Factura).');
        $company = $context->current();

        if ($document->status === 'draft') {
            try {
                if ($document->hasNumberReservation()) {
                    $document = $service->touchReservation($document);
                } else {
                    $document = $service->reserveNumber($document);
                }
            } catch (\Throwable $e) {
                // Formularul se deschide; utilizatorul poate configura seriile.
            }
        }

        $document->load('items');

        $seriesList = $company->series()
            ->where('type', $document->type)
            ->where('active', true)
            ->orderByDesc('year')
            ->orderByDesc('is_default')
            ->orderBy('prefix')
            ->get();

        return view('documents.edit', [
            'document' => $document->fresh(['items']),
            'company' => $company,
            'clients' => $company->clients()->orderBy('name')->get(),
            'products' => $company->products()->where('active', true)->orderBy('name')->get(),
            'seriesList' => $seriesList,
            'currencies' => config('currencies'),
            'paymentTerms' => config('payment_terms'),
        ]);
    }

    public function reserveNumber(Request $request, Document $document, CompanyContext $context, DocumentService $service): JsonResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        abort_unless($document->status === 'draft', 422, 'Doar ciornele pot rezerva un număr.');

        $data = $request->validate([
            'series' => ['nullable', 'string', 'max:20'],
            'issue_date' => ['nullable', 'date'],
            'number' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! empty($data['issue_date'])) {
            $document->update(['issue_date' => $data['issue_date']]);
        }

        try {
            $document = $service->reserveNumber(
                $document->fresh(),
                $data['series'] ?? null,
                isset($data['number']) ? (int) $data['number'] : null
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $availability = $service->availableNumbers(
            $context->current(),
            $document->type,
            (string) $document->series,
            (int) ($document->issue_year ?: $document->issue_date->format('Y')),
            $document->id
        );

        return response()->json([
            'series' => $document->series,
            'number' => $document->number,
            'number_full' => $document->number_full,
            'number_reserved_at' => optional($document->number_reserved_at)?->toIso8601String(),
            'available_numbers' => $availability['available'],
            'gap_numbers' => $availability['gaps'],
            'next_number' => $availability['next'],
        ]);
    }

    public function releaseNumber(Document $document, CompanyContext $context, DocumentService $service): JsonResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        $service->releaseReservation($document);

        return response()->json(['ok' => true]);
    }

    public function touchNumber(Document $document, CompanyContext $context, DocumentService $service): JsonResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        abort_unless($document->status === 'draft', 422);

        try {
            $document = $service->touchReservation($document);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'number_full' => $document->number_full,
            'number_reserved_at' => optional($document->number_reserved_at)?->toIso8601String(),
        ]);
    }

    public function update(Request $request, Document $document, CompanyContext $context, DocumentService $service): RedirectResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        abort_unless($document->canEdit(), 403, 'Documentul nu poate fi editat (posibil trimis în e-Factura).');

        $currencies = array_keys(config('currencies', ['RON' => 'RON']));
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'payment_term' => ['nullable', 'string', Rule::in(array_keys(config('payment_terms', [])))],
            'currency' => ['required', 'string', 'size:3', Rule::in($currencies)],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'series' => ['nullable', 'string', 'max:20'],
            'document_language' => ['nullable', 'string', Rule::in(array_keys(config('document_languages', ['ro' => 'Română'])))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['nullable', 'numeric', 'decimal:0,2'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.details' => ['nullable', 'array'],
            'items.*.details.*' => ['nullable', 'string', 'max:2000'],
            'action' => ['nullable', 'in:draft,issue'],
            ...DocumentFooterFields::rules(),
        ]);

        if (($data['currency'] ?? 'RON') === 'RON') {
            $data['exchange_rate'] = 1;
        } elseif (empty($data['exchange_rate'])) {
            return back()->withInput()->withErrors(['exchange_rate' => 'Completează cursul valutar.']);
        } else {
            $data['exchange_rate'] = round((float) $data['exchange_rate'], 4);
        }

        $company = $context->current();
        $data = DocumentFooterFields::fromRequest($request, $data, $company);
        $data['document_language'] = $this->resolveDocumentLanguage($company, $data['document_language'] ?? null);
        $data = $this->resolvePaymentTermDates($data);

        $data['items'] = $this->assertCompleteItems($data['items'] ?? []);

        $document->update(array_merge([
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?? null,
            'payment_term' => $data['payment_term'] ?? null,
            'currency' => $data['currency'],
            'exchange_rate' => $data['exchange_rate'],
            'series' => $document->status === 'draft' ? ($data['series'] ?? $document->series) : $document->series,
            'document_language' => $data['document_language'],
        ], DocumentFooterFields::persistable($data)));

        if (! empty($data['client_id'])) {
            $service->syncClientSnapshot($document, $context->current()->clients()->find($data['client_id']));
        }

        $service->replaceItems($document, $data['items']);

        if ($document->type === 'invoice' && $document->status === 'draft' && $document->client_id) {
            try {
                app(\App\Services\ClientPenaltyService::class)
                    ->appendPenaltyLinesToInvoice($document->fresh(['items', 'client']), $service);
            } catch (Throwable $e) {
                // salvare document prioritară
            }
        }

        if ($document->status === 'draft') {
            try {
                $service->reserveNumber($document->fresh(), $data['series'] ?? null);
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['series' => $e->getMessage()]);
            }
        }

        if ($document->status === 'draft' && ($data['action'] ?? 'draft') === 'issue') {
            $service->issueAndMaybeSendEfactura($document->fresh());
        }

        return $this->redirectAfterSave($document->fresh(), $company, 'Document actualizat.');
    }

    public function issue(Document $document, CompanyContext $context, DocumentService $service): RedirectResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        $service->issueAndMaybeSendEfactura($document);
        $document = $document->fresh();

        return $this->redirectAfterSave(
            $document,
            $context->current(),
            'Document emis: '.$document->number_full
        );
    }

    public function cancel(Document $document, CompanyContext $context, DocumentService $service): RedirectResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        abort_unless($document->canCancel(), 403, 'Documentul nu poate fi anulat (posibil trimis în e-Factura).');

        $service->cancelDocument($document);

        return back()->with('status', 'Document anulat. Numărul din serie a fost eliberat, dacă era ultimul emis.');
    }

    public function destroy(Document $document, CompanyContext $context, DocumentService $service): RedirectResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        abort_unless($document->canDelete(), 403, 'Documentul nu poate fi șters (posibil trimis în e-Factura).');

        $listType = $document->listType();
        $service->deleteDocument($document);

        return redirect()
            ->route('documents.index', ['type' => $listType])
            ->with('status', 'Document șters. Numărul din serie a fost eliberat, dacă era ultimul emis.');
    }

    public function storno(Request $request, Document $document, CompanyContext $context, DocumentService $service): RedirectResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');
        abort_unless($document->canStorno(), 403, 'Factura nu poate fi stornată.');

        try {
            $storno = $service->createStorno($document, $request->user());
        } catch (\Throwable $e) {
            return back()->with('status', 'Stornare eșuată: '.$e->getMessage());
        }

        return redirect()->route('documents.show', $storno)
            ->with('status', 'Factură storno emisă: '.$storno->number_full);
    }

    public function createCorrection(string $kind, CompanyContext $context, DocumentService $documents): View|RedirectResponse
    {
        abort_unless(in_array($kind, ['storno', 'credit_note'], true), 404);
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'documents_manage');
        $documents->ensureDefaultSeries($company);

        $seriesType = $kind === 'credit_note' ? 'credit_note' : 'invoice';
        $hasSeries = $company->series()->where('type', $seriesType)->where('active', true)->exists();
        if (! $hasSeries) {
            return redirect()->route('documents.index', ['type' => $kind === 'credit_note' ? 'credit_note' : 'invoice']);
        }

        $invoices = $company->documents()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->withCount([
                'relatedDocuments as storno_count' => fn ($q) => $q->where('status', 'storno'),
                'relatedDocuments as credit_note_count' => fn ($q) => $q->where('type', 'credit_note'),
            ])
            ->latest('issue_date')
            ->latest('id')
            ->limit(200)
            ->get()
            ->filter(fn (Document $d) => $kind === 'storno' ? $d->canStorno() : $d->canCreditNote())
            ->values();

        return view('documents.corrections-create', [
            'company' => $company,
            'kind' => $kind,
            'invoices' => $invoices,
            'heading' => $kind === 'storno' ? 'Emitere › Factură storno' : 'Emitere › Notă de creditare',
            'subheading' => $kind === 'storno'
                ? 'Selectează factura emisă pe care vrei să o stornezi integral'
                : 'Selectează factura emisă pentru care emiți nota de creditare',
        ]);
    }

    public function storeCorrection(Request $request, string $kind, CompanyContext $context, DocumentService $service): RedirectResponse
    {
        $this->authorizeCompanyAbility($context->current(), 'documents_manage');

        abort_unless(in_array($kind, ['storno', 'credit_note'], true), 404);
        $company = $context->current();

        $data = $request->validate([
            'document_id' => ['required', 'integer', 'exists:documents,id'],
        ]);

        $document = Document::query()
            ->where('company_id', $company->id)
            ->where('id', $data['document_id'])
            ->firstOrFail();

        try {
            if ($kind === 'storno') {
                abort_unless($document->canStorno(), 403, 'Factura nu poate fi stornată.');
                $created = $service->createStorno($document, $request->user());
                $msg = 'Factură storno emisă: '.$created->number_full;
            } else {
                abort_unless($document->canCreditNote(), 403, 'Factura nu poate primi notă de creditare.');
                $created = $service->createCreditNote($document, $request->user());
                $msg = 'Notă de creditare emisă: '.$created->number_full;
            }
        } catch (\Throwable $e) {
            return back()->with('status', 'Emitere eșuată: '.$e->getMessage());
        }

        return $this->redirectAfterSave($created, $company, $msg);
    }

    public function pdf(Document $document, CompanyContext $context, InvoicePdfService $invoicePdf)
    {
        $this->authorizeDocument($document, $context);

        return $invoicePdf->make($document)
            ->download($document->pdfFileName())
            ->withHeaders([
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }

    public function pdfSigned(Document $document, InvoicePdfService $invoicePdf)
    {
        return $invoicePdf->make($document)
            ->download($document->pdfFileName())
            ->withHeaders([
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }

    public function email(Document $document, CompanyContext $context, InvoicePdfService $invoicePdf, ReliableMail $mail): RedirectResponse
    {
        $this->authorizeDocument($document, $context, 'documents_manage');

        $recipients = dc_parse_emails($document->client_email ?: $document->client?->email);
        abort_unless($recipients !== [], 422, 'Clientul nu are email.');

        $sentAt = now();
        $document->loadMissing('company');

        try {
            $mail->send(
                new DocumentSentMail($document, $invoicePdf->output($document)),
                $recipients,
                $document->company
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'email' => 'Emailul nu a putut fi trimis: '.$e->getMessage(),
            ]);
        }

        $list = implode(', ', $recipients);
        $when = dc_datetime($sentAt);

        return back()->with('status', "Email trimis către {$list} la {$when}.");
    }

    private function authorizeDocument(Document $document, CompanyContext $context, string $ability = 'documents_view'): void
    {
        abort_unless($document->company_id === $context->current()?->id, 403);
        $this->authorizeCompanyAbility($context->current(), $ability);
    }

    /**
     * Normalizează due_date din payment_term + issue_date (nu ne bazăm doar pe JS).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveDocumentLanguage(\App\Models\Company $company, ?string $lang): string
    {
        $available = array_keys($company->availableDocumentLanguages());
        $lang = $lang ?: 'ro';

        return in_array($lang, $available, true) ? $lang : 'ro';
    }

    private function resolvePaymentTermDates(array $data): array
    {
        $term = (string) ($data['payment_term'] ?? 'date');
        $issueRaw = $data['issue_date'] ?? null;
        if (! $issueRaw) {
            return $data;
        }

        try {
            $issue = Carbon::parse($issueRaw)->startOfDay();
        } catch (\Throwable) {
            return $data;
        }

        if ($term === 'none') {
            $data['due_date'] = null;
        } elseif ($term === 'issue') {
            $data['due_date'] = $issue->toDateString();
        } elseif ($term === 'month_end') {
            $data['due_date'] = $issue->copy()->endOfMonth()->toDateString();
        } elseif (ctype_digit($term)) {
            $data['due_date'] = $issue->copy()->addDays((int) $term)->toDateString();
        } else {
            // 'date' — păstrează due_date din formular
            $due = $data['due_date'] ?? null;
            $data['due_date'] = filled($due) ? Carbon::parse($due)->toDateString() : null;
        }

        $data['payment_term'] = $term !== '' ? $term : null;

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

            if ($isEmpty) {
                $errors["items.$index.name"] = "Linia {$line} este goală — completeaz-o sau șterge-o.";
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

            $complete[] = $row;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($complete === []) {
            throw ValidationException::withMessages([
                'items' => 'Adaugă cel puțin o linie completă pe factură.',
            ]);
        }

        return $complete;
    }

    private function redirectAfterSave(Document $document, $company, string $status): RedirectResponse
    {
        $redirect = redirect()
            ->route('documents.index', ['type' => $document->listType()])
            ->with('status', $status);
        $warning = $this->clientBankWarning($document, $company);
        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    private function clientBankWarning(Document $document, $company): ?string
    {
        if ($document->type !== 'invoice' || ! $document->client_id || ! $company) {
            return null;
        }

        /** @var Client|null $client */
        $client = $company->clients()->find($document->client_id);
        if (! $client) {
            return null;
        }

        if ($client->hasBankAccountDetails()) {
            return null;
        }

        $editUrl = e(route('clients.edit', $client));
        $name = e($client->name);

        return 'Atenție: la clientul <strong>'.$name.'</strong> nu există cont bancar (IBAN) completat'
            .'. <a href="'.$editUrl.'" class="underline font-semibold">Completează datele clientului</a>.';
    }
}
