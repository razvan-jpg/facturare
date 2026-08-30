<?php

namespace App\Console\Commands;

use App\Services\RecurringDailyReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendRecurringDailyReport extends Command
{
    protected $signature = 'recurring:daily-report
        {--date= : Data emiterii (Y-m-d), implicit azi Europe/Bucharest}
        {--to= : Destinatari (virgule); implicit razvan@fly-david.ro,razvan@dateconta.ro}';

    protected $description = 'Trimite pe email PDF-ul cu raportul zilnic al emiterilor din recurente (toate firmele)';

    public function handle(RecurringDailyReportService $reports): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'), 'Europe/Bucharest')->startOfDay()
            : Carbon::now('Europe/Bucharest')->startOfDay();

        $to = $this->option('to') ?: null;
        $summary = $reports->collect($date);
        $dest = $to
            ?: implode(', ', (array) config('dateconta.recurring_daily_report_emails', []));

        if ($summary['grand_total'] <= 0) {
            $this->info(sprintf(
                'Raport %s: 0 documente → sărit (%s)',
                $date->toDateString(),
                $dest
            ));

            return self::SUCCESS;
        }

        $ok = $reports->send($date, $to);

        $this->info(sprintf(
            'Raport %s: %d documente (facturi %d, proforme %d) → %s (%s)',
            $date->toDateString(),
            $summary['grand_total'],
            $summary['totals']['invoice'] ?? 0,
            $summary['totals']['proforma'] ?? 0,
            $ok ? 'trimis' : 'EȘEC trimitere',
            $dest
        ));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
