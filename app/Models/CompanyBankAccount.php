<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBankAccount extends Model
{
    protected $fillable = [
        'company_bank_id', 'iban', 'currency', 'show_on_invoice', 'sort_order',
    ];

    protected $casts = [
        'show_on_invoice' => 'boolean',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(CompanyBank::class, 'company_bank_id');
    }

    public function normalizedIban(): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $this->iban) ?: '');
    }
}
