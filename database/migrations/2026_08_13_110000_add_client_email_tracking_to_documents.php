<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'client_email_status')) {
                $table->string('client_email_status', 20)->default('none')->after('auto_email_cc_address');
            }
            if (! Schema::hasColumn('documents', 'client_email_sent_at')) {
                $table->timestamp('client_email_sent_at')->nullable()->after('client_email_status');
            }
            if (! Schema::hasColumn('documents', 'client_email_error')) {
                $table->text('client_email_error')->nullable()->after('client_email_sent_at');
            }
            if (! Schema::hasColumn('documents', 'client_email_attempts')) {
                $table->unsignedTinyInteger('client_email_attempts')->default(0)->after('client_email_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            foreach (['client_email_attempts', 'client_email_error', 'client_email_sent_at', 'client_email_status'] as $col) {
                if (Schema::hasColumn('documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
