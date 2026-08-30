<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'admin_last_name')) {
                $table->string('admin_last_name', 100)->nullable()->after('reg_com');
            }
            if (! Schema::hasColumn('clients', 'admin_first_name')) {
                $table->string('admin_first_name', 100)->nullable()->after('admin_last_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'admin_first_name')) {
                $table->dropColumn('admin_first_name');
            }
            if (Schema::hasColumn('clients', 'admin_last_name')) {
                $table->dropColumn('admin_last_name');
            }
        });
    }
};
