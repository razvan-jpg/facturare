<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'allow_card_payment')) {
                $table->boolean('allow_card_payment')->default(false)->after('notes');
            }
            if (! Schema::hasColumn('documents', 'contract_number')) {
                $table->string('contract_number', 100)->nullable()->after('allow_card_payment');
            }
            if (! Schema::hasColumn('documents', 'despatch_advice')) {
                $table->string('despatch_advice', 100)->nullable()->after('contract_number');
            }
            if (! Schema::hasColumn('documents', 'prepared_by')) {
                $table->string('prepared_by', 255)->nullable()->after('despatch_advice');
            }
            if (! Schema::hasColumn('documents', 'prepared_by_cnp')) {
                $table->string('prepared_by_cnp', 20)->nullable()->after('prepared_by');
            }
            if (! Schema::hasColumn('documents', 'delegate_name')) {
                $table->string('delegate_name', 255)->nullable()->after('prepared_by_cnp');
            }
            if (! Schema::hasColumn('documents', 'delegate_id_card')) {
                $table->string('delegate_id_card', 50)->nullable()->after('delegate_name');
            }
            if (! Schema::hasColumn('documents', 'vehicle_reg')) {
                $table->string('vehicle_reg', 50)->nullable()->after('delegate_id_card');
            }
            if (! Schema::hasColumn('documents', 'auto_email_client')) {
                $table->boolean('auto_email_client')->default(false)->after('vehicle_reg');
            }
            if (! Schema::hasColumn('documents', 'auto_email_cc')) {
                $table->boolean('auto_email_cc')->default(false)->after('auto_email_client');
            }
            if (! Schema::hasColumn('documents', 'auto_email_cc_address')) {
                $table->string('auto_email_cc_address', 255)->nullable()->after('auto_email_cc');
            }
        });

        Schema::table('recurring_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('recurring_invoices', 'allow_card_payment')) {
                $table->boolean('allow_card_payment')->default(false)->after('notes');
            }
            if (! Schema::hasColumn('recurring_invoices', 'contract_number')) {
                $table->string('contract_number', 100)->nullable()->after('allow_card_payment');
            }
            if (! Schema::hasColumn('recurring_invoices', 'despatch_advice')) {
                $table->string('despatch_advice', 100)->nullable()->after('contract_number');
            }
            if (! Schema::hasColumn('recurring_invoices', 'prepared_by')) {
                $table->string('prepared_by', 255)->nullable()->after('despatch_advice');
            }
            if (! Schema::hasColumn('recurring_invoices', 'prepared_by_cnp')) {
                $table->string('prepared_by_cnp', 20)->nullable()->after('prepared_by');
            }
            if (! Schema::hasColumn('recurring_invoices', 'delegate_name')) {
                $table->string('delegate_name', 255)->nullable()->after('prepared_by_cnp');
            }
            if (! Schema::hasColumn('recurring_invoices', 'delegate_id_card')) {
                $table->string('delegate_id_card', 50)->nullable()->after('delegate_name');
            }
            if (! Schema::hasColumn('recurring_invoices', 'vehicle_reg')) {
                $table->string('vehicle_reg', 50)->nullable()->after('delegate_id_card');
            }
            if (! Schema::hasColumn('recurring_invoices', 'auto_email_client')) {
                $table->boolean('auto_email_client')->default(false)->after('vehicle_reg');
            }
            if (! Schema::hasColumn('recurring_invoices', 'auto_email_cc')) {
                $table->boolean('auto_email_cc')->default(false)->after('auto_email_client');
            }
            if (! Schema::hasColumn('recurring_invoices', 'auto_email_cc_address')) {
                $table->string('auto_email_cc_address', 255)->nullable()->after('auto_email_cc');
            }
        });
    }

    public function down(): void
    {
        $cols = [
            'allow_card_payment', 'contract_number', 'despatch_advice',
            'prepared_by', 'prepared_by_cnp', 'delegate_name', 'delegate_id_card',
            'vehicle_reg', 'auto_email_client', 'auto_email_cc', 'auto_email_cc_address',
        ];

        Schema::table('documents', function (Blueprint $table) use ($cols) {
            foreach ($cols as $col) {
                if (Schema::hasColumn('documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('recurring_invoices', function (Blueprint $table) use ($cols) {
            foreach ($cols as $col) {
                if (Schema::hasColumn('recurring_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
