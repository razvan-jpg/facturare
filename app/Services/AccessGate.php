<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class AccessGate
{
    /**
     * La înregistrare:
     * - până la promo_free_until (inclusiv 31.03.2027): acces gratuit până la acea dată;
     * - după 31.03.2027: 6 luni gratuite de la data creării contului (trial_months_after_promo).
     */
    public function applyOnRegister(User $user): void
    {
        $promoUntil = Carbon::parse(config('dateconta.promo_free_until'))->endOfDay();
        $registeredAt = ($user->created_at ?? now())->copy();

        // Conturi create în perioada promo (până la sfârșitul zilei 31.03.2027).
        if ($registeredAt->lte($promoUntil)) {
            $user->forceFill([
                'plan' => 'free_promo',
                'trial_ends_at' => null,
                'access_until' => $promoUntil,
            ])->save();

            return;
        }

        // Conturi noi după 31.03.2027 → 6 luni gratuite de la înregistrare.
        $months = max(1, (int) config('dateconta.trial_months_after_promo', 6));
        $trialEnds = $registeredAt->copy()->addMonths($months)->endOfDay();

        $user->forceFill([
            'plan' => 'trial',
            'trial_ends_at' => $trialEnds,
            'access_until' => $trialEnds,
        ])->save();
    }

    public function hasAccess(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Subuser: moștenește accesul proprietarului + loc activ (din 01.04.2027).
        if ($user->created_by_user_id) {
            $parent = $user->relationLoaded('createdBy')
                ? $user->createdBy
                : $user->createdBy()->first();
            if (
                $parent
                && $this->hasAccess($parent)
                && app(SubuserSeatService::class)->subuserHasSeat($user)
            ) {
                return true;
            }

            return false;
        }

        // Paid fără access_until = nelimitat.
        if ($user->plan === 'paid' && ! $user->access_until) {
            return true;
        }

        $until = $this->effectiveAccessUntil($user);

        return $until !== null && now()->lte($until);
    }

    public function accessLabel(User $user): ?string
    {
        $until = $this->effectiveAccessUntil($user);
        if (! $until) {
            return $user->is_admin || ($user->plan === 'paid' && ! $user->access_until)
                ? 'Acces activ'
                : null;
        }

        return 'Acces până la '.dc_date($until);
    }

    /**
     * Data efectivă de expirare: maxim dintre promo platformă (31.03.2027), access_until și trial.
     * În perioada promo, nimeni nu coboară sub promo_free_until.
     */
    public function effectiveAccessUntil(User $user): ?Carbon
    {
        if ($user->is_admin) {
            return null;
        }

        // Subuser: folosește expirarea proprietarului (dacă e mai avantajoasă / singura).
        if ($user->created_by_user_id) {
            $parent = $user->relationLoaded('createdBy')
                ? $user->createdBy
                : $user->createdBy()->first();
            if ($parent) {
                return $this->effectiveAccessUntil($parent);
            }
        }

        // Paid nelimitat (fără dată de expirare).
        if ($user->plan === 'paid' && ! $user->access_until) {
            return null;
        }

        $promoUntil = Carbon::parse(config('dateconta.promo_free_until'))->endOfDay();

        $dates = [];
        if (now()->lte($promoUntil)) {
            $dates[] = $promoUntil;
        }
        if ($user->access_until) {
            $dates[] = $user->access_until->copy();
        }
        if ($user->trial_ends_at) {
            $dates[] = $user->trial_ends_at->copy();
        }

        if ($dates === []) {
            return null;
        }

        return collect($dates)->sortByDesc(fn (Carbon $d) => $d->timestamp)->first();
    }

    /**
     * Baza pentru prelungiri la plată / bonus:
     * max(promo platformă, access_until, trial), cel puțin azi.
     *
     * Nu folosim effectiveAccessUntil() aici — pentru admin / paid nelimitat
     * effective e null și am prelungi greșit de la data plății.
     */
    public function extensionBaseDate(User $user): Carbon
    {
        $now = now()->endOfDay();
        $promoUntil = Carbon::parse(config('dateconta.promo_free_until'))->endOfDay();

        $candidates = [];
        if (now()->lte($promoUntil)) {
            $candidates[] = $promoUntil->copy();
        }
        if ($user->access_until) {
            $candidates[] = $user->access_until->copy();
        }
        if ($user->trial_ends_at) {
            $candidates[] = $user->trial_ends_at->copy();
        }

        if ($candidates === []) {
            return $now->copy();
        }

        /** @var Carbon $base */
        $base = collect($candidates)->sortByDesc(fn (Carbon $d) => $d->timestamp)->first();

        return $base->gt($now) ? $base->copy() : $now->copy();
    }

    /** Prelungește access_until cu zile și/sau luni, pornind de la data efectivă curentă (sau azi). */
    public function extendAccess(User $user, int $days = 0, int $months = 0): Carbon
    {
        $base = $this->extensionBaseDate($user);

        if ($days > 0) {
            $base = $base->copy()->addDays($days);
        }
        if ($months > 0) {
            // Avoid 31 Mar + 1 month → 1 May overflow.
            $base = $base->copy()->addMonthsNoOverflow($months);
        }

        $user->forceFill([
            'access_until' => $base->endOfDay(),
        ])->save();

        return $base;
    }

    /**
     * Ajustează access_until cu un număr întreg de săptămâni (pozitiv = adaugă, negativ = scade).
     * Scăderea nu coboară sub sfârșitul zilei curente.
     */
    public function adjustAccessByWeeks(User $user, int $weeks): Carbon
    {
        if ($weeks === 0) {
            return $this->extensionBaseDate($user);
        }

        $base = $this->extensionBaseDate($user);
        $new = $base->copy()->addWeeks($weeks)->endOfDay();
        if ($new->lt(now()->endOfDay())) {
            $new = now()->endOfDay();
        }

        $user->forceFill([
            'access_until' => $new,
        ])->save();

        return $new;
    }

    /**
     * Rezumat abonament / promo pentru meniul contului.
     *
     * @return array{
     *     plan: string,
     *     plan_label: string,
     *     ends_at: ?Carbon,
     *     days_remaining: ?int,
     *     progress: int,
     *     promotions: list<string>,
     *     label: ?string
     * }
     */
    public function subscriptionSummary(User $user): array
    {
        $promoUntil = Carbon::parse(config('dateconta.promo_free_until'))->endOfDay();
        $now = now();
        $promotions = [];

        if ($now->lte($promoUntil)) {
            $promotions[] = 'Acces gratuit platformă până la '.dc_date($promoUntil);
        } elseif ($user->plan === 'trial' && $user->trial_ends_at) {
            $months = max(1, (int) config('dateconta.trial_months_after_promo', 6));
            $promotions[] = $months.' luni gratuite de la înregistrare (până la '.dc_date($user->trial_ends_at).')';
        }

        if ($user->is_admin) {
            return [
                'plan' => 'admin',
                'plan_label' => 'Admin',
                'ends_at' => null,
                'days_remaining' => null,
                'progress' => 100,
                'promotions' => $promotions !== [] ? $promotions : ['Acces administrator nelimitat'],
                'label' => 'Acces administrator',
            ];
        }

        $ends = $this->effectiveAccessUntil($user);
        $days = $ends ? max(0, (int) $now->copy()->startOfDay()->diffInDays($ends->copy()->startOfDay(), false)) : null;
        $start = $user->created_at?->copy()->startOfDay() ?? $now->copy()->startOfDay();

        if ($user->access_until && $user->access_until->gt($promoUntil)) {
            $promotions[] = 'Bonus recomandare / prelungire până la '.dc_date($user->access_until);
        }

        if ($user->plan === 'paid') {
            return [
                'plan' => 'paid',
                'plan_label' => 'Plătit',
                'ends_at' => $ends,
                'days_remaining' => $days,
                'progress' => $this->progressPercent($start, $ends, $days),
                'promotions' => $promotions !== [] ? $promotions : ['Abonament activ'],
                'label' => $ends ? 'Abonament până la '.dc_date($ends) : 'Abonament activ',
            ];
        }

        if ($ends && $now->lte($ends)) {
            $plan = $now->lte($promoUntil) ? 'free_promo' : ($user->plan ?: 'trial');
            $planLabel = $plan === 'free_promo'
                ? 'Gratuit'
                : (($user->plan === 'trial') ? '6 luni gratuite' : 'Probă');

            return [
                'plan' => $plan,
                'plan_label' => $planLabel,
                'ends_at' => $ends,
                'days_remaining' => $days,
                'progress' => $this->progressPercent($start, $ends, $days),
                'promotions' => $promotions,
                'label' => $planLabel.' până la '.dc_date($ends),
            ];
        }

        return [
            'plan' => 'expired',
            'plan_label' => 'Expirat',
            'ends_at' => $ends,
            'days_remaining' => 0,
            'progress' => 0,
            'promotions' => $promotions !== [] ? $promotions : ['Nicio promoție activă'],
            'label' => 'Acces expirat',
        ];
    }

    private function progressPercent(?Carbon $start, ?Carbon $ends, ?int $daysRemaining): int
    {
        if (! $ends || $daysRemaining === null) {
            return 100;
        }

        $startDay = ($start ?? now())->copy()->startOfDay();
        $endDay = $ends->copy()->startOfDay();
        $total = max(1, (int) $startDay->diffInDays($endDay, false));
        if ($total <= 0) {
            return 0;
        }

        return (int) max(0, min(100, round(($daysRemaining / $total) * 100)));
    }
}
