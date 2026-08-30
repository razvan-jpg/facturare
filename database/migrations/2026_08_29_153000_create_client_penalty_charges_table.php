<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_penalty_charges')) {
            return;
        }

        Schema::create('client_penalty_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 20); // opening | invoice
            $table->foreignId('source_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->decimal('principal_base', 15, 2)->default(0);
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('days')->default(0);
            $table->decimal('percent', 8, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 20)->default('accrued'); // accrued | billed | paid | void
            $table->foreignId('billed_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('billed_item_id')->nullable()->constrained('document_items')->nullOnDelete();
            $table->date('paid_at')->nullable();
            $table->foreignId('paid_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['source_type', 'source_document_id']);
            $table->index(['company_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_penalty_charges');
    }
};
