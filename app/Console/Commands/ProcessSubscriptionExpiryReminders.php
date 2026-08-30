<?php

namespace App\Console\Commands;

use App\Services\SubscriptionExpiryReminderService;
use Illuminate\Console\Command;

class ProcessSubscriptionExpiryReminders extends Command
{
    protected $signature = 'reminders:subscription-expiry';

    protected $description = 'Notifică utilizatorii cu 10 și 5 zile înainte de expirarea abonamentului';

    public function handle(SubscriptionExpiryReminderService $service): int
    {
        $sent = $service->processDue();
        $this->info("Trimise: {$sent}");

        return self::SUCCESS;
    }
}
