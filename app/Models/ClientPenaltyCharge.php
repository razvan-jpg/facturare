<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPenaltyCharge extends Model
{
    public const SOURCE_OPENING = 'opening';

    public const SOURCE_INVOICE = 'invoice';

    public const STATUS_ACCRUED = 'accrued';

    public const STATUS_BILLED = 'billed';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'company_id', 'client_id', 'source_type', 'source_document_id',
        'principal_base', 'period_from', 'period_to', 'days', 'percent', 'amount',
        'status', 'billed_document_id', 'billed_item_id', 'paid_at', 'paid_payment_id',
    ];

    protected $casts = [
        'principal_base' => 'decimal:2',
        'period_from' => 'date',
        'period_to' => 'date',
        'percent' => 'decimal:4',
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    public function billedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'billed_document_id');
    }

    public function billedItem(): BelongsTo
    {
        return $this->belongsTo(DocumentItem::class, 'billed_item_id');
    }

    public function paidPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'paid_payment_id');
    }
}
