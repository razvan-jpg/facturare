<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'card_integrations')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->text('card_integrations')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'card_integrations')) {
                $table->dropColumn('card_integrations');
            }
        });
    }
};
