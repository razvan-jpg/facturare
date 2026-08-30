<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clients', 'bank_name')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('bank_name', 100)->nullable()->after('iban');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'bank_name')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('bank_name');
            });
        }
    }
};
