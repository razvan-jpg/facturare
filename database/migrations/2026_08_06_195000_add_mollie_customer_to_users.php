<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'mollie_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('mollie_customer_id', 64)->nullable()->after('access_until');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'mollie_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('mollie_customer_id');
            });
        }
    }
};
