<?php

namespace App\Console\Commands;

use App\Services\DailyOpsReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendDailyOpsReport extends Command
{
    protected $signature = 'ops:daily-report
        {--date= : Data raportului (Y-m-d), implicit azi Europe/Bucharest}
        {--to= : Destinatari CSV (implicit config dateconta.daily_ops_report_emails)}
        {--force : Retrimite chiar dacă a fost deja marcat ca trimis}';

    protected $description = 'Trimite emailul recapitular zilnic (toată platforma) către ops';

    public function handle(DailyOpsReportService $reports): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'), 'Europe/Bucharest')->startOfDay()
            : Carbon::now('Europe/Bucharest')->startOfDay();

        $to = $this->option('to') ?: null;
        $force = (bool) $this->option('force');
        $summary = $reports->collect($date);
        $dest = $to
            ?: implode(', ', (array) config('dateconta.daily_ops_report_emails', []));

        if (! $force && $reports->wasSent($date)) {
            $this->info(sprintf(
                'Raport ops %s → deja trimis (folosește --force pentru retrimitere) [%s]',
                $date->toDateString(),
                $dest
            ));

            return self::SUCCESS;
        }

        $ok = $reports->send($date, $to, $force);

        $this->info(sprintf(
            'Raport ops %s → %s (doc=%d, eF=%d/%d ok/err, plăți=%d, vizitatori=%d) [%s]',
            $date->toDateString(),
            $ok ? 'trimis' : 'EȘEC',
            $summary['totals']['documents'],
            $summary['efactura']['ok'],
            $summary['efactura']['errors'],
            $summary['payments_count'],
            $summary['visitors']['total'],
            $dest
        ));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
