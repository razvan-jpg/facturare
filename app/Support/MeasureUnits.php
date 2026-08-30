<?php

namespace App\Support;

/**
 * Unități de măsură UN/ECE Rec. 20 — catalog de referință + aliasuri.
 * Lista live pe firmă e în `measure_units`; aici e corespondența e-Factura.
 */
class MeasureUnits
{
    /**
     * @return array<string, array{code:string,short:string,label:string}>
     */
    public static function definitions(): array
    {
        return [
            'H87' => ['code' => 'H87', 'short' => 'buc', 'label' => 'bucăți'],
            'KGM' => ['code' => 'KGM', 'short' => 'kg', 'label' => 'kilogram'],
            'GRM' => ['code' => 'GRM', 'short' => 'g', 'label' => 'gram'],
            'TNE' => ['code' => 'TNE', 'short' => 't', 'label' => 'tonă'],
            'LTR' => ['code' => 'LTR', 'short' => 'l', 'label' => 'litru'],
            'MLT' => ['code' => 'MLT', 'short' => 'ml', 'label' => 'mililitru'],
            'MTR' => ['code' => 'MTR', 'short' => 'm', 'label' => 'metru'],
            'MTK' => ['code' => 'MTK', 'short' => 'm²', 'label' => 'metru pătrat'],
            'MTQ' => ['code' => 'MTQ', 'short' => 'm³', 'label' => 'metru cub'],
            'KMT' => ['code' => 'KMT', 'short' => 'km', 'label' => 'kilometru'],
            'CMT' => ['code' => 'CMT', 'short' => 'cm', 'label' => 'centimetru'],
            'MMT' => ['code' => 'MMT', 'short' => 'mm', 'label' => 'milimetru'],
            'HUR' => ['code' => 'HUR', 'short' => 'oră', 'label' => 'oră'],
            'DAY' => ['code' => 'DAY', 'short' => 'zi', 'label' => 'zi'],
            'MON' => ['code' => 'MON', 'short' => 'lună', 'label' => 'lună'],
            'SET' => ['code' => 'SET', 'short' => 'set', 'label' => 'set'],
            'PR' => ['code' => 'PR', 'short' => 'pereche', 'label' => 'pereche'],
            'E48' => ['code' => 'E48', 'short' => 'serv', 'label' => 'serviciu'],
            'C62' => ['code' => 'C62', 'short' => 'un', 'label' => 'unitate'],
            'XPP' => ['code' => 'XPP', 'short' => 'pachet', 'label' => 'pachet'],
            'XBX' => ['code' => 'XBX', 'short' => 'cutie', 'label' => 'cutie'],
        ];
    }

    /** Aliasuri vechi / libere → cod UNECE. */
    private const ALIASES = [
        'buc' => 'H87', 'buc.' => 'H87', 'bucata' => 'H87', 'bucată' => 'H87',
        'bucati' => 'H87', 'bucăți' => 'H87', 'piece' => 'H87', 'pcs' => 'H87',
        'h87' => 'H87', 'h97' => 'H87',
        'kg' => 'KGM', 'kilogram' => 'KGM', 'kilograme' => 'KGM', 'kgm' => 'KGM',
        'g' => 'GRM', 'gr' => 'GRM', 'gram' => 'GRM', 'grame' => 'GRM', 'grm' => 'GRM',
        't' => 'TNE', 'to' => 'TNE', 'tona' => 'TNE', 'tonă' => 'TNE', 'tone' => 'TNE', 'tne' => 'TNE',
        'l' => 'LTR', 'lt' => 'LTR', 'litru' => 'LTR', 'litri' => 'LTR', 'ltr' => 'LTR',
        'ml' => 'MLT', 'mlt' => 'MLT',
        'm' => 'MTR', 'metru' => 'MTR', 'metri' => 'MTR', 'mtr' => 'MTR',
        'm2' => 'MTK', 'm²' => 'MTK', 'mp' => 'MTK', 'mtk' => 'MTK',
        'm3' => 'MTQ', 'm³' => 'MTQ', 'mc' => 'MTQ', 'mtq' => 'MTQ',
        'km' => 'KMT', 'kmt' => 'KMT',
        'cm' => 'CMT', 'cmt' => 'CMT',
        'mm' => 'MMT', 'mmt' => 'MMT',
        'h' => 'HUR', 'ora' => 'HUR', 'oră' => 'HUR', 'ore' => 'HUR', 'hur' => 'HUR',
        'zi' => 'DAY', 'zile' => 'DAY', 'day' => 'DAY',
        'luna' => 'MON', 'lună' => 'MON', 'luni' => 'MON', 'mon' => 'MON',
        'set' => 'SET',
        'pereche' => 'PR', 'pr' => 'PR',
        'serv' => 'E48', 'serviciu' => 'E48', 'servicii' => 'E48', 'e48' => 'E48',
        'un' => 'C62', 'unitate' => 'C62', 'c62' => 'C62',
        'pachet' => 'XPP', 'xpp' => 'XPP',
        'cutie' => 'XBX', 'xbx' => 'XBX',
    ];

    public static function defaultCode(): string
    {
        return 'H87';
    }

    public static function defaultName(): string
    {
        return self::definitions()[self::defaultCode()]['short'];
    }

    /** Cod UNECE dacă e cunoscut; null pentru U/M custom necunoscută. */
    public static function resolveUnece(?string $unit): ?string
    {
        $raw = trim((string) $unit);
        if ($raw === '') {
            return self::defaultCode();
        }

        $upper = strtoupper($raw);
        if (isset(self::definitions()[$upper])) {
            return $upper;
        }

        $key = mb_strtolower($raw);

        return self::ALIASES[$key] ?? null;
    }

    /**
     * Normalizează la cod UNECE (compatibilitate veche).
     * Necunoscut → H87.
     */
    public static function code(?string $unit): string
    {
        return self::resolveUnece($unit) ?? self::defaultCode();
    }

    /**
     * Nume canonic de stocat/afișat: short UNECE sau textul introdus (max 32).
     */
    public static function canonicalName(?string $unit): string
    {
        $raw = trim((string) $unit);
        if ($raw === '') {
            return self::defaultName();
        }

        $unece = self::resolveUnece($raw);
        if ($unece) {
            return self::definitions()[$unece]['short'];
        }

        // Păstrează U/M custom (ex. palet); limitează lungimea.
        $name = preg_replace('/\s+/u', ' ', $raw) ?: self::defaultName();

        return mb_substr($name, 0, 32);
    }

    /** Afișare scurtă pe PDF/listă — custom rămâne ca atare. */
    public static function short(?string $unit): string
    {
        return self::canonicalName($unit);
    }

    public static function label(?string $unit): string
    {
        $unece = self::resolveUnece($unit);
        if ($unece) {
            $row = self::definitions()[$unece];

            return $row['short'].' — '.$row['label'].' ('.$unece.')';
        }

        $name = self::canonicalName($unit);

        return $name.' (custom)';
    }

    /**
     * Opțiuni legacy select: name => label.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::definitions() as $code => $row) {
            $out[$row['short']] = $row['short'].' — '.$row['label'].' ('.$code.')';
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function lookupMap(): array
    {
        $map = [];
        foreach (self::ALIASES as $alias => $code) {
            $map[$alias] = self::definitions()[$code]['short'];
        }
        foreach (self::definitions() as $code => $row) {
            $map[mb_strtolower($code)] = $row['short'];
            $map[mb_strtolower($row['short'])] = $row['short'];
            $map[mb_strtolower($row['label'])] = $row['short'];
        }

        return $map;
    }

    /** @deprecated Folosește partialul unit-input / datalist. */
    public static function optionsHtml(?string $selected = null): string
    {
        $selectedName = self::canonicalName($selected);
        $html = '';
        foreach (self::options() as $name => $label) {
            $sel = $name === $selectedName ? ' selected' : '';
            $html .= '<option value="'.e($name).'"'.$sel.'>'.e($label).'</option>';
        }

        return $html;
    }
}
