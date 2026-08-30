<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Emitere recurente preferat dimineața (04:00–09:59).
        $schedule->command('recurring:process')
            ->cron('0 4-9 * * *')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(55);
        // Plasă de siguranță: dacă cron-ul de dimineață a ratat ceva, recuperează scadentele.
        $schedule->command('recurring:process')
            ->dailyAt('10:20')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(55);
        $schedule->command('recurring:process')
            ->dailyAt('14:00')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(55);
        // După catch-up 10:20: verificare/retry email beneficiari, apoi raport.
        $schedule->command('recurring:finalize-day')
            ->dailyAt('10:25')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(50);
        // După catch-up 14:00: re-trimite raportul agregat pe zi (dacă s-au emis documente).
        $schedule->command('recurring:finalize-day')
            ->dailyAt('14:15')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(50);
        // Plasă de siguranță seara: acoperă emitere manuală / late catch-up după 14:15.
        $schedule->command('recurring:finalize-day')
            ->dailyAt('20:00')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(50);
        // Recapitulare zilnică platformă (documente, recurente, încasări, vizitatori).
        // Requires cPanel cron every minute: curl …/cron/run?token=… (not */17).
        // 23:51 = minut atins și de cron */17 (fallback dacă cPanel regresează); 23:55 = oră „oficială”.
        // Idempotent via cache (DailyOpsReportService). Catch-up suplimentar în ScheduleRunController.
        $schedule->command('ops:daily-report')
            ->dailyAt('23:51')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(50);
        $schedule->command('ops:daily-report')
            ->dailyAt('23:55')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(50);
        $schedule->command('penalties:accrue')
            ->dailyAt('01:00')
            ->timezone('Europe/Bucharest')
            ->withoutOverlapping(50);
        $schedule->command('reminders:overdue')->dailyAt('09:00')->withoutOverlapping();
        $schedule->command('reminders:subscription-expiry')->dailyAt('09:15')->withoutOverlapping();
        $schedule->command('subscriptions:charge-mollie-recurring')->dailyAt('10:00')->withoutOverlapping();
        $schedule->command('subscriptions:issue-missing-invoices')->dailyAt('10:30')->withoutOverlapping();
        // Coadă delay_N + reconcile (poll până ok, auto-fix/resend la nok/error).
        $schedule->command('efactura:process-scheduled')->everyTenMinutes()->withoutOverlapping(9);
        $schedule->command('efactura:reconcile')->everyFiveMinutes()->withoutOverlapping(4);
        $schedule->command('documents:expire-number-reservations')->everyFiveMinutes()->withoutOverlapping(4);
        // Consumă joburile din coadă (email etc.) fără a bloca request-urile web.
        $schedule->command('queue:work database --stop-when-empty --max-time=45 --tries=3 --sleep=1')
            ->everyMinute()
            ->withoutOverlapping(2);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
