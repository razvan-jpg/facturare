<?php

namespace App\Models;

use App\Services\ClientPenaltyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class Payment extends Model
{
    public const METHOD_LABELS = [
        'op' => 'OP',
        'receipt' => 'Chitanță',
        'cash' => 'Numerar',
        'card' => 'Card',
        'bank' => 'Transfer',
        'other' => 'Altele',
    ];

    protected $fillable = [
        'company_id', 'document_id', 'client_id', 'method', 'paid_at',
        'amount', 'currency', 'reference', 'notes',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (Payment $payment) {
            try {
                app(ClientPenaltyService::class)->onPaymentRecorded($payment);
            } catch (Throwable) {
                // nu blochează încasarea
            }
        });
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? (string) $this->method;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
