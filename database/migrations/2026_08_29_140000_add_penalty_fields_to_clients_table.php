<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'penalty_percent')) {
                $table->decimal('penalty_percent', 8, 4)->nullable()->after('opening_balance_date');
            }
            if (! Schema::hasColumn('clients', 'penalty_billing_enabled')) {
                $table->boolean('penalty_billing_enabled')->default(false)->after('penalty_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'penalty_billing_enabled')) {
                $table->dropColumn('penalty_billing_enabled');
            }
            if (Schema::hasColumn('clients', 'penalty_percent')) {
                $table->dropColumn('penalty_percent');
            }
        });
    }
};
