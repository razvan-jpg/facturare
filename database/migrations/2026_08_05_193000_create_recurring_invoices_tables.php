<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('frequency', 20); // weekly|monthly|quarterly|semiannual|annual
            $table->date('start_date');
            $table->date('next_run_date')->index();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('due_days')->default(15);
            $table->string('currency', 3)->default('RON');
            $table->text('notes')->nullable();
            $table->boolean('auto_issue')->default(true);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_generated_at')->nullable();
            $table->foreignId('last_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedInteger('generated_count')->default(0);
            $table->timestamps();
        });

        Schema::create('recurring_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('name');
            $table->string('unit', 20)->default('buc');
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('recurring_invoice_id')->nullable()->after('related_document_id')
                ->constrained('recurring_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_invoice_id');
        });
        Schema::dropIfExists('recurring_invoice_items');
        Schema::dropIfExists('recurring_invoices');
    }
};
