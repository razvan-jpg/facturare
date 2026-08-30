<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Company;
use App\Services\CompanyContext;
use App\Services\CompanyPermission;
use Illuminate\Http\Request;

trait ResolvesApiCompany
{
    protected function apiCompany(Request $request): Company
    {
        $company = $request->attributes->get('api_company');
        if ($company instanceof Company) {
            return $company;
        }

        $company = app(CompanyContext::class)->current($request->user());
        abort_unless($company, 409, 'Nicio societate selectată.');

        return $company;
    }

    protected function authorizeAbility(Request $request, string $ability): Company
    {
        $company = $this->apiCompany($request);
        app(CompanyPermission::class)->authorize($request->user(), $company, $ability);

        return $company;
    }
}
