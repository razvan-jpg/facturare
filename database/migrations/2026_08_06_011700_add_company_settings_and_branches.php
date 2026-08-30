<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'capital_social')) {
                $table->string('capital_social', 50)->nullable()->after('county');
            }
            if (! Schema::hasColumn('companies', 'website')) {
                $table->string('website', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('companies', 'vat_on_collection')) {
                $table->boolean('vat_on_collection')->default(false)->after('vat_payer');
            }
            if (! Schema::hasColumn('companies', 'document_languages')) {
                $table->json('document_languages')->nullable()->after('invoice_notes');
            }
            if (! Schema::hasColumn('companies', 'email_invoice_subject')) {
                $table->string('email_invoice_subject', 255)->nullable()->after('document_languages');
            }
            if (! Schema::hasColumn('companies', 'email_invoice_body')) {
                $table->text('email_invoice_body')->nullable()->after('email_invoice_subject');
            }
            if (! Schema::hasColumn('companies', 'preferences')) {
                $table->json('preferences')->nullable()->after('email_invoice_body');
            }
        });

        if (! Schema::hasTable('company_branches')) {
            Schema::create('company_branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('county', 100)->nullable();
                $table->string('phone', 50)->nullable();
                $table->boolean('is_main')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('document_series', function (Blueprint $table) {
            if (! Schema::hasColumn('document_series', 'description')) {
                $table->string('description', 255)->nullable()->after('prefix');
            }
            if (! Schema::hasColumn('document_series', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('active');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_branches');

        Schema::table('companies', function (Blueprint $table) {
            foreach ([
                'capital_social', 'website', 'vat_on_collection', 'document_languages',
                'email_invoice_subject', 'email_invoice_body', 'preferences',
            ] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('document_series', function (Blueprint $table) {
            if (Schema::hasColumn('document_series', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('document_series', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
