<?php

namespace App\Providers;

use App\Services\CompanyContext;
use App\Services\PlatformSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
        $this->app->singleton(CompanyContext::class);
        $this->app->singleton(PlatformSettings::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        try {
            $this->app->make(PlatformSettings::class)->applyToConfig();
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }

        $this->ensureOpeningBalanceColumns();
        $this->ensureClientPenaltyColumns();
        $this->ensureSeriesFirstNumberColumn();
        $this->ensureMeasureUnitsTable();
        $this->ensureRecurringDocumentTypeColumn();
        $this->ensureRecurringNextRunNullable();
        $this->ensureUiLocaleNormalized();
        $this->ensureForcedInvoiceTemplates();
    }

    /** One-shot schema guard for client penalty fields + charges table (self-disables after success). */
    private function ensureClientPenaltyColumns(): void
    {
        $flag = storage_path('framework/client_penalty_bootstrapped_v3');
        if (is_file($flag)) {
            return;
        }

        try {
            if (! Schema::hasTable('clients')) {
                return;
            }

            if (! Schema::hasColumn('clients', 'penalty_percent')) {
                DB::statement('ALTER TABLE `clients` ADD `penalty_percent` DECIMAL(8,4) NULL AFTER `opening_balance_date`');
            }
            if (! Schema::hasColumn('clients', 'penalty_billing_enabled')) {
                DB::statement('ALTER TABLE `clients` ADD `penalty_billing_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `penalty_percent`');
            }
            if (! Schema::hasColumn('clients', 'opening_installment_amount')) {
                DB::statement('ALTER TABLE `clients` ADD `opening_installment_amount` DECIMAL(15,2) NULL AFTER `opening_balance_date`');
            }
            if (! Schema::hasColumn('clients', 'opening_installment_count')) {
                DB::statement('ALTER TABLE `clients` ADD `opening_installment_count` SMALLINT UNSIGNED NULL AFTER `opening_installment_amount`');
            }

            if (! Schema::hasTable('client_penalty_charges')) {
                Schema::create('client_penalty_charges', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                    $table->string('source_type', 20);
                    $table->foreignId('source_document_id')->nullable()->constrained('documents')->nullOnDelete();
                    $table->decimal('principal_base', 15, 2)->default(0);
                    $table->date('period_from');
                    $table->date('period_to');
                    $table->unsignedInteger('days')->default(0);
                    $table->decimal('percent', 8, 4)->default(0);
                    $table->decimal('amount', 15, 2)->default(0);
                    $table->string('status', 20)->default('accrued');
                    $table->foreignId('billed_document_id')->nullable()->constrained('documents')->nullOnDelete();
                    $table->foreignId('billed_item_id')->nullable()->constrained('document_items')->nullOnDelete();
                    $table->date('paid_at')->nullable();
                    $table->foreignId('paid_payment_id')->nullable()->constrained('payments')->nullOnDelete();
                    $table->timestamps();
                    $table->index(['client_id', 'status']);
                    $table->index(['source_type', 'source_document_id']);
                    $table->index(['company_id', 'client_id']);
                });
            }

            foreach ([
                '2026_08_29_140000_add_penalty_fields_to_clients_table',
                '2026_08_29_153000_create_client_penalty_charges_table',
                '2026_08_30_060000_add_opening_installments_to_clients_table',
            ] as $mig) {
                if (! DB::table('migrations')->where('migration', $mig)->exists()) {
                    $batch = (int) (DB::table('migrations')->max('batch') ?: 0) + 1;
                    DB::table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
                }
            }

            @file_put_contents($flag, date('c'));
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }
    }

    /** One-shot schema guard for opening_balance (self-disables after success). */
    private function ensureOpeningBalanceColumns(): void
    {
        $flag = storage_path('framework/opening_balance_bootstrapped');
        if (is_file($flag)) {
            return;
        }

        try {
            if (! Schema::hasTable('clients')) {
                return;
            }

            if (! Schema::hasColumn('clients', 'opening_balance')) {
                DB::statement('ALTER TABLE `clients` ADD `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `notes`');
            }
            if (! Schema::hasColumn('clients', 'opening_balance_date')) {
                DB::statement('ALTER TABLE `clients` ADD `opening_balance_date` DATE NULL AFTER `opening_balance`');
            }

            $mig = '2026_08_08_010000_add_opening_balance_to_clients_table';
            if (! DB::table('migrations')->where('migration', $mig)->exists()) {
                $batch = (int) (DB::table('migrations')->max('batch') ?: 0) + 1;
                DB::table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
            }

            @file_put_contents($flag, date('c'));
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }
    }

    /** One-shot schema guard for document_series.first_number. */
    private function ensureSeriesFirstNumberColumn(): void
    {
        $flag = storage_path('framework/series_first_number_bootstrapped');
        if (is_file($flag)) {
            return;
        }

        try {
            if (! Schema::hasTable('document_series')) {
                return;
            }

            if (! Schema::hasColumn('document_series', 'first_number')) {
                DB::statement('ALTER TABLE `document_series` ADD `first_number` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `prefix`');
            }

            // Backfill: prag = next_number (sau min. numerelor deja folosite, dacă e mai mic).
            $series = DB::table('document_series')->select('id', 'company_id', 'type', 'prefix', 'year', 'next_number', 'first_number')->get();
            foreach ($series as $s) {
                $currentFirst = (int) ($s->first_number ?? 0);
                if ($currentFirst > 1) {
                    continue;
                }
                $minTaken = DB::table('documents')
                    ->where('company_id', $s->company_id)
                    ->where('type', $s->type)
                    ->where('series', $s->prefix)
                    ->whereNotNull('number')
                    ->where(function ($q) use ($s) {
                        $q->where('issue_year', $s->year)
                            ->orWhere(function ($q2) use ($s) {
                                $q2->whereNull('issue_year')->whereYear('issue_date', $s->year);
                            });
                    })
                    ->min('number');
                $next = max(1, (int) $s->next_number);
                $first = $minTaken !== null ? min($next, max(1, (int) $minTaken)) : $next;
                if ($first !== $currentFirst) {
                    DB::table('document_series')->where('id', $s->id)->update(['first_number' => $first]);
                }
            }

            $mig = '2026_08_10_140000_add_first_number_to_document_series';
            if (! DB::table('migrations')->where('migration', $mig)->exists()) {
                $batch = (int) (DB::table('migrations')->max('batch') ?: 0) + 1;
                DB::table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
            }

            @file_put_contents($flag, date('c'));
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }
    }

    private function ensureMeasureUnitsTable(): void
    {
        $flag = storage_path('framework/measure_units_bootstrapped');
        if (is_file($flag)) {
            return;
        }

        try {
            if (! Schema::hasTable('measure_units')) {
                Schema::create('measure_units', function ($table) {
                    $table->id();
                    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                    $table->string('name', 32);
                    $table->string('unece_code', 10)->nullable();
                    $table->boolean('active')->default(true);
                    $table->timestamps();
                    $table->unique(['company_id', 'name']);
                });
            }

            foreach (['products', 'document_items', 'recurring_invoice_items'] as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'unit')) {
                    continue;
                }
                try {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `unit` VARCHAR(32) NOT NULL DEFAULT 'buc'");
                } catch (\Throwable) {
                }
            }

            $mig = '2026_08_10_143000_create_measure_units_table';
            if (! DB::table('migrations')->where('migration', $mig)->exists()) {
                $batch = (int) (DB::table('migrations')->max('batch') ?: 0) + 1;
                DB::table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
            }

            @file_put_contents($flag, date('c'));
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }
    }

    /** One-shot schema guard for recurring_invoices.document_type. */
    private function ensureRecurringDocumentTypeColumn(): void
    {
        $flag = storage_path('framework/recurring_document_type_bootstrapped');
        if (is_file($flag)) {
            return;
        }

        try {
            if (! Schema::hasTable('recurring_invoices')) {
                return;
            }

            if (! Schema::hasColumn('recurring_invoices', 'document_type')) {
                DB::statement("ALTER TABLE `recurring_invoices` ADD `document_type` VARCHAR(20) NOT NULL DEFAULT 'invoice' AFTER `series`");
            }

            $mig = '2026_08_11_081700_add_document_type_to_recurring_invoices';
            if (! DB::table('migrations')->where('migration', $mig)->exists()) {
                $batch = (int) (DB::table('migrations')->max('batch') ?: 0) + 1;
                DB::table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
            }

            @file_put_contents($flag, date('c'));
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }
    }

    /** One-shot: FIRST CONSULTING / FLY DAVID → machete DateConta fixe. */
    private function ensureForcedInvoiceTemplates(): void
    {
        $flag = storage_path('framework/forced_invoice_templates_bootstrapped');
        if (is_file($flag)) {
            return;
        }

        try {
            if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'invoice_template')) {
                return;
            }

            $map = config('invoice_templates.forced_by_cui', []);
            if (! is_array($map) || $map === []) {
                @file_put_contents($flag, date('c'));

                return;
            }

            foreach ($map as $cui => $template) {
                $digits = preg_replace('/\D+/', '', strtoupper((string) $cui)) ?: '';
                if ($digits === '' || ! is_string($template) || $template === '') {
                    continue;
                }
                DB::table('companies')
                    ->whereRaw("REPLACE(REPLACE(UPPER(cui), 'RO', ''), ' ', '') = ?", [$digits])
                    ->update(['invoice_template' => $template]);
            }

            @file_put_contents($flag, date('c'));
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }
    }

    /** One-shot: normalize legacy ui_locale codes (en → en_US, zh → zh_CN). */
    private function ensureUiLocaleNormalized(): void
    {
        $flag = storage_path('framework/ui_locale_normalized_bootstrapped');
        if (is_file($flag)) {
            return;
        }

        try {
            if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'ui_locale')) {
                return;
            }

            DB::table('users')->where('ui_locale', 'en')->update(['ui_locale' => 'en_US']);
            DB::table('users')->where('ui_locale', 'zh')->update(['ui_locale' => 'zh_CN']);

            $mig = '2026_08_13_072000_normalize_ui_locale_en_to_en_us';
            if (! DB::table('migrations')->where('migration', $mig)->exists()) {
                $batch = (int) (DB::table('migrations')->max('batch') ?: 0) + 1;
                DB::table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
            }

            @file_put_contents($flag, date('c'));
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }
    }

    /** One-shot: next_run_date nullable when abonamentul e pauzat. */
    private function ensureRecurringNextRunNullable(): void
    {
        $flag = storage_path('framework/recurring_next_run_nullable_bootstrapped');
        if (is_file($flag)) {
            return;
        }

        try {
            if (! Schema::hasTable('recurring_invoices')) {
                return;
            }

            $col = DB::selectOne(
                "SELECT IS_NULLABLE AS is_nullable FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'recurring_invoices' AND COLUMN_NAME = 'next_run_date'"
            );
            if ($col && strtoupper((string) $col->is_nullable) === 'NO') {
                DB::statement('ALTER TABLE `recurring_invoices` MODIFY `next_run_date` DATE NULL');
            }

            DB::table('recurring_invoices')
                ->where('active', false)
                ->whereNotNull('next_run_date')
                ->update(['next_run_date' => null]);

            $mig = '2026_08_12_183000_make_recurring_next_run_date_nullable';
            if (! DB::table('migrations')->where('migration', $mig)->exists()) {
                $batch = (int) (DB::table('migrations')->max('batch') ?: 0) + 1;
                DB::table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
            }

            @file_put_contents($flag, date('c'));
        } catch (\Throwable) {
            // DB unavailable during early boot / migrate
        }
    }
}
