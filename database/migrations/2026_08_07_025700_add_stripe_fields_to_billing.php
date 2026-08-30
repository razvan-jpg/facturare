<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'stripe_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_customer_id', 64)->nullable()->after('mollie_customer_id');
            });
        }

        if (! Schema::hasColumn('subscription_orders', 'stripe_session_id')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->string('stripe_session_id', 128)->nullable()->after('mollie_payment_id');
                $table->string('stripe_subscription_id', 64)->nullable()->after('stripe_session_id');
                $table->string('stripe_payment_intent', 64)->nullable()->after('stripe_subscription_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_orders', 'stripe_session_id')) {
            Schema::table('subscription_orders', function (Blueprint $table) {
                $table->dropColumn(['stripe_session_id', 'stripe_subscription_id', 'stripe_payment_intent']);
            });
        }

        if (Schema::hasColumn('users', 'stripe_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('stripe_customer_id');
            });
        }
    }
};
