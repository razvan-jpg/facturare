<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('overdue_reminders_enabled')->default(false)->after('efactura_send_mode');
            $table->unsignedSmallInteger('overdue_reminder_frequency_days')->default(7)->after('overdue_reminders_enabled');
            $table->string('overdue_reminder_scope', 20)->default('both')->after('overdue_reminder_frequency_days'); // invoices|balance|both
            $table->boolean('overdue_reminder_include_statement')->default(true)->after('overdue_reminder_scope');
            $table->unsignedSmallInteger('overdue_reminder_grace_days')->default(0)->after('overdue_reminder_include_statement');
        });

        Schema::create('overdue_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('scope', 20);
            $table->boolean('included_statement')->default(false);
            $table->json('document_ids')->nullable();
            $table->decimal('balance_total', 14, 2)->default(0);
            $table->unsignedInteger('invoice_count')->default(0);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['client_id', 'sent_at']);
            $table->index(['company_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overdue_reminder_logs');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'overdue_reminders_enabled',
                'overdue_reminder_frequency_days',
                'overdue_reminder_scope',
                'overdue_reminder_include_statement',
                'overdue_reminder_grace_days',
            ]);
        });
    }
};
