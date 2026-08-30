<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'company_id', 'name', 'sku', 'unit', 'type', 'price', 'vat_rate', 'description', 'active',
    ];

    protected $casts = [
            'price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'active' => 'boolean',
        ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
