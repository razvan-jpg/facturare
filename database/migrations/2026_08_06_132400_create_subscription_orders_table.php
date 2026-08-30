<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('period_key', 8);
            $table->unsignedTinyInteger('months');
            $table->decimal('amount_net', 10, 2);
            $table->decimal('amount_vat', 10, 2);
            $table->decimal('amount_total', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->string('payment_method', 16); // card|op
            $table->string('status', 24)->default('pending'); // pending|awaiting_op|paid|failed|cancelled
            $table->string('billing_name')->nullable();
            $table->string('billing_cui', 32)->nullable();
            $table->string('billing_phone', 50)->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_county', 100)->nullable();
            $table->boolean('recurring')->default(false);
            $table->string('netopia_ref', 64)->nullable();
            $table->text('netopia_error')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('access_until_after')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};
