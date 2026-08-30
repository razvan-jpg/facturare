<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SubscriptionOrder;
use App\Services\AccessGate;
use App\Services\EuPlatescPaymentService;
use App\Services\MolliePaymentService;
use App\Services\NetopiaPaymentService;
use App\Services\StripePaymentService;
use App\Services\SubscriptionOrderService;
use App\Services\SubuserSeatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function expired(): View
    {
        return view('billing.expired');
    }

    public function order(
        Company $company,
        AccessGate $accessGate,
        SubscriptionOrderService $orders,
        NetopiaPaymentService $netopia,
        MolliePaymentService $mollie,
        EuPlatescPaymentService $euplatesc,
        StripePaymentService $stripe,
    ): View {
        $this->authorizeCompany($company);

        $owner = $company->owner;
        $until = $owner ? $accessGate->effectiveAccessUntil($owner) : null;
        $summary = $owner ? $accessGate->subscriptionSummary($owner) : null;
        $periods = [];
        foreach (array_keys($orders->periods()) as $key) {
            $periods[$key] = $orders->priceBreakdown($key);
        }

        $stripeReady = $stripe->isConfigured();
        $netopiaReady = $netopia->isConfigured();
        $mollieReady = $mollie->isConfigured();
        $euplatescReady = $euplatesc->isConfigured();
        $cardReady = $stripeReady || $netopiaReady || $mollieReady || $euplatescReady;

        $defaultProcessor = old('payment_processor');
        if (! $defaultProcessor) {
            // Preferință RO: NETOPIA → Eu Plătesc → Mollie → Stripe
            $defaultProcessor = $netopiaReady
                ? 'netopia'
                : ($euplatescReady ? 'euplatesc' : ($mollieReady ? 'mollie' : ($stripeReady ? 'stripe' : 'netopia')));
        }

        $netopiaRonByPeriod = [];
        if ($netopiaReady) {
            try {
                $fx = app(\App\Services\ExchangeRateService::class);
                foreach ($periods as $key => $p) {
                    $converted = $fx->convertSubscriptionAmountsToRon(
                        (float) $p['amount_net'],
                        (float) $p['amount_vat'],
                        (float) $p['amount_total'],
                        (string) $p['currency'],
                        (float) $p['vat_rate'],
                    );
                    $netopiaRonByPeriod[$key] = [
                        'amount_total' => $converted['amount_total'],
                        'currency' => 'RON',
                        'fx_rate' => $converted['fx_rate'],
                        'fx_bnr' => $converted['fx_bnr'],
                    ];
                }
            } catch (\Throwable) {
                $netopiaRonByPeriod = [];
            }
        }

        return view('billing.order', [
            'company' => $company,
            'until' => $until,
            'summary' => $summary,
            'periods' => $periods,
            'netopiaRonByPeriod' => $netopiaRonByPeriod,
            'stripeReady' => $stripeReady,
            'netopiaReady' => $netopiaReady,
            'mollieReady' => $mollieReady,
            'euplatescReady' => $euplatescReady,
            'cardReady' => $cardReady,
            'defaultProcessor' => $defaultProcessor,
            'operator' => config('dateconta.platform_operator'),
        ]);
    }

    public function seatsOrder(
        Company $company,
        SubuserSeatService $seats,
        NetopiaPaymentService $netopia,
        MolliePaymentService $mollie,
        EuPlatescPaymentService $euplatesc,
        StripePaymentService $stripe,
    ): View|RedirectResponse {
        $this->authorizeCompany($company);
        abort_unless(auth()->user()?->canManageCompanyUsers(), 403);

        if ($seats->seatsExemptForOwner(auth()->user())) {
            return redirect()
                ->route('company-users.index')
                ->with('status', 'Contul de administrator nu necesită abonament de locuri — subuserii și invitații sunt nelimitați.');
        }

        $periodsMeta = [];
        foreach (array_keys($seats->periods()) as $key) {
            // Breakdown de bază pentru 1 loc — UI recalculează după nr. locuri.
            $periodsMeta[$key] = $seats->priceBreakdown($key, 1);
        }

        $stripeReady = $stripe->isConfigured();
        $netopiaReady = $netopia->isConfigured();
        $mollieReady = $mollie->isConfigured();
        $euplatescReady = $euplatesc->isConfigured();
        $cardReady = $stripeReady || $netopiaReady || $mollieReady || $euplatescReady;
        $defaultProcessor = old('payment_processor') ?: ($netopiaReady
            ? 'netopia'
            : ($euplatescReady ? 'euplatesc' : ($mollieReady ? 'mollie' : ($stripeReady ? 'stripe' : 'netopia'))));

        return view('billing.seats-order', [
            'company' => $company,
            'seatSummary' => $seats->summary(auth()->user()),
            'periods' => $periodsMeta,
            'pricePerSeatMonth' => $seats->pricePerSeatMonth(),
            'billableFrom' => $seats->billableFrom(),
            'stripeReady' => $stripeReady,
            'netopiaReady' => $netopiaReady,
            'mollieReady' => $mollieReady,
            'euplatescReady' => $euplatescReady,
            'cardReady' => $cardReady,
            'defaultProcessor' => $defaultProcessor,
            'operator' => config('dateconta.platform_operator'),
        ]);
    }

    public function placeSeatsOrder(
        Request $request,
        Company $company,
        SubscriptionOrderService $orders,
        SubuserSeatService $seats,
        NetopiaPaymentService $netopia,
        MolliePaymentService $mollie,
        EuPlatescPaymentService $euplatesc,
        StripePaymentService $stripe,
    ): RedirectResponse|View {
        $this->authorizeCompany($company);
        abort_unless($request->user()?->canManageCompanyUsers(), 403);

        if ($seats->seatsExemptForOwner($request->user())) {
            return redirect()
                ->route('company-users.index')
                ->with('status', 'Contul de administrator nu necesită abonament de locuri — subuserii și invitații sunt nelimitați.');
        }

        $periodKeys = array_keys($seats->periods());
        $data = $request->validate([
            'seats' => ['required', 'integer', 'min:1', 'max:100'],
            'period' => ['required', Rule::in($periodKeys)],
            'payment_method' => ['required', Rule::in(['card', 'op'])],
            'payment_processor' => [
                Rule::requiredIf(fn () => $request->input('payment_method') === 'card'),
                'nullable',
                Rule::in(['stripe', 'netopia', 'mollie', 'euplatesc']),
            ],
            'billing_name' => ['required', 'string', 'max:255'],
            'billing_cui' => ['nullable', 'string', 'max:32'],
            'billing_phone' => ['nullable', 'string', 'max:50'],
            'billing_email' => ['required', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_county' => ['nullable', 'string', 'max:100'],
            'terms' => ['accepted'],
        ]);

        return $this->dispatchOrderCheckout(
            $request,
            $company,
            $orders,
            $netopia,
            $mollie,
            $euplatesc,
            $stripe,
            $data,
            SubscriptionOrder::PRODUCT_SUBUSER_SEATS,
            (int) $data['seats'],
            'billing.seats',
        );
    }

    public function placeOrder(
        Request $request,
        Company $company,
        SubscriptionOrderService $orders,
        NetopiaPaymentService $netopia,
        MolliePaymentService $mollie,
        EuPlatescPaymentService $euplatesc,
        StripePaymentService $stripe,
    ): RedirectResponse|View {
        $this->authorizeCompany($company);

        $periodKeys = array_keys($orders->periods());
        $data = $request->validate([
            'period' => ['required', Rule::in($periodKeys)],
            'payment_method' => ['required', Rule::in(['card', 'op'])],
            'payment_processor' => [
                Rule::requiredIf(fn () => $request->input('payment_method') === 'card'),
                'nullable',
                Rule::in(['stripe', 'netopia', 'mollie', 'euplatesc']),
            ],
            'billing_name' => ['required', 'string', 'max:255'],
            'billing_cui' => ['nullable', 'string', 'max:32'],
            'billing_phone' => ['nullable', 'string', 'max:50'],
            'billing_email' => ['required', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_county' => ['nullable', 'string', 'max:100'],
            'recurring' => ['nullable', 'boolean'],
            'terms' => ['accepted'],
        ]);

        return $this->dispatchOrderCheckout(
            $request,
            $company,
            $orders,
            $netopia,
            $mollie,
            $euplatesc,
            $stripe,
            $data,
            SubscriptionOrder::PRODUCT_PLATFORM,
            0,
            'billing.order',
        );
    }

    private function dispatchOrderCheckout(
        Request $request,
        Company $company,
        SubscriptionOrderService $orders,
        NetopiaPaymentService $netopia,
        MolliePaymentService $mollie,
        EuPlatescPaymentService $euplatesc,
        StripePaymentService $stripe,
        array $data,
        string $productType,
        int $seats,
        string $backRoute,
    ): RedirectResponse|View {
        $processor = $data['payment_method'] === 'card' ? ($data['payment_processor'] ?? null) : null;
        $ready = [
            'stripe' => $stripe->isConfigured(),
            'netopia' => $netopia->isConfigured(),
            'mollie' => $mollie->isConfigured(),
            'euplatesc' => $euplatesc->isConfigured(),
        ];

        if ($data['payment_method'] === 'card') {
            if (! isset($ready[$processor]) || ! $ready[$processor]) {
                return redirect()->route($backRoute, $company)
                    ->withInput()
                    ->with('warning', 'Procesatorul selectat nu este activ. Alege alt procesator sau OP.');
            }
            if (! in_array(true, $ready, true)) {
                return redirect()->route($backRoute, $company)
                    ->with('warning', 'Plata cu cardul nu este încă activă. Alege OP sau contactează '.config('dateconta.contact_email').'.');
            }
        }

        $order = $orders->createPending(
            $request->user(),
            $company,
            $data['period'],
            $data['payment_method'],
            [
                'name' => $data['billing_name'],
                'cui' => $data['billing_cui'] ?? null,
                'phone' => $data['billing_phone'] ?? null,
                'email' => $data['billing_email'],
                'address' => $data['billing_address'] ?? null,
                'city' => $data['billing_city'] ?? null,
                'county' => $data['billing_county'] ?? null,
            ],
            $productType === SubscriptionOrder::PRODUCT_PLATFORM
                && $data['payment_method'] === 'card'
                && $request->boolean('recurring'),
            $processor,
            $productType,
            $seats,
        );

        if ($order->payment_method === 'op') {
            return redirect()->route('billing.op', $order)
                ->with('status', 'Comanda a fost înregistrată. Efectuează plata OP folosind datele de mai jos.');
        }

        if ($processor === 'stripe') {
            try {
                $checkoutUrl = $stripe->createCheckout($order);
            } catch (\Throwable $e) {
                $order->forceFill(['status' => 'failed', 'netopia_error' => $e->getMessage()])->save();

                return redirect()->route($backRoute, $company)
                    ->with('warning', 'Nu am putut iniția plata Stripe: '.$e->getMessage());
            }

            return view('billing.stripe-redirect', [
                'order' => $order,
                'checkoutUrl' => $checkoutUrl,
            ]);
        }

        if ($processor === 'mollie') {
            try {
                $checkoutUrl = $mollie->createCheckout($order);
            } catch (\Throwable $e) {
                $order->forceFill(['status' => 'failed', 'netopia_error' => $e->getMessage()])->save();

                return redirect()->route($backRoute, $company)
                    ->with('warning', 'Nu am putut iniția plata Mollie: '.$e->getMessage());
            }

            return view('billing.mollie-redirect', [
                'order' => $order,
                'checkoutUrl' => $checkoutUrl,
            ]);
        }

        if ($processor === 'euplatesc') {
            try {
                $form = $euplatesc->buildPaymentForm($order);
            } catch (\Throwable $e) {
                $order->forceFill(['status' => 'failed', 'netopia_error' => $e->getMessage()])->save();

                return redirect()->route($backRoute, $company)
                    ->with('warning', 'Nu am putut iniția plata Eu Plătesc: '.$e->getMessage());
            }

            return view('billing.euplatesc-redirect', [
                'order' => $order,
                'form' => $form,
            ]);
        }

        try {
            $form = $netopia->buildCardPaymentForm($order);
            $order->forceFill(['payment_processor' => 'netopia'])->save();
        } catch (\Throwable $e) {
            $order->forceFill(['status' => 'failed', 'netopia_error' => $e->getMessage()])->save();

            return redirect()->route($backRoute, $company)
                ->with('warning', 'Nu am putut iniția plata Netopia: '.$e->getMessage());
        }

        return view('billing.netopia-redirect', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    public function opPending(SubscriptionOrder $order): View
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);

        return view('billing.op-pending', [
            'order' => $order->load('company'),
            'operator' => config('dateconta.platform_operator'),
        ]);
    }

    public function netopiaConfirm(Request $request, NetopiaPaymentService $netopia): Response
    {
        $result = $netopia->handleConfirm(
            (string) $request->input('env_key', ''),
            (string) $request->input('data', ''),
            $request->input('iv'),
            $request->input('cipher'),
        );

        return response($result['xml'], 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function netopiaReturn(Request $request, string $order, NetopiaPaymentService $netopia): RedirectResponse
    {
        $model = SubscriptionOrder::query()->where('number', $order)->first();
        if (! $model) {
            return redirect()->route('login')
                ->with('warning', 'Comanda nu a fost găsită.');
        }

        try {
            $model = $netopia->syncSubscriptionOrder($model, $request);
        } catch (\Throwable $e) {
            report($e);
            $model->refresh();
        }

        $userId = auth()->id();
        if (! $userId || (int) $model->user_id !== (int) $userId) {
            return redirect()->route('login')
                ->with('status', $model->isPaid()
                    ? 'Plata a fost confirmată. Autentifică-te pentru a continua.'
                    : 'Te-ai întors de la Netopia. Autentifică-te pentru a vedea statusul comenzii.');
        }

        if ($model->isPaid()) {
            return redirect()->route('billing.success', $model)
                ->with('status', 'Plata a fost confirmată. Abonamentul a fost prelungit.');
        }

        if ($model->status === 'failed') {
            return redirect()->route('billing.order', $model->company_id)
                ->with('warning', 'Plata Netopia nu a fost finalizată. Poți încerca din nou.');
        }

        return redirect()->route('billing.success', $model)
            ->with('status', 'Am înregistrat întoarcerea de la Netopia. Dacă plata a reușit, abonamentul se activează în câteva momente.');
    }

    public function mollieWebhook(Request $request, MolliePaymentService $mollie): Response
    {
        try {
            $mollie->handleWebhook($request->input('id'));
        } catch (\Throwable $e) {
            report($e);

            return response('Error', 500);
        }

        return response('OK', 200);
    }

    public function mollieReturn(string $order, MolliePaymentService $mollie): RedirectResponse
    {
        $model = SubscriptionOrder::query()->where('number', $order)->first();
        if (! $model || (int) $model->user_id !== (int) auth()->id()) {
            return redirect()->route('companies.index', ['all' => 1])
                ->with('warning', 'Comanda nu a fost găsită.');
        }

        $model = $mollie->syncOrder($model);

        if ($model->isPaid()) {
            return redirect()->route('billing.success', $model)
                ->with('status', 'Plata a fost confirmată. Abonamentul a fost prelungit.');
        }

        if ($model->status === 'failed') {
            return redirect()->route('billing.order', $model->company_id)
                ->with('warning', 'Plata Mollie nu a fost finalizată. Poți încerca din nou.');
        }

        return redirect()->route('billing.success', $model)
            ->with('status', 'Am înregistrat întoarcerea de la Mollie. Dacă plata a reușit, abonamentul se activează în câteva momente (webhook).');
    }

    public function euplatescSilent(Request $request, EuPlatescPaymentService $euplatesc): Response
    {
        try {
            $euplatesc->handleCallback($request->all());
        } catch (\Throwable $e) {
            report($e);

            return response('Error', 500);
        }

        return response('OK', 200);
    }

    public function euplatescReturn(string $order, Request $request, EuPlatescPaymentService $euplatesc): RedirectResponse
    {
        $model = SubscriptionOrder::query()->where('number', $order)->first();
        if (! $model || (int) $model->user_id !== (int) auth()->id()) {
            return redirect()->route('companies.index', ['all' => 1])
                ->with('warning', 'Comanda nu a fost găsită.');
        }

        try {
            $euplatesc->handleCallback(array_merge($request->all(), ['invoice_id' => $order]));
            $model->refresh();
        } catch (\Throwable $e) {
            report($e);
        }

        if ($model->isPaid()) {
            return redirect()->route('billing.success', $model)
                ->with('status', 'Plata a fost confirmată. Abonamentul a fost prelungit.');
        }

        if ($model->status === 'failed') {
            return redirect()->route('billing.order', $model->company_id)
                ->with('warning', 'Plata Eu Plătesc nu a fost finalizată. Poți încerca din nou.');
        }

        return redirect()->route('billing.success', $model)
            ->with('status', 'Am înregistrat întoarcerea de la Eu Plătesc. Dacă plata a reușit, abonamentul se activează în câteva momente.');
    }

    public function stripeWebhook(Request $request, StripePaymentService $stripe): Response
    {
        try {
            $stripe->handleWebhook(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
            );
        } catch (\Throwable $e) {
            report($e);

            return response('Error', 400);
        }

        return response('OK', 200);
    }

    public function stripeReturn(Request $request, string $order, StripePaymentService $stripe): RedirectResponse
    {
        $model = SubscriptionOrder::query()->where('number', $order)->first();
        if (! $model || (int) $model->user_id !== (int) auth()->id()) {
            return redirect()->route('companies.index', ['all' => 1])
                ->with('warning', 'Comanda nu a fost găsită.');
        }

        try {
            $model = $stripe->syncSession($request->query('session_id'), $model) ?: $model->fresh();
        } catch (\Throwable $e) {
            report($e);
        }

        if ($model->isPaid()) {
            return redirect()->route('billing.success', $model)
                ->with('status', 'Plata Stripe a fost confirmată. Abonamentul a fost prelungit.');
        }

        if ($model->status === 'failed') {
            return redirect()->route('billing.order', $model->company_id)
                ->with('warning', 'Plata Stripe nu a fost finalizată. Poți încerca din nou.');
        }

        return redirect()->route('billing.success', $model)
            ->with('status', 'Am înregistrat întoarcerea de la Stripe. Dacă plata a reușit, abonamentul se activează în câteva momente (webhook).');
    }

    public function success(SubscriptionOrder $order): View
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);

        return view('billing.success', [
            'order' => $order->load('company'),
        ]);
    }

    private function authorizeCompany(Company $company): void
    {
        $user = auth()->user();
        if ($user?->is_admin) {
            return;
        }

        // Abonament: doar proprietarul firmei (nu subuserii).
        abort_unless(
            $user && (int) $company->owner_id === (int) $user->id,
            403
        );
    }
}
