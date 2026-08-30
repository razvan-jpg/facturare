<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DocumentTemplateVariables
{
    /** @var list<string> */
    public const MONTHS_RO = [
        1 => 'ianuarie',
        2 => 'februarie',
        3 => 'martie',
        4 => 'aprilie',
        5 => 'mai',
        6 => 'iunie',
        7 => 'iulie',
        8 => 'august',
        9 => 'septembrie',
        10 => 'octombrie',
        11 => 'noiembrie',
        12 => 'decembrie',
    ];

    /**
     * Înlocuiește #luna#, #luna+1#, #luna-2#, #an#, #an+1# etc. pe baza datei de emitere.
     */
    public function expand(?string $text, CarbonInterface|string|null $date = null): ?string
    {
        if ($text === null) {
            return null;
        }

        if ($text === '' || ! str_contains($text, '#')) {
            return $text;
        }

        $base = $date instanceof CarbonInterface
            ? $date->copy()->startOfDay()
            : Carbon::parse($date ?: now())->startOfDay();

        $text = preg_replace_callback(
            '/#luna([+-]\d+)?#/iu',
            function (array $m) use ($base) {
                $offset = isset($m[1]) ? (int) $m[1] : 0;
                $month = $base->copy()->addMonthsNoOverflow($offset);

                return self::MONTHS_RO[(int) $month->month] ?? $month->translatedFormat('F');
            },
            $text
        );

        $text = preg_replace_callback(
            '/#an([+-]\d+)?#/iu',
            function (array $m) use ($base) {
                $offset = isset($m[1]) ? (int) $m[1] : 0;
                // Offset pe #an# = ani; dacă e folosit după luna cu offset, rămâne independent (ca în SmartBill).
                return (string) ($base->year + $offset);
            },
            $text
        );

        return $text;
    }
}
