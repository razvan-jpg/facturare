<?php

use App\Support\UiLocales;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('dc_date')) {
    /** Format afișare: zz/ll/aaaa */
    function dc_date(null|string|CarbonInterface $value, string $empty = '—'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return $empty;
        }
    }
}

if (! function_exists('dc_datetime')) {
    /** Format afișare: zz/ll/aaaa HH:mm */
    function dc_datetime(null|string|CarbonInterface $value, string $empty = '—'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $empty;
        }
    }
}

if (! function_exists('dc_date_input')) {
    /** Valoare pentru input text zz/ll/aaaa (sau gol). */
    function dc_date_input(null|string|CarbonInterface $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Dacă vine deja zz/ll/aaaa din old(), păstrează.
        if (is_string($value) && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return is_string($value) ? $value : '';
        }
    }
}

if (! function_exists('dc_parse_emails')) {
    /**
     * Extrage adrese email dintr-un șir separat prin virgulă (sau ;).
     *
     * @return list<string>
     */
    function dc_parse_emails(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/[,;]+/', $value) ?: [];
        $emails = [];
        foreach ($parts as $part) {
            $email = mb_strtolower(trim($part), 'UTF-8');
            if ($email !== '') {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }
}

if (! function_exists('dc_format_cui')) {
    /**
     * Afișare CUI: cu prefix RO pentru plătitori TVA, doar cifre pentru neplătitori.
     */
    function dc_format_cui(?string $cui, bool $vatPayer = false): string
    {
        $digits = preg_replace('/\D+/', '', (string) $cui) ?? '';
        if ($digits === '') {
            return trim((string) $cui);
        }

        return $vatPayer ? 'RO'.$digits : $digits;
    }
}

if (! function_exists('dc_normalize_county')) {
    /** Normalizează valori vechi tip „Sector 3” → „București - Sector 3”. */
    function dc_normalize_county(?string $county): string
    {
        $county = trim((string) $county);
        if ($county === '') {
            return '';
        }

        if (preg_match('/^(?:bucure[sș]ti\s*[-–]?\s*)?sector(?:ul)?\s*([1-6])$/iu', $county, $m)) {
            return 'București - Sector '.$m[1];
        }

        return $county;
    }
}

if (! function_exists('ui_locale_codes')) {
    /** @return list<string> */
    function ui_locale_codes(): array
    {
        return UiLocales::codes();
    }
}

if (! function_exists('ui_locale_options')) {
    /** @return array<string, string> */
    function ui_locale_options(): array
    {
        return UiLocales::options();
    }
}

if (! function_exists('ui_locale_normalize')) {
    function ui_locale_normalize(?string $locale): string
    {
        return UiLocales::normalize($locale);
    }
}

if (! function_exists('dc_parse_date')) {
    /** Normalizează zz/ll/aaaa (sau Y-m-d) → Y-m-d. */
    function dc_parse_date(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return $value;
        }
    }
}
