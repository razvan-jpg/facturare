<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email', 500)->nullable()->change();
        });

        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'client_email')) {
                $table->string('client_email', 500)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }
};
