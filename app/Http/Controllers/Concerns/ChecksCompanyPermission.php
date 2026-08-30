<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use App\Services\CompanyPermission;

trait ChecksCompanyPermission
{
    protected function companyPermission(): CompanyPermission
    {
        return app(CompanyPermission::class);
    }

    protected function authorizeCompanyAbility(?Company $company, string $ability): void
    {
        $this->companyPermission()->authorize(auth()->user(), $company, $ability);
    }
}
