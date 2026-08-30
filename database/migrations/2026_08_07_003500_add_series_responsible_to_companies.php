<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'series_responsible_name')) {
                $table->string('series_responsible_name', 255)->nullable()->after('invoice_notes');
            }
            if (! Schema::hasColumn('companies', 'series_responsible_role')) {
                $table->string('series_responsible_role', 255)->nullable()->after('series_responsible_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'series_responsible_role')) {
                $table->dropColumn('series_responsible_role');
            }
            if (Schema::hasColumn('companies', 'series_responsible_name')) {
                $table->dropColumn('series_responsible_name');
            }
        });
    }
};
