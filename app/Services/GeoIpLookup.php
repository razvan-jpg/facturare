<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GeoIpLookup
{
    /** @var array<string, string> */
    private const COUNTRY_RO = [
        'RO' => 'România',
        'MD' => 'Moldova',
        'DE' => 'Germania',
        'IT' => 'Italia',
        'ES' => 'Spania',
        'FR' => 'Franța',
        'GB' => 'Regatul Unit',
        'US' => 'SUA',
        'AT' => 'Austria',
        'HU' => 'Ungaria',
        'BG' => 'Bulgaria',
        'PL' => 'Polonia',
        'NL' => 'Olanda',
        'BE' => 'Belgia',
        'CH' => 'Elveția',
        'CA' => 'Canada',
        'IE' => 'Irlanda',
        'GR' => 'Grecia',
        'PT' => 'Portugalia',
        'CZ' => 'Cehia',
        'SE' => 'Suedia',
        'DK' => 'Danemarca',
        'NO' => 'Norvegia',
        'FI' => 'Finlanda',
        'UA' => 'Ucraina',
        'TR' => 'Turcia',
        'IL' => 'Israel',
        'AE' => 'EAU',
        'AU' => 'Australia',
        'CY' => 'Cipru',
        'LU' => 'Luxemburg',
        'SK' => 'Slovacia',
        'SI' => 'Slovenia',
        'HR' => 'Croația',
        'RS' => 'Serbia',
        'XX' => 'Necunoscut',
    ];

    /**
     * @return array{code: ?string, name: ?string}
     */
    public function resolve(?string $ip, ?string $headerCountry = null): array
    {
        $headerCode = $this->normalizeCode($headerCountry);
        if ($headerCode) {
            return [
                'code' => $headerCode,
                'name' => $this->nameFor($headerCode),
            ];
        }

        if (! $ip || ! $this->isPublicIp($ip)) {
            return ['code' => null, 'name' => null];
        }

        return Cache::remember('geoip:'.$ip, now()->addDays(7), function () use ($ip) {
            try {
                $response = Http::timeout(0.8)
                    ->connectTimeout(0.4)
                    ->acceptJson()
                    ->get('http://ip-api.com/json/'.$ip, [
                        'fields' => 'status,country,countryCode',
                        'lang' => 'en',
                    ]);

                if (! $response->ok()) {
                    return ['code' => null, 'name' => null];
                }

                $data = $response->json();
                if (($data['status'] ?? null) !== 'success') {
                    return ['code' => null, 'name' => null];
                }

                $code = $this->normalizeCode($data['countryCode'] ?? null);
                if (! $code) {
                    return ['code' => null, 'name' => null];
                }

                return [
                    'code' => $code,
                    'name' => $this->nameFor($code, $data['country'] ?? null),
                ];
            } catch (\Throwable) {
                return ['code' => null, 'name' => null];
            }
        });
    }

    public function nameFor(?string $code, ?string $fallback = null): ?string
    {
        $code = $this->normalizeCode($code);
        if (! $code) {
            return $fallback ? Str::limit($fallback, 80, '') : null;
        }

        return self::COUNTRY_RO[$code]
            ?? ($fallback ? Str::limit($fallback, 80, '') : $code);
    }

    private function normalizeCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '' || $code === 'XX' || $code === 'T1' || strlen($code) !== 2) {
            return null;
        }

        return $code;
    }

    private function isPublicIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
