<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_orders')) {
            return;
        }

        Schema::table('subscription_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_orders', 'invoice_document_id')) {
                $table->foreignId('invoice_document_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('documents')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_orders') || ! Schema::hasColumn('subscription_orders', 'invoice_document_id')) {
            return;
        }

        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_document_id');
        });
    }
};
