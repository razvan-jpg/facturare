<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItem extends Model
{
    protected $fillable = [
        'document_id', 'product_id', 'position', 'name', 'description', 'unit', 'quantity',
        'unit_price', 'vat_rate', 'line_subtotal', 'line_vat', 'line_total', 'details',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_vat' => 'decimal:2',
        'line_total' => 'decimal:2',
        'details' => 'array',
    ];

    public function detail(string $key, mixed $default = null): mixed
    {
        return data_get($this->details ?? [], $key, $default);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
