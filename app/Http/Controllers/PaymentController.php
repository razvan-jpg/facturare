<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCompanyPermission;
use App\Models\Client;
use App\Models\Document;
use App\Models\Payment;
use App\Services\ClientBalanceService;
use App\Services\CollectionService;
use App\Services\CompanyContext;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use ChecksCompanyPermission;

    public function index(CompanyContext $context): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'payments_view');
        $payments = $company->payments()->with(['document', 'client'])->latest('paid_at')->paginate(25);

        return view('payments.index', compact('payments', 'company'));
    }

    public function create(CompanyContext $context, DocumentService $documents): View|RedirectResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'payments_manage');
        $documents->ensureDefaultSeries($company);

        $seriesList = $company->series()
            ->where('type', 'receipt')
            ->where('active', true)
            ->orderByDesc('year')
            ->orderByDesc('is_default')
            ->orderBy('prefix')
            ->get();

        if ($seriesList->isEmpty()) {
            return redirect()->route('documents.index', ['type' => 'receipt']);
        }

        return view('payments.create', [
            'company' => $company,
            'clients' => $company->clients()->orderBy('name')->get(),
            'seriesList' => $seriesList,
            'currencies' => config('currencies'),
            'cashLimit' => CollectionService::CASH_DAILY_LIMIT_RON,
        ]);
    }

    public function unpaidInvoices(Request $request, CompanyContext $context, ClientBalanceService $balances): JsonResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'payments_manage');
        $data = $request->validate([
            'client_id' => ['required', 'integer'],
        ]);

        $client = Client::query()
            ->where('company_id', $company->id)
            ->where('id', $data['client_id'])
            ->firstOrFail();

        $rows = $company->documents()
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->where('client_id', $client->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get(['id', 'type', 'number_full', 'issue_date', 'due_date', 'total', 'paid_amount', 'currency', 'payment_status']);

        $openingRemaining = $balances->remainingOpeningBalance($client);
        $openingDate = $client->effectiveOpeningBalanceDate();

        return response()->json([
            'client_id' => $client->id,
            'opening' => [
                'remaining' => $openingRemaining,
                'registered' => $balances->openingBalance($client),
                'date' => $openingDate,
                'date_ro' => $openingDate ? \Illuminate\Support\Carbon::parse($openingDate)->format('d/m/Y') : null,
            ],
            'invoices' => $rows->map(fn (Document $d) => [
                'id' => $d->id,
                'type' => $d->type,
                'type_label' => $d->type === 'proforma' ? 'Proformă' : 'Factură',
                'number' => $d->number_full ?: ('#'.$d->id),
                'issue_date' => $d->issue_date?->format('Y-m-d'),
                'issue_date_ro' => $d->issue_date?->format('d/m/Y'),
                'due_date' => $d->due_date?->format('Y-m-d'),
                'remaining' => round($d->remainingAmount(), 2),
                'currency' => $d->currency,
                'payment_status' => $d->payment_status,
            ])->values(),
        ]);
    }

    public function collect(Request $request, CompanyContext $context, CollectionService $collections): RedirectResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'payments_manage');
        $currencies = array_keys(config('currencies', ['RON' => 'RON']));

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'instrument' => ['required', 'in:receipt,op'],
            'series' => ['nullable', 'string', 'max:20'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'currency' => ['required', 'string', 'size:3', Rule::in($currencies)],
            'document_language' => ['nullable', 'string', Rule::in(array_keys(config('document_languages', ['ro' => 'Română'])))],
            'reprezentand' => ['nullable', 'string', 'max:5000'],
            'invoice_ids' => ['nullable', 'array'],
            'invoice_ids.*' => ['integer'],
            'include_opening' => ['nullable', 'in:1'],
        ]);

        $client = Client::query()
            ->where('company_id', $company->id)
            ->where('id', $data['client_id'])
            ->firstOrFail();

        if (($data['instrument'] ?? '') === 'receipt' && blank($data['series'] ?? null)) {
            return back()->withInput()->withErrors(['series' => 'Alege seria chitanței.']);
        }

        // Bifă „Sold inițial” (implicit bifat în UI). Controlează alocarea înaintea facturilor.
        $applyOpening = $request->boolean('include_opening');

        $result = $collections->collect(
            $company,
            $request->user(),
            $client,
            $data['instrument'],
            (float) $data['amount'],
            $data['paid_at'],
            $data['currency'],
            (string) ($data['reprezentand'] ?? ''),
            $data['series'] ?? null,
            (string) ($data['document_language'] ?? 'ro'),
            array_map('intval', $data['invoice_ids'] ?? []),
            $applyOpening,
        );

        if ($result['receipt']) {
            return redirect()
                ->route('documents.show', $result['receipt'])
                ->with('status', 'Încasare înregistrată. Chitanța '.$result['receipt']->number_full.' a fost emisă.');
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Încasare (OP) înregistrată.');
    }

    public function store(Request $request, CompanyContext $context, DocumentService $documents): RedirectResponse
    {
        $this->authorizeCompanyAbility($context->current(), 'payments_manage');

        $company = $context->current();
        $data = $request->validate([
            'document_id' => ['required', 'exists:documents,id'],
            'method' => ['required', 'in:cash,op,card,other,receipt'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $document = Document::where('company_id', $company->id)->findOrFail($data['document_id']);
        $amount = round((float) $data['amount'], 2);

        // Proformă încasată integral → factură fiscală + e-Factura după setările firmei.
        if ($document->type === 'proforma' && $document->status === 'issued') {
            $remaining = round($document->remainingAmount(), 2);
            if ($amount + 0.009 >= $remaining && $remaining > 0.009) {
                try {
                    $invoice = $documents->issueInvoiceFromPaidProforma(
                        $document,
                        $data['paid_at'],
                        $amount,
                        (string) ($data['reference'] ?? ''),
                        (string) ($data['notes'] ?? 'Încasare proformă'),
                        $data['method'],
                    );

                    $status = 'Proforma a fost încasată. Factură fiscală emisă: '.($invoice->number_full ?: '#'.$invoice->id).'.';
                    if ($invoice->efactura_status === 'queued' || filled($invoice->efactura_scheduled_at)) {
                        $status .= ' e-Factura urmează programarea din Setări.';
                    } elseif ($invoice->efactura_status === 'sent' || $invoice->efactura_status === 'ok') {
                        $status .= ' e-Factura a fost trimisă / programată.';
                    }

                    return redirect()
                        ->route('documents.show', $invoice)
                        ->with('status', $status);
                } catch (\Throwable $e) {
                    return back()->withInput()->with('status', 'Încasarea proformei a eșuat: '.$e->getMessage());
                }
            }
        }

        Payment::create([
            'company_id' => $company->id,
            'document_id' => $document->id,
            'client_id' => $document->client_id,
            'method' => $data['method'],
            'paid_at' => $data['paid_at'],
            'amount' => $amount,
            'currency' => $document->currency,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $document->refreshPaymentStatus();

        return back()->with('status', 'Încasare înregistrată.');
    }

    public function destroy(Payment $payment, CompanyContext $context): RedirectResponse
    {
        abort_unless($payment->company_id === $context->current()?->id, 403);
        $this->authorizeCompanyAbility($context->current(), 'payments_manage');
        $document = $payment->document;
        $payment->delete();
        $document?->refreshPaymentStatus();

        return back()->with('status', 'Încasare ștearsă.');
    }
}
