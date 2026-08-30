<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('documents', 'documents_company_type_status_issue_idx', function (Blueprint $table) {
            $table->index(['company_id', 'type', 'status', 'issue_date'], 'documents_company_type_status_issue_idx');
        });
        $this->addIndexIfMissing('documents', 'documents_company_pay_due_idx', function (Blueprint $table) {
            $table->index(['company_id', 'type', 'status', 'payment_status', 'due_date'], 'documents_company_pay_due_idx');
        });
        $this->addIndexIfMissing('documents', 'documents_company_status_id_idx', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'id'], 'documents_company_status_id_idx');
        });
        $this->addIndexIfMissing('documents', 'documents_related_status_idx', function (Blueprint $table) {
            $table->index(['related_document_id', 'status'], 'documents_related_status_idx');
        });

        $this->addIndexIfMissing('payments', 'payments_company_paid_at_idx', function (Blueprint $table) {
            $table->index(['company_id', 'paid_at'], 'payments_company_paid_at_idx');
        });

        $this->addIndexIfMissing('clients', 'clients_company_name_idx', function (Blueprint $table) {
            $table->index(['company_id', 'name'], 'clients_company_name_idx');
        });

        $this->addIndexIfMissing('products', 'products_company_active_name_idx', function (Blueprint $table) {
            $table->index(['company_id', 'active', 'name'], 'products_company_active_name_idx');
        });

        if (Schema::hasTable('visitor_sessions')) {
            $this->addIndexIfMissing('visitor_sessions', 'visitor_sessions_ip_last_seen_idx', function (Blueprint $table) {
                $table->index(['ip', 'last_seen_at'], 'visitor_sessions_ip_last_seen_idx');
            });
        }
    }

    public function down(): void
    {
        $drops = [
            'documents' => [
                'documents_company_type_status_issue_idx',
                'documents_company_pay_due_idx',
                'documents_company_status_id_idx',
                'documents_related_status_idx',
            ],
            'payments' => ['payments_company_paid_at_idx'],
            'clients' => ['clients_company_name_idx'],
            'products' => ['products_company_active_name_idx'],
            'visitor_sessions' => ['visitor_sessions_ip_last_seen_idx'],
        ];

        foreach ($drops as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $index) {
                    try {
                        $blueprint->dropIndex($index);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            });
        }
    }

    private function addIndexIfMissing(string $table, string $index, callable $callback): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, $callback);
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");

            foreach ($rows as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, $table, $index]
        );

        return (bool) $row;
    }
};
