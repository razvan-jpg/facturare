<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_bank_id')->constrained('company_banks')->cascadeOnDelete();
            $table->string('iban', 64);
            $table->string('currency', 3)->default('RON');
            $table->boolean('show_on_invoice')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('efactura_scheduled_at')->nullable()->after('efactura_checked_at');
        });

        // Migrate legacy single bank/IBAN + normalize send mode
        $companies = DB::table('companies')->select('id', 'iban', 'bank_name', 'efactura_send_mode')->get();
        foreach ($companies as $company) {
            $mode = $company->efactura_send_mode ?: 'manual';
            if ($mode === 'auto') {
                DB::table('companies')->where('id', $company->id)->update(['efactura_send_mode' => 'on_save']);
            }

            if (blank($company->iban) && blank($company->bank_name)) {
                continue;
            }

            $bankId = DB::table('company_banks')->insertGetId([
                'company_id' => $company->id,
                'name' => $company->bank_name ?: 'Bancă principală',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (filled($company->iban)) {
                DB::table('company_bank_accounts')->insert([
                    'company_bank_id' => $bankId,
                    'iban' => $company->iban,
                    'currency' => 'RON',
                    'show_on_invoice' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('efactura_scheduled_at');
        });
        Schema::dropIfExists('company_bank_accounts');
        Schema::dropIfExists('company_banks');
    }
};
