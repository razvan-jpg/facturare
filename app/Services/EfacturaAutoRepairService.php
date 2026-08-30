<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Throwable;

class EfacturaAutoRepairService
{
    public function __construct(private AnafClient $anaf) {}

    /**
     * @return array{types: list<string>, codes: list<string>, fingerprint: string}
     */
    public function diagnose(?string $error): array
    {
        $raw = (string) $error;
        $lower = mb_strtolower($raw);
        $codes = [];
        if (preg_match_all('/\bBR-[A-Z0-9-]+/u', $raw, $m)) {
            $codes = array_values(array_unique($m[0]));
        }

        $types = [];
        if (
            str_contains($lower, 'br-ro-100')
            || str_contains($lower, 'sector-ro')
            || (str_contains($lower, 'sector') && (str_contains($lower, 'ro-b') || str_contains($lower, 'bucure')))
        ) {
            $types[] = 'missing_sector';
        }
        if (str_contains($lower, 'bucure') && (str_contains($lower, 'localitate') || str_contains($lower, 'bt-52') || str_contains($lower, 'city'))) {
            $types[] = 'bucharest_city';
        }
        if (
            str_contains($lower, 'timeout')
            || str_contains($lower, 'timed out')
            || str_contains($lower, 'connection')
            || str_contains($lower, 'token')
            || str_contains($lower, 'unauthorized')
            || str_contains($lower, 'http 5')
            || str_contains($lower, 'http 429')
            || str_contains($lower, 'temporar')
        ) {
            $types[] = 'transient';
        }
        if ($types === [] && $codes === [] && $raw !== '') {
            $types[] = 'unknown';
        }
        if ($raw === '') {
            $types[] = 'transient';
        }

        return [
            'types' => array_values(array_unique($types)),
            'codes' => $codes,
            'fingerprint' => substr(hash('sha256', mb_strtolower(trim($raw))), 0, 40),
        ];
    }

    /**
     * @param  array{types: list<string>, codes: list<string>, fingerprint: string}  $diagnosis
     */
    public function repair(Document $document, array $diagnosis): bool
    {
        $types = $diagnosis['types'] ?? [];
        if (in_array('transient', $types, true) && count($types) === 1) {
            // Doar retry, fără mutarea datelor.
            return true;
        }

        $changed = false;
        $needsAddressFix = array_intersect($types, ['missing_sector', 'bucharest_city', 'unknown']) !== [];

        if ($needsAddressFix) {
            $changed = $this->repairBuyerAddress($document) || $changed;
        }

        // Sector explicit din textul existent (fără ANAF).
        if (in_array('missing_sector', $types, true) || in_array('bucharest_city', $types, true)) {
            $changed = $this->applySectorFromText($document) || $changed;
        }

        return $changed || in_array('transient', $types, true);
    }

    private function repairBuyerAddress(Document $document): bool
    {
        $document->loadMissing('client');
        $client = $document->client;
        $cui = preg_replace('/\D+/', '', (string) ($client?->cui ?: $document->client_cui)) ?: '';
        if ($cui === '' || strlen($cui) < 2) {
            return false;
        }

        try {
            $data = $this->anaf->lookup($cui);
        } catch (Throwable $e) {
            Log::warning('e-Factura auto-repair ANAF lookup failed', [
                'document_id' => $document->id,
                'cui' => $cui,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! is_array($data)) {
            return false;
        }

        $changed = false;
        if ($client instanceof Client) {
            $payload = array_filter([
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'county' => $data['county'] ?? null,
            ], fn ($v) => filled($v));

            if ($payload !== []) {
                $before = $client->only(['address', 'city', 'county']);
                $client->fill($payload)->save();
                $after = $client->only(['address', 'city', 'county']);
                $changed = $before !== $after;
            }
        }

        if ($client) {
            $document->forceFill([
                'client_address' => $client->fullAddress(),
                'client_name' => $client->name ?: $document->client_name,
                'client_cui' => $client->type === 'person'
                    ? ($client->cnp ?: $document->client_cui)
                    : ($client->cui ?: $document->client_cui),
            ])->save();
        }

        return $changed;
    }

    private function applySectorFromText(Document $document): bool
    {
        $document->loadMissing('client');
        $client = $document->client;
        $hay = trim(implode(' ', array_filter([
            $client?->county,
            $client?->city,
            $client?->address,
            $document->client_address,
        ])));

        if (! preg_match('/sector(?:ul)?\s*([1-6])/iu', $hay, $m)) {
            return false;
        }

        $sector = 'Sector '.$m[1];
        $changed = false;

        if ($client) {
            $county = 'București - '.$sector;
            $city = 'București';
            if ($client->county !== $county || $client->city !== $city) {
                $client->forceFill([
                    'city' => $city,
                    'county' => $county,
                ])->save();
                $changed = true;
            }
            $document->forceFill([
                'client_address' => $client->fullAddress(),
            ])->save();
        }

        return $changed;
    }
}
