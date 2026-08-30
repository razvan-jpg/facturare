<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EfacturaInvite;
use App\Services\AnafOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnafOAuthController extends Controller
{
    public function redirect(Request $request, Company $company, AnafOAuthService $oauth): RedirectResponse
    {
        $this->authorizeCompany($company);

        if (! $oauth->isConfigured()) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('status', 'ANAF OAuth nu este configurat pe server. Adaugă ANAF_CLIENT_ID și ANAF_CLIENT_SECRET în .env.');
        }

        if (blank($company->cui)) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('status', 'Completează CUI-ul societății înainte de autorizarea SPV.');
        }

        $url = $oauth->authorizeUrl([
            'company_id' => $company->id,
            'user_id' => $request->user()->id,
            'by' => $request->user()->email,
        ]);

        return redirect()->away($url);
    }

    public function inviteStart(string $token, AnafOAuthService $oauth): RedirectResponse|View
    {
        $invite = EfacturaInvite::where('token', $token)->with('company')->firstOrFail();

        if (! $invite->isValid()) {
            return view('efactura.invite-invalid', ['invite' => $invite]);
        }

        if (! $oauth->isConfigured()) {
            return view('efactura.invite-invalid', [
                'invite' => $invite,
                'message' => 'Aplicația nu are încă configurate credentialele OAuth ANAF.',
            ]);
        }

        $url = $oauth->authorizeUrl([
            'company_id' => $invite->company_id,
            'invite_token' => $invite->token,
            'by' => $invite->email,
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request, AnafOAuthService $oauth): RedirectResponse|View
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('companies.index')
                ->with('status', 'Autorizarea ANAF a fost refuzată: '.$request->string('error'));
        }

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $state = $oauth->decodeState($request->string('state'));
            $company = Company::findOrFail($state['company_id'] ?? 0);
            $tokens = $oauth->exchangeCode($request->string('code'));
            $oauth->storeTokens($company, $tokens, $state['by'] ?? null);

            if (! empty($state['invite_token'])) {
                $invite = EfacturaInvite::where('token', $state['invite_token'])
                    ->where('company_id', $company->id)
                    ->first();
                $invite?->update(['accepted_at' => now()]);

                return view('efactura.invite-success', compact('company', 'invite'));
            }

            if (auth()->check()) {
                return redirect()
                    ->route('companies.edit', $company)
                    ->with('status', 'SPV ANAF autorizat cu succes pentru '.$company->name.'.');
            }

            return view('efactura.invite-success', ['company' => $company, 'invite' => null]);
        } catch (\Throwable $e) {
            return redirect()
                ->route('companies.index')
                ->with('status', 'Autorizare ANAF eșuată: '.$e->getMessage());
        }
    }

    private function authorizeCompany(Company $company): void
    {
        $user = auth()->user();
        if ($user?->is_admin) {
            return;
        }

        abort_unless(
            $user && app(\App\Services\CompanyPermission::class)->can($user, $company, 'efactura_manage'),
            403
        );
    }
}
