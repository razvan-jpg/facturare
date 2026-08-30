<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WebLoginToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'redirect',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /** Asigură tabela dacă migrarea nu a putut rula pe host (LiteSpeed blochează artisan ops). */
    public static function ensureSchema(): void
    {
        if (Schema::hasTable('web_login_tokens')) {
            return;
        }

        Schema::create('web_login_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('redirect', 500)->default('/dashboard');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
