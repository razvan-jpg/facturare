<?php

namespace App\Console\Commands;

use App\Services\ClientPenaltyService;
use Illuminate\Console\Command;

class AccrueClientPenalties extends Command
{
    protected $signature = 'penalties:accrue
                            {--date= : Data de referință Y-m-d (implicit azi, Europe/Bucharest)}';

    protected $description = 'Actualizează penalitățile calculate (accrued) pentru toți clienții cu procent setat';

    public function handle(ClientPenaltyService $penalties): int
    {
        $date = $this->option('date')
            ? \Illuminate\Support\Carbon::parse($this->option('date'), 'Europe/Bucharest')->startOfDay()
            : null;

        $n = $penalties->accrueAllEligible($date);
        $this->info("penalties:accrue → {$n} clienți");

        return self::SUCCESS;
    }
}
