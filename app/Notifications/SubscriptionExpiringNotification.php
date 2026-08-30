<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Carbon $accessUntil,
        public int $daysBefore,
        public ?string $orderUrl = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $when = $this->daysBefore === 1
            ? 'mâine'
            : ('în '.$this->daysBefore.' zile');

        return [
            'kind' => 'subscription_expiry',
            'days_before' => $this->daysBefore,
            'access_until' => $this->accessUntil->toDateString(),
            'title' => 'Abonamentul expiră '.$when,
            'body' => 'Accesul DateConta Facturare expiră pe '.dc_date($this->accessUntil).'. Comandă un abonament pentru a continua fără întrerupere.',
            'order_url' => $this->orderUrl,
        ];
    }
}
