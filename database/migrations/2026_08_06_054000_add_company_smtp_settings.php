<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'mail_use_custom_smtp')) {
                $table->boolean('mail_use_custom_smtp')->default(false)->after('email_invoice_body');
            }
            if (! Schema::hasColumn('companies', 'mail_smtp_username')) {
                $table->string('mail_smtp_username', 255)->nullable()->after('mail_use_custom_smtp');
            }
            if (! Schema::hasColumn('companies', 'mail_smtp_password')) {
                $table->text('mail_smtp_password')->nullable()->after('mail_smtp_username');
            }
            if (! Schema::hasColumn('companies', 'mail_smtp_host')) {
                $table->string('mail_smtp_host', 255)->nullable()->after('mail_smtp_password');
            }
            if (! Schema::hasColumn('companies', 'mail_smtp_port')) {
                $table->unsignedSmallInteger('mail_smtp_port')->nullable()->after('mail_smtp_host');
            }
            if (! Schema::hasColumn('companies', 'mail_smtp_tls')) {
                $table->boolean('mail_smtp_tls')->default(false)->after('mail_smtp_port');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach ([
                'mail_use_custom_smtp',
                'mail_smtp_username',
                'mail_smtp_password',
                'mail_smtp_host',
                'mail_smtp_port',
                'mail_smtp_tls',
            ] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
