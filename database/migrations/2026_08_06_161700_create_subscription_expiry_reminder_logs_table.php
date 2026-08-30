<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_expiry_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('days_before');
            $table->date('access_until_date');
            $table->string('email');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(
                ['user_id', 'days_before', 'access_until_date'],
                'sub_expiry_reminder_unique'
            );
            $table->index(['sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_expiry_reminder_logs');
    }
};
