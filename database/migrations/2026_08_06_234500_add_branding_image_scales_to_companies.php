<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'logo_scale')) {
                $table->string('logo_scale', 8)->default('100')->after('logo_path');
            }
            if (! Schema::hasColumn('companies', 'signature_scale')) {
                $table->string('signature_scale', 8)->default('100')->after('signature_path');
            }
            if (! Schema::hasColumn('companies', 'stamp_scale')) {
                $table->string('stamp_scale', 8)->default('100')->after('stamp_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach (['logo_scale', 'signature_scale', 'stamp_scale'] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
