<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'opening_installment_amount')) {
                $table->decimal('opening_installment_amount', 15, 2)->nullable()->after('opening_balance_date');
            }
            if (! Schema::hasColumn('clients', 'opening_installment_count')) {
                $table->unsignedSmallInteger('opening_installment_count')->nullable()->after('opening_installment_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'opening_installment_count')) {
                $table->dropColumn('opening_installment_count');
            }
            if (Schema::hasColumn('clients', 'opening_installment_amount')) {
                $table->dropColumn('opening_installment_amount');
            }
        });
    }
};
