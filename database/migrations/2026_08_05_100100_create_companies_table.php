<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('cui', 20)->nullable()->index();
            $table->string('reg_com', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->string('country')->default('România');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('bank_name')->nullable();
            $table->boolean('vat_payer')->default(true);
            $table->decimal('default_vat_rate', 5, 2)->default(21);
            $table->string('logo_path')->nullable();
            $table->string('invoice_color', 20)->default('#0F4C5C');
            $table->text('invoice_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('owner'); // owner|operator
            $table->timestamps();
            $table->unique(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('companies');
    }
};
