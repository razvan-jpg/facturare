<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverdueReminderLog extends Model
{
    protected $fillable = [
        'company_id', 'client_id', 'email', 'scope', 'included_statement',
        'document_ids', 'balance_total', 'invoice_count', 'sent_at',
    ];

    protected $casts = [
        'included_statement' => 'boolean',
        'document_ids' => 'array',
        'balance_total' => 'decimal:2',
        'sent_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
