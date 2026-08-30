<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class RecurringInvoice extends Model
{
    public const FREQUENCIES = [
        'weekly' => 'Săptămânală',
        'monthly' => 'Lunară',
        'quarterly' => 'Trimestrială',
        'semiannual' => 'Semestrială',
        'annual' => 'Anuală',
    ];

    public const DOCUMENT_TYPES = [
        'invoice' => 'Factură fiscală',
        'proforma' => 'Proformă',
    ];

    protected $fillable = [
        'company_id', 'client_id', 'created_by', 'title', 'subscription_number', 'frequency',
        'start_date', 'next_run_date', 'end_date', 'due_days', 'payment_term',
        'currency', 'series', 'document_type', 'document_language', 'max_documents',
        'notes', 'allow_card_payment', 'contract_number', 'despatch_advice',
        'prepared_by', 'prepared_by_cnp', 'delegate_name', 'delegate_id_card', 'vehicle_reg',
        'auto_email_client', 'auto_email_cc', 'auto_email_cc_address',
        'auto_issue', 'active', 'last_generated_at', 'last_document_id',
        'generated_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_run_date' => 'date',
        'end_date' => 'date',
        'auto_issue' => 'boolean',
        'active' => 'boolean',
        'allow_card_payment' => 'boolean',
        'auto_email_client' => 'boolean',
        'auto_email_cc' => 'boolean',
        'last_generated_at' => 'datetime',
        'due_days' => 'integer',
        'max_documents' => 'integer',
        'generated_count' => 'integer',
    ];

    /** null / -1 / 0 = nelimitat */
    public function hasDocumentLimit(): bool
    {
        return $this->max_documents !== null && (int) $this->max_documents > 0;
    }

    public function remainingDocuments(): ?int
    {
        if (! $this->hasDocumentLimit()) {
            return null;
        }

        return max(0, (int) $this->max_documents - (int) $this->generated_count);
    }

    public function reachedDocumentLimit(): bool
    {
        return $this->hasDocumentLimit() && (int) $this->generated_count >= (int) $this->max_documents;
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
        return $this->hasMany(RecurringInvoiceItem::class)->orderBy('position');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function lastDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'last_document_id');
    }

    public function frequencyLabel(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    public function documentType(): string
    {
        $type = (string) ($this->document_type ?: 'invoice');

        return array_key_exists($type, self::DOCUMENT_TYPES) ? $type : 'invoice';
    }

    public function documentTypeLabel(): string
    {
        return self::DOCUMENT_TYPES[$this->documentType()] ?? $this->documentType();
    }

    public function displayTitle(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }
        if (filled($this->subscription_number)) {
            return 'Abonament '.$this->subscription_number;
        }

        return 'Abonament '.($this->client?->name ?: '#'.$this->id);
    }

    public function isDue(?CarbonInterface $on = null): bool
    {
        $on = $on ? Carbon::parse($on)->startOfDay() : now()->startOfDay();

        if (! $this->active) {
            return false;
        }

        if (! $this->next_run_date) {
            return false;
        }

        if ($this->end_date && $on->gt($this->end_date->copy()->startOfDay())) {
            return false;
        }

        return $this->next_run_date->copy()->startOfDay()->lte($on);
    }

    public function advanceNextRunDate(): void
    {
        if (! $this->next_run_date) {
            return;
        }

        $next = $this->calculateNextDate($this->next_run_date);
        $this->next_run_date = $next;

        if ($this->end_date && $next->gt($this->end_date)) {
            $this->active = false;
            $this->next_run_date = null;
        }

        $this->save();
    }

    public function calculateNextDate(CarbonInterface $from): Carbon
    {
        $date = Carbon::parse($from)->startOfDay();

        return match ($this->frequency) {
            'weekly' => $date->copy()->addWeek(),
            'monthly' => $date->copy()->addMonthNoOverflow(),
            'quarterly' => $date->copy()->addMonthsNoOverflow(3),
            'semiannual' => $date->copy()->addMonthsNoOverflow(6),
            'annual' => $date->copy()->addYearNoOverflow(),
            default => $date->copy()->addMonthNoOverflow(),
        };
    }

    public function estimatedTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $sub = round((float) $item->quantity * (float) $item->unit_price, 2);
            $vat = round($sub * ((float) $item->vat_rate / 100), 2);
            $total += $sub + $vat;
        }

        return $total;
    }
}
