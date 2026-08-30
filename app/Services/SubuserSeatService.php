<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SubuserSeatService
{
    public function billableFrom(): Carbon
    {
        return Carbon::parse((string) config('dateconta.subuser_seats.billable_from', '2027-04-01'))
            ->startOfDay();
    }

    public function isBillablePeriod(?Carbon $at = null): bool
    {
        return ($at ?? now())->gte($this->billableFrom());
    }

    public function pricePerSeatMonth(): float
    {
        return round((float) config('dateconta.subuser_seats.price_per_seat_month', 1), 2);
    }

    /** @return array<string, array{label: string, months: int}> */
    public function periods(): array
    {
        return config('dateconta.subuser_seats.periods', []);
    }

    /**
     * @return array{
     *   period_key: string,
     *   label: string,
     *   months: int,
     *   seats: int,
     *   amount_net: float,
     *   amount_vat: float,
     *   amount_total: float,
     *   vat_rate: float,
     *   currency: string,
     *   unit_label: string
     * }
     */
    public function priceBreakdown(string $periodKey, int $seats): array
    {
        $period = $this->periods()[$periodKey] ?? null;
        if (! $period) {
            throw new \InvalidArgumentException('Perioadă invalidă.');
        }

        $seats = max(1, $seats);
        $months = max(1, (int) $period['months']);
        $vatRate = (float) config('dateconta.subuser_seats.vat_rate', config('dateconta.subscription.vat_rate', 21));
        $unit = $this->pricePerSeatMonth();
        $net = round($unit * $seats * $months, 2);
        $vat = round($net * $vatRate / 100, 2);
        $total = round($net + $vat, 2);

        return [
            'period_key' => $periodKey,
            'label' => (string) $period['label'],
            'months' => $months,
            'seats' => $seats,
            'amount_net' => $net,
            'amount_vat' => $vat,
            'amount_total' => $total,
            'vat_rate' => $vatRate,
            'currency' => (string) config('dateconta.subuser_seats.currency', 'EUR'),
            'unit_label' => number_format($unit, 2, ',', '.').' EUR / loc / lună',
        ];
    }

    public function seatsUntil(?User $owner): ?Carbon
    {
        return $owner?->subuser_seats_until;
    }

    public function seatQuota(?User $owner): int
    {
        return max(0, (int) ($owner?->subuser_seat_quota ?? 0));
    }

    /**
     * Cont admin: subuserii / invitații nu consumă locuri și nu au limită de perioadă.
     */
    public function seatsExemptForOwner(?User $owner): bool
    {
        return (bool) ($owner?->is_admin);
    }

    /**
     * Subuseri creați de owner + utilizatori invitați (operator) pe societățile lui.
     *
     * @return Collection<int, User>
     */
    public function collaborators(User $owner): Collection
    {
        $ownedIds = $owner->ownedCompanies()->pluck('id');

        $created = $owner->managedUsers()->get();

        $invited = User::query()
            ->where('id', '!=', $owner->id)
            ->where(function ($q) use ($owner) {
                $q->whereNull('created_by_user_id')
                    ->orWhere('created_by_user_id', '!=', $owner->id);
            })
            ->whereHas('companies', function ($q) use ($ownedIds) {
                $q->whereIn('companies.id', $ownedIds)
                    ->where('company_user.role', 'operator');
            })
            ->get();

        return $created->concat($invited)->unique('id')->sortBy('name')->values();
    }

    /** Colaboratori care ocupă loc (adminii invitați nu consumă loc). */
    public function seatOccupants(User $owner): Collection
    {
        return $this->collaborators($owner)
            ->filter(fn (User $user) => ! $user->is_admin)
            ->values();
    }

    public function managedCount(User $owner): int
    {
        return $this->seatOccupants($owner)->count();
    }

    public function hasActiveSeats(User $owner): bool
    {
        if ($this->seatsExemptForOwner($owner)) {
            return true;
        }

        if (! $this->isBillablePeriod()) {
            return true;
        }

        $until = $this->seatsUntil($owner);
        if (! $until || $until->lt(now())) {
            return false;
        }

        return $this->seatQuota($owner) > 0;
    }

    public function canCreateSubuser(User $owner): bool
    {
        if ($this->seatsExemptForOwner($owner)) {
            return true;
        }

        if (! $this->isBillablePeriod()) {
            return true;
        }

        if (! $this->hasActiveSeats($owner)) {
            return false;
        }

        return $this->managedCount($owner) < $this->seatQuota($owner);
    }

    public function canAddCollaborator(User $owner, User $collaborator): bool
    {
        if ($this->seatsExemptForOwner($owner) || $collaborator->is_admin) {
            return true;
        }

        if ($this->collaborators($owner)->contains('id', $collaborator->id)) {
            return true;
        }

        return $this->canCreateSubuser($owner);
    }

    public function collaboratorHasSeat(User $owner, User $collaborator): bool
    {
        if ($this->seatsExemptForOwner($owner) || $collaborator->is_admin) {
            return true;
        }

        if (! $this->isBillablePeriod()) {
            return true;
        }

        if (! $this->hasActiveSeats($owner)) {
            return false;
        }

        $quota = $this->seatQuota($owner);
        $allowedIds = $this->seatOccupants($owner)
            ->sortBy('id')
            ->take($quota)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array((int) $collaborator->id, $allowedIds, true);
    }

    public function subuserHasSeat(User $subuser): bool
    {
        if (! $subuser->created_by_user_id) {
            return true;
        }

        $parent = $subuser->relationLoaded('createdBy')
            ? $subuser->createdBy
            : $subuser->createdBy()->first();

        if (! $parent) {
            return false;
        }

        return $this->collaboratorHasSeat($parent, $subuser);
    }

    public function applyPaidSeats(User $owner, int $seats, int $months): Carbon
    {
        $seats = max(1, $seats);
        $months = max(1, $months);
        $billableFrom = $this->billableFrom();

        $owner->subuser_seat_quota = max($this->seatQuota($owner), $seats);

        $base = now()->endOfDay();
        if ($owner->subuser_seats_until && $owner->subuser_seats_until->gt($base)) {
            $base = $owner->subuser_seats_until->copy();
        }

        if ($base->lt($billableFrom)) {
            $until = $billableFrom->copy()->addMonths($months)->subDay()->endOfDay();
        } else {
            $until = $base->copy()->addMonths($months);
        }

        $owner->subuser_seats_until = $until;
        $owner->save();

        return $until;
    }

    /**
     * @return array{
     *   quota: int,
     *   until: ?Carbon,
     *   used: int,
     *   available: int,
     *   billable: bool,
     *   billable_from: Carbon,
     *   active: bool,
     *   price_label: string,
     *   unlimited: bool
     * }
     */
    public function summary(User $owner): array
    {
        $unlimited = $this->seatsExemptForOwner($owner);
        $quota = $this->seatQuota($owner);
        $used = $this->managedCount($owner);
        $until = $this->seatsUntil($owner);
        $billable = $this->isBillablePeriod();
        $active = $this->hasActiveSeats($owner);

        return [
            'quota' => $quota,
            'until' => $until,
            'used' => $used,
            'available' => $unlimited || ! $billable
                ? PHP_INT_MAX
                : max(0, ($active ? $quota : 0) - $used),
            'billable' => $billable && ! $unlimited,
            'billable_from' => $this->billableFrom(),
            'active' => $active,
            'price_label' => number_format($this->pricePerSeatMonth(), 2, ',', '.').' EUR / loc / lună',
            'unlimited' => $unlimited,
        ];
    }
}
