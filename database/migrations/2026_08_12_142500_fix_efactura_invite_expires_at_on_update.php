<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL a pus pe expires_at DEFAULT + ON UPDATE current_timestamp
     * (prima coloană TIMESTAMP din tabel). La save pe sent_at, expires_at
     * devenea „acum” → invitația apărea invalidă imediat.
     */
    public function up(): void
    {
        if (! Schema::hasTable('efactura_invites')) {
            return;
        }

        DB::statement('ALTER TABLE efactura_invites MODIFY expires_at DATETIME NOT NULL');

        // Repară invitațiile încă neacceptate, expirate artificial de bug.
        DB::table('efactura_invites')
            ->whereNull('accepted_at')
            ->whereColumn('expires_at', '<=', 'created_at')
            ->update([
                'expires_at' => DB::raw('DATE_ADD(COALESCE(sent_at, created_at), INTERVAL 7 DAY)'),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('efactura_invites')) {
            return;
        }

        DB::statement('ALTER TABLE efactura_invites MODIFY expires_at TIMESTAMP NOT NULL');
    }
};
