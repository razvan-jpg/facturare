<?php

namespace App\Console\Commands;

use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Services\MolliePaymentService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Reînnoiește abonamentele Mollie cu flag recurring, cu 3 zile înainte de expirare.
 */
class ChargeMollieRecurringSubscriptions extends Command
{
    protected $signature = 'subscriptions:charge-mollie-recurring {--days=3 : Zile înainte de expirare}';

    protected $description = 'Debitează plățile recurente Mollie înainte de expirarea accesului';

    public function handle(MolliePaymentService $mollie): int
    {
        if (! $mollie->isConfigured()) {
            $this->warn('Mollie neconfigurat — skip.');

            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $charged = 0;
        $failed = 0;

        $users = User::query()
            ->whereNotNull('mollie_customer_id')
            ->whereNotNull('access_until')
            ->where('access_until', '>', now())
            ->whereDate('access_until', '<=', now()->addDays($days)->toDateString())
            ->get();

        foreach ($users as $user) {
            $template = SubscriptionOrder::query()
                ->where('user_id', $user->id)
                ->where('status', 'paid')
                ->where('payment_processor', 'mollie')
                ->where('recurring', true)
                ->latest('paid_at')
                ->first();

            if (! $template) {
                continue;
            }

            // Evită dubluri: o comandă pending/paid recurentă creată recent pentru același user.
            $recent = SubscriptionOrder::query()
                ->where('user_id', $user->id)
                ->where('payment_processor', 'mollie')
                ->where('recurring', true)
                ->where('created_at', '>=', now()->subDays($days + 1))
                ->whereIn('status', ['pending', 'paid'])
                ->where('id', '!=', $template->id)
                ->exists();

            if ($recent) {
                continue;
            }

            try {
                $order = $mollie->chargeRecurring($template);
                $this->line("OK user #{$user->id} order {$order->number} status={$order->status}");
                $charged++;
            } catch (Throwable $e) {
                $this->error("FAIL user #{$user->id}: ".$e->getMessage());
                report($e);
                $failed++;
            }
        }

        $this->info("Charged={$charged} failed={$failed} (window {$days}d)");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
