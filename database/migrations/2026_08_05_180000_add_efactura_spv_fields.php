<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('efactura_send_mode', 20)->default('manual')->after('invoice_notes');
            $table->text('anaf_access_token')->nullable()->after('efactura_send_mode');
            $table->text('anaf_refresh_token')->nullable()->after('anaf_access_token');
            $table->timestamp('anaf_token_expires_at')->nullable()->after('anaf_refresh_token');
            $table->timestamp('anaf_authorized_at')->nullable()->after('anaf_token_expires_at');
            $table->string('anaf_authorized_by')->nullable()->after('anaf_authorized_at');
            $table->string('anaf_cif', 20)->nullable()->after('anaf_authorized_by');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('efactura_upload_id')->nullable()->after('efactura_status');
            $table->string('efactura_download_id')->nullable()->after('efactura_upload_id');
            $table->text('efactura_error')->nullable()->after('efactura_download_id');
            $table->timestamp('efactura_sent_at')->nullable()->after('efactura_error');
            $table->timestamp('efactura_checked_at')->nullable()->after('efactura_sent_at');
        });

        Schema::create('efactura_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efactura_invites');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'efactura_upload_id',
                'efactura_download_id',
                'efactura_error',
                'efactura_sent_at',
                'efactura_checked_at',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'efactura_send_mode',
                'anaf_access_token',
                'anaf_refresh_token',
                'anaf_token_expires_at',
                'anaf_authorized_at',
                'anaf_authorized_by',
                'anaf_cif',
            ]);
        });
    }
};
