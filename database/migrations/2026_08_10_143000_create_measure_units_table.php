<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('measure_units')) {
            Schema::create('measure_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name', 32);
                $table->string('unece_code', 10)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'name']);
            });
        }

        foreach (['products', 'document_items', 'recurring_invoice_items'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'unit')) {
                continue;
            }
            try {
                DB::statement("ALTER TABLE `{$table}` MODIFY `unit` VARCHAR(32) NOT NULL DEFAULT 'buc'");
            } catch (\Throwable) {
                // ignore if already wide / engine differences
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('measure_units');
    }
};
