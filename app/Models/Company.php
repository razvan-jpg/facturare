<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use RuntimeException;

class Company extends Model
{
    public const DEFAULT_SIGNATURE_TEXT = "Factura este valabilă fără semnătură\nși ștampilă, conform art. 319 alin. 29\ndin Legea 227/2015.";

    /** Scări afișare imagini branding pe PDF: 25% … 200%, pas 25. */
    public const BRANDING_SCALES = [
        '25' => '25%',
        '50' => '50%',
        '75' => '75%',
        '100' => '100%',
        '125' => '125%',
        '150' => '150%',
        '175' => '175%',
        '200' => '200%',
    ];

    /** Valori vechi (fracții) → procente. */
    public const BRANDING_SCALE_LEGACY = [
        '1/4' => '25',
        '1/3' => '25',
        '1/2' => '50',
        '1/1' => '100',
    ];

    /** Alfabet pentru cod promoțional (majuscule + cifre). */
    public const PROMO_CODE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public const EFACTURA_SEND_MODES = [
        'on_save' => 'La salvarea facturii',
        'delay_1' => 'La 1 zi după emitere',
        'delay_2' => 'La 2 zile după emitere',
        'delay_3' => 'La 3 zile după emitere',
        'manual' => 'Manual',
    ];

    public const OVERDUE_REMINDER_SCOPES = [
        'invoices' => 'Pe facturi restante',
        'balance' => 'Pe sold restant',
        'both' => 'Pe facturi și pe sold',
    ];

    public const OVERDUE_REMINDER_FREQUENCIES = [
        1 => 'Zilnic',
        3 => 'La 3 zile',
        7 => 'Săptămânal',
        14 => 'La 14 zile',
        30 => 'Lunar',
    ];

    protected $fillable = [
        'owner_id', 'name', 'cui', 'reg_com', 'address', 'city', 'county', 'country',
        'capital_social', 'phone', 'email', 'website', 'iban', 'bank_name',
        'vat_payer', 'vat_on_collection', 'default_vat_rate',
        'logo_path', 'logo_scale', 'signature_path', 'signature_scale', 'stamp_path', 'stamp_scale',
        'signature_text', 'show_signature_text',
        'invoice_color', 'invoice_template', 'invoice_notes',
        'series_responsible_name', 'series_responsible_role',
        'card_integrations',
        'document_languages', 'email_invoice_subject', 'email_invoice_body', 'preferences',
        'mail_use_custom_smtp', 'mail_smtp_username', 'mail_smtp_password',
        'mail_smtp_host', 'mail_smtp_port', 'mail_smtp_tls',
        'efactura_send_mode', 'anaf_access_token', 'anaf_refresh_token',
        'anaf_token_expires_at', 'anaf_authorized_at', 'anaf_authorized_by', 'anaf_cif',
        'overdue_reminders_enabled', 'overdue_reminder_frequency_days',
        'overdue_reminder_scope', 'overdue_reminder_include_statement',
        'overdue_reminder_grace_days',
    ];

    protected $casts = [
        'vat_payer' => 'boolean',
        'vat_on_collection' => 'boolean',
        'default_vat_rate' => 'decimal:2',
        'document_languages' => 'array',
        'preferences' => 'array',
        'card_integrations' => 'encrypted:array',
        'mail_use_custom_smtp' => 'boolean',
        'mail_smtp_password' => 'encrypted',
        'mail_smtp_port' => 'integer',
        'mail_smtp_tls' => 'boolean',
        'anaf_access_token' => 'encrypted',
        'anaf_refresh_token' => 'encrypted',
        'anaf_token_expires_at' => 'datetime',
        'anaf_authorized_at' => 'datetime',
        'overdue_reminders_enabled' => 'boolean',
        'overdue_reminder_include_statement' => 'boolean',
        'overdue_reminder_frequency_days' => 'integer',
        'overdue_reminder_grace_days' => 'integer',
        'show_signature_text' => 'boolean',
    ];

    protected $hidden = [
        'anaf_access_token',
        'anaf_refresh_token',
        'mail_smtp_password',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (filled($company->promo_code)) {
                return;
            }

            $company->promo_code = static::generateUniquePromoCode();
        });
    }

    /** Cod unic de forma XXXX-XXXX-XXXX (litere majuscule + cifre). */
    public static function generateUniquePromoCode(int $maxAttempts = 32): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = static::randomPromoCode();
            if (! static::query()->where('promo_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Nu s-a putut genera un cod promoțional unic.');
    }

    public static function randomPromoCode(): string
    {
        $alphabet = self::PROMO_CODE_ALPHABET;
        $len = strlen($alphabet);
        $chunk = static function () use ($alphabet, $len): string {
            $out = '';
            for ($i = 0; $i < 4; $i++) {
                $out .= $alphabet[random_int(0, $len - 1)];
            }

            return $out;
        };

        return $chunk().'-'.$chunk().'-'.$chunk();
    }

    public function hasCustomSmtp(): bool
    {
        return (bool) $this->mail_use_custom_smtp
            && filled($this->mail_smtp_host)
            && filled($this->mail_smtp_username)
            && filled($this->mail_smtp_password)
            && filled($this->mail_smtp_port);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    public function referredByCompany(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_company_id');
    }

    public function referredCompanies(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_company_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(DocumentSeries::class);
    }

    public function measureUnits(): HasMany
    {
        return $this->hasMany(MeasureUnit::class)->orderBy('name');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(CompanyBranch::class)->orderByDesc('is_main')->orderBy('sort_order')->orderBy('id');
    }

    public function preference(string $key, mixed $default = null): mixed
    {
        return data_get($this->preferences ?? [], $key, $default);
    }

    /** Documente pe pagină în liste (facturi, proforme etc.). */
    public function documentsPerPage(): int
    {
        $allowed = [10, 25, 50, 100];
        $value = (int) $this->preference('documents_per_page', 25);

        return in_array($value, $allowed, true) ? $value : 25;
    }

    /**
     * Limbi activate pentru emitere (mereu include ro).
     *
     * @return array<string, string> code => label
     */
    public function availableDocumentLanguages(): array
    {
        $all = config('document_languages', ['ro' => 'Română']);
        $enabled = $this->document_languages ?: ['ro'];
        if (! is_array($enabled)) {
            $enabled = ['ro'];
        }
        if (! in_array('ro', $enabled, true)) {
            array_unshift($enabled, 'ro');
        }

        $out = [];
        foreach ($enabled as $code) {
            if (isset($all[$code])) {
                $out[$code] = $all[$code];
            }
        }

        return $out !== [] ? $out : ['ro' => $all['ro'] ?? 'Română'];
    }

    /** Zile scadență din Preferințe generale. */
    public function defaultDueDays(): int
    {
        return max(0, min(365, (int) $this->preference('default_due_days', 15)));
    }

    /**
     * Termen de plată implicit pentru emitere document
     * (mapat pe cheile din config/payment_terms.php).
     */
    public function defaultPaymentTerm(): string
    {
        $days = $this->defaultDueDays();
        $terms = config('payment_terms', []);

        if ($days === 0) {
            return array_key_exists('issue', $terms) ? 'issue' : 'date';
        }

        $key = (string) $days;
        if (array_key_exists($key, $terms)) {
            return $key;
        }

        // ex. 20 zile — nu e în listă; folosim „La data” + scadență calculată
        return 'date';
    }

    public function invoiceTemplateKey(): string
    {
        if ($forced = $this->forcedInvoiceTemplateKey()) {
            return $forced;
        }

        $key = $this->invoice_template ?: 'classic';
        $templates = config('invoice_templates', []);

        return array_key_exists($key, $templates) ? $key : 'classic';
    }

    /** Machetă impusă pe CUI (ex. FIRST CONSULTING / FLY DAVID), sau null. */
    public function forcedInvoiceTemplateKey(): ?string
    {
        $cui = $this->normalizedCui();
        if ($cui === '') {
            return null;
        }

        $map = config('invoice_templates.forced_by_cui', []);
        if (! is_array($map)) {
            return null;
        }

        $key = $map[$cui] ?? null;
        if (! is_string($key) || $key === '') {
            return null;
        }

        $templates = config('invoice_templates', []);
        unset($templates['forced_by_cui']);

        return array_key_exists($key, $templates) ? $key : null;
    }

    public function invoiceTemplateLocked(): bool
    {
        return $this->forcedInvoiceTemplateKey() !== null;
    }

    public function invoicePdfView(): string
    {
        return 'documents.pdf.'.$this->invoiceTemplateKey();
    }

    public function invoiceColor(): string
    {
        return $this->invoice_color ?: '#0F4C5C';
    }

    /** CUI normalizat (fără RO / spații) pentru filtre machete restricționate. */
    public function normalizedCui(): string
    {
        return preg_replace('/\D+/', '', strtoupper((string) ($this->cui ?? ''))) ?: '';
    }

    /**
     * Machete disponibile pentru această firmă (exclude restricționate dacă CUI-ul nu e pe listă).
     * Păstrează macheta curentă chiar dacă e restricționată, ca să nu se blocheze salvarea.
     *
     * @return array<string, array<string, mixed>>
     */
    public function availableInvoiceTemplates(): array
    {
        $all = config('invoice_templates', []);
        unset($all['forced_by_cui']);

        if ($forced = $this->forcedInvoiceTemplateKey()) {
            return [
                $forced => $all[$forced] ?? ['name' => $forced],
            ];
        }

        $cui = $this->normalizedCui();
        $current = (string) ($this->invoice_template ?: 'classic');
        $out = [];

        foreach ($all as $key => $meta) {
            if (! is_array($meta)) {
                continue;
            }
            $restricted = (bool) ($meta['restricted'] ?? false);
            if ($restricted) {
                $allowed = array_map(
                    fn ($v) => preg_replace('/\D+/', '', strtoupper((string) $v)) ?: '',
                    $meta['allowed_cui'] ?? []
                );
                if ($cui === '' || (! in_array($cui, $allowed, true) && $key !== $current)) {
                    continue;
                }
            }
            $out[$key] = $meta;
        }

        return $out !== [] ? $out : ['classic' => $all['classic'] ?? ['name' => 'Clasic']];
    }

    /** Numele administratorului (persoana responsabilă din decizia de inseriere serii). */
    public function seriesResponsibleName(): ?string
    {
        $name = trim((string) ($this->series_responsible_name ?? ''));

        return $name !== '' ? $name : null;
    }

    public function seriesResponsibleRole(): string
    {
        $role = trim((string) ($this->series_responsible_role ?? ''));

        return $role !== '' ? $role : 'Administrator';
    }

    /**
     * Factor de scară pentru logo / semnătură / ștampilă (1/4 … 1/1).
     *
     * @param  'logo'|'signature'|'stamp'  $kind
     */
    public function brandingScaleFactor(string $kind): float
    {
        $pct = (int) $this->brandingScaleKey($kind);

        return max(0.25, min(2.0, $pct / 100));
    }

    /**
     * @param  'logo'|'signature'|'stamp'  $kind
     */
    public function brandingScaleKey(string $kind): string
    {
        $column = match ($kind) {
            'logo' => 'logo_scale',
            'signature' => 'signature_scale',
            'stamp' => 'stamp_scale',
            default => 'logo_scale',
        };
        $raw = (string) ($this->{$column} ?: '100');
        if (isset(self::BRANDING_SCALE_LEGACY[$raw])) {
            $raw = self::BRANDING_SCALE_LEGACY[$raw];
        }
        if (! array_key_exists($raw, self::BRANDING_SCALES)) {
            $raw = '100';
        }

        return $raw;
    }

    /**
     * Dimensiuni fixe pentru logo/semnătură/ștampilă pe PDF.
     * Scara (25%–200%) se aplică DOAR acestor imagini, nu paginii.
     * DomPDF ignoră max-height — folosim mereu width/height explicite, plafonate.
     *
     * @param  'logo'|'signature'|'stamp'  $kind
     * @return array{w:int,h:int}
     */
    public function brandingDisplaySize(string $kind): array
    {
        // Cutie la 100% (nu depinde de rezoluția fișierului sursă)
        [$baseW, $baseH] = match ($kind) {
            'signature' => [140, 56],
            'stamp' => [100, 72],
            default => [140, 48], // logo
        };

        $factor = $this->brandingScaleFactor($kind); // 0.25 … 2.0
        $boxW = max(12, (int) round($baseW * $factor));
        $boxH = max(10, (int) round($baseH * $factor));

        // Plafon absolut (chiar și la 200%) — nu poate umple pagina
        $boxW = min($boxW, 280);
        $boxH = min($boxH, 120);

        $path = match ($kind) {
            'logo' => $this->logoAbsolutePath(),
            'signature' => $this->signatureAbsolutePath(),
            'stamp' => $this->stampAbsolutePath(),
            default => null,
        };

        if ($path) {
            $info = @getimagesize($path);
            if (is_array($info) && ! empty($info[0]) && ! empty($info[1])) {
                $natW = (int) $info[0];
                $natH = (int) $info[1];
                $fit = min($boxW / $natW, $boxH / $natH);

                return [
                    'w' => max(8, (int) round($natW * $fit)),
                    'h' => max(8, (int) round($natH * $fit)),
                ];
            }
        }

        return ['w' => $boxW, 'h' => $boxH];
    }

    /** Cale absolută pe disc pentru DomPDF (sau null). */
    public function brandingAbsolutePath(?string $relative): ?string
    {
        if (! filled($relative)) {
            return null;
        }

        $path = public_path(ltrim($relative, '/'));

        return is_file($path) ? $path : null;
    }

    public function brandingUrl(?string $relative): ?string
    {
        if (! filled($relative)) {
            return null;
        }

        return asset(ltrim($relative, '/'));
    }

    public function logoAbsolutePath(): ?string
    {
        return $this->brandingAbsolutePath($this->logo_path);
    }

    public function signatureAbsolutePath(): ?string
    {
        return $this->brandingAbsolutePath($this->signature_path);
    }

    public function stampAbsolutePath(): ?string
    {
        return $this->brandingAbsolutePath($this->stamp_path);
    }

    /** Data-URI pentru DomPDF (mai stabil decât path pe hosting). */
    public function brandingDataUri(?string $relative): ?string
    {
        $path = $this->brandingAbsolutePath($relative);
        if (! $path) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    public function logoDataUri(): ?string
    {
        return $this->brandingDataUri($this->logo_path);
    }

    public function signatureDataUri(): ?string
    {
        return $this->brandingDataUri($this->signature_path);
    }

    public function stampDataUri(): ?string
    {
        return $this->brandingDataUri($this->stamp_path);
    }

    public function signatureTextForInvoice(): ?string
    {
        if (! $this->show_signature_text) {
            return null;
        }

        $text = trim((string) ($this->signature_text ?: self::DEFAULT_SIGNATURE_TEXT));

        return $text !== '' ? $text : null;
    }

    /**
     * Textul de pe factură, mereu pe 3 rânduri (stânga, jos).
     *
     * @return list<string>
     */
    public function signatureTextLines(): array
    {
        $text = $this->signatureTextForInvoice();
        if ($text === null) {
            return [];
        }

        $parts = preg_split('/\R+/u', $text) ?: [];
        $parts = array_values(array_filter(array_map(
            static fn ($line) => trim((string) $line),
            $parts
        ), static fn ($line) => $line !== ''));

        if ($parts === []) {
            return [];
        }

        if (count($parts) >= 3) {
            return array_slice($parts, 0, 3);
        }

        if (count($parts) === 2) {
            return [$parts[0], $parts[1], ''];
        }

        $words = preg_split('/\s+/u', $parts[0], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return [$parts[0], '', ''];
        }

        $n = count($words);
        $a = (int) ceil($n / 3);
        $b = (int) ceil(($n - $a) / 2);

        return [
            implode(' ', array_slice($words, 0, $a)),
            implode(' ', array_slice($words, $a, $b)),
            implode(' ', array_slice($words, $a + $b)),
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function efacturaInvites(): HasMany
    {
        return $this->hasMany(EfacturaInvite::class);
    }

    public function banks(): HasMany
    {
        return $this->hasMany(CompanyBank::class)->orderBy('sort_order')->orderBy('id');
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    public function overdueReminderLogs(): HasMany
    {
        return $this->hasMany(OverdueReminderLog::class);
    }

    public function overdueReminderScopeLabel(): string
    {
        return self::OVERDUE_REMINDER_SCOPES[$this->overdue_reminder_scope ?? 'both']
            ?? ($this->overdue_reminder_scope ?? 'both');
    }

    public function bankAccounts(): HasManyThrough
    {
        return $this->hasManyThrough(CompanyBankAccount::class, CompanyBank::class);
    }

    public function invoiceBankAccounts(): Collection
    {
        return $this->banks()
            ->with(['accounts' => fn ($q) => $q->where('show_on_invoice', true)->orderBy('sort_order')->orderBy('id')])
            ->get()
            ->flatMap(function (CompanyBank $bank) {
                return $bank->accounts->map(function (CompanyBankAccount $account) use ($bank) {
                    $account->setRelation('bank', $bank);

                    return $account;
                });
            })
            ->take(3)
            ->values();
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->city, $this->county, $this->country])
            ->filter()
            ->implode(', ');
    }

    public function numericCui(): string
    {
        return preg_replace('/\D+/', '', (string) ($this->anaf_cif ?: $this->cui)) ?: '';
    }

    public function isAnafAuthorized(): bool
    {
        return filled($this->anaf_access_token) && filled($this->anaf_refresh_token);
    }

    public function efacturaSendMode(): string
    {
        $mode = $this->efactura_send_mode ?: 'manual';

        return $mode === 'auto' ? 'on_save' : $mode;
    }

    public function efacturaSendModeLabel(): string
    {
        return self::EFACTURA_SEND_MODES[$this->efacturaSendMode()] ?? $this->efacturaSendMode();
    }

    public function efacturaDelayDays(): ?int
    {
        return match ($this->efacturaSendMode()) {
            'delay_1' => 1,
            'delay_2' => 2,
            'delay_3' => 3,
            default => null,
        };
    }

    public function shouldQueueEfacturaOnIssue(): bool
    {
        return in_array($this->efacturaSendMode(), ['on_save', 'delay_1', 'delay_2', 'delay_3'], true);
    }

    public function clearAnafAuthorization(): void
    {
        $this->forceFill([
            'anaf_access_token' => null,
            'anaf_refresh_token' => null,
            'anaf_token_expires_at' => null,
            'anaf_authorized_at' => null,
            'anaf_authorized_by' => null,
            'anaf_cif' => null,
        ])->save();
    }

    public function extendAnafConnection(int $days = 90): void
    {
        $this->forceFill([
            'anaf_authorized_at' => now(),
            'anaf_token_expires_at' => now()->addDays($days),
        ])->save();
    }
}
