<?php

namespace App\Console\Commands;

use App\Services\SubscriptionInvoiceService;
use Illuminate\Console\Command;

class IssueMissingSubscriptionInvoices extends Command
{
    protected $signature = 'subscriptions:issue-missing-invoices {--limit=100 : Max orders to process}';

    protected $description = 'Emite facturi fiscale FLY DAVID pentru abonamente plătite fără factură';

    public function handle(SubscriptionInvoiceService $invoices): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $result = $invoices->issueMissing($limit);

        $this->info('Emise: '.$result['issued'].' · sărite: '.$result['skipped']);
        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
