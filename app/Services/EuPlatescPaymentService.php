<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentCardPayment;
use App\Models\SubscriptionOrder;
use RuntimeException;

/**
 * Integrare EuPlătesc — formular POST + silent URL.
 *
 * @see https://www.euplatesc.ro
 */
class EuPlatescPaymentService
{
    public function __construct(
        private SubscriptionOrderService $orders,
        private CompanyIntegrations $integrations,
    ) {}

    /** Configurat pentru abonamentul DateConta (platformă). */
    public function isConfigured(): bool
    {
        if (! config('euplatesc.enabled')) {
            return false;
        }

        return filled(config('euplatesc.mid')) && filled(config('euplatesc.key'));
    }

    /**
     * @return array{url:string,fields:array<string,string>}
     */
    public function buildPaymentForm(SubscriptionOrder $order): array
    {
        return $this->buildForm(
            invoiceId: $order->number,
            amount: (float) $order->amount_total,
            currency: (string) $order->currency,
            description: 'Abonament DateConta '.$order->number,
            name: (string) $order->billing_name,
            email: (string) $order->billing_email,
            phone: (string) ($order->billing_phone ?: '0700000000'),
            address: (string) ($order->billing_address ?: '-'),
            city: (string) ($order->billing_city ?: '-'),
            county: (string) ($order->billing_county ?: '-'),
            silentUrl: route('billing.euplatesc.silent'),
            successUrl: route('billing.euplatesc.return', ['order' => $order->number]),
            failedUrl: route('billing.euplatesc.return', ['order' => $order->number]),
            mid: (string) config('euplatesc.mid'),
            key: (string) config('euplatesc.key'),
            paymentUrl: (string) config('euplatesc.payment_url'),
        );
    }

    /**
     * @return array{url:string,fields:array<string,string>}
     */
    public function buildDocumentPaymentForm(DocumentCardPayment $checkout, Document $document): array
    {
        $document->loadMissing('company');
        $company = $document->company;
        if (! $company || ! $this->integrations->isEuPlatescReady($company)) {
            throw new RuntimeException('Eu Plătesc nu este configurat pentru această firmă.');
        }

        return $this->buildForm(
            invoiceId: $checkout->checkout_number,
            amount: (float) $checkout->amount,
            currency: (string) $checkout->currency,
            description: 'Plată '.$document->number_full,
            name: (string) $document->client_name,
            email: (string) ($document->client_email ?: 'client@example.com'),
            phone: '0700000000',
            address: (string) ($document->client_address ?: '-'),
            city: '-',
            county: '-',
            silentUrl: route('plata.euplatesc.silent'),
            successUrl: route('documents.pay.return', ['checkout' => $checkout->checkout_number]),
            failedUrl: route('documents.pay.return', ['checkout' => $checkout->checkout_number]),
            mid: (string) $this->integrations->get($company, 'euplatesc', 'mid', ''),
            key: (string) $this->integrations->get($company, 'euplatesc', 'key', ''),
            paymentUrl: $this->integrations->euplatescPaymentUrl(),
        );
    }

    /**
     * @return array{url:string,fields:array<string,string>}
     */
    private function buildForm(
        string $invoiceId,
        float $amount,
        string $currency,
        string $description,
        string $name,
        string $email,
        string $phone,
        string $address,
        string $city,
        string $county,
        string $silentUrl,
        string $successUrl,
        string $failedUrl,
        string $mid,
        string $key,
        string $paymentUrl,
    ): array {
        if ($mid === '' || $key === '') {
            throw new RuntimeException('EuPlătesc nu este configurat (MID / KEY).');
        }

        $amountFmt = number_format($amount, 2, '.', '');
        $currency = strtoupper($currency);
        if ($currency === 'EURO') {
            $currency = 'EUR';
        }

        $data = [
            'amount' => $amountFmt,
            'curr' => $currency,
            'invoice_id' => $invoiceId,
            'order_desc' => $description,
            'merch_id' => $mid,
            'timestamp' => now()->format('YmdHis'),
            'nonce' => bin2hex(random_bytes(16)),
        ];
        $data['fp_hash'] = strtoupper($this->mac($data, $key));

        $parts = preg_split('/\s+/', trim($name), 2) ?: [];
        $fname = $parts[0] ?? 'Client';
        $lname = $parts[1] ?? '-';

        $fields = array_merge($data, [
            'fname' => $fname,
            'lname' => $lname,
            'company' => $name,
            'add' => $address,
            'city' => $city,
            'state' => $county,
            'zip' => '000000',
            'country' => 'Romania',
            'phone' => $phone,
            'email' => $email,
            'ExtraData[silenturl]' => $silentUrl,
            'ExtraData[successurl]' => $successUrl,
            'ExtraData[failedurl]' => $failedUrl,
            'lang' => 'ro',
        ]);

        return [
            'url' => $paymentUrl,
            'fields' => $fields,
        ];
    }

    /**
     * Silent / return callback — validează fp_hash și marchează plata.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(array $payload): bool
    {
        $invoiceId = (string) ($payload['invoice_id'] ?? $payload['InvoiceID'] ?? '');
        if ($invoiceId === '') {
            return false;
        }

        $hash = (string) ($payload['fp_hash'] ?? '');
        if ($hash === '') {
            return false;
        }

        $macData = [];
        foreach (['amount', 'curr', 'invoice_id', 'ep_id', 'merch_id', 'action', 'message', 'approval', 'timestamp', 'nonce'] as $field) {
            if (array_key_exists($field, $payload)) {
                $macData[$field] = (string) $payload[$field];
            }
        }

        $action = strtolower((string) ($payload['action'] ?? $payload['epstatus'] ?? ''));
        $approved = in_array($action, ['0', '00', 'approved'], true)
            || (($payload['message'] ?? '') === 'Approved');

        $ref = (string) ($payload['ep_id'] ?? $payload['approval'] ?? $invoiceId);

        if (str_starts_with($invoiceId, 'DF-')) {
            $docs = app(DocumentCardPaymentService::class);
            $checkout = $docs->findByCheckoutNumber($invoiceId);
            if (! $checkout) {
                return false;
            }
            $checkout->loadMissing('company');
            $company = $checkout->company;
            $key = $company ? (string) $this->integrations->get($company, 'euplatesc', 'key', '') : '';
            if ($key !== '' && $macData !== []) {
                $expected = strtoupper($this->mac($macData, $key));
                if (! hash_equals($expected, strtoupper($hash)) && ! $approved) {
                    return false;
                }
            }
            if ($approved) {
                $docs->markPaid($checkout, $ref);

                return true;
            }
            $docs->markFailed($checkout, 'EuPlătesc: '.((string) ($payload['message'] ?? $action ?: 'failed')));

            return false;
        }

        if (! $this->isConfigured()) {
            return false;
        }

        if ($macData !== []) {
            $expected = strtoupper($this->mac($macData, (string) config('euplatesc.key')));
            if (! hash_equals($expected, strtoupper($hash)) && ! $approved) {
                return false;
            }
        }

        $order = SubscriptionOrder::query()->where('number', $invoiceId)->first();
        if (! $order) {
            return false;
        }

        if ($approved) {
            $this->orders->markPaid($order, $ref);

            return true;
        }

        if (! $order->isPaid()) {
            $order->forceFill([
                'status' => 'failed',
                'netopia_error' => 'EuPlătesc: '.((string) ($payload['message'] ?? $action ?: 'failed')),
            ])->save();
        }

        return false;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function mac(array $data, string $key): string
    {
        $str = '';
        foreach ($data as $value) {
            $value = (string) $value;
            $str .= strlen($value).$value;
        }

        $binKey = pack('H*', preg_replace('/\s+/', '', $key) ?? '');

        return hash_hmac('md5', $str, $binKey);
    }
}
