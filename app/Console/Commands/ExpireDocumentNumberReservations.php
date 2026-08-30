<?php

namespace App\Console\Commands;

use App\Services\DocumentService;
use Illuminate\Console\Command;

class ExpireDocumentNumberReservations extends Command
{
    protected $signature = 'documents:expire-number-reservations';

    protected $description = 'Eliberează rezervările de număr expirate (ciorne fără heartbeat)';

    public function handle(DocumentService $documents): int
    {
        $count = $documents->expireStaleReservations();
        $this->info("Rezervări eliberate: {$count}");

        return self::SUCCESS;
    }
}
