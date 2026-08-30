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

class SubuserInvitedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{company: string, cui: ?string, rights: list<string>}>  $accessSummary
     */
    public function __construct(
        public User $recipient,
        public User $inviter,
        public string $inviterCompanyName,
        public array $accessSummary,
    ) {
        $this->locale(MailLocale::forUser($this->inviter));
    }

    public function envelope(): Envelope
    {
        MailLocale::apply((string) $this->locale);
        $brand = config('dateconta.brand_name', 'DateConta Facturare');

        return new Envelope(
            subject: __(':brand: invitație pe societățile administrate de :name', [
                'brand' => $brand,
                'name' => $this->inviter->name,
            ]),
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name', $brand)
            ),
            replyTo: filled($this->inviter->email)
                ? [new Address((string) $this->inviter->email, (string) $this->inviter->name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.subuser-invited',
            text: 'emails.subuser-invited-text',
            with: [
                'loginUrl' => rtrim((string) config('app.url'), '/'),
                'showPromise' => false,
            ],
        );
    }
}
