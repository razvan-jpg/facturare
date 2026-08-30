<?php

namespace App\Console\Commands;

use App\Services\RecurringDailyReportService;
use App\Services\RecurringEmailFollowUpService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FinalizeRecurringDay extends Command
{
    protected $signature = 'recurring:finalize-day
        {--date= : Data emiterii (Y-m-d), implicit azi Europe/Bucharest}
        {--skip-email-followup : Sare peste verificare/retry email}
        {--skip-report : Sare peste raportul PDF}';

    protected $description = 'După fereastra de emitere: verifică/reîncearcă emailurile beneficiarilor, apoi trimite raportul zilnic';

    public function handle(
        RecurringEmailFollowUpService $followUp,
        RecurringDailyReportService $reports,
    ): int {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'), 'Europe/Bucharest')->startOfDay()
            : Carbon::now('Europe/Bucharest')->startOfDay();

        $follow = [
            'alerted' => 0,
            'retried' => 0,
            'sent_after_retry' => 0,
            'still_failed' => 0,
            'documents' => collect(),
        ];

        if (! $this->option('skip-email-followup')) {
            $follow = $followUp->run($date);
            $this->info(sprintf(
                'Email follow-up %s: alertă=%d, retry=%d, trimise după retry=%d, încă eșuate=%d',
                $date->toDateString(),
                $follow['alerted'],
                $follow['retried'],
                $follow['sent_after_retry'],
                $follow['still_failed']
            ));
        }

        if ($this->option('skip-report')) {
            return self::SUCCESS;
        }

        $summary = $reports->collect($date);
        $dest = implode(', ', (array) config('dateconta.recurring_daily_report_emails', []));

        if ($summary['grand_total'] <= 0) {
            $this->info(sprintf(
                'Raport %s: 0 documente → sărit (%s)',
                $date->toDateString(),
                $dest
            ));

            return self::SUCCESS;
        }

        $ok = $reports->send($date);

        $this->info(sprintf(
            'Raport %s: %d documente → %s (%s)',
            $date->toDateString(),
            $summary['grand_total'],
            $ok ? 'trimis' : 'EȘEC trimitere',
            $dest
        ));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
