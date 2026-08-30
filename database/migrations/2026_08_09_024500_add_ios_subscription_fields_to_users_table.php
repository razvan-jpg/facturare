<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ios_original_transaction_id', 64)->nullable()->unique()->after('stripe_customer_id');
            $table->string('ios_product_id', 128)->nullable()->after('ios_original_transaction_id');
            $table->timestamp('ios_expires_at')->nullable()->after('ios_product_id');
            $table->string('ios_subscription_status', 32)->nullable()->after('ios_expires_at');
            $table->string('ios_environment', 16)->nullable()->after('ios_subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ios_original_transaction_id',
                'ios_product_id',
                'ios_expires_at',
                'ios_subscription_status',
                'ios_environment',
            ]);
        });
    }
};
