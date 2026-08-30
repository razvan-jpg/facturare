<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCardPayment;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use RuntimeException;
use Throwable;

/**
 * Integrare Mollie Payments — redirect la checkout, confirmare via webhook.
 * Recurring: customer + sequenceType first, apoi charge fără interacțiune.
 *
 * @see https://github.com/mollie/mollie-api-php
 */
class MolliePaymentService
{
    public function __construct(
        private SubscriptionOrderService $orders,
        private CompanyIntegrations $integrations,
    ) {}

    /** Configurat pentru abonamentul DateConta (platformă). */
    public function isConfigured(): bool
    {
        if (! config('mollie.enabled')) {
            return false;
        }

        $key = trim((string) config('mollie.key'));

        return $key !== '' && (str_starts_with($key, 'test_') || str_starts_with($key, 'live_'));
    }

    /**
     * Creează plata Mollie și returnează URL-ul de checkout.
     */
    public function createCheckout(SubscriptionOrder $order): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Mollie nu este configurat (MOLLIE_KEY / MOLLIE_ENABLED).');
        }

        $client = $this->client();
        $payload = [
            'amount' => [
                'currency' => strtoupper((string) $order->currency),
                'value' => number_format((float) $order->amount_total, 2, '.', ''),
            ],
            'description' => 'Abonament DateConta '.$order->number,
            'redirectUrl' => route('billing.mollie.return', ['order' => $order->number]),
            'webhookUrl' => route('billing.mollie.webhook'),
            'metadata' => [
                'kind' => 'subscription',
                'order_number' => $order->number,
                'order_id' => (string) $order->id,
                'recurring' => $order->recurring ? '1' : '0',
            ],
        ];

        $methods = config('mollie.methods');
        if (is_array($methods) && $methods !== []) {
            $payload['method'] = $methods;
        }

        if ($order->recurring) {
            $user = $order->user ?: User::query()->find($order->user_id);
            if (! $user) {
                throw new RuntimeException('Utilizatorul comenzii lipsește.');
            }

            $payload['customerId'] = $this->ensureCustomer($client, $user, $order);
            $payload['sequenceType'] = 'first';
            // Mandate card: forțează creditcard dacă nu e deja setat un set de metode.
            if (empty($payload['method'])) {
                $payload['method'] = 'creditcard';
            }
        }

        /** @var Payment $payment */
        $payment = $client->payments->create($payload);

        $order->forceFill([
            'mollie_payment_id' => $payment->id,
            'payment_processor' => 'mollie',
            'netopia_error' => null,
        ])->save();

        $checkout = $payment->getCheckoutUrl();
        if (! $checkout) {
            throw new RuntimeException('Mollie nu a returnat URL de checkout.');
        }

        return $checkout;
    }

    public function createDocumentCheckout(DocumentCardPayment $checkout, Document $document): string
    {
        $document->loadMissing('company');
        $company = $document->company;
        if (! $company || ! $this->integrations->isMollieReady($company)) {
            throw new RuntimeException('Mollie nu este configurat pentru această firmă.');
        }

        $payload = [
            'amount' => [
                'currency' => strtoupper((string) $checkout->currency),
                'value' => number_format((float) $checkout->amount, 2, '.', ''),
            ],
            'description' => 'Plată '.$document->number_full,
            'redirectUrl' => route('documents.pay.return', ['checkout' => $checkout->checkout_number]),
            'webhookUrl' => route('plata.mollie.webhook'),
            'metadata' => [
                'kind' => 'document',
                'checkout_number' => $checkout->checkout_number,
                'document_id' => (string) $document->id,
                'company_id' => (string) $company->id,
            ],
        ];

        /** @var Payment $payment */
        $payment = $this->clientForCompany($company)->payments->create($payload);

        $checkout->forceFill([
            'mollie_payment_id' => $payment->id,
            'external_ref' => $payment->id,
        ])->save();

        $url = $payment->getCheckoutUrl();
        if (! $url) {
            throw new RuntimeException('Mollie nu a returnat URL de checkout.');
        }

        return $url;
    }

    /**
     * Debitare recurentă (fără redirect) pe baza unui mandat valid.
     */
    public function chargeRecurring(SubscriptionOrder $template): SubscriptionOrder
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Mollie neconfigurat.');
        }

        $user = $template->user ?: User::query()->findOrFail($template->user_id);
        $customerId = trim((string) $user->mollie_customer_id);
        if ($customerId === '') {
            throw new RuntimeException('Lipsește Mollie customer pentru plată recurentă.');
        }

        $company = $template->company;
        if (! $company) {
            throw new RuntimeException('Societatea comenzii lipsește.');
        }

        $order = $this->orders->createPending(
            $user,
            $company,
            (string) $template->period_key,
            'card',
            [
                'name' => $template->billing_name,
                'cui' => $template->billing_cui,
                'phone' => $template->billing_phone,
                'email' => $template->billing_email,
                'address' => $template->billing_address,
                'city' => $template->billing_city,
                'county' => $template->billing_county,
            ],
            true,
            'mollie',
        );

        $client = $this->client();
        $payload = [
            'amount' => [
                'currency' => strtoupper((string) $order->currency),
                'value' => number_format((float) $order->amount_total, 2, '.', ''),
            ],
            'description' => 'Reînnoire DateConta '.$order->number,
            'redirectUrl' => route('billing.mollie.return', ['order' => $order->number]),
            'webhookUrl' => route('billing.mollie.webhook'),
            'customerId' => $customerId,
            'sequenceType' => 'recurring',
            'metadata' => [
                'order_number' => $order->number,
                'order_id' => (string) $order->id,
                'recurring' => '1',
                'renewal_of' => $template->number,
            ],
        ];

        try {
            /** @var Payment $payment */
            $payment = $client->payments->create($payload);
        } catch (Throwable $e) {
            $order->forceFill([
                'status' => 'failed',
                'netopia_error' => 'Mollie recurring: '.$e->getMessage(),
            ])->save();
            throw $e;
        }

        $order->forceFill([
            'mollie_payment_id' => $payment->id,
            'payment_processor' => 'mollie',
        ])->save();

        if ($payment->isPaid()) {
            $this->orders->markPaid($order, $payment->id);
        }

        return $order->fresh();
    }

    /**
     * Webhook Mollie: primește id-ul plății, reîncarcă statusul și marchează comanda.
     */
    public function handleWebhook(?string $paymentId): void
    {
        if (! $paymentId) {
            return;
        }

        $docs = app(DocumentCardPaymentService::class);
        $checkout = $docs->findByMollieId($paymentId);
        if ($checkout) {
            $checkout->loadMissing('company');
            $company = $checkout->company;
            if (! $company || ! $this->integrations->isMollieReady($company)) {
                throw new RuntimeException('Mollie neconfigurat pentru firmă.');
            }
            $payment = $this->clientForCompany($company)->payments->get($paymentId);
            $this->syncPayment($payment);

            return;
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('Mollie neconfigurat.');
        }

        $payment = $this->client()->payments->get($paymentId);
        $this->syncPayment($payment);
    }

    /**
     * Sincronizare la return (browser) — idempotentă.
     */
    public function syncOrder(SubscriptionOrder $order): SubscriptionOrder
    {
        if (! $order->mollie_payment_id || ! $this->isConfigured()) {
            return $order;
        }

        try {
            $payment = $this->client()->payments->get($order->mollie_payment_id);
            $this->syncPayment($payment);
        } catch (Throwable) {
            // return page should still load even if Mollie is briefly unavailable
        }

        return $order->fresh();
    }

    private function ensureCustomer(MollieApiClient $client, User $user, SubscriptionOrder $order): string
    {
        $existing = trim((string) $user->mollie_customer_id);
        if ($existing !== '') {
            return $existing;
        }

        $customer = $client->customers->create([
            'name' => (string) ($order->billing_name ?: $user->name),
            'email' => (string) ($order->billing_email ?: $user->email),
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ]);

        $user->forceFill(['mollie_customer_id' => $customer->id])->save();

        return (string) $customer->id;
    }

    private function syncPayment(Payment $payment): void
    {
        $meta = $payment->metadata ?? null;
        $kind = '';
        $orderNumber = '';
        $checkoutNumber = '';
        if (is_object($meta)) {
            $kind = (string) ($meta->kind ?? '');
            $orderNumber = (string) ($meta->order_number ?? '');
            $checkoutNumber = (string) ($meta->checkout_number ?? '');
        } elseif (is_array($meta)) {
            $kind = (string) ($meta['kind'] ?? '');
            $orderNumber = (string) ($meta['order_number'] ?? '');
            $checkoutNumber = (string) ($meta['checkout_number'] ?? '');
        }

        if ($kind === 'document' || $checkoutNumber !== '' || str_starts_with($checkoutNumber, 'DF-')) {
            $docs = app(DocumentCardPaymentService::class);
            $checkout = $checkoutNumber !== ''
                ? $docs->findByCheckoutNumber($checkoutNumber)
                : $docs->findByMollieId($payment->id);
            if (! $checkout) {
                $checkout = $docs->findByMollieId($payment->id);
            }
            if (! $checkout) {
                return;
            }
            $checkout->forceFill(['mollie_payment_id' => $payment->id])->save();
            if ($payment->isPaid() && ! $payment->hasRefunds() && ! $payment->hasChargebacks()) {
                $docs->markPaid($checkout, $payment->id);

                return;
            }
            if ($payment->isCanceled() || $payment->isExpired() || $payment->isFailed()) {
                $docs->markFailed($checkout, 'Mollie: '.$payment->status);
            }

            return;
        }

        $order = $orderNumber !== ''
            ? SubscriptionOrder::query()->where('number', $orderNumber)->first()
            : SubscriptionOrder::query()->where('mollie_payment_id', $payment->id)->first();

        if (! $order) {
            // Fallback: checkout document după mollie id
            $docs = app(DocumentCardPaymentService::class);
            $checkout = $docs->findByMollieId($payment->id);
            if ($checkout) {
                if ($payment->isPaid() && ! $payment->hasRefunds() && ! $payment->hasChargebacks()) {
                    $docs->markPaid($checkout, $payment->id);
                }

                return;
            }

            return;
        }

        $order->forceFill(['mollie_payment_id' => $payment->id])->save();

        // Salvează customerId din plată, dacă există.
        if (! empty($payment->customerId) && $order->user) {
            $user = $order->user;
            if (! $user->mollie_customer_id) {
                $user->forceFill(['mollie_customer_id' => $payment->customerId])->save();
            }
        }

        if ($payment->isPaid() && ! $payment->hasRefunds() && ! $payment->hasChargebacks()) {
            $this->orders->markPaid($order, $payment->id);

            return;
        }

        if ($payment->isCanceled() || $payment->isExpired() || $payment->isFailed()) {
            if (! $order->isPaid() && $order->status !== 'awaiting_op') {
                $order->forceFill([
                    'status' => 'failed',
                    'netopia_error' => 'Mollie: '.$payment->status,
                ])->save();
            }
        }
    }

    private function client(): MollieApiClient
    {
        $client = new MollieApiClient;
        $client->setApiKey(trim((string) config('mollie.key')));

        return $client;
    }

    private function clientForCompany(Company $company): MollieApiClient
    {
        $key = trim((string) $this->integrations->get($company, 'mollie', 'key', ''));
        if ($key === '') {
            throw new RuntimeException('Lipsește cheia Mollie a firmei.');
        }
        $client = new MollieApiClient;
        $client->setApiKey($key);

        return $client;
    }
}
