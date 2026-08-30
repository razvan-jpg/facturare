<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('subuser_seat_quota')->default(0)->after('created_by_user_id');
            $table->timestamp('subuser_seats_until')->nullable()->after('subuser_seat_quota');
        });

        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->string('product_type', 32)->default('platform')->after('company_id');
            $table->unsignedInteger('seats')->default(0)->after('months');
            $table->index(['product_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropIndex(['product_type', 'status']);
            $table->dropColumn(['product_type', 'seats']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subuser_seat_quota', 'subuser_seats_until']);
        });
    }
};
