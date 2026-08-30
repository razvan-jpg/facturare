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

class SubuserCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{company: string, cui: ?string, rights: list<string>}>  $accessSummary
     */
    public function __construct(
        public User $recipient,
        public User $creator,
        public string $creatorCompanyName,
        public string $plainPassword,
        public array $accessSummary,
    ) {
        $this->locale(MailLocale::forUser($this->creator));
    }

    public function envelope(): Envelope
    {
        MailLocale::apply((string) $this->locale);
        $brand = config('dateconta.brand_name', 'DateConta Facturare');

        return new Envelope(
            subject: __(':brand: cont creat de :name', [
                'brand' => $brand,
                'name' => $this->creator->name,
            ]),
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name', $brand)
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.subuser-created',
            text: 'emails.subuser-created-text',
            with: [
                'loginUrl' => rtrim((string) config('app.url'), '/'),
                'showPromise' => false,
            ],
        );
    }
}
