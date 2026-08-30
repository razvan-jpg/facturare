<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReferralService
{
    public function __construct(private AccessGate $accessGate) {}

    public function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($code)) ?? '');
        $normalized = preg_replace('/[^A-Z0-9\-]/', '', $normalized) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    public function findByCode(?string $code): ?Company
    {
        $normalized = $this->normalizeCode($code);
        if (! $normalized) {
            return null;
        }

        return Company::query()->where('promo_code', $normalized)->first();
    }

    /**
     * Validează codul introdus la crearea societății.
     *
     * @throws ValidationException
     */
    public function validateForCreator(?string $code, User $creator): ?Company
    {
        $normalized = $this->normalizeCode($code);
        if (! $normalized) {
            return null;
        }

        if (! preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $normalized)) {
            throw ValidationException::withMessages([
                'referral_code' => 'Codul promoțional trebuie să aibă forma XXXX-XXXX-XXXX.',
            ]);
        }

        $referrer = $this->findByCode($normalized);
        if (! $referrer) {
            throw ValidationException::withMessages([
                'referral_code' => 'Codul promoțional nu este valid.',
            ]);
        }

        if ((int) $referrer->owner_id === (int) $creator->id) {
            throw ValidationException::withMessages([
                'referral_code' => 'Nu poți folosi codul unei societăți pe care o deții deja.',
            ]);
        }

        if ($creator->companies()->where('companies.id', $referrer->id)->exists()) {
            throw ValidationException::withMessages([
                'referral_code' => 'Nu poți folosi codul unei societăți din contul tău.',
            ]);
        }

        return $referrer;
    }

    /**
     * Leagă societatea nouă de recomandant și aplică bonusurile.
     *
     * @return array{invitee_days: int, referrer_months: int, referrer_total: int}
     */
    public function apply(Company $created, Company $referrer, User $creator): array
    {
        $inviteeDays = (int) config('dateconta.referral.invitee_bonus_days', 14);
        $referrerMonths = (int) config('dateconta.referral.referrer_bonus_months', 1);
        $every = max(1, (int) config('dateconta.referral.referrer_every', 2));

        return DB::transaction(function () use ($created, $referrer, $creator, $inviteeDays, $referrerMonths, $every) {
            $created->forceFill([
                'referred_by_company_id' => $referrer->id,
            ])->save();

            $this->accessGate->extendAccess($creator, days: $inviteeDays);

            $referrer = Company::query()->lockForUpdate()->findOrFail($referrer->id);
            $totalReferrals = Company::query()
                ->where('referred_by_company_id', $referrer->id)
                ->count();

            $rewardsDue = intdiv($totalReferrals, $every);
            $alreadyGranted = (int) $referrer->referral_rewards_granted;
            $toGrant = max(0, $rewardsDue - $alreadyGranted);
            $monthsGranted = 0;

            if ($toGrant > 0) {
                $owner = User::query()->find($referrer->owner_id);
                if ($owner) {
                    $this->accessGate->extendAccess($owner, months: $referrerMonths * $toGrant);
                    $monthsGranted = $referrerMonths * $toGrant;
                }
                $referrer->forceFill([
                    'referral_rewards_granted' => $alreadyGranted + $toGrant,
                ])->save();
            }

            return [
                'invitee_days' => $inviteeDays,
                'referrer_months' => $monthsGranted,
                'referrer_total' => $totalReferrals,
            ];
        });
    }
}
