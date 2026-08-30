<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentCardPayment;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mollie\Api\MollieApiClient;
use RuntimeException;

class DocumentCardPaymentService
{
    public function __construct(
        private CardProcessors $processors,
        private NetopiaPaymentService $netopia,
        private EuPlatescPaymentService $euplatesc,
        private MolliePaymentService $mollie,
        private StripePaymentService $stripe,
    ) {}

    public function documentAllowsCard(Document $document): bool
    {
        $document->loadMissing('company');
        if (! $document->allow_card_payment || ! $this->processors->anyActive($document->company)) {
            return false;
        }
        if (! in_array($document->type, ['invoice', 'proforma'], true)) {
            return false;
        }
        // Acceptă și draft: PDF-ul poate fi generat înainte de emitere; plata reală cere rest > 0.
        if (! in_array($document->status, ['issued', 'draft'], true)) {
            return false;
        }

        return $document->remainingAmount() > 0.009;
    }

    /**
     * Linkuri semnate pentru PDF / email — câte unul per procesator activ.
     *
     * @return list<array{key:string,label:string,short:string,url:string}>
     */
    public function paymentLinks(Document $document): array
    {
        $document->loadMissing('company');
        // Doar procesatoarele configurate + active pentru firmă; fără rest de plată → fără linkuri.
        if (! $this->documentAllowsCard($document)) {
            return [];
        }

        $links = [];
        foreach ($this->processors->active($document->company) as $key => $meta) {
            $links[] = [
                'key' => $key,
                'label' => $meta['label'],
                'short' => $meta['short'],
                'url' => URL::signedRoute('documents.pay.start', [
                    'document' => $document->id,
                    'processor' => $key,
                ]),
            ];
        }

        return $links;
    }

    public function hubUrl(Document $document): ?string
    {
        $document->loadMissing('company');
        if (! $this->documentAllowsCard($document)) {
            return null;
        }

        return URL::signedRoute('documents.pay.show', ['document' => $document->id]);
    }

    /**
     * @return array{type:string,form?:array,checkoutUrl?:string,checkout:DocumentCardPayment}
     */
    public function start(Document $document, string $processor): array
    {
        if (! $this->documentAllowsCard($document)) {
            throw new RuntimeException('Plata cu cardul nu este disponibilă pentru acest document.');
        }
        $document->loadMissing('company');
        if (! $this->processors->isActive($processor, $document->company)) {
            throw new RuntimeException('Procesatorul selectat nu este activ.');
        }

        $amount = round($document->remainingAmount(), 2);
        $checkout = DocumentCardPayment::query()->create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'processor' => $processor,
            'checkout_number' => $this->makeCheckoutNumber($document),
            'amount' => $amount,
            'currency' => strtoupper((string) $document->currency) ?: 'RON',
            'status' => 'pending',
        ]);

        return match ($processor) {
            'netopia' => [
                'type' => 'form',
                'form' => $this->netopia->buildDocumentPaymentForm($checkout, $document),
                'checkout' => $checkout,
            ],
            'euplatesc' => [
                'type' => 'form',
                'form' => $this->euplatesc->buildDocumentPaymentForm($checkout, $document),
                'checkout' => $checkout,
            ],
            'mollie' => [
                'type' => 'redirect',
                'checkoutUrl' => $this->mollie->createDocumentCheckout($checkout, $document),
                'checkout' => $checkout->fresh(),
            ],
            'stripe' => [
                'type' => 'redirect',
                'checkoutUrl' => $this->stripe->createDocumentCheckout($checkout, $document),
                'checkout' => $checkout->fresh(),
            ],
            default => throw new RuntimeException('Procesator necunoscut.'),
        };
    }

    public function findByCheckoutNumber(string $number): ?DocumentCardPayment
    {
        return DocumentCardPayment::query()->where('checkout_number', $number)->first();
    }

    public function findByMollieId(string $paymentId): ?DocumentCardPayment
    {
        return DocumentCardPayment::query()->where('mollie_payment_id', $paymentId)->first();
    }

    public function markPaid(DocumentCardPayment $checkout, ?string $externalRef = null): DocumentCardPayment
    {
        if ($checkout->isPaid()) {
            return $checkout;
        }

        return DB::transaction(function () use ($checkout, $externalRef) {
            $checkout = DocumentCardPayment::query()->lockForUpdate()->findOrFail($checkout->id);
            if ($checkout->isPaid()) {
                return $checkout;
            }

            $document = Document::query()->lockForUpdate()->findOrFail($checkout->document_id);
            $amount = round((float) $checkout->amount, 2);
            if ($amount <= 0) {
                throw new RuntimeException('Sumă invalidă.');
            }

            // Nu depăși restul de plată la momentul confirmării.
            $remaining = round($document->remainingAmount(), 2);
            if ($remaining <= 0.009) {
                $checkout->forceFill([
                    'status' => 'paid',
                    'external_ref' => $externalRef,
                    'paid_at' => now(),
                    'error' => 'Document deja achitat.',
                ])->save();

                return $checkout->fresh();
            }
            if ($amount > $remaining + 0.009) {
                $amount = $remaining;
            }

            $paidAt = now()->toDateString();
            $reference = $externalRef ?: $checkout->checkout_number;
            $payNotes = 'Plată card online ('.$checkout->processor.')';

            // Proformă: încasare → emite automat factura fiscală cu data încasării.
            if ($document->type === 'proforma') {
                $invoice = app(DocumentService::class)->issueInvoiceFromPaidProforma(
                    $document,
                    $paidAt,
                    $amount,
                    $reference,
                    $payNotes,
                    'card',
                );

                $checkout->forceFill([
                    'status' => 'paid',
                    'amount' => $amount,
                    'external_ref' => $externalRef,
                    'paid_at' => now(),
                    'error' => null,
                    'document_id' => $invoice->id,
                ])->save();

                $document->refreshPaymentStatus();

                return $checkout->fresh();
            }

            Payment::create([
                'company_id' => $document->company_id,
                'document_id' => $document->id,
                'client_id' => $document->client_id,
                'method' => 'card',
                'paid_at' => $paidAt,
                'amount' => $amount,
                'currency' => $document->currency,
                'reference' => $reference,
                'notes' => $payNotes,
            ]);

            $document->refreshPaymentStatus();

            $checkout->forceFill([
                'status' => 'paid',
                'amount' => $amount,
                'external_ref' => $externalRef,
                'paid_at' => now(),
                'error' => null,
            ])->save();

            return $checkout->fresh();
        });
    }

    public function markFailed(DocumentCardPayment $checkout, string $message): void
    {
        if ($checkout->isPaid()) {
            return;
        }

        $checkout->forceFill([
            'status' => 'failed',
            'error' => $message,
        ])->save();
    }

    private function makeCheckoutNumber(Document $document): string
    {
        // Prefix DF- pentru a distinge de comenzile de abonament (DC-…).
        return 'DF-'.$document->id.'-'.Str::upper(Str::random(8));
    }
}
