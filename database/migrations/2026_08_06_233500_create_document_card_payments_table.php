<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_card_payments')) {
            return;
        }

        Schema::create('document_card_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('processor', 32);
            $table->string('checkout_number', 64)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('RON');
            $table->string('status', 32)->default('pending'); // pending|paid|failed
            $table->string('external_ref', 128)->nullable();
            $table->string('mollie_payment_id', 64)->nullable()->index();
            $table->text('error')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_card_payments');
    }
};
