<?php

namespace App\Http\Middleware;

use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySelected
{
    public function __construct(private CompanyContext $companyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->companyContext->current($request->user());

        if ($company) {
            view()->share('currentCompany', $company);
        }

        // Pe rutele de societăți / profil / billing permitem navigarea fără redirect,
        // dar tot rezolvăm societatea activă (implicit prima, dacă e cazul).
        if ($request->routeIs('companies.*', 'logout', 'billing.*', 'profile.*', 'billing.netopia.*', 'admin.*', 'company-users.*')) {
            return $next($request);
        }

        if (! $company) {
            return redirect()->route('companies.create')
                ->with('status', 'Adaugă prima societate pentru a începe facturarea.');
        }

        return $next($request);
    }
}
