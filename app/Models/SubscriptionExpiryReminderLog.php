<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionExpiryReminderLog extends Model
{
    protected $fillable = [
        'user_id', 'days_before', 'access_until_date', 'email', 'sent_at',
    ];

    protected $casts = [
        'days_before' => 'integer',
        'access_until_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
