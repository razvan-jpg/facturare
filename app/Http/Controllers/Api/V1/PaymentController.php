<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use App\Models\Payment;
use App\Services\CollectionService;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PaymentController extends Controller
{
    use ResolvesApiCompany;

    public function index(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'payments_view');
        $query = $company->payments()->with(['document', 'client'])->latest('paid_at');
        if ($since = $request->query('since')) {
            $query->where('updated_at', '>', $since);
        }

        $payments = $query->limit(200)->get();

        return response()->json([
            'data' => $payments->map(fn (Payment $p) => $this->serialize($p))->values(),
        ]);
    }

    public function store(Request $request, DocumentService $documents): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'payments_manage');
        $data = $request->validate([
            'document_id' => ['required', 'exists:documents,id'],
            'method' => ['required', 'in:cash,op,card,other,receipt'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'client_uuid' => ['nullable', 'uuid'],
        ]);

        $document = Document::where('company_id', $company->id)->findOrFail($data['document_id']);
        $amount = round((float) $data['amount'], 2);

        if ($document->type === 'proforma' && $document->status === 'issued') {
            $remaining = round($document->remainingAmount(), 2);
            if ($amount + 0.009 >= $remaining && $remaining > 0.009) {
                $invoice = $documents->issueInvoiceFromPaidProforma(
                    $document,
                    $data['paid_at'],
                    $amount,
                    (string) ($data['reference'] ?? ''),
                    (string) ($data['notes'] ?? 'Încasare proformă'),
                    $data['method'],
                );

                $payment = $invoice->payments()->latest('id')->first();

                return response()->json([
                    'data' => $payment
                        ? $this->serialize($payment->fresh(['document', 'client']))
                        : [
                            'id' => null,
                            'document_id' => $invoice->id,
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->number_full,
                            'amount' => $amount,
                            'method' => $data['method'],
                            'paid_at' => $data['paid_at'],
                        ],
                    'invoice' => [
                        'id' => $invoice->id,
                        'number_full' => $invoice->number_full,
                        'efactura_status' => $invoice->efactura_status,
                    ],
                ], 201);
            }
        }

        $payment = Payment::create([
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

        return response()->json(['data' => $this->serialize($payment->fresh(['document', 'client']))], 201);
    }

    public function collect(Request $request, CollectionService $collections): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'payments_manage');
        $currencies = array_keys(config('currencies', ['RON' => 'RON']));

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'instrument' => ['required', 'in:receipt,op'],
            'series' => ['nullable', 'string', 'max:20'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3', Rule::in($currencies)],
            'document_language' => ['nullable', 'string'],
            'reprezentand' => ['nullable', 'string', 'max:5000'],
            'invoice_ids' => ['nullable', 'array'],
            'invoice_ids.*' => ['integer'],
            'include_opening' => ['nullable', 'boolean'],
        ]);

        $client = Client::query()
            ->where('company_id', $company->id)
            ->where('id', $data['client_id'])
            ->firstOrFail();

        if (($data['instrument'] ?? '') === 'receipt' && blank($data['series'] ?? null)) {
            return response()->json(['message' => 'Alege seria chitanței.', 'errors' => ['series' => ['Alege seria chitanței.']]], 422);
        }

        try {
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
                $request->boolean('include_opening', true),
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'receipt' => $result['receipt'] ? [
                'id' => $result['receipt']->id,
                'number_full' => $result['receipt']->number_full,
            ] : null,
            'payments' => collect($result['payments'] ?? [])->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
            ])->values(),
            'message' => $result['receipt']
                ? 'Încasare înregistrată. Chitanța '.$result['receipt']->number_full.' a fost emisă.'
                : 'Încasare (OP) înregistrată.',
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'company_id' => $payment->company_id,
            'document_id' => $payment->document_id,
            'client_id' => $payment->client_id,
            'method' => $payment->method,
            'paid_at' => optional($payment->paid_at)?->toDateString(),
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'reference' => $payment->reference,
            'notes' => $payment->notes,
            'updated_at' => optional($payment->updated_at)?->toIso8601String(),
            'created_at' => optional($payment->created_at)?->toIso8601String(),
        ];
    }
}
