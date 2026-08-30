<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoice;
use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessRecurringInvoices extends Command
{
    protected $signature = 'recurring:process {--force : Ignoră intervalul orar 04:00–10:00}';

    protected $description = 'Generează facturile/proformele recurente scadente (preferat 04:00–10:00 Europe/Bucharest)';

    public function handle(RecurringInvoiceService $service): int
    {
        $now = Carbon::now('Europe/Bucharest');
        $inWindow = $now->hour >= 4 && $now->hour < 10;

        if (! $this->option('force') && ! $inWindow) {
            $stillDue = RecurringInvoice::query()
                ->where('active', true)
                ->whereDate('next_run_date', '<=', $now->toDateString())
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhereColumn('next_run_date', '<=', 'end_date');
                })
                ->exists();

            if (! $stillDue) {
                $this->warn('În afara intervalului 04:00–10:00 (Europe/Bucharest) și nu există scadente de recuperat.');

                return self::SUCCESS;
            }

            $this->info('Catch-up: există recurente scadente neemise — procesez în afara intervalului preferat.');
        }

        $count = $service->processDue();
        $this->info("Generate: {$count}");

        return self::SUCCESS;
    }
}
