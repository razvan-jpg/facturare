<?php

namespace App\Console\Commands;

use App\Services\EfacturaService;
use Illuminate\Console\Command;

class ProcessScheduledEfactura extends Command
{
    protected $signature = 'efactura:process-scheduled';

    protected $description = 'Trimite în e-Factura facturile programate (1/2/3 zile)';

    public function handle(EfacturaService $efactura): int
    {
        $sent = $efactura->processDueScheduled();
        $this->info("Trimise: {$sent}");

        return self::SUCCESS;
    }
}
