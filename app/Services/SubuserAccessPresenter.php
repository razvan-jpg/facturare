<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;

class SubuserAccessPresenter
{
    public function __construct(private CompanyPermission $permissions) {}

    /**
     * @return list<array{company: string, cui: ?string, rights: list<string>}>
     */
    public function accessSummary(User $owner, User $collaborator): array
    {
        $ownedIds = $owner->ownedCompanies()->pluck('id');
        $memberships = $collaborator->companies()
            ->whereIn('companies.id', $ownedIds)
            ->orderBy('companies.name')
            ->get();

        $categories = $this->permissions->categories();

        return $memberships->map(function (Company $company) use ($categories, $collaborator) {
            if ($collaborator->is_admin) {
                $rights = ['Acces complet de administrator pe platformă (toate categoriile)'];
            } else {
                $perms = $this->permissions->normalizePermissions(
                    $company->pivot->permissions ?? null,
                    $company->pivot->role ?? null
                );

                $rights = [];
                foreach ($categories as $catKey => $catLabel) {
                    $view = in_array($catKey.'_view', $perms, true);
                    $manage = in_array($catKey.'_manage', $perms, true);
                    if ($manage) {
                        $rights[] = $catLabel.': vizualizare + creare/editare';
                    } elseif ($view) {
                        $rights[] = $catLabel.': doar vizualizare';
                    }
                }

                if ($rights === []) {
                    $rights[] = 'Fără drepturi pe categorii (doar accesul pe firmă în selector)';
                }
            }

            return [
                'company' => (string) $company->name,
                'cui' => $company->cui ? (string) $company->cui : null,
                'rights' => $rights,
            ];
        })->values()->all();
    }

    public function primaryCompanyName(User $owner, ?Company $preferred = null): string
    {
        if ($preferred && (int) $preferred->owner_id === (int) $owner->id) {
            return (string) ($preferred->name ?: 'societatea sa');
        }

        $first = $owner->ownedCompanies()->orderBy('name')->value('name');

        return (string) ($first ?: 'societatea sa');
    }
}
