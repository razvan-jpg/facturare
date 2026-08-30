<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCardPayment;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService
{
    public function __construct(
        private SubscriptionOrderService $orders,
        private CompanyIntegrations $integrations,
    ) {}

    public function isConfigured(): bool
    {
        return (bool) config('stripe.enabled')
            && filled(config('stripe.secret'))
            && filled(config('stripe.key'));
    }

    public function client(): StripeClient
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe nu este configurat (STRIPE_KEY / STRIPE_SECRET).');
        }

        return new StripeClient((string) config('stripe.secret'));
    }

    public function clientForCompany(Company $company): StripeClient
    {
        if (! $this->integrations->isStripeReady($company)) {
            throw new RuntimeException('Stripe nu este configurat pentru această firmă.');
        }

        return new StripeClient((string) $this->integrations->get($company, 'stripe', 'secret'));
    }

    public function createCheckout(SubscriptionOrder $order): string
    {
        $order->loadMissing(['user', 'company']);
        $user = $order->user;
        if (! $user) {
            throw new RuntimeException('Comanda nu are utilizator.');
        }

        $breakdown = $this->orders->priceBreakdown($order->period_key);
        $customerId = $this->ensureCustomer($user, $order);
        $mode = $order->recurring ? 'subscription' : 'payment';
        $amountCents = (int) round(((float) $breakdown['amount_total']) * 100);
        $currency = strtolower((string) $breakdown['currency']);

        $params = [
            'mode' => $mode,
            'customer' => $customerId,
            'client_reference_id' => $order->number,
            'success_url' => route('billing.stripe.return', ['order' => $order->number]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.order', $order->company_id),
            'metadata' => [
                'kind' => 'subscription',
                'order_number' => $order->number,
                'period_key' => $order->period_key,
                'company_id' => (string) $order->company_id,
                'user_id' => (string) $order->user_id,
            ],
            'locale' => 'ro',
            'allow_promotion_codes' => false,
        ];

        if ($mode === 'subscription') {
            $params['line_items'] = [
                ['price' => $this->recurringPriceId($order->period_key, $breakdown), 'quantity' => 1],
            ];
            $params['subscription_data'] = [
                'metadata' => [
                    'kind' => 'subscription',
                    'order_number' => $order->number,
                    'period_key' => $order->period_key,
                    'user_id' => (string) $order->user_id,
                ],
            ];
        } else {
            $params['line_items'] = [[
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amountCents,
                    'tax_behavior' => 'inclusive',
                    'product_data' => [
                        'name' => (string) config('dateconta.subscription.product_name', 'DateConta Facturare').' — '.$breakdown['label'],
                        'description' => 'Abonament platformă · net '.number_format((float) $breakdown['amount_net'], 2, '.', '').
                            ' + TVA '.number_format((float) $breakdown['amount_vat'], 2, '.', '').' '.$breakdown['currency'],
                        'metadata' => ['period_key' => $order->period_key],
                    ],
                ],
                'quantity' => 1,
            ]];
            $params['payment_intent_data'] = [
                'metadata' => [
                    'kind' => 'subscription',
                    'order_number' => $order->number,
                ],
            ];
        }

        $session = $this->client()->checkout->sessions->create($params);

        $order->forceFill([
            'payment_processor' => 'stripe',
            'stripe_session_id' => $session->id,
        ])->save();

        if (! filled($session->url)) {
            throw new RuntimeException('Stripe Checkout nu a returnat URL.');
        }

        return (string) $session->url;
    }

    public function createDocumentCheckout(DocumentCardPayment $checkout, Document $document): string
    {
        $document->loadMissing(['company', 'client']);
        $company = $document->company;
        if (! $company || ! $this->integrations->isStripeReady($company)) {
            throw new RuntimeException('Stripe nu este configurat pentru această firmă.');
        }

        $amountCents = (int) round(((float) $checkout->amount) * 100);
        if ($amountCents < 1) {
            throw new RuntimeException('Sumă invalidă pentru plata Stripe.');
        }

        $currency = strtolower((string) $checkout->currency ?: 'ron');
        $params = [
            'mode' => 'payment',
            'client_reference_id' => $checkout->checkout_number,
            'success_url' => route('documents.pay.return', ['checkout' => $checkout->checkout_number]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => URL::signedRoute('documents.pay.show', ['document' => $document->id]),
            'locale' => 'ro',
            'metadata' => [
                'kind' => 'document',
                'checkout_number' => $checkout->checkout_number,
                'document_id' => (string) $document->id,
                'company_id' => (string) $company->id,
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amountCents,
                    'tax_behavior' => 'inclusive',
                    'product_data' => [
                        'name' => 'Plată '.$document->number_full,
                        'description' => ($company->name ?? 'Factură').' · '.$checkout->checkout_number,
                    ],
                ],
                'quantity' => 1,
            ]],
            'payment_intent_data' => [
                'metadata' => [
                    'kind' => 'document',
                    'checkout_number' => $checkout->checkout_number,
                    'document_id' => (string) $document->id,
                    'company_id' => (string) $company->id,
                ],
            ],
        ];

        $email = trim((string) ($document->client?->email ?? ''));
        if ($email !== '') {
            $params['customer_email'] = $email;
        }

        $session = $this->clientForCompany($company)->checkout->sessions->create($params);

        $checkout->forceFill([
            'external_ref' => $session->id,
        ])->save();

        if (! filled($session->url)) {
            throw new RuntimeException('Stripe Checkout nu a returnat URL.');
        }

        return (string) $session->url;
    }

    public function syncSession(?string $sessionId, ?SubscriptionOrder $order = null): ?SubscriptionOrder
    {
        if (! filled($sessionId)) {
            return $order;
        }

        // Document checkout (chei firmă) — înainte de abonament platformă.
        $docCheckout = DocumentCardPayment::query()
            ->where('processor', 'stripe')
            ->where(function ($q) use ($sessionId) {
                $q->where('external_ref', $sessionId)
                    ->orWhere('checkout_number', $sessionId);
            })
            ->first();

        if ($docCheckout) {
            $this->syncDocumentSession($sessionId, $docCheckout);

            return $order;
        }

        if (! $this->isConfigured()) {
            return $order;
        }

        $session = $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent', 'subscription'],
        ]);

        $kind = (string) ($session->metadata['kind'] ?? '');
        if ($kind === 'document') {
            $this->syncDocumentSession($sessionId);

            return $order;
        }

        $orderNumber = $session->client_reference_id
            ?: ($session->metadata['order_number'] ?? null);

        if (! $order && filled($orderNumber)) {
            $order = SubscriptionOrder::query()->where('number', $orderNumber)->first();
        }

        if (! $order) {
            return null;
        }

        return $this->fulfillFromSession($order, $session);
    }

    public function syncDocumentSession(?string $sessionId, ?DocumentCardPayment $checkout = null): ?DocumentCardPayment
    {
        if (! filled($sessionId)) {
            return $checkout;
        }

        if (! $checkout) {
            $checkout = DocumentCardPayment::query()
                ->where('processor', 'stripe')
                ->where('external_ref', $sessionId)
                ->first();
        }

        if (! $checkout) {
            // Fallback: session metadata after retrieve with platform client (same account).
            if ($this->isConfigured()) {
                try {
                    $probe = $this->client()->checkout->sessions->retrieve($sessionId);
                    $checkoutNumber = (string) ($probe->metadata['checkout_number'] ?? $probe->client_reference_id ?? '');
                    if ($checkoutNumber !== '') {
                        $checkout = DocumentCardPayment::query()->where('checkout_number', $checkoutNumber)->first();
                    }
                } catch (\Throwable) {
                    // ignore — try company keys below if checkout known
                }
            }
        }

        if (! $checkout) {
            return null;
        }

        $checkout->loadMissing('company');
        $company = $checkout->company;
        if (! $company) {
            return $checkout;
        }

        try {
            $session = $this->clientForCompany($company)->checkout->sessions->retrieve($sessionId, [
                'expand' => ['payment_intent'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe document session sync failed', [
                'checkout' => $checkout->checkout_number,
                'error' => $e->getMessage(),
            ]);

            return $checkout;
        }

        $paid = ($session->payment_status ?? null) === 'paid'
            || ($session->status ?? null) === 'complete';

        $checkout->forceFill(['external_ref' => $session->id])->save();

        $docs = app(DocumentCardPaymentService::class);
        if ($paid) {
            $pi = is_string($session->payment_intent ?? null)
                ? $session->payment_intent
                : ($session->payment_intent->id ?? $session->id);

            return $docs->markPaid($checkout, (string) $pi);
        }

        return $checkout->fresh();
    }

    public function handleWebhook(string $payload, string $signatureHeader): void
    {
        $event = $this->constructWebhookEvent($payload, $signatureHeader);

        $type = (string) $event->type;
        $object = $event->data->object ?? null;

        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $sessionId = $object->id ?? null;
            $kind = (string) ($object->metadata->kind ?? $object->metadata['kind'] ?? '');
            $checkoutNumber = (string) ($object->metadata->checkout_number
                ?? $object->metadata['checkout_number']
                ?? $object->client_reference_id
                ?? '');

            if ($kind === 'document' || str_starts_with($checkoutNumber, 'DF-')) {
                if (filled($sessionId)) {
                    $checkout = $checkoutNumber !== ''
                        ? DocumentCardPayment::query()->where('checkout_number', $checkoutNumber)->first()
                        : null;
                    $this->syncDocumentSession((string) $sessionId, $checkout);
                }

                return;
            }

            if (filled($sessionId)) {
                $this->syncSession((string) $sessionId);
            }

            return;
        }

        if ($type === 'invoice.paid') {
            $this->handleInvoicePaid($object);
        }
    }

    private function constructWebhookEvent(string $payload, string $signatureHeader): object
    {
        $secrets = [];
        $platform = trim((string) config('stripe.webhook_secret'));
        if ($platform !== '') {
            $secrets[] = $platform;
        }

        // Încearcă secretul firmei din metadata (cont Stripe separat).
        $data = json_decode($payload, true);
        $companyId = null;
        if (is_array($data)) {
            $companyId = $data['data']['object']['metadata']['company_id'] ?? null;
        }
        if (filled($companyId)) {
            $company = Company::query()->find($companyId);
            if ($company) {
                $companySecret = trim((string) $this->integrations->get($company, 'stripe', 'webhook_secret', ''));
                if ($companySecret !== '' && ! in_array($companySecret, $secrets, true)) {
                    $secrets[] = $companySecret;
                }
            }
        }

        if ($secrets === []) {
            if (! is_array($data) || empty($data['type'])) {
                throw new RuntimeException('Payload webhook Stripe invalid.');
            }
            Log::warning('Stripe webhook without signing secret — signature not verified');

            return \Stripe\Event::constructFrom($data);
        }

        $last = null;
        foreach ($secrets as $secret) {
            try {
                return Webhook::constructEvent($payload, $signatureHeader, $secret);
            } catch (SignatureVerificationException $e) {
                $last = $e;
            }
        }

        throw new RuntimeException('Semnătură webhook Stripe invalidă: '.($last?->getMessage() ?? ''), 0, $last);
    }

    private function fulfillFromSession(SubscriptionOrder $order, Session $session): SubscriptionOrder
    {
        $paid = ($session->payment_status ?? null) === 'paid'
            || ($session->status ?? null) === 'complete';

        if (! $paid) {
            return $order;
        }

        $updates = [
            'stripe_session_id' => $session->id,
            'payment_processor' => 'stripe',
        ];

        if (filled($session->subscription)) {
            $subId = is_string($session->subscription)
                ? $session->subscription
                : ($session->subscription->id ?? null);
            if ($subId) {
                $updates['stripe_subscription_id'] = $subId;
            }
        }

        if (filled($session->payment_intent)) {
            $pi = is_string($session->payment_intent)
                ? $session->payment_intent
                : ($session->payment_intent->id ?? null);
            if ($pi) {
                $updates['stripe_payment_intent'] = $pi;
            }
        }

        if (filled($session->customer) && $order->user) {
            $customerId = is_string($session->customer)
                ? $session->customer
                : ($session->customer->id ?? null);
            if ($customerId && blank($order->user->stripe_customer_id)) {
                $order->user->forceFill(['stripe_customer_id' => $customerId])->save();
            }
        }

        $order->forceFill($updates)->save();

        $ref = $updates['stripe_payment_intent']
            ?? $updates['stripe_subscription_id']
            ?? $session->id;

        return $this->orders->markPaid($order->fresh(), (string) $ref);
    }

    private function handleInvoicePaid(object $invoice): void
    {
        $billingReason = (string) ($invoice->billing_reason ?? '');
        $subscriptionId = (string) ($invoice->subscription ?? '');

        if ($billingReason === 'subscription_create') {
            return;
        }

        if ($billingReason !== 'subscription_cycle' || $subscriptionId === '') {
            $orderNumber = $invoice->metadata->order_number
                ?? ($invoice->metadata['order_number'] ?? null);
            if (filled($orderNumber)) {
                $order = SubscriptionOrder::query()->where('number', $orderNumber)->first();
                if ($order && ! $order->isPaid()) {
                    $this->orders->markPaid($order, (string) ($invoice->payment_intent ?? $invoice->id));
                }
            }

            return;
        }

        $template = SubscriptionOrder::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->where('status', 'paid')
            ->latest('paid_at')
            ->first();

        if (! $template) {
            Log::warning('Stripe invoice.paid without local template', [
                'subscription' => $subscriptionId,
                'invoice' => $invoice->id ?? null,
            ]);

            return;
        }

        $pi = (string) ($invoice->payment_intent ?? '');
        if ($pi !== '' && SubscriptionOrder::query()->where('stripe_payment_intent', $pi)->where('status', 'paid')->exists()) {
            return;
        }

        $renewal = $this->orders->createPending(
            $template->user,
            $template->company,
            $template->period_key,
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
            'stripe',
        );

        $renewal->forceFill([
            'stripe_subscription_id' => $subscriptionId,
            'stripe_payment_intent' => $pi !== '' ? $pi : null,
        ])->save();

        $this->orders->markPaid($renewal, $pi !== '' ? $pi : (string) $invoice->id);
    }

    private function ensureCustomer(User $user, SubscriptionOrder $order): string
    {
        $existing = trim((string) $user->stripe_customer_id);
        $stripe = $this->client();

        if ($existing !== '') {
            try {
                $customer = $stripe->customers->retrieve($existing);
                if (! ($customer->deleted ?? false)) {
                    return $existing;
                }
            } catch (InvalidRequestException $e) {
                // ID din test / alt cont — recreăm pe mediul curent (live).
                Log::info('Stripe customer missing, recreating', [
                    'user_id' => $user->id,
                    'old_customer' => $existing,
                    'error' => $e->getMessage(),
                ]);
            } catch (ApiErrorException $e) {
                Log::warning('Stripe customer retrieve failed, recreating', [
                    'user_id' => $user->id,
                    'old_customer' => $existing,
                    'error' => $e->getMessage(),
                ]);
            }

            $user->forceFill(['stripe_customer_id' => null])->save();
        }

        $customer = $stripe->customers->create([
            'email' => $order->billing_email ?: $user->email,
            'name' => $order->billing_name ?: $user->name,
            'metadata' => [
                'user_id' => (string) $user->id,
                'company_id' => (string) $order->company_id,
            ],
        ]);

        $user->forceFill(['stripe_customer_id' => $customer->id])->save();

        return $customer->id;
    }

    /**
     * @param  array<string, mixed>  $breakdown
     */
    private function recurringPriceId(string $periodKey, array $breakdown): string
    {
        $configured = (string) (config('stripe.price_ids.'.$periodKey) ?: '');
        if ($configured !== '') {
            return $configured;
        }

        $lookup = 'dateconta_'.$periodKey.'_gross_v1';
        $amountCents = (int) round(((float) $breakdown['amount_total']) * 100);
        $currency = strtolower((string) $breakdown['currency']);
        $stripe = $this->client();

        $existing = $stripe->prices->all([
            'lookup_keys' => [$lookup],
            'active' => true,
            'limit' => 1,
        ]);
        if (! empty($existing->data[0]?->id)) {
            return (string) $existing->data[0]->id;
        }

        $productName = (string) config('dateconta.subscription.product_name', 'DateConta Facturare');
        $productId = null;
        try {
            $search = $stripe->products->search([
                'query' => "active:'true' AND name:'".str_replace("'", "\\'", $productName)."'",
                'limit' => 1,
            ]);
            $productId = $search->data[0]->id ?? null;
        } catch (\Throwable) {
            $productId = null;
        }

        if (! $productId) {
            $listed = $stripe->products->all(['limit' => 20, 'active' => true]);
            foreach ($listed->data as $p) {
                if (($p->name ?? '') === $productName) {
                    $productId = $p->id;
                    break;
                }
            }
        }

        if (! $productId) {
            $product = $stripe->products->create([
                'name' => $productName,
                'metadata' => ['app' => 'dateconta-facturare'],
            ]);
            $productId = $product->id;
        }

        $recurring = match ($periodKey) {
            '1m' => ['interval' => 'month', 'interval_count' => 1],
            '3m' => ['interval' => 'month', 'interval_count' => 3],
            '6m' => ['interval' => 'month', 'interval_count' => 6],
            '1y' => ['interval' => 'year', 'interval_count' => 1],
            default => ['interval' => 'month', 'interval_count' => 1],
        };

        $price = $stripe->prices->create([
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => $amountCents,
            'lookup_key' => $lookup,
            'transfer_lookup_key' => true,
            'recurring' => $recurring,
            'tax_behavior' => 'inclusive',
            'nickname' => $breakdown['label'].' (TVA inclus)',
            'metadata' => [
                'period_key' => $periodKey,
                'amount_net' => (string) $breakdown['amount_net'],
                'amount_vat' => (string) $breakdown['amount_vat'],
            ],
        ]);

        return $price->id;
    }
}
