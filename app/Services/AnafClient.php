<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnafClient
{
    public function lookup(string $cui): ?array
    {
        $cui = preg_replace('/\D+/', '', $cui) ?? '';

        if ($cui === '') {
            return null;
        }

        $results = $this->lookupMany([$cui]);

        return $results[$cui] ?? null;
    }

    /**
     * Preluare în loturi (max. 100 CUI / request ANAF).
     *
     * @param  list<string>  $cuis
     * @return array<string, array{name: string, cui: string, reg_com: string, address: string, city: string, county: string, phone: string, vat_payer: bool}>
     */
    public function lookupMany(array $cuis): array
    {
        $normalized = [];
        foreach ($cuis as $cui) {
            $digits = preg_replace('/\D+/', '', (string) $cui) ?? '';
            if ($digits !== '' && strlen($digits) <= 10) {
                $normalized[$digits] = true;
            }
        }

        $unique = array_keys($normalized);
        if ($unique === []) {
            return [];
        }

        $out = [];
        foreach (array_chunk($unique, 100) as $chunk) {
            $mapped = $this->requestChunk($chunk);
            foreach ($mapped as $key => $row) {
                $out[$key] = $row;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $cuis
     * @return array<string, array{name: string, cui: string, reg_com: string, address: string, city: string, county: string, phone: string, vat_payer: bool}>
     */
    private function requestChunk(array $cuis): array
    {
        $payload = array_map(
            fn (string $cui) => ['cui' => (int) $cui, 'data' => now()->format('Y-m-d')],
            $cuis
        );

        try {
            $response = Http::timeout(45)
                ->acceptJson()
                ->post('https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva', $payload);

            if (! $response->successful()) {
                return $this->fallbackChunk($cuis);
            }

            $data = $response->json();
            $foundList = data_get($data, 'found');
            if (! is_array($foundList)) {
                // Răspuns pe un singur CUI (formă veche / un element)
                $single = data_get($data, 'found.0') ?? data_get($data, '0') ?? null;
                $foundList = $single ? [$single] : [];
            }

            $out = [];
            foreach ($foundList as $found) {
                $mapped = $this->mapFound($found);
                if ($mapped === null) {
                    continue;
                }
                $key = preg_replace('/\D+/', '', $mapped['cui']) ?? '';
                if ($key !== '') {
                    $out[$key] = $mapped;
                }
            }

            foreach ($cuis as $cui) {
                if (! isset($out[$cui])) {
                    $fallback = $this->fallbackLookup($cui);
                    if ($fallback !== null) {
                        $out[$cui] = $fallback;
                    }
                }
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('ANAF lookupMany failed: '.$e->getMessage());

            return $this->fallbackChunk($cuis);
        }
    }

    /**
     * @param  list<string>  $cuis
     * @return array<string, array{name: string, cui: string, reg_com: string, address: string, city: string, county: string, phone: string, vat_payer: bool}>
     */
    private function fallbackChunk(array $cuis): array
    {
        $out = [];
        foreach ($cuis as $cui) {
            $fallback = $this->fallbackLookup($cui);
            if ($fallback !== null) {
                $out[$cui] = $fallback;
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $found
     * @return array{name: string, cui: string, reg_com: string, address: string, city: string, county: string, phone: string, vat_payer: bool}|null
     */
    private function mapFound(mixed $found): ?array
    {
        if (! is_array($found)) {
            return null;
        }

        $general = data_get($found, 'date_generale', $found);

        if (! $general || ! data_get($general, 'denumire')) {
            return null;
        }

        $sede = data_get($found, 'adresa_sediu_social', []) ?: [];
        $fullAddress = trim((string) (data_get($general, 'adresa') ?? ''));

        $city = trim((string) (
            data_get($sede, 'sdenumire_Localitate')
            ?: data_get($general, 'sdenumire_Localitate')
            ?: ''
        ));
        $countyRaw = trim((string) (
            data_get($sede, 'sdenumire_Judet')
            ?: data_get($general, 'sdenumire_Judet')
            ?: ''
        ));

        [$city, $county] = $this->normalizePlace($city, $countyRaw, $fullAddress);
        $address = $this->streetAddress(is_array($sede) ? $sede : [], $fullAddress, $city, $county);
        $cui = (string) (data_get($general, 'cui') ?? '');

        return [
            'name' => (string) data_get($general, 'denumire'),
            'cui' => $cui,
            'reg_com' => (string) (data_get($general, 'nrRegCom') ?? ''),
            'address' => $address,
            'city' => $city,
            'county' => $county,
            'phone' => (string) (data_get($general, 'telefon') ?? ''),
            'vat_payer' => (bool) data_get($found, 'inregistrare_scop_Tva.scpTVA', true),
        ];
    }

    /**
     * Construiește doar strada (+ nr. + detalii), fără localitate/județ.
     *
     * @param  array<string, mixed>  $sede
     */
    private function streetAddress(array $sede, string $fullAddress, string $city, string $county): string
    {
        $street = trim((string) (data_get($sede, 'sdenumire_Strada') ?? ''));
        $number = trim((string) (data_get($sede, 'snumar_Strada') ?? ''));
        $details = trim((string) (data_get($sede, 'sdetalii_Adresa') ?? ''));

        if ($street !== '') {
            $parts = [$street];
            if ($number !== '') {
                $parts[] = (preg_match('/^nr\.?\s*/iu', $number) ? $number : 'nr. '.$number);
            }
            if ($details !== '') {
                $parts[] = $details;
            }

            return implode(', ', $parts);
        }

        return $this->stripPlaceFromAddress($fullAddress, $city, $county);
    }

    private function stripPlaceFromAddress(string $address, string $city, string $county): string
    {
        if ($address === '') {
            return '';
        }

        $parts = preg_split('/\s*,\s*/u', $address) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));

        $drop = array_filter([
            $city,
            $county,
            'București',
            'Bucuresti',
            'Municipiul București',
            'Municipiul Bucuresti',
        ]);

        $parts = array_values(array_filter($parts, function (string $part) use ($drop) {
            $ascii = Str::ascii(mb_strtolower($part));

            // JUD. CLUJ / JUDEȚUL CLUJ / MUN. CLUJ-NAPOCA / LOC. ... / SECTOR 3
            if (preg_match('/^(jud\.?|judetul|judet|mun\.?|municipiul|loc\.?|orasul|orașul|com\.?|comuna|sat\.?|sat)\b/iu', $part)) {
                return false;
            }
            if (preg_match('/^sector(?:ul)?\s*[1-6]$/iu', $part)) {
                return false;
            }

            foreach ($drop as $token) {
                $token = trim((string) $token);
                if ($token === '') {
                    continue;
                }
                if (Str::ascii(mb_strtolower($token)) === $ascii) {
                    return false;
                }
            }

            return true;
        }));

        return implode(', ', $parts);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function normalizePlace(string $city, string $county, string $address): array
    {
        $sectorFromCity = $this->extractSector($city);
        $sectorFromAddress = $this->extractSector($address);
        $sector = $sectorFromCity ?: $sectorFromAddress;

        $countyNorm = $this->matchCounty($county);
        $cityClean = $this->cleanLocality($city);

        // Județ clar în afara Bucureștiului (ex. Călărași + Str. București) — nu confunda strada cu localitatea.
        $trustedNonBucharestCounty = $countyNorm !== ''
            && ! $this->isBucharest($countyNorm)
            && ! str_starts_with($countyNorm, 'București');

        if ($trustedNonBucharestCounty) {
            return [$cityClean, $countyNorm];
        }

        if ($this->isBucharest($county) || $this->isBucharest($city) || $this->addressIndicatesBucharest($address) || $sector) {
            $countyOut = $sector
                ? 'București - '.$sector
                : (str_starts_with($countyNorm, 'București - Sector') ? $countyNorm : 'București');

            return [
                'București',
                $countyOut,
            ];
        }

        // Dacă localitatea ANAF e goală, nu inventăm nimic (mai ales nu data înființării).
        return [$cityClean, $countyNorm ?: $county];
    }

    /**
     * Detectează București în adresa completă, ignorând denumiri de stradă (ex. „Str. București”).
     */
    private function addressIndicatesBucharest(string $address): bool
    {
        if ($address === '') {
            return false;
        }

        $withoutStreets = preg_replace(
            '/\b(str\.?|strada|bd\.?|bulevardul|sos\.?|șoseaua|soseaua|alea|intr\.?|intrarea)\s+[^,]*/iu',
            '',
            $address
        ) ?? $address;

        return $this->isBucharest($withoutStreets);
    }

    private function cleanLocality(string $city): string
    {
        $city = trim($city);
        if ($city === '') {
            return '';
        }

        // "Mun. Cluj-Napoca" / "Loc. Bărăbanţ Mun. Alba Iulia" → păstrăm denumirea utilă
        $city = preg_replace('/^(mun\.?|municipiul|loc\.?|orasul|orașul|com\.?|comuna|sat\.?|sat)\s+/iu', '', $city) ?? $city;

        // "Sector 3 Mun. București" → lăsăm normalizePlace să trateze Bucureștiul
        if ($this->extractSector($city) && $this->isBucharest($city)) {
            return 'București';
        }

        return trim($city);
    }

    private function extractSector(string $text): ?string
    {
        if (preg_match('/SECTOR(?:UL)?\s*([1-6])/iu', $text, $m)) {
            return 'Sector '.$m[1];
        }

        return null;
    }

    private function isBucharest(string $value): bool
    {
        $n = Str::ascii(mb_strtolower($value));

        return str_contains($n, 'bucure') || str_contains($n, 'municipiul bucure');
    }

    private function matchCounty(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        if ($this->isBucharest($raw)) {
            return 'București';
        }

        if (preg_match('/sector(?:ul)?\s*([1-6])/iu', $raw, $m)) {
            return 'București - Sector '.$m[1];
        }

        $needle = Str::ascii(mb_strtolower(trim($raw)));
        $needle = preg_replace('/^(judetul|jud\.?)\s+/u', '', $needle) ?? $needle;

        foreach (config('romania.counties', []) as $county) {
            $candidate = Str::ascii(mb_strtolower($county));
            if ($candidate === $needle || str_contains($needle, $candidate) || str_contains($candidate, $needle)) {
                return $county;
            }
        }

        return $raw;
    }

    private function fallbackLookup(string $cui): ?array
    {
        if ($cui === '38254880') {
            $op = config('dateconta.platform_operator');

            return [
                'name' => $op['name'],
                'cui' => $op['cui'],
                'reg_com' => $op['reg_com'],
                'address' => $op['address'],
                'city' => $op['city'],
                'county' => $op['county'],
                'phone' => '',
                'vat_payer' => true,
            ];
        }

        return null;
    }
}
