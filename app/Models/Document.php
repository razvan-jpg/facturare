<?php

namespace App\Models;

use App\Services\ExchangeRateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    public const TYPE_LABELS = [
        'invoice' => 'Factură',
        'proforma' => 'Proformă',
        'delivery' => 'Aviz',
        'receipt' => 'Chitanță',
        'credit_note' => 'Notă de creditare',
    ];

    /** Etichete pentru filtre de listă (inclusiv storno = facturi cu status storno). */
    public const LIST_LABELS = [
        'invoice' => 'Facturi',
        'proforma' => 'Proforme',
        'delivery' => 'Avize',
        'receipt' => 'Chitanțe',
        'credit_note' => 'Note de creditare',
        'storno' => 'Facturi storno',
    ];

    /** Tipul de listă din meniu (facturi / proforme / storno …). */
    public function listType(): string
    {
        if ($this->status === 'storno') {
            return 'storno';
        }

        return array_key_exists($this->type, self::LIST_LABELS) ? $this->type : 'invoice';
    }

    public const STATUS_LABELS = [
        'draft' => 'Ciornă',
        'issued' => 'Emisă',
        'cancelled' => 'Anulată',
        'storno' => 'Storno',
    ];

    public const PAYMENT_STATUS_LABELS = [
        'unpaid' => 'Neachitată',
        'partial' => 'Parțial achitată',
        'paid' => 'Achitată',
    ];

    public const EFACTURA_LABELS = [
        'none' => 'Netrimisă',
        'queued' => 'Programată / în coadă',
        'uploaded' => 'Trimisă (așteaptă validare)',
        'processing' => 'În prelucrare ANAF',
        'ok' => 'Acceptată ANAF',
        'nok' => 'Respinsă ANAF',
        'error' => 'Eroare trimitere',
    ];

    public const CLIENT_EMAIL_LABELS = [
        'none' => 'N/A',
        'pending' => 'În așteptare',
        'sent' => 'Trimis',
        'failed' => 'Eșuat',
        'skipped' => 'Neconfigurat',
    ];

    protected $fillable = [
        'company_id', 'client_id', 'created_by', 'type', 'status', 'series', 'number',
        'number_full', 'number_reserved_at', 'issue_date', 'issue_year', 'due_date', 'payment_term', 'currency', 'exchange_rate',
        'subtotal', 'vat_total', 'total', 'paid_amount', 'payment_status', 'notes', 'document_language',
        'allow_card_payment', 'contract_number', 'despatch_advice',
        'prepared_by', 'prepared_by_cnp', 'delegate_name', 'delegate_id_card', 'vehicle_reg',
        'auto_email_client', 'auto_email_cc', 'auto_email_cc_address',
        'client_email_status', 'client_email_sent_at', 'client_email_error', 'client_email_attempts',
        'client_name', 'client_cui', 'client_reg_com', 'client_address', 'client_email',
        'related_document_id', 'recurring_invoice_id', 'efactura_status', 'efactura_upload_id',
        'efactura_download_id', 'efactura_error', 'efactura_sent_at', 'efactura_checked_at',
        'efactura_scheduled_at',
        'efactura_auto_attempts', 'efactura_auto_last_error', 'efactura_auto_next_at', 'efactura_auto_alerted_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'allow_card_payment' => 'boolean',
        'auto_email_client' => 'boolean',
        'auto_email_cc' => 'boolean',
        'client_email_sent_at' => 'datetime',
        'client_email_attempts' => 'integer',
        'efactura_sent_at' => 'datetime',
        'efactura_checked_at' => 'datetime',
        'efactura_scheduled_at' => 'datetime',
        'efactura_auto_attempts' => 'integer',
        'efactura_auto_next_at' => 'datetime',
        'efactura_auto_alerted_at' => 'datetime',
        'number_reserved_at' => 'datetime',
        'issue_year' => 'integer',
    ];

    public function wantsClientEmail(): bool
    {
        return (bool) $this->auto_email_client
            || ((bool) $this->auto_email_cc && filled($this->auto_email_cc_address));
    }

    /**
     * Destinatari la trimiterea automată (emitere) — aceeași logică ca maybeAutoEmailDocument.
     *
     * @return array{to: list<string>, cc: list<string>}
     */
    public function emailAutoDeliveryTargets(?array $platformCc = null): array
    {
        $this->loadMissing('client');

        $to = [];
        if ($this->auto_email_client) {
            $to = array_merge(
                $to,
                dc_parse_emails($this->client_email ?: $this->client?->email)
            );
        }
        if ($this->auto_email_cc && filled($this->auto_email_cc_address)) {
            $to = array_merge($to, dc_parse_emails($this->auto_email_cc_address));
        }
        $to = array_values(array_unique(array_filter($to)));

        $cc = array_values(array_unique(array_filter(array_map(
            'trim',
            $platformCc ?? []
        ))));
        if ($this->recurring_invoice_id) {
            $platform = trim((string) config('dateconta.recurring_document_email_cc', ''));
            if ($platform !== '') {
                $cc[] = $platform;
            }
        }
        $cc = array_values(array_unique(array_filter($cc)));

        return ['to' => $to, 'cc' => $cc];
    }

    /**
     * Destinatari la retrimitere manuală — client + CC document + CC platformă (ca la emitere).
     *
     * @return array{to: list<string>, cc: list<string>}
     */
    public function emailManualResendTargets(): array
    {
        $this->loadMissing('client');

        $to = dc_parse_emails($this->client_email ?: $this->client?->email);
        if ($this->auto_email_cc && filled($this->auto_email_cc_address)) {
            $to = array_merge($to, dc_parse_emails($this->auto_email_cc_address));
        }
        $to = array_values(array_unique(array_filter($to)));

        $cc = [];
        if ($this->recurring_invoice_id) {
            $platform = trim((string) config('dateconta.recurring_document_email_cc', ''));
            if ($platform !== '') {
                $cc[] = $platform;
            }
        }
        $cc = array_values(array_unique(array_filter($cc)));

        return ['to' => $to, 'cc' => $cc];
    }

    public function hasNumberReservation(): bool
    {
        return $this->status === 'draft'
            && filled($this->number)
            && filled($this->number_reserved_at);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Facturile / proformele acoperite de această chitanță (nr. + data emiterii).
     *
     * @return list<array{number: string, date: ?string}>
     */
    public function receiptCollectedInvoices(): array
    {
        if ($this->type !== 'receipt' || ! filled($this->number_full)) {
            return [];
        }

        $fromPayments = Payment::query()
            ->where('company_id', $this->company_id)
            ->where('reference', $this->number_full)
            ->whereNotNull('document_id')
            ->with(['document:id,number_full,type,issue_date'])
            ->orderBy('id')
            ->get()
            ->map(function (Payment $p) {
                $doc = $p->document;
                if (! $doc || ! filled($doc->number_full)) {
                    return null;
                }

                return [
                    'number' => (string) $doc->number_full,
                    'date' => $doc->issue_date ? $doc->issue_date->toDateString() : null,
                ];
            })
            ->filter()
            ->unique('number')
            ->values()
            ->all();

        if ($fromPayments !== []) {
            return array_values($fromPayments);
        }

        // Fallback: text pe linie / note (chitanțe vechi fără plăți legate).
        $blob = trim(implode(' ', [
            (string) $this->notes,
            ...$this->items->map(fn ($i) => trim((string) $i->name.' '.(string) $i->description))->all(),
        ]));
        if ($blob === '') {
            return [];
        }

        $found = [];
        // „factura FCBM-FF-3564 din 01/09/2026” sau doar numărul
        if (preg_match_all(
            '/\b([A-Z0-9]{1,12}-[A-Z0-9]{1,12}-\d{3,6})\b(?:\s+din\s+(\d{1,2}\/\d{1,2}\/\d{4}))?/iu',
            $blob,
            $m,
            PREG_SET_ORDER
        )) {
            foreach ($m as $row) {
                $number = strtoupper($row[1]);
                $date = null;
                if (! empty($row[2])) {
                    try {
                        $date = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $row[2])->toDateString();
                    } catch (\Throwable) {
                        $date = null;
                    }
                }
                $found[$number] = ['number' => $number, 'date' => $date];
            }
        }

        return array_values($found);
    }

    /** @return list<string> */
    public function receiptCollectedInvoiceNumbers(): array
    {
        return array_values(array_map(
            fn (array $row) => $row['number'],
            $this->receiptCollectedInvoices()
        ));
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function typeLabel(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $key = 'invoice.type_'.$this->type;
        $translated = trans($key, [], $locale);
        if (is_string($translated) && $translated !== $key) {
            return $translated;
        }

        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function documentLocale(): string
    {
        $lang = (string) ($this->document_language ?: 'ro');
        $available = array_keys(config('document_languages', ['ro' => 'Română']));

        return in_array($lang, $available, true) ? $lang : 'ro';
    }

    public function currencyCode(): string
    {
        return strtoupper((string) ($this->currency ?: 'RON'));
    }

    public function exchangeRateToRon(): ?float
    {
        if (! $this->needsForeignExchangeRate()) {
            return null;
        }

        $fx = (float) ($this->exchange_rate ?? 0);

        return $fx > 1.0001 ? round($fx, 4) : null;
    }

    /** Curs pentru afișare (listă/PDF): salvat sau BNR/ECB la data emiterii. */
    public function resolvedExchangeRateToRon(): ?float
    {
        if (! $this->needsForeignExchangeRate()) {
            return null;
        }

        $stored = $this->exchangeRateToRon();
        if ($stored !== null) {
            return $stored;
        }

        try {
            $rate = app(ExchangeRateService::class)->resolveForDocument(
                $this->currencyCode(),
                $this->issue_date?->toDateString() ?? now('Europe/Bucharest')->toDateString()
            );

            return $rate > 1.0001 ? round($rate, 4) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function amountInRon(float $amount): ?float
    {
        $fx = $this->exchangeRateToRon();
        if ($fx !== null) {
            return round($amount * $fx, 2);
        }

        return $this->currencyCode() === 'RON' ? round($amount, 2) : null;
    }

    public function amountInRonForDisplay(float $amount): ?float
    {
        $fx = $this->resolvedExchangeRateToRon();
        if ($fx === null) {
            return null;
        }

        return round($amount * $fx, 2);
    }

    public function totalInRon(): ?float
    {
        return $this->amountInRon((float) $this->total);
    }

    public function totalInRonForDisplay(): ?float
    {
        return $this->amountInRonForDisplay((float) $this->total);
    }

    public function showsRonPrimaryTotal(): bool
    {
        return $this->resolvedExchangeRateToRon() !== null;
    }

    public function showsRonEquivalentInList(): bool
    {
        return $this->needsForeignExchangeRate() && $this->totalInRonForDisplay() !== null;
    }

    public function needsForeignExchangeRate(): bool
    {
        return $this->currencyCode() !== 'RON';
    }

    public function hasValidForeignExchangeRate(): bool
    {
        if (! $this->needsForeignExchangeRate()) {
            return true;
        }

        return (float) ($this->exchange_rate ?? 0) > 1.0001;
    }

    public function foreignExchangeRateBlockMessage(): string
    {
        return 'Factura în '.$this->currencyCode().' nu are curs valutar valid. Completează cursul BNR înainte de trimitere (email / e-Factura).';
    }

    /**
     * Completează cursul lipsă/greșit (≤ 1) din BNR/ECB la data emiterii.
     */
    public function ensureExchangeRate(): bool
    {
        if (! $this->needsForeignExchangeRate()) {
            if ((float) ($this->exchange_rate ?? 0) !== 1.0) {
                $this->forceFill(['exchange_rate' => 1])->save();
            }

            return true;
        }

        if ($this->hasValidForeignExchangeRate()) {
            return true;
        }

        try {
            $rate = app(ExchangeRateService::class)->resolveForDocument(
                $this->currencyCode(),
                $this->issue_date?->toDateString() ?? now('Europe/Bucharest')->toDateString(),
                null
            );
            if ($rate > 1.0001) {
                $this->forceFill(['exchange_rate' => round($rate, 4)])->save();

                return true;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ensureExchangeRate failed', [
                'document_id' => $this->id,
                'currency' => $this->currencyCode(),
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    public function clientIbanForPdf(): ?string
    {
        $iban = filled($this->client_iban ?? null)
            ? (string) $this->client_iban
            : (string) ($this->client?->iban ?? '');

        $iban = trim($iban);

        return $iban !== '' ? $iban : null;
    }

    public function clientBankNameForPdf(): ?string
    {
        $bank = filled($this->client_bank_name ?? null)
            ? (string) $this->client_bank_name
            : (string) ($this->client?->bank_name ?? '');

        $bank = trim($bank);

        return $bank !== '' ? $bank : null;
    }

    /** Termen de plată pentru formular (salvat sau dedus din scadență). */
    public function resolvedPaymentTerm(): string
    {
        $saved = (string) ($this->payment_term ?? '');
        if ($saved !== '' && array_key_exists($saved, config('payment_terms', []))) {
            return $saved;
        }

        if (! $this->due_date || ! $this->issue_date) {
            return 'none';
        }

        $issue = $this->issue_date->copy()->startOfDay();
        $due = $this->due_date->copy()->startOfDay();
        $days = (int) $issue->diffInDays($due, false);

        if ($days === 0) {
            return 'issue';
        }

        if ($due->equalTo($issue->copy()->endOfMonth()->startOfDay())) {
            return 'month_end';
        }

        foreach ([5, 7, 10, 15, 30, 60, 90] as $n) {
            if ($days === $n) {
                return (string) $n;
            }
        }

        return 'date';
    }

    /** Nume fișier PDF: număr document + client (sigur pentru filesystem). */
    public function pdfFileName(): string
    {
        $number = trim((string) ($this->number_full ?: $this->typeLabel()));
        $client = trim((string) ($this->client_name ?: $this->client?->name ?: 'client'));

        $safe = static function (string $value): string {
            $value = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $value);
            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
            $value = trim($value, " .-_");

            return $value !== '' ? $value : 'document';
        };

        $base = $safe($number).' - '.$safe($client);
        if (mb_strlen($base) > 180) {
            $base = mb_substr($base, 0, 180);
        }

        return $base.'.pdf';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function paymentStatusLabel(): string
    {
        if ($this->type !== 'invoice') {
            return '—';
        }

        $status = $this->payment_status ?: 'unpaid';

        return self::PAYMENT_STATUS_LABELS[$status] ?? $status;
    }

    public function efacturaStatusLabel(): string
    {
        $status = $this->efactura_status ?: 'none';

        return self::EFACTURA_LABELS[$status] ?? $status;
    }

    /** Respinsă / eroare, încă în bucla automată de reîncercare. */
    public function isEfacturaAutoRetrying(): bool
    {
        if (! in_array($this->efactura_status, ['nok', 'error'], true)) {
            return false;
        }

        return (int) $this->efactura_auto_attempts > 0
            || ($this->efactura_auto_next_at !== null);
    }

    public function canSendEfactura(): bool
    {
        // Nu permite retrimiterea cât timp e deja în SPV (uploaded/processing/ok) —
        // altfel se creează dubluri acceptate în ANAF. Doar none / queued / nok / error.
        return in_array($this->type, ['invoice', 'credit_note'], true)
            && in_array($this->status, ['issued', 'storno'], true)
            && in_array($this->efactura_status ?: 'none', ['none', 'queued', 'nok', 'error'], true);
    }

    /** Deja încărcată în SPV — doar poll stare, nu upload nou. */
    public function isEfacturaPendingInSpv(): bool
    {
        return in_array($this->type, ['invoice', 'credit_note'], true)
            && in_array($this->efactura_status, ['uploaded', 'processing'], true)
            && filled($this->efactura_upload_id);
    }

    /** Factură / storno / notă de credit — poate genera XML UBL pentru depunere manuală SPV. */
    public function canExportEfacturaXml(): bool
    {
        return in_array($this->type, ['invoice', 'credit_note'], true)
            && in_array($this->status, ['issued', 'storno'], true);
    }

    public function efacturaXmlFileName(): string
    {
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) ($this->number_full ?: 'factura_'.$this->id));
        $base = trim((string) $base, '_');

        return ($base !== '' ? $base : 'factura_'.$this->id).'.xml';
    }

    /** Trimisă / acceptată în SPV — nu se mai editează, anulează sau șterge. */
    public function isSentToEfactura(): bool
    {
        return in_array($this->type, ['invoice', 'credit_note'], true)
            && in_array($this->efactura_status, ['uploaded', 'processing', 'ok'], true);
    }

    /**
     * Factură emisă de peste 5 zile calendaristice, încă nedepusă în e-Factura (SPV).
     */
    public function isEfacturaSubmissionOverdue(): bool
    {
        if (! in_array($this->type, ['invoice', 'credit_note'], true)
            || ! in_array($this->status, ['issued', 'storno'], true)) {
            return false;
        }

        if ($this->isSentToEfactura()) {
            return false;
        }

        if (! $this->issue_date) {
            return false;
        }

        $issue = $this->issue_date instanceof \Carbon\CarbonInterface
            ? $this->issue_date->copy()->startOfDay()
            : \Illuminate\Support\Carbon::parse($this->issue_date)->startOfDay();

        return $issue->diffInDays(now()->startOfDay()) > 5;
    }

    public function canEdit(): bool
    {
        return ! $this->isSentToEfactura()
            && in_array($this->status, ['draft', 'issued'], true);
    }

    public function canCancel(): bool
    {
        return ! $this->isSentToEfactura()
            && $this->status === 'issued';
    }

    public function canDelete(): bool
    {
        return ! $this->isSentToEfactura()
            && ! in_array($this->status, ['storno'], true);
    }

    /** Stornare: factură emisă (inclusiv dacă e deja în e-Factura). */
    public function canStorno(): bool
    {
        if ($this->type !== 'invoice' || $this->status !== 'issued') {
            return false;
        }

        if (isset($this->credit_note_count) && (int) $this->credit_note_count > 0) {
            return false;
        }
        if (! isset($this->credit_note_count) && $this->relatedDocuments()->where('type', 'credit_note')->exists()) {
            return false;
        }

        if (isset($this->storno_count)) {
            return (int) $this->storno_count === 0;
        }

        return ! $this->relatedDocuments()->where('status', 'storno')->exists();
    }

    /** Notă de creditare pe o factură emisă (fără storno / NC deja emisă). */
    public function canCreditNote(): bool
    {
        if ($this->type !== 'invoice' || $this->status !== 'issued') {
            return false;
        }

        if (isset($this->storno_count) && (int) $this->storno_count > 0) {
            return false;
        }
        if (isset($this->credit_note_count)) {
            return (int) $this->credit_note_count === 0;
        }

        return ! $this->relatedDocuments()->where('status', 'storno')->exists()
            && ! $this->relatedDocuments()->where('type', 'credit_note')->exists();
    }

    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_document_id');
    }

    public function relatedDocuments(): HasMany
    {
        return $this->hasMany(self::class, 'related_document_id');
    }

    public function isEfacturaQueuedPending(): bool
    {
        return $this->efactura_status === 'queued'
            && $this->efactura_scheduled_at
            && $this->efactura_scheduled_at->isFuture();
    }

    public function remainingAmount(): float
    {
        if ($this->isClosedByStorno()) {
            return 0.0;
        }

        if ($this->isProformaSettledByFiscalInvoice()) {
            return 0.0;
        }

        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    /**
     * Proformă din care s-a emis deja factură fiscală (obligația trece pe factură; plata e pe factură).
     */
    public function isProformaSettledByFiscalInvoice(): bool
    {
        if ($this->type !== 'proforma') {
            return false;
        }

        return Document::query()
            ->where('company_id', $this->company_id)
            ->where('related_document_id', $this->id)
            ->where('type', 'invoice')
            ->whereIn('status', ['issued', 'storno'])
            ->exists();
    }

    /**
     * Storno integral: documentul e storno SAU are cel puțin un storno legat.
     * În ambele cazuri nu mai există sumă de încasat — status Achitată.
     */
    public function isClosedByStorno(): bool
    {
        if ($this->status === 'storno') {
            return true;
        }

        if (isset($this->storno_count)) {
            return (int) $this->storno_count > 0;
        }

        return $this->relatedDocuments()->where('status', 'storno')->exists();
    }

    public function refreshPaymentStatus(): void
    {
        // Storno sau factură stornată: închise fiscal — rămân „Achitată”.
        if ($this->isClosedByStorno()) {
            $paid = (float) $this->payments()->sum('amount');
            $target = abs((float) $this->total);
            // Preferă suma plăților dacă acoperă totalul; altfel forțează abs(total).
            $this->paid_amount = $paid + 0.009 >= $target ? $paid : $target;
            $this->payment_status = 'paid';
            $this->save();

            return;
        }

        // Proformă convertită în factură fiscală: plata e pe factură, proforma rămâne „încasată”.
        if ($this->isProformaSettledByFiscalInvoice()) {
            $this->paid_amount = (float) $this->total;
            $this->payment_status = 'paid';
            $this->save();

            return;
        }

        $paid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $paid;

        if ($paid <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($paid + 0.009 < (float) $this->total) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'paid';
        }

        $this->save();
    }
}
