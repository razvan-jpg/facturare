<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSeries extends Model
{
    public const TYPES = [
        'invoice' => 'Factură',
        'proforma' => 'Proformă',
        'delivery' => 'Aviz',
        'receipt' => 'Chitanță',
        'credit_note' => 'Notă de creditare',
    ];

    protected $fillable = [
        'company_id', 'type', 'prefix', 'description', 'first_number', 'next_number', 'year', 'active', 'is_default',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
