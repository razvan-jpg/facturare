<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recurring_invoices')) {
            return;
        }

        if (! Schema::hasColumn('recurring_invoices', 'document_type')) {
            Schema::table('recurring_invoices', function (Blueprint $table) {
                $table->string('document_type', 20)->default('invoice')->after('series');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recurring_invoices') && Schema::hasColumn('recurring_invoices', 'document_type')) {
            Schema::table('recurring_invoices', function (Blueprint $table) {
                $table->dropColumn('document_type');
            });
        }
    }
};
