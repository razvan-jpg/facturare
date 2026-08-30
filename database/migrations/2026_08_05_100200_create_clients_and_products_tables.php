<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('company'); // company|person
            $table->string('cui', 20)->nullable()->index();
            $table->string('reg_com', 50)->nullable();
            $table->string('cnp', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->string('country')->default('România');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('iban', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku', 64)->nullable();
            $table->string('unit', 20)->default('buc');
            $table->string('type', 20)->default('service'); // service|product
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('clients');
    }
};
