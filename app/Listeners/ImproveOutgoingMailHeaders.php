<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class ImproveOutgoingMailHeaders
{
    public function handle(MessageSending $event): void
    {
        $message = $event->message;
        $headers = $message->getHeaders();
        $fromDomain = 'dateconta.ro';
        $contact = config('dateconta.contact_email', 'contact.facturare@dateconta.ro');

        if (! $headers->has('X-Mailer')) {
            $headers->addTextHeader('X-Mailer', 'DateConta Facturare');
        }

        if (! $headers->has('X-Auto-Response-Suppress')) {
            $headers->addTextHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');
        }

        if (! $headers->has('List-Unsubscribe')) {
            $headers->addTextHeader('List-Unsubscribe', '<mailto:'.$contact.'?subject=Dezabonare DateConta>');
        }

        // Align Message-ID with sending domain (helps spam filters).
        if (! $headers->has('Message-ID')) {
            $headers->addIdHeader('Message-ID', bin2hex(random_bytes(12)).'@'.$fromDomain);
        }
    }
}
