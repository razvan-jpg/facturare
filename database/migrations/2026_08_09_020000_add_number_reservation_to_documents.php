<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('number_reserved_at')->nullable()->after('number_full');
            $table->unsignedSmallInteger('issue_year')->nullable()->after('issue_date');
            $table->index(['company_id', 'type', 'series', 'number', 'status'], 'documents_series_number_status_idx');
        });

        DB::table('documents')
            ->whereNotNull('issue_date')
            ->whereNull('issue_year')
            ->update(['issue_year' => DB::raw('YEAR(issue_date)')]);

        Schema::table('documents', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'type', 'series', 'issue_year', 'number'],
                'documents_series_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique('documents_series_number_unique');
            $table->dropIndex('documents_series_number_status_idx');
            $table->dropColumn(['number_reserved_at', 'issue_year']);
        });
    }
};
