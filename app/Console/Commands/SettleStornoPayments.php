<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Console\Command;

class SettleStornoPayments extends Command
{
    protected $signature = 'documents:settle-storno-payments
                            {--dry-run : Doar afișează câte perechi ar fi procesate}';

    protected $description = 'Marchează Achitată toate perechile storno + factură originală (backfill idempotent)';

    public function handle(DocumentService $documents): int
    {
        $dry = (bool) $this->option('dry-run');
        $stornos = Document::query()
            ->where('status', 'storno')
            ->whereNotNull('related_document_id')
            ->orderBy('id')
            ->get();

        $this->info('Storno găsite: '.$stornos->count().($dry ? ' (dry-run)' : ''));

        $processed = 0;
        $skipped = 0;
        foreach ($stornos as $storno) {
            $original = Document::query()->find($storno->related_document_id);
            if (! $original) {
                $this->warn("Storno #{$storno->id}: original lipsă ({$storno->related_document_id})");
                $skipped++;

                continue;
            }

            if ($dry) {
                $this->line("  ar procesa: original #{$original->id} + storno #{$storno->id} ({$storno->number_full})");
                $processed++;

                continue;
            }

            $before = [
                $original->payment_status,
                (float) $original->paid_amount,
                $storno->payment_status,
                (float) $storno->paid_amount,
                (int) $original->payments()->count(),
            ];

            $documents->settleStornoPair($original, $storno);

            $original->refresh();
            $storno->refresh();
            $after = [
                $original->payment_status,
                (float) $original->paid_amount,
                $storno->payment_status,
                (float) $storno->paid_amount,
                (int) $original->payments()->count(),
            ];

            $changed = $before !== $after;
            $this->line(($changed ? '  UPD' : '  OK ').
                " original #{$original->id} ({$original->payment_status}) ← storno #{$storno->id} {$storno->number_full} ({$storno->payment_status})");
            $processed++;
        }

        $this->info('Perechi procesate: '.$processed.($skipped ? " · sărite: {$skipped}" : ''));

        return self::SUCCESS;
    }
}
