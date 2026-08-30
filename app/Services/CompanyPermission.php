<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;

class CompanyPermission
{
    /** @var array<string, Company|null> */
    private array $membershipMemo = [];

    /** @return array<string, string> */
    public function categories(): array
    {
        return config('company_permissions.categories', []);
    }

    /** @return array<string, string> */
    public function abilities(): array
    {
        return config('company_permissions.abilities', []);
    }

    /** @return list<string> */
    public function actionKeys(): array
    {
        return array_values(array_filter(
            array_keys($this->abilities()),
            fn (string $key) => $key !== 'access'
        ));
    }

    public function can(?User $user, ?Company $company, string $ability): bool
    {
        if (! $user || ! $company) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        if ((int) $company->owner_id === (int) $user->id) {
            return true;
        }

        $membership = $this->membership($user, $company);
        if (! $membership) {
            return false;
        }

        // Operator pe firmele altui owner: necesită loc (din 01.04.2027), plătit de owner.
        $companyOwnerId = (int) $company->owner_id;
        if ($companyOwnerId > 0 && $companyOwnerId !== (int) $user->id) {
            $owner = $company->relationLoaded('owner')
                ? $company->owner
                : $company->owner()->first();
            if (
                ! $owner
                || ! app(SubuserSeatService::class)->collaboratorHasSeat($owner, $user)
            ) {
                return false;
            }
        }

        if ($ability === 'access') {
            return true;
        }

        $permissions = $this->normalizePermissions($membership->pivot->permissions ?? null, $membership->pivot->role ?? null);

        // Creare/editare implică și vizualizare pe aceeași categorie.
        if (str_ends_with($ability, '_view')) {
            $manage = substr($ability, 0, -5).'_manage';
            if (in_array($manage, $permissions, true)) {
                return true;
            }
        }

        return in_array($ability, $permissions, true);
    }

    public function canAny(?User $user, ?Company $company, array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($this->can($user, $company, $ability)) {
                return true;
            }
        }

        return false;
    }

    public function authorize(?User $user, ?Company $company, string $ability): void
    {
        abort_unless($this->can($user, $company, $ability), 403);
    }

    /**
     * @return list<string>
     */
    public function normalizePermissions(mixed $raw, ?string $role = null): array
    {
        $all = $this->actionKeys();

        if ($role === 'owner') {
            return $all;
        }

        // Fără valoare (operator vechi) → acces complet. Listă goală [] → fără drepturi pe categorii.
        if ($raw === null) {
            return $all;
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '' || strtolower($trimmed) === 'null') {
                return $all;
            }
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return [];
            }
            $raw = $decoded;
        }

        if (! is_array($raw)) {
            return [];
        }

        // Explicit: niciun checkbox bifat → fără acces pe categorii.
        if ($raw === []) {
            return [];
        }

        $legacy = config('company_permissions.legacy_map', []);
        $keys = [];
        foreach ($raw as $key => $value) {
            $token = null;
            if (is_int($key) && is_string($value)) {
                $token = $value;
            } elseif (is_string($key) && $value) {
                $token = $key;
            }
            if (! $token) {
                continue;
            }
            if (isset($legacy[$token])) {
                foreach ($legacy[$token] as $mapped) {
                    $keys[] = $mapped;
                }
            } else {
                $keys[] = $token;
            }
        }

        return array_values(array_unique(array_intersect($all, $keys)));
    }

    /**
     * @param  list<string>  $checked
     * @return list<string>
     */
    public function filterChecked(array $checked): array
    {
        $keys = array_values(array_intersect($this->actionKeys(), $checked));
        // Creare/editare ⇒ și vizualizare pe aceeași categorie (salvare consistentă).
        foreach ($keys as $key) {
            if (str_ends_with($key, '_manage')) {
                $view = substr($key, 0, -7).'_view';
                if (in_array($view, $this->actionKeys(), true) && ! in_array($view, $keys, true)) {
                    $keys[] = $view;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    private function membership(User $user, Company $company): ?Company
    {
        $key = $user->id.':'.$company->id;
        if (array_key_exists($key, $this->membershipMemo)) {
            return $this->membershipMemo[$key];
        }

        return $this->membershipMemo[$key] = $user->companies()
            ->where('companies.id', $company->id)
            ->first();
    }
}
