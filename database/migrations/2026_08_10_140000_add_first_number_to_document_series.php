<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_series')) {
            return;
        }

        if (! Schema::hasColumn('document_series', 'first_number')) {
            Schema::table('document_series', function (Blueprint $table) {
                $table->unsignedInteger('first_number')->default(1)->after('prefix');
            });
        }

        // Migrare din alt soft: golurile sub „următorul număr” nu trebuie reciclate.
        // Setăm pragul = next_number (sau minimul numerelor deja folosite în DateConta, dacă e mai mic).
        $series = DB::table('document_series')->select('id', 'company_id', 'type', 'prefix', 'year', 'next_number')->get();
        foreach ($series as $s) {
            $minTaken = DB::table('documents')
                ->where('company_id', $s->company_id)
                ->where('type', $s->type)
                ->where('series', $s->prefix)
                ->where(function ($q) use ($s) {
                    $q->where('issue_year', $s->year)
                        ->orWhere(function ($q2) use ($s) {
                            $q2->whereNull('issue_year')
                                ->whereYear('issue_date', $s->year);
                        });
                })
                ->whereNotNull('number')
                ->min('number');

            $next = max(1, (int) $s->next_number);
            $first = $minTaken !== null ? min($next, max(1, (int) $minTaken)) : $next;

            DB::table('document_series')->where('id', $s->id)->update(['first_number' => $first]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_series') && Schema::hasColumn('document_series', 'first_number')) {
            Schema::table('document_series', function (Blueprint $table) {
                $table->dropColumn('first_number');
            });
        }
    }
};
