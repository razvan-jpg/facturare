<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionOrder extends Model
{
    public const PRODUCT_PLATFORM = 'platform';

    public const PRODUCT_SUBUSER_SEATS = 'subuser_seats';

    protected $fillable = [
        'number', 'user_id', 'company_id', 'invoice_document_id', 'product_type', 'period_key', 'months', 'seats',
        'amount_net', 'amount_vat', 'amount_total', 'currency', 'vat_rate',
        'payment_method', 'payment_processor', 'status', 'billing_name', 'billing_cui', 'billing_phone',
        'billing_email', 'billing_address', 'billing_city', 'billing_county',
        'recurring', 'netopia_ref', 'mollie_payment_id',
        'stripe_session_id', 'stripe_subscription_id', 'stripe_payment_intent',
        'netopia_error', 'paid_at', 'access_until_after',
    ];

    protected $casts = [
        'amount_net' => 'float',
        'amount_vat' => 'float',
        'amount_total' => 'float',
        'vat_rate' => 'float',
        'seats' => 'integer',
        'recurring' => 'boolean',
        'paid_at' => 'datetime',
        'access_until_after' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoiceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'invoice_document_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function periodLabel(): string
    {
        if ($this->isSubuserSeats()) {
            $label = (string) (config('dateconta.subuser_seats.periods.'.$this->period_key.'.label') ?? $this->period_key);
            $seats = max(1, (int) $this->seats);

            return $label.' · '.$seats.' '.($seats === 1 ? 'loc' : 'locuri');
        }

        return (string) (config('dateconta.subscription.periods.'.$this->period_key.'.label') ?? $this->period_key);
    }

    public function isSubuserSeats(): bool
    {
        return ($this->product_type ?: self::PRODUCT_PLATFORM) === self::PRODUCT_SUBUSER_SEATS;
    }

    public function productName(): string
    {
        if ($this->isSubuserSeats()) {
            return (string) config('dateconta.subuser_seats.product_name', 'DateConta Facturare — locuri utilizatori');
        }

        return (string) config('dateconta.subscription.product_name', 'DateConta Facturare');
    }
}
