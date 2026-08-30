<?php

namespace App\Console\Commands;

use App\Services\OverdueReminderService;
use Illuminate\Console\Command;

class ProcessOverdueReminders extends Command
{
    protected $signature = 'reminders:overdue';

    protected $description = 'Trimite notificările de restanțe către clienți, conform setărilor societăților';

    public function handle(OverdueReminderService $service): int
    {
        $sent = $service->processDue();
        $this->info("Trimise: {$sent}");

        return self::SUCCESS;
    }
}
