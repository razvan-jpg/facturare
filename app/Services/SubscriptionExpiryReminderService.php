<?php

namespace App\Services;

use App\Mail\SubscriptionExpiryReminderMail;
use App\Models\SubscriptionExpiryReminderLog;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Support\Facades\Log;

class SubscriptionExpiryReminderService
{
    public function __construct(
        private readonly AccessGate $accessGate,
        private readonly ReliableMail $mail,
    ) {}

    /** @return list<int> */
    public function daysBefore(): array
    {
        $days = config('dateconta.subscription_reminders.days_before', [10, 5]);

        return array_values(array_unique(array_map('intval', (array) $days)));
    }

    public function processDue(int $limit = 200): int
    {
        $targets = $this->daysBefore();
        if ($targets === []) {
            return 0;
        }

        $sent = 0;

        User::query()
            ->where('is_admin', false)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($targets, &$sent, $limit) {
                foreach ($users as $user) {
                    if ($sent >= $limit) {
                        return false;
                    }

                    try {
                        if ($this->maybeRemind($user, $targets)) {
                            $sent++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Subscription expiry reminder failed', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                return $sent < $limit;
            });

        return $sent;
    }

    /**
     * @param  list<int>  $targets
     */
    public function maybeRemind(User $user, array $targets): bool
    {
        if ($user->is_admin) {
            return false;
        }

        // Paid fără plafon = nelimitat — fără reminder.
        if ($user->plan === 'paid' && ! $user->access_until) {
            return false;
        }

        $until = $this->accessGate->effectiveAccessUntil($user);
        if (! $until) {
            return false;
        }

        $daysLeft = (int) now()->startOfDay()->diffInDays($until->copy()->startOfDay(), false);
        if (! in_array($daysLeft, $targets, true)) {
            return false;
        }

        $untilDate = $until->toDateString();
        $already = SubscriptionExpiryReminderLog::query()
            ->where('user_id', $user->id)
            ->where('days_before', $daysLeft)
            ->whereDate('access_until_date', $untilDate)
            ->exists();

        if ($already) {
            return false;
        }

        $orderUrl = $this->orderUrlFor($user);

        // Notificare in-app (fereastră / banner).
        $user->notify(new SubscriptionExpiringNotification($until->copy(), $daysLeft, $orderUrl));

        // Email (dacă există adresă).
        $emailSentTo = null;
        if (filled($user->email)) {
            try {
                $this->mail->send(
                    new SubscriptionExpiryReminderMail($user, $until->copy(), $daysLeft),
                    $user->email
                );
                $emailSentTo = $user->email;
            } catch (\Throwable $e) {
                Log::warning('Subscription expiry email failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        SubscriptionExpiryReminderLog::create([
            'user_id' => $user->id,
            'days_before' => $daysLeft,
            'access_until_date' => $untilDate,
            'email' => $emailSentTo ?: (string) ($user->email ?: ''),
            'sent_at' => now(),
        ]);

        return true;
    }

    private function orderUrlFor(User $user): ?string
    {
        $company = null;
        if ($user->current_company_id) {
            $company = $user->companies()
                ->where('companies.id', $user->current_company_id)
                ->first();
        }
        $company ??= $user->companies()->orderBy('companies.name')->first();

        return $company ? route('billing.order', $company) : route('companies.index');
    }
}
