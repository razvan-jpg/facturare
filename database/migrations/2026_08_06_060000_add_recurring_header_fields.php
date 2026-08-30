<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('recurring_invoices', 'subscription_number')) {
                $table->string('subscription_number', 40)->nullable()->after('title');
            }
            if (! Schema::hasColumn('recurring_invoices', 'series')) {
                $table->string('series', 20)->nullable()->after('currency');
            }
            if (! Schema::hasColumn('recurring_invoices', 'document_language')) {
                $table->string('document_language', 10)->default('ro')->after('series');
            }
            if (! Schema::hasColumn('recurring_invoices', 'payment_term')) {
                $table->string('payment_term', 30)->nullable()->after('due_days');
            }
            if (! Schema::hasColumn('recurring_invoices', 'max_documents')) {
                $table->integer('max_documents')->nullable()->after('payment_term');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            foreach (['subscription_number', 'series', 'document_language', 'payment_term', 'max_documents'] as $col) {
                if (Schema::hasColumn('recurring_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
