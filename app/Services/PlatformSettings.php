<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PlatformSettings
{
    private const CACHE_KEY = 'platform_settings.all';

    public function all(): array
    {
        try {
            if (! Schema::hasTable('platform_settings')) {
                return [];
            }
        } catch (Throwable) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, 300, function () {
            return PlatformSetting::query()->pluck('value', 'key')->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function set(string $key, mixed $value): void
    {
        PlatformSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value],
        );
        Cache::forget(self::CACHE_KEY);
    }

    /** @param  array<string, mixed>  $pairs */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Suprascrie config/netopia, mollie, euplatesc cu valorile din DB (dacă există).
     */
    public function applyToConfig(): void
    {
        $all = $this->all();
        if ($all === []) {
            return;
        }

        // Dacă Admin a salvat vreodată netopia.enabled, respectă DB (inclusiv dezactivat).
        // .env rămâne fallback doar când cheia lipsește din DB.
        if (array_key_exists('netopia.enabled', $all)) {
            config(['netopia.enabled' => filter_var($all['netopia.enabled'], FILTER_VALIDATE_BOOLEAN)]);
        }
        if (array_key_exists('netopia.sandbox', $all)) {
            $sandbox = filter_var($all['netopia.sandbox'], FILTER_VALIDATE_BOOLEAN);
            config([
                'netopia.sandbox' => $sandbox,
                'netopia.payment_url' => $sandbox
                    ? 'https://sandboxsecure.mobilpay.ro'
                    : 'https://secure.mobilpay.ro',
            ]);
        }
        if (array_key_exists('netopia.signature', $all) && filled(trim((string) $all['netopia.signature']))) {
            config(['netopia.signature' => trim((string) $all['netopia.signature'])]);
        }

        if (array_key_exists('mollie.enabled', $all)) {
            config(['mollie.enabled' => filter_var($all['mollie.enabled'], FILTER_VALIDATE_BOOLEAN)]);
        }
        if (array_key_exists('mollie.key', $all) && filled($all['mollie.key'])) {
            config(['mollie.key' => $all['mollie.key']]);
        }

        if (array_key_exists('euplatesc.enabled', $all)) {
            config(['euplatesc.enabled' => filter_var($all['euplatesc.enabled'], FILTER_VALIDATE_BOOLEAN)]);
        }
        if (array_key_exists('euplatesc.sandbox', $all)) {
            $sandbox = filter_var($all['euplatesc.sandbox'], FILTER_VALIDATE_BOOLEAN);
            config([
                'euplatesc.sandbox' => $sandbox,
                'euplatesc.payment_url' => $sandbox
                    ? 'https://secure.euplatesc.ro/tdsprocess/tranzactd.php'
                    : 'https://secure.euplatesc.ro/tdsprocess/tranzactd.php',
            ]);
        }
        if (array_key_exists('euplatesc.mid', $all) && filled($all['euplatesc.mid'])) {
            config(['euplatesc.mid' => $all['euplatesc.mid']]);
        }
        if (array_key_exists('euplatesc.key', $all) && filled($all['euplatesc.key'])) {
            config(['euplatesc.key' => $all['euplatesc.key']]);
        }

        if (array_key_exists('stripe.enabled', $all)) {
            config(['stripe.enabled' => filter_var($all['stripe.enabled'], FILTER_VALIDATE_BOOLEAN)]);
        }
        if (array_key_exists('stripe.key', $all) && filled($all['stripe.key'])) {
            config(['stripe.key' => $all['stripe.key']]);
        }
        if (array_key_exists('stripe.secret', $all) && filled($all['stripe.secret'])) {
            config(['stripe.secret' => $all['stripe.secret']]);
        }
        if (array_key_exists('stripe.webhook_secret', $all) && filled($all['stripe.webhook_secret'])) {
            config(['stripe.webhook_secret' => $all['stripe.webhook_secret']]);
        }
    }
}
