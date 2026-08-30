<?php

namespace App\Models;

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
        // Retrimitere permisă oricând statusul ≠ ok (inclusiv nok / error / uploaded / queued).
        return in_array($this->type, ['invoice', 'credit_note'], true)
            && in_array($this->status, ['issued', 'storno'], true)
            && ($this->efactura_status ?: 'none') !== 'ok';
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

        return max(0, (float) $this->total - (float) $this->paid_amount);
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
