<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class IosSubscriptionGate
{
    public function freeUntil(): Carbon
    {
        $date = (string) config('dateconta.ios_subscription.free_until', config('dateconta.promo_free_until', '2027-03-31'));

        return Carbon::parse($date, config('app.timezone'))->endOfDay();
    }

    /**
     * Cont App Review / test: ignoră promo + trial iOS (trebuie IAP activ).
     */
    public function forcesPaywall(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->ios_force_paywall) {
            return true;
        }

        $emails = config('dateconta.ios_subscription.review_force_paywall_emails', []);
        if (! is_array($emails) || $emails === []) {
            return false;
        }

        return in_array(strtolower((string) $user->email), $emails, true);
    }

    /**
     * Conturi create după perioada promo iOS: trial de N luni de la înregistrare.
     * Conturile din promo (created_at ≤ free_until) nu primesc trial după 31.03.2027 — merg pe IAP.
     */
    public function trialEndsAt(?User $user): ?Carbon
    {
        if (! $user?->created_at || $this->forcesPaywall($user)) {
            return null;
        }

        $freeUntil = $this->freeUntil();
        if ($user->created_at->lte($freeUntil)) {
            return null;
        }

        $months = max(1, (int) config('dateconta.ios_subscription.trial_months_after_promo', 1));

        return $user->created_at->copy()->addMonths($months)->endOfDay();
    }

    public function isInIosTrial(?User $user): bool
    {
        $ends = $this->trialEndsAt($user);

        return $ends !== null && now()->lte($ends);
    }

    /** @deprecated Folosește productIds() */
    public function productId(): string
    {
        $ids = $this->productIds();

        return $ids[0] ?? 'ro.dateconta.facturare.premium.monthly';
    }

    /**
     * @return list<string>
     */
    public function productIds(): array
    {
        $ids = config('dateconta.ios_subscription.product_ids');
        if (is_array($ids) && $ids !== []) {
            return array_values(array_map('strval', $ids));
        }

        $single = (string) config('dateconta.ios_subscription.product_id', 'ro.dateconta.facturare.premium.monthly');

        return $single !== '' ? [$single] : [];
    }

    public function isKnownProduct(string $productId): bool
    {
        return in_array($productId, $this->productIds(), true);
    }

    public function hasIosAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        // App Review / conturi forțate: doar entitlement App Store (fără promo/trial).
        if ($this->forcesPaywall($user)) {
            return $this->hasActiveEntitlement($user);
        }

        if (now()->lte($this->freeUntil())) {
            return true;
        }

        if ($this->isInIosTrial($user)) {
            return true;
        }

        return $this->hasActiveEntitlement($user);
    }

    public function hasActiveEntitlement(User $user): bool
    {
        $status = (string) ($user->ios_subscription_status ?? '');
        if (in_array($status, ['revoked', 'expired', 'refunded'], true)) {
            return false;
        }

        if (! $user->ios_expires_at) {
            return false;
        }

        // Mică toleranță pentru billing retry / ceas.
        return $user->ios_expires_at->copy()->addDay()->isFuture()
            && in_array($status, ['active', 'billing_retry', 'grace_period', ''], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function statusPayload(User $user): array
    {
        $freeUntil = $this->freeUntil();
        $forcePaywall = $this->forcesPaywall($user);
        $inPromo = ! $forcePaywall && now()->lte($freeUntil);
        $trialEnds = $this->trialEndsAt($user);
        $inTrial = $this->isInIosTrial($user);
        $entitled = $this->hasActiveEntitlement($user);

        return [
            'free_until' => $freeUntil->toIso8601String(),
            'in_free_period' => $inPromo,
            'force_paywall' => $forcePaywall,
            'trial_ends_at' => optional($trialEnds)?->toIso8601String(),
            'in_trial' => $inTrial,
            'product_id' => $user->ios_product_id ?: $this->productId(),
            'product_ids' => $this->productIds(),
            'has_access' => $this->hasIosAccess($user),
            'has_entitlement' => $entitled,
            'expires_at' => optional($user->ios_expires_at)?->toIso8601String(),
            'status' => $user->ios_subscription_status,
            'environment' => $user->ios_environment,
            'note' => 'Abonamentul iOS (1 / 3 / 6 / 12 luni via App Store) este separat de abonamentul web. Conturile noi după perioada gratuită primesc 1 lună de test pe iOS.',
        ];
    }
}
