<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // invoice|proforma|delivery|receipt
            $table->string('prefix', 20);
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedSmallInteger('year');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'type', 'prefix', 'year']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20); // invoice|proforma|delivery|receipt
            $table->string('status', 20)->default('draft'); // draft|issued|cancelled|storno
            $table->string('series', 20)->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->string('number_full', 40)->nullable()->index();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('RON');
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid'); // unpaid|partial|paid
            $table->text('notes')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_cui', 20)->nullable();
            $table->string('client_reg_com', 50)->nullable();
            $table->string('client_address')->nullable();
            $table->string('client_email')->nullable();
            $table->foreignId('related_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('efactura_status')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'type', 'status']);
        });

        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('name');
            $table->string('unit', 20)->default('buc');
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->decimal('line_subtotal', 15, 2)->default(0);
            $table->decimal('line_vat', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 20)->default('op'); // cash|op|card|other|receipt
            $table->date('paid_at');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('RON');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('document_items');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_series');
    }
};
