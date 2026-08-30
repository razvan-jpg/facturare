<?php

namespace App\Console\Commands;

use App\Services\EfacturaReconcileService;
use Illuminate\Console\Command;

class RefreshEfacturaStatuses extends Command
{
    protected $signature = 'efactura:refresh-statuses';

    protected $description = 'Alias: rulează reconcilierea e-Factura (poll + auto-retry)';

    public function handle(EfacturaReconcileService $reconcile): int
    {
        $stats = $reconcile->run(50);

        $this->info(sprintf(
            'Actualizate/reconciliate: poll=%d retry=%d ok=%d alert=%d',
            $stats['polled'],
            $stats['retried'],
            $stats['ok'],
            $stats['alerted']
        ));

        return self::SUCCESS;
    }
}
