<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('referred_by_company_id')
                ->nullable()
                ->after('promo_code')
                ->constrained('companies')
                ->nullOnDelete();
            $table->unsignedInteger('referral_rewards_granted')->default(0)->after('referred_by_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_company_id');
            $table->dropColumn('referral_rewards_granted');
        });
    }
};
