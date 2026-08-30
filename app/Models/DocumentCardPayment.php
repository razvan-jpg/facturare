<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentCardPayment extends Model
{
    protected $fillable = [
        'document_id', 'company_id', 'processor', 'checkout_number',
        'amount', 'currency', 'status', 'external_ref', 'mollie_payment_id',
        'error', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
