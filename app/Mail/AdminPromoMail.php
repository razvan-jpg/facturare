<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPromoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?User $sender = null,
        public ?User $recipientUser = null,
    ) {
        $this->locale(MailLocale::forUser($this->recipientUser ?? $this->sender));
    }

    public function envelope(): Envelope
    {
        MailLocale::apply((string) $this->locale);
        $fromName = (string) config('dateconta.admin_promo.from_name', 'Razvan Ivan — FLY DAVID SRL');
        $fromEmail = (string) config('mail.from.address');
        $reply = (string) config('dateconta.admin_promo.reply_to', config('dateconta.contact_email'));

        return new Envelope(
            subject: __('Îți recomand DateConta Facturare — facturare simplă pentru firme din România'),
            from: new Address($fromEmail, $fromName),
            replyTo: filled($reply) ? [new Address($reply, $fromName)] : [],
        );
    }

    public function content(): Content
    {
        MailLocale::apply((string) $this->locale);
        $appUrl = rtrim((string) config('app.url'), '/');

        return new Content(
            html: 'emails.admin-promo',
            text: 'emails.admin-promo-text',
            with: [
                'senderName' => (string) config('dateconta.admin_promo.sender_name', 'Razvan Ivan'),
                'companyName' => (string) config('dateconta.platform_operator.name', 'FLY DAVID SRL'),
                'registerUrl' => $appUrl.'/register',
                'promoFreeUntil' => config('dateconta.promo_free_until', '2027-03-31'),
            ],
        );
    }
}
