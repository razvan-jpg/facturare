<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AnafOAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('dateconta.anaf.client_id'))
            && filled(config('dateconta.anaf.client_secret'));
    }

    public function authorizeUrl(array $statePayload): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Aplicația OAuth ANAF nu este configurată (ANAF_CLIENT_ID / ANAF_CLIENT_SECRET).');
        }

        $state = $this->encodeState($statePayload);

        return config('dateconta.anaf.authorize_url').'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('dateconta.anaf.client_id'),
            'redirect_uri' => config('dateconta.anaf.callback_url'),
            'token_content_type' => config('dateconta.anaf.token_content_type', 'jwt'),
            'state' => $state,
        ]);
    }

    public function encodeState(array $payload): string
    {
        $payload['exp'] = now()->addMinutes(30)->timestamp;
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public function decodeState(string $state): array
    {
        $json = base64_decode(strtr($state, '-_', '+/'), true);
        if ($json === false) {
            throw new RuntimeException('State OAuth invalid.');
        }

        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($payload['exp'] ?? 0) < now()->timestamp) {
            throw new RuntimeException('Sesiunea de autorizare a expirat. Reîncearcă.');
        }

        return $payload;
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()
            ->withBasicAuth(
                (string) config('dateconta.anaf.client_id'),
                (string) config('dateconta.anaf.client_secret')
            )
            ->post(config('dateconta.anaf.token_url'), [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('dateconta.anaf.callback_url'),
                'token_content_type' => config('dateconta.anaf.token_content_type', 'jwt'),
            ]);

        if (! $response->successful()) {
            Log::warning('ANAF token exchange failed', ['body' => $response->body()]);
            throw new RuntimeException('Nu am putut obține token-ul ANAF. Verifică certificatul și drepturile SPV.');
        }

        return $response->json();
    }

    public function refresh(Company $company): array
    {
        if (! filled($company->anaf_refresh_token)) {
            throw new RuntimeException('Firma nu are refresh token ANAF. Reautorizează SPV.');
        }

        $response = Http::asForm()
            ->withBasicAuth(
                (string) config('dateconta.anaf.client_id'),
                (string) config('dateconta.anaf.client_secret')
            )
            ->post(config('dateconta.anaf.token_url'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $company->anaf_refresh_token,
                'token_content_type' => config('dateconta.anaf.token_content_type', 'jwt'),
            ]);

        if (! $response->successful()) {
            Log::warning('ANAF token refresh failed', [
                'company_id' => $company->id,
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Refresh token ANAF invalid. Reautorizează SPV.');
        }

        $tokens = $response->json();
        $this->storeTokens($company, $tokens, $company->anaf_authorized_by);

        return $tokens;
    }

    public function storeTokens(Company $company, array $tokens, ?string $authorizedBy = null): void
    {
        $expiresIn = (int) ($tokens['expires_in'] ?? 7776000);

        $company->forceFill([
            'anaf_access_token' => $tokens['access_token'] ?? null,
            'anaf_refresh_token' => $tokens['refresh_token'] ?? $company->anaf_refresh_token,
            'anaf_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 300)),
            'anaf_authorized_at' => now(),
            'anaf_authorized_by' => $authorizedBy ?: $company->anaf_authorized_by,
            'anaf_cif' => $company->numericCui() ?: preg_replace('/\D+/', '', (string) $company->cui),
        ])->save();
    }

    public function accessToken(Company $company): string
    {
        if (! $company->isAnafAuthorized()) {
            throw new RuntimeException('Firma nu este autorizată în SPV ANAF.');
        }

        if ($company->anaf_token_expires_at && $company->anaf_token_expires_at->isPast()) {
            $this->refresh($company);
            $company->refresh();
        }

        return (string) $company->anaf_access_token;
    }
}
