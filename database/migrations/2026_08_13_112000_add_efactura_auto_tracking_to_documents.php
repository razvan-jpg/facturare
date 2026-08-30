<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'efactura_auto_attempts')) {
                $table->unsignedTinyInteger('efactura_auto_attempts')->default(0)->after('efactura_scheduled_at');
            }
            if (! Schema::hasColumn('documents', 'efactura_auto_last_error')) {
                $table->string('efactura_auto_last_error', 255)->nullable()->after('efactura_auto_attempts');
            }
            if (! Schema::hasColumn('documents', 'efactura_auto_next_at')) {
                $table->timestamp('efactura_auto_next_at')->nullable()->after('efactura_auto_last_error');
            }
            if (! Schema::hasColumn('documents', 'efactura_auto_alerted_at')) {
                $table->timestamp('efactura_auto_alerted_at')->nullable()->after('efactura_auto_next_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            foreach ([
                'efactura_auto_alerted_at',
                'efactura_auto_next_at',
                'efactura_auto_last_error',
                'efactura_auto_attempts',
            ] as $col) {
                if (Schema::hasColumn('documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
