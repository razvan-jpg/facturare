<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('companies', 'stamp_path')) {
                $table->string('stamp_path')->nullable()->after('signature_path');
            }
            if (! Schema::hasColumn('companies', 'signature_text')) {
                $table->string('signature_text', 255)->nullable()->after('stamp_path');
            }
            if (! Schema::hasColumn('companies', 'invoice_template')) {
                $table->string('invoice_template', 40)->default('classic')->after('invoice_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach (['signature_path', 'stamp_path', 'signature_text', 'invoice_template'] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
