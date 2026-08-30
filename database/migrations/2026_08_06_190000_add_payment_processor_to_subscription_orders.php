<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscription_orders', 'payment_processor')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->string('payment_processor', 16)->nullable()->after('payment_method');
            });
        }

        if (! Schema::hasColumn('subscription_orders', 'mollie_payment_id')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->string('mollie_payment_id', 64)->nullable()->after('netopia_ref');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_orders', 'mollie_payment_id')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->dropColumn('mollie_payment_id');
            });
        }

        if (Schema::hasColumn('subscription_orders', 'payment_processor')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->dropColumn('payment_processor');
            });
        }
    }
};
