<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'plan', 'trial_ends_at', 'access_until', 'mollie_customer_id', 'stripe_customer_id', 'ui_locale', 'current_company_id', 'created_by_user_id',
        'subuser_seat_quota', 'subuser_seats_until',
        'ios_original_transaction_id', 'ios_product_id', 'ios_expires_at', 'ios_subscription_status', 'ios_environment',
        'ios_force_paywall',
    ];

    public function uiLocale(): string
    {
        return \App\Support\UiLocales::normalize($this->ui_locale ?: 'ro');
    }

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'trial_ends_at' => 'datetime',
        'access_until' => 'datetime',
        'subuser_seats_until' => 'datetime',
        'ios_expires_at' => 'datetime',
        'ios_force_paywall' => 'boolean',
        'is_admin' => 'boolean',
        'subuser_seat_quota' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->is_admin) {
                throw new RuntimeException('Contul de administrator nu poate fi șters.');
            }
        });
    }

    /**
     * Închide contul (soft delete): datele de business rămân în DB pentru backup/export.
     * Eliberează email-ul pentru o eventuală reînregistrare; subuserii creați sunt închiși odată cu proprietarul.
     */
    public function closeAccount(): void
    {
        if ($this->is_admin) {
            throw new RuntimeException('Contul de administrator nu poate fi șters.');
        }

        if ($this->trashed()) {
            return;
        }

        if (! $this->isSubUser()) {
            $this->managedUsers()->each(function (User $sub) {
                $sub->closeAccount();
            });
        }

        // Păstrează identitatea recuperabilă (deleted.{id}.email@…) și eliberează adresa originală.
        if (! str_starts_with((string) $this->email, 'deleted.'.$this->id.'.')) {
            $this->email = 'deleted.'.$this->id.'.'.$this->email;
            $this->saveQuietly();
        }

        $this->delete();
    }

    /** Email-ul original înainte de închiderea contului (dacă a fost prefixat). */
    public function originalEmail(): string
    {
        $email = (string) $this->email;
        $prefix = 'deleted.'.$this->id.'.';
        if (str_starts_with($email, $prefix)) {
            return substr($email, strlen($prefix));
        }

        return $email;
    }

    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by_user_id');
    }

    public function managedUsers(): HasMany
    {
        return $this->hasMany(self::class, 'created_by_user_id');
    }

    public function isSubUser(): bool
    {
        return $this->created_by_user_id !== null;
    }

    /** Subuser creat de acest owner (cont dedicat). */
    public function isCreatedBy(User $owner): bool
    {
        return $this->created_by_user_id !== null
            && (int) $this->created_by_user_id === (int) $owner->id;
    }

    /** Utilizator invitat pe firmele owner-ului (cont propriu, fără created_by). */
    public function isInvitedBy(User $owner): bool
    {
        if ($this->isCreatedBy($owner) || (int) $this->id === (int) $owner->id) {
            return false;
        }

        $ownedIds = $owner->ownedCompanies()->pluck('id');
        if ($ownedIds->isEmpty()) {
            return false;
        }

        return $this->companies()
            ->whereIn('companies.id', $ownedIds)
            ->where('company_user.role', 'operator')
            ->exists();
    }

    public function canManageCompanyUsers(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if ($this->isSubUser()) {
            return false;
        }

        return $this->ownedCompanies()->exists();
    }

    public function visitorSessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class);
    }
}
