<?php

namespace App\Support;

class UiLocales
{
    /** @var array<string, string> */
    public const ALIASES = [
        'en' => 'en_US',
        'zh' => 'zh_CN',
        'cn' => 'zh_CN',
        'cmn' => 'zh_Hans',
        'ua' => 'uk',
        'gr' => 'el',
        'no' => 'nb',
        'nn' => 'nb',
        'iw' => 'he',
        'jp' => 'ja',
        'cz' => 'cs',
        'dk' => 'da',
        'se' => 'sv',
        'at' => 'de_AT',
        'ch' => 'de_CH',
        'eg' => 'ar_EG',
        'sy' => 'ar_SY',
        'jo' => 'ar_JO',
        'tn' => 'ar_TN',
        'ma' => 'ar_MA',
        'tl' => 'fil',
        'ko_KR' => 'ko',
        'kr' => 'ko',
        'fa_IR' => 'fa',
        'swahili' => 'sw',
    ];

    /**
     * @return array<string, array{label: string, flag: string, short?: string, sort_key?: string}>
     */
    public static function all(): array
    {
        $raw = config('ui_locales', []);
        $out = [];
        foreach ($raw as $code => $meta) {
            if ($code === 'forced_by_cui') {
                continue;
            }
            if (is_string($meta)) {
                $out[$code] = [
                    'label' => $meta,
                    'flag' => '',
                    'short' => strtoupper($code),
                    'sort_key' => mb_strtolower($meta),
                ];
            } elseif (is_array($meta)) {
                $out[$code] = [
                    'label' => (string) ($meta['label'] ?? $code),
                    'flag' => (string) ($meta['flag'] ?? ''),
                    'short' => (string) ($meta['short'] ?? strtoupper($code)),
                    'sort_key' => (string) ($meta['sort_key'] ?? mb_strtolower((string) ($meta['label'] ?? $code))),
                ];
            }
        }

        // Română, English US, English UK, apoi restul alfabetic după sort_key.
        uasort($out, function (array $a, array $b): int {
            $ka = (string) ($a['sort_key'] ?? '');
            $kb = (string) ($b['sort_key'] ?? '');
            $fixed = ['0' => 0, '1' => 1, '2' => 2];
            $fa = $fixed[$ka] ?? null;
            $fb = $fixed[$kb] ?? null;
            if ($fa !== null || $fb !== null) {
                if ($fa !== null && $fb !== null) {
                    return $fa <=> $fb;
                }

                return $fa !== null ? -1 : 1;
            }

            return strcasecmp($ka, $kb);
        });

        return $out;
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function normalize(?string $locale): string
    {
        $locale = trim((string) $locale);
        if ($locale === '') {
            return 'ro';
        }

        if (isset(self::ALIASES[$locale])) {
            $locale = self::ALIASES[$locale];
        }

        // ar-EG / de-AT → ar_EG / de_AT
        $locale = str_replace('-', '_', $locale);

        return in_array($locale, self::codes(), true) ? $locale : 'ro';
    }

    public static function label(string $code): string
    {
        $meta = self::all()[$code] ?? null;

        return $meta['label'] ?? $code;
    }

    public static function flag(string $code): string
    {
        $meta = self::all()[$code] ?? null;

        return $meta['flag'] ?? '';
    }

    /** Text pentru <option>: „🇺🇸 English (US)”. */
    public static function optionLabel(string $code): string
    {
        $flag = self::flag($code);
        $label = self::label($code);

        return trim(($flag !== '' ? $flag.' ' : '').$label);
    }

    /**
     * @return array<string, string> code => option label
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::codes() as $code) {
            $out[$code] = self::optionLabel($code);
        }

        return $out;
    }
}
