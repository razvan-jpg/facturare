<?php

namespace App\Models;

use App\Services\ClientBalanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'company_id', 'name', 'type', 'cui', 'reg_com',
        'admin_last_name', 'admin_first_name', 'cnp', 'address', 'city',
        'county', 'country', 'phone', 'email', 'iban', 'bank_name', 'notes',
        'opening_balance', 'opening_balance_date',
        'opening_installment_amount', 'opening_installment_count',
        'penalty_percent', 'penalty_billing_enabled',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'opening_installment_amount' => 'decimal:2',
        'opening_installment_count' => 'integer',
        'penalty_percent' => 'decimal:4',
        'penalty_billing_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if ($client->opening_balance === null || $client->opening_balance === '') {
                $client->opening_balance = 0;
            }
            // Sold inițial necompletat: dată implicită = ziua creării clientului.
            if (blank($client->opening_balance_date)) {
                $client->opening_balance_date = now()->toDateString();
            }
            if ($client->penalty_billing_enabled === null) {
                $client->penalty_billing_enabled = false;
            }
        });
    }

    /** Data soldului inițial, sau data creării clientului dacă lipsește. */
    public function effectiveOpeningBalanceDate(): string
    {
        if ($this->opening_balance_date) {
            return $this->opening_balance_date instanceof \DateTimeInterface
                ? $this->opening_balance_date->format('Y-m-d')
                : \Illuminate\Support\Carbon::parse($this->opening_balance_date)->toDateString();
        }

        if ($this->created_at) {
            return $this->created_at->toDateString();
        }

        return now()->toDateString();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function openingBalance(): float
    {
        return app(ClientBalanceService::class)->openingBalance($this);
    }

    public function openInvoicesRemaining(): float
    {
        return app(ClientBalanceService::class)->openInvoicesRemaining($this);
    }

    public function currentBalance(): float
    {
        return app(ClientBalanceService::class)->currentBalance($this);
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->city, $this->county, $this->country])
            ->filter()
            ->implode(', ');
    }

    /** @return list<string> */
    public function emailAddresses(): array
    {
        return dc_parse_emails($this->email);
    }

    public function hasBankAccountDetails(): bool
    {
        return filled($this->iban) || filled($this->bank_name);
    }
}
