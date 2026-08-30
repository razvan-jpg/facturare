<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SubscriptionOrderService
{
    public function __construct(
        private AccessGate $accessGate,
        private ExchangeRateService $exchangeRates,
        private SubuserSeatService $subuserSeats,
    ) {}

    public function periods(): array
    {
        return config('dateconta.subscription.periods', []);
    }

    public function priceBreakdown(string $periodKey): array
    {
        $period = $this->periods()[$periodKey] ?? null;
        if (! $period) {
            throw new \InvalidArgumentException('Perioadă invalidă.');
        }

        $vatRate = (float) config('dateconta.subscription.vat_rate', 21);
        $net = round((float) $period['price'], 2);
        $vat = round($net * $vatRate / 100, 2);
        $total = round($net + $vat, 2);
        $bonusDays = (int) ($period['bonus_days'] ?? 0);
        $bonusMonths = (int) ($period['bonus_months'] ?? 0);
        $bonusLabel = $period['bonus_label'] ?? null;
        if (! $bonusLabel && ($bonusDays > 0 || $bonusMonths > 0)) {
            $parts = [];
            if ($bonusMonths > 0) {
                $parts[] = $bonusMonths === 1 ? '+1 lună bonus' : '+'.$bonusMonths.' luni bonus';
            }
            if ($bonusDays > 0) {
                $parts[] = $bonusDays === 7 ? '+1 săptămână bonus' : ($bonusDays === 14 ? '+2 săptămâni bonus' : '+'.$bonusDays.' zile bonus');
            }
            $bonusLabel = implode(', ', $parts);
        }

        return [
            'period_key' => $periodKey,
            'label' => $period['label'],
            'months' => (int) $period['months'],
            'seats' => 0,
            'bonus_days' => $bonusDays,
            'bonus_months' => $bonusMonths,
            'bonus_label' => $bonusLabel,
            'amount_net' => $net,
            'amount_vat' => $vat,
            'amount_total' => $total,
            'vat_rate' => $vatRate,
            'currency' => (string) config('dateconta.subscription.currency', 'EUR'),
        ];
    }

    public function createPending(
        User $user,
        Company $company,
        string $periodKey,
        string $paymentMethod,
        array $billing,
        bool $recurring = false,
        ?string $paymentProcessor = null,
        string $productType = SubscriptionOrder::PRODUCT_PLATFORM,
        int $seats = 0,
    ): SubscriptionOrder {
        $productType = $productType ?: SubscriptionOrder::PRODUCT_PLATFORM;
        $price = $productType === SubscriptionOrder::PRODUCT_SUBUSER_SEATS
            ? $this->subuserSeats->priceBreakdown($periodKey, $seats)
            : $this->priceBreakdown($periodKey);

        // NETOPIA live: încasare + factură în RON (curs BNR + markup, implicit +2%).
        if ($paymentMethod === 'card' && $paymentProcessor === 'netopia') {
            try {
                $converted = $this->exchangeRates->convertSubscriptionAmountsToRon(
                    (float) $price['amount_net'],
                    (float) $price['amount_vat'],
                    (float) $price['amount_total'],
                    (string) $price['currency'],
                    (float) $price['vat_rate'],
                );
            } catch (Throwable $e) {
                report($e);
                $approx = (float) config('dateconta.subscription.eur_ron_approx', 5.0);
                $markup = (float) config('dateconta.subscription.netopia_ron_markup', 1.02);
                $rate = round(max(0.01, $approx) * max(0.01, $markup), 4);
                $netRon = round((float) $price['amount_net'] * $rate, 2);
                $vatRon = round($netRon * ((float) $price['vat_rate']) / 100, 2);
                $converted = [
                    'amount_net' => $netRon,
                    'amount_vat' => $vatRon,
                    'amount_total' => round($netRon + $vatRon, 2),
                    'currency' => 'RON',
                    'fx_rate' => $rate,
                    'fx_bnr' => $approx,
                ];
            }
            $price['amount_net'] = $converted['amount_net'];
            $price['amount_vat'] = $converted['amount_vat'];
            $price['amount_total'] = $converted['amount_total'];
            $price['currency'] = 'RON';
        }

        return SubscriptionOrder::query()->create([
            'number' => $this->nextNumber(),
            'user_id' => $user->id,
            'company_id' => $company->id,
            'product_type' => $productType,
            'period_key' => $price['period_key'],
            'months' => $price['months'],
            'seats' => (int) ($price['seats'] ?? 0),
            'amount_net' => $price['amount_net'],
            'amount_vat' => $price['amount_vat'],
            'amount_total' => $price['amount_total'],
            'currency' => $price['currency'],
            'vat_rate' => $price['vat_rate'],
            'payment_method' => $paymentMethod,
            'payment_processor' => $paymentMethod === 'card' ? $paymentProcessor : null,
            'status' => $paymentMethod === 'op' ? 'awaiting_op' : 'pending',
            'billing_name' => $billing['name'] ?? $company->name,
            'billing_cui' => $billing['cui'] ?? $company->cui,
            'billing_phone' => $billing['phone'] ?? $company->phone,
            'billing_email' => $billing['email'] ?? $company->email ?? $user->email,
            'billing_address' => $billing['address'] ?? $company->address,
            'billing_city' => $billing['city'] ?? $company->city,
            'billing_county' => $billing['county'] ?? $company->county,
            'recurring' => $recurring,
        ]);
    }

    public function markPaid(SubscriptionOrder $order, ?string $paymentRef = null): SubscriptionOrder
    {
        if ($order->isPaid()) {
            return $order;
        }

        $paid = DB::transaction(function () use ($order, $paymentRef) {
            $order = SubscriptionOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->isPaid()) {
                return $order;
            }

            $owner = $order->company?->owner ?: $order->user;

            if ($order->isSubuserSeats()) {
                $until = $this->subuserSeats->applyPaidSeats(
                    $owner,
                    max(1, (int) $order->seats),
                    max(1, (int) $order->months),
                );
            } else {
                $period = $this->periods()[$order->period_key] ?? [];
                $bonusDays = (int) ($period['bonus_days'] ?? 0);
                $bonusMonths = (int) ($period['bonus_months'] ?? 0);

                $until = $this->accessGate->extendAccess(
                    $owner,
                    days: $bonusDays,
                    months: (int) $order->months + $bonusMonths,
                );

                if ($owner->plan !== 'paid' && ! $owner->is_admin) {
                    $owner->forceFill(['plan' => 'paid'])->save();
                }
            }

            $updates = [
                'status' => 'paid',
                'paid_at' => now(),
                'access_until_after' => $until,
                'netopia_error' => null,
            ];

            if ($paymentRef) {
                if (($order->payment_processor ?? '') === 'mollie' || str_starts_with($paymentRef, 'tr_')) {
                    $updates['mollie_payment_id'] = $paymentRef;
                } else {
                    $updates['netopia_ref'] = $paymentRef;
                }
            }

            $order->forceFill($updates)->save();

            return $order->fresh();
        });

        // După commit: factură fiscală FLY DAVID + email (e-Factura după setările firmei).
        try {
            app(SubscriptionInvoiceService::class)->issueForPaidOrder($paid);
        } catch (Throwable $e) {
            Log::error('Subscription invoice after payment failed', [
                'order' => $paid->number,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }

        return $paid->fresh();
    }

    private function nextNumber(): string
    {
        do {
            $number = 'DC-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (SubscriptionOrder::query()->where('number', $number)->exists());

        return $number;
    }
}
