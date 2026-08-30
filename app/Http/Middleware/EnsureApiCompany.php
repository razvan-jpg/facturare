<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\CompanyContext;
use App\Services\CompanyPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiCompany
{
    public function __construct(
        private CompanyContext $context,
        private CompanyPermission $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Neautentificat.'], 401);
        }

        $headerId = $request->header('X-Company-Id');
        if ($headerId !== null && $headerId !== '') {
            $company = Company::query()->find((int) $headerId);
            if (! $company) {
                return response()->json(['message' => 'Societatea nu există.'], 404);
            }

            if (! $user->is_admin && ! $this->permissions->can($user, $company, 'access')) {
                return response()->json(['message' => 'Nu ai acces la această societate.'], 403);
            }

            $this->context->set($company);
            $request->attributes->set('api_company', $company);

            return $next($request);
        }

        $company = $this->context->current($user);
        if (! $company) {
            return response()->json([
                'message' => 'Nicio societate selectată. Creează o societate sau trimite X-Company-Id.',
                'code' => 'company_required',
            ], 409);
        }

        $request->attributes->set('api_company', $company);

        return $next($request);
    }
}
