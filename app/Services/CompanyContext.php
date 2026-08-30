<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class CompanyContext
{
    public const SESSION_KEY = 'current_company_id';

    /** @var array<int, Company|null> */
    private array $memo = [];

    public function current(?User $user = null): ?Company
    {
        $user = $user ?: auth()->user();

        if (! $user) {
            return null;
        }

        $uid = (int) $user->id;
        if (array_key_exists($uid, $this->memo)) {
            return $this->memo[$uid];
        }

        // Admin: poate lucra pe orice societate (mod suport), nu doar pe membership.
        if ($user->is_admin) {
            return $this->memo[$uid] = $this->resolveForAdmin($user);
        }

        $companies = $user->companies()->orderBy('companies.id')->get();

        if ($companies->isEmpty()) {
            $this->clearForUser($user);

            return $this->memo[$uid] = null;
        }

        // O singură societate → mereu ea e activa.
        if ($companies->count() === 1) {
            $only = $companies->first();
            $this->persist($user, $only);

            return $this->memo[$uid] = $only;
        }

        // API mobil: header X-Company-Id are prioritate față de sesiune.
        $headerId = request()?->header('X-Company-Id');
        if ($headerId !== null && $headerId !== '') {
            $fromHeader = $companies->firstWhere('id', (int) $headerId);
            if ($fromHeader) {
                $this->persist($user, $fromHeader);

                return $this->memo[$uid] = $fromHeader;
            }
        }

        $sessionId = Session::get(self::SESSION_KEY);
        if ($sessionId) {
            $fromSession = $companies->firstWhere('id', (int) $sessionId);
            if ($fromSession) {
                $this->persist($user, $fromSession);

                return $this->memo[$uid] = $fromSession;
            }
        }

        $preferredId = $user->current_company_id ? (int) $user->current_company_id : null;
        if ($preferredId) {
            $fromUser = $companies->firstWhere('id', $preferredId);
            if ($fromUser) {
                $this->persist($user, $fromUser);

                return $this->memo[$uid] = $fromUser;
            }
        }

        // Nu a ales încă → prima societate (cea mai veche / id minim).
        $first = $companies->first();
        $this->persist($user, $first);

        return $this->memo[$uid] = $first;
    }

    public function set(Company $company): void
    {
        $user = auth()->user();
        if ($user) {
            if (! $user->is_admin) {
                abort_unless(
                    $user->companies()->where('companies.id', $company->id)->exists(),
                    403
                );
            }
            $this->persist($user, $company);
            $this->memo[(int) $user->id] = $company;
        } else {
            Session::put(self::SESSION_KEY, $company->id);
        }
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        $this->memo = [];
    }

    public function isAdminSupportMode(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (! $user?->is_admin) {
            return false;
        }

        $company = $this->current($user);
        if (! $company) {
            return false;
        }

        return ! $user->companies()->where('companies.id', $company->id)->exists();
    }

    private function resolveForAdmin(User $user): ?Company
    {
        $headerId = request()?->header('X-Company-Id');
        $candidateIds = array_filter([
            ($headerId !== null && $headerId !== '') ? (int) $headerId : null,
            Session::get(self::SESSION_KEY) ? (int) Session::get(self::SESSION_KEY) : null,
            $user->current_company_id ? (int) $user->current_company_id : null,
        ]);

        foreach ($candidateIds as $id) {
            $company = Company::query()->find($id);
            if ($company) {
                $this->persist($user, $company);

                return $company;
            }
        }

        // Fallback: prima societate din membership, apoi orice societate din platformă.
        $fromMembership = $user->companies()->orderBy('companies.id')->first();
        if ($fromMembership) {
            $this->persist($user, $fromMembership);

            return $fromMembership;
        }

        $any = Company::query()->orderBy('id')->first();
        if ($any) {
            $this->persist($user, $any);

            return $any;
        }

        $this->clearForUser($user);

        return null;
    }

    private function persist(User $user, Company $company): void
    {
        Session::put(self::SESSION_KEY, $company->id);

        if ((int) $user->current_company_id !== (int) $company->id) {
            try {
                $user->forceFill(['current_company_id' => $company->id])->saveQuietly();
            } catch (\Throwable) {
                // Coloana poate lipsi temporar pe unele medii — sesiunea rămâne sursa de adevăr.
            }
            $user->current_company_id = $company->id;
        }
    }

    private function clearForUser(User $user): void
    {
        Session::forget(self::SESSION_KEY);
        if ($user->current_company_id) {
            $user->forceFill(['current_company_id' => null])->saveQuietly();
        }
    }
}
