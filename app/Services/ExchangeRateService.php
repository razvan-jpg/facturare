<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExchangeRateService
{
    /** Curs BNR: RON pentru 1 unitate valută. */
    public function rateToRon(string $currency): float
    {
        $currency = strtoupper(trim($currency));
        if ($currency === '' || $currency === 'RON') {
            return 1.0;
        }

        $rates = $this->bnrRates();
        if (! isset($rates[$currency])) {
            throw new RuntimeException('Nu am găsit curs BNR pentru '.$currency.'.');
        }

        return round((float) $rates[$currency], 4);
    }

    /** Curs BNR × markup (ex. 1.02 = +2%) pentru încasări NETOPIA. */
    public function rateToRonWithMarkup(string $currency, ?float $markup = null): float
    {
        $currency = strtoupper(trim($currency));
        if ($currency === '' || $currency === 'RON') {
            return 1.0;
        }

        $markup ??= (float) config('dateconta.subscription.netopia_ron_markup', 1.02);
        if ($markup <= 0) {
            $markup = 1.0;
        }

        return round($this->rateToRon($currency) * $markup, 4);
    }

    /**
     * Convertește sume catalog (net/TVA/total) în RON cu același curs (BNR × markup).
     *
     * @return array{amount_net: float, amount_vat: float, amount_total: float, currency: string, fx_rate: float, fx_bnr: float, source_currency: string, source_net: float, source_vat: float, source_total: float}
     */
    public function convertSubscriptionAmountsToRon(
        float $amountNet,
        float $amountVat,
        float $amountTotal,
        string $sourceCurrency = 'EUR',
        ?float $vatRate = null,
        ?float $markup = null,
    ): array {
        $sourceCurrency = strtoupper(trim($sourceCurrency)) ?: 'EUR';
        $vatRate ??= (float) config('dateconta.subscription.vat_rate', 21);

        if ($sourceCurrency === 'RON') {
            return [
                'amount_net' => round($amountNet, 2),
                'amount_vat' => round($amountVat, 2),
                'amount_total' => round($amountTotal, 2),
                'currency' => 'RON',
                'fx_rate' => 1.0,
                'fx_bnr' => 1.0,
                'source_currency' => 'RON',
                'source_net' => round($amountNet, 2),
                'source_vat' => round($amountVat, 2),
                'source_total' => round($amountTotal, 2),
            ];
        }

        $bnr = $this->rateToRon($sourceCurrency);
        $rate = $this->rateToRonWithMarkup($sourceCurrency, $markup);
        $netRon = round($amountNet * $rate, 2);
        $vatRon = round($netRon * $vatRate / 100, 2);
        $totalRon = round($netRon + $vatRon, 2);

        return [
            'amount_net' => $netRon,
            'amount_vat' => $vatRon,
            'amount_total' => $totalRon,
            'currency' => 'RON',
            'fx_rate' => $rate,
            'fx_bnr' => $bnr,
            'source_currency' => $sourceCurrency,
            'source_net' => round($amountNet, 2),
            'source_vat' => round($amountVat, 2),
            'source_total' => round($amountTotal, 2),
        ];
    }

    /** @return array<string, float> */
    public function bnrRates(): array
    {
        return Cache::remember('bnr_fx_rates_v1', now()->addHours(6), function () {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'DateConta-Facturare/1.0'])
                ->get('https://www.bnr.ro/nbrfxrates.xml');

            if (! $response->successful()) {
                throw new RuntimeException('Nu am putut prelua cursurile BNR.');
            }

            $xml = @simplexml_load_string($response->body());
            if (! $xml) {
                throw new RuntimeException('Răspuns BNR invalid.');
            }

            $rates = [];
            foreach ($xml->Body->Cube->Rate ?? [] as $rate) {
                $code = strtoupper((string) $rate['currency']);
                $multiplier = (float) ($rate['multiplier'] ?? 1);
                $value = (float) str_replace(',', '.', (string) $rate);
                if ($code === '' || $value <= 0) {
                    continue;
                }
                if ($multiplier > 1) {
                    $value = $value / $multiplier;
                }
                $rates[$code] = $value;
            }

            if ($rates === []) {
                throw new RuntimeException('Lista de cursuri BNR este goală.');
            }

            Log::info('BNR FX rates refreshed', ['count' => count($rates)]);

            return $rates;
        });
    }
}
