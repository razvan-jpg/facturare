<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'promo_code')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('promo_code', 14)->nullable()->unique()->after('name');
            });
        }

        Company::query()
            ->whereNull('promo_code')
            ->orderBy('id')
            ->each(function (Company $company) {
                $company->forceFill([
                    'promo_code' => Company::generateUniquePromoCode(),
                ])->saveQuietly();
            });

        // MySQL permite mai multe NULL într-un UNIQUE — forțăm NOT NULL după backfill.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE companies MODIFY promo_code VARCHAR(14) NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite nu suportă MODIFY; coloana rămâne nullable, dar e backfill-uită.
        } else {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('promo_code', 14)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['promo_code']);
            $table->dropColumn('promo_code');
        });
    }
};
