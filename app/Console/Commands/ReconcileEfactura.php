<?php

namespace App\Console\Commands;

use App\Services\EfacturaReconcileService;
use Illuminate\Console\Command;

class ReconcileEfactura extends Command
{
    protected $signature = 'efactura:reconcile {--limit=40 : Maxim documente pe pas}';

    protected $description = 'Poll e-Factura până Acceptată ANAF; la respingere/eroare corectează (unde e posibil) și retrimite';

    public function handle(EfacturaReconcileService $reconcile): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));
        $stats = $reconcile->run($limit);

        $this->info(sprintf(
            'e-Factura reconcile: poll=%d, catch_up=%d, retry=%d, ok=%d, alert=%d',
            $stats['polled'],
            $stats['catch_up'] ?? 0,
            $stats['retried'],
            $stats['ok'],
            $stats['alerted']
        ));

        return self::SUCCESS;
    }
}
