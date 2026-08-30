<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Services\AnafOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EfacturaController extends Controller
{
    use ResolvesApiCompany;

    public function status(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'efactura_view');

        return response()->json([
            'authorized' => $company->isAnafAuthorized(),
            'anaf_cif' => $company->anaf_cif,
            'authorized_at' => optional($company->anaf_authorized_at)?->toIso8601String(),
            'authorized_by' => $company->anaf_authorized_by,
            'send_mode' => $company->efactura_send_mode,
            'oauth_url' => url('/anaf/oauth/redirect/'.$company->id),
            'web_settings_url' => url('/companies/'.$company->id.'/edit?tab=efactura'),
        ]);
    }

    public function oauthUrl(Request $request, AnafOAuthService $oauth): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'efactura_manage');

        if (! $oauth->isConfigured()) {
            return response()->json([
                'message' => 'ANAF OAuth nu este configurat pe server.',
            ], 422);
        }

        if (blank($company->cui)) {
            return response()->json([
                'message' => 'Completează CUI-ul societății înainte de autorizarea SPV.',
            ], 422);
        }

        $url = $oauth->authorizeUrl([
            'company_id' => $company->id,
            'user_id' => $request->user()->id,
            'by' => $request->user()->email,
        ]);

        return response()->json(['url' => $url]);
    }
}
