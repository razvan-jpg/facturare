<?php

namespace App\Services;

use App\Models\Company;

/**
 * Credențiale procesatoare card per firmă (încasare facturi emise de societate).
 * Pentru abonamentul DateConta, NETOPIA de pe firma operator (FLY DAVID) are prioritate
 * față de Admin / PlatformSettings / .env.
 */
class CompanyIntegrations
{
    /** @return array<string, mixed> */
    public function all(Company $company): array
    {
        $raw = $company->card_integrations;
        if (! is_array($raw)) {
            return [];
        }

        return $raw;
    }

    /** @return array<string, mixed> */
    public function processor(Company $company, string $key): array
    {
        $all = $this->all($company);
        $row = $all[$key] ?? [];

        return is_array($row) ? $row : [];
    }

    public function get(Company $company, string $processor, string $field, mixed $default = null): mixed
    {
        $row = $this->processor($company, $processor);

        return array_key_exists($field, $row) ? $row[$field] : $default;
    }

    public function getBool(Company $company, string $processor, string $field, bool $default = false): bool
    {
        $value = $this->get($company, $processor, $field);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** @param  array<string, mixed>  $data */
    public function put(Company $company, string $processor, array $data): void
    {
        $all = $this->all($company);
        $current = is_array($all[$processor] ?? null) ? $all[$processor] : [];
        $all[$processor] = array_merge($current, $data);
        $company->forceFill(['card_integrations' => $all])->save();
    }

    public function netopiaDir(Company $company): string
    {
        return storage_path('app/companies/'.$company->id.'/netopia');
    }

    public function netopiaPublicPath(Company $company): string
    {
        return $this->netopiaDir($company).'/public.cer';
    }

    public function netopiaPrivatePath(Company $company): string
    {
        return $this->netopiaDir($company).'/private.key';
    }

    public function isNetopiaReady(Company $company): bool
    {
        return $this->netopiaConfigurationStatus($company)['ready'];
    }

    /**
     * @return array{
     *     ready: bool,
     *     enabled: bool,
     *     signature: bool,
     *     public_cer: bool,
     *     private_key: bool,
     *     sandbox: bool,
     *     missing: list<string>
     * }
     */
    public function netopiaConfigurationStatus(Company $company): array
    {
        $enabled = $this->getBool($company, 'netopia', 'enabled');
        $sig = trim((string) $this->get($company, 'netopia', 'signature', '')) !== '';
        $pub = is_readable($this->netopiaPublicPath($company));
        $priv = is_readable($this->netopiaPrivatePath($company));
        $sandbox = $this->getBool($company, 'netopia', 'sandbox', false);

        $missing = [];
        if (! $enabled) {
            $missing[] = 'Bifează „Activează NETOPIA pentru plata facturilor”.';
        }
        if (! $sig) {
            $missing[] = 'Completează semnătura merchant.';
        }
        if (! $pub) {
            $missing[] = 'Încarcă public.cer din panoul NETOPIA al firmei.';
        }
        if (! $priv) {
            $missing[] = 'Încarcă private.key din panoul NETOPIA al firmei.';
        }

        return [
            'ready' => $enabled && $sig && $pub && $priv,
            'enabled' => $enabled,
            'signature' => $sig,
            'public_cer' => $pub,
            'private_key' => $priv,
            'sandbox' => $sandbox,
            'missing' => $missing,
        ];
    }

    public function isEuPlatescReady(Company $company): bool
    {
        if (! $this->getBool($company, 'euplatesc', 'enabled')) {
            return false;
        }

        return filled($this->get($company, 'euplatesc', 'mid'))
            && filled($this->get($company, 'euplatesc', 'key'));
    }

    public function isMollieReady(Company $company): bool
    {
        if (! $this->getBool($company, 'mollie', 'enabled')) {
            return false;
        }
        $key = trim((string) $this->get($company, 'mollie', 'key', ''));

        return $key !== '' && (str_starts_with($key, 'test_') || str_starts_with($key, 'live_'));
    }

    public function isStripeReady(Company $company): bool
    {
        if (! $this->getBool($company, 'stripe', 'enabled')) {
            return false;
        }
        $key = trim((string) $this->get($company, 'stripe', 'key', ''));
        $secret = trim((string) $this->get($company, 'stripe', 'secret', ''));

        return $key !== ''
            && $secret !== ''
            && (str_starts_with($key, 'pk_test_') || str_starts_with($key, 'pk_live_'))
            && (str_starts_with($secret, 'sk_test_') || str_starts_with($secret, 'sk_live_') || str_starts_with($secret, 'rk_live_') || str_starts_with($secret, 'rk_test_'));
    }

    public function isReady(Company $company, string $processor): bool
    {
        return match ($processor) {
            'netopia' => $this->isNetopiaReady($company),
            'euplatesc' => $this->isEuPlatescReady($company),
            'mollie' => $this->isMollieReady($company),
            'stripe' => $this->isStripeReady($company),
            default => false,
        };
    }

    /**
     * @return array<string, array{key:string,label:string,short:string}>
     */
    public function active(Company $company): array
    {
        $defs = [
            'netopia' => ['label' => 'NETOPIA Payments', 'short' => 'NETOPIA'],
            'euplatesc' => ['label' => 'Eu Plătesc', 'short' => 'Eu Plătesc'],
            'mollie' => ['label' => 'Mollie', 'short' => 'Mollie'],
            'stripe' => ['label' => 'Stripe', 'short' => 'Stripe'],
        ];
        $active = [];
        foreach ($defs as $key => $meta) {
            if ($this->isReady($company, $key)) {
                $active[$key] = ['key' => $key] + $meta;
            }
        }

        return $active;
    }

    public function anyActive(Company $company): bool
    {
        return $this->active($company) !== [];
    }

    public function netopiaPaymentUrl(Company $company): string
    {
        $sandbox = $this->getBool($company, 'netopia', 'sandbox', false);

        return $sandbox
            ? 'https://sandboxsecure.mobilpay.ro'
            : 'https://secure.mobilpay.ro';
    }

    /**
     * Chei private NETOPIA ale firmelor (pentru IPN), fără a depinde doar de checkout-uri pending.
     *
     * @return list<string>
     */
    public function allNetopiaPrivateKeyPaths(): array
    {
        $paths = [];
        $root = storage_path('app/companies');
        if (! is_dir($root)) {
            return [];
        }

        foreach (scandir($root) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || ! ctype_digit((string) $entry)) {
                continue;
            }
            $path = $root.'/'.$entry.'/netopia/private.key';
            if (is_readable($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function euplatescPaymentUrl(): string
    {
        return 'https://secure.euplatesc.ro/tdsprocess/tranzactd.php';
    }
}
