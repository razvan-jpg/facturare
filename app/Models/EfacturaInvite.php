<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EfacturaInvite extends Model
{
    protected $fillable = [
        'company_id', 'invited_by', 'email', 'token', 'expires_at', 'accepted_at', 'sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isValid(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public static function createFor(Company $company, string $email, ?User $user = null): self
    {
        return self::create([
            'company_id' => $company->id,
            'invited_by' => $user?->id,
            'email' => $email,
            'token' => Str::random(48),
            // copy(): nu muta instanța partajată a now(); valabilitate 7 zile.
            'expires_at' => now()->copy()->addDays(7),
        ]);
    }
}
