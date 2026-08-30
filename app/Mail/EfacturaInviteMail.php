<?php

namespace App\Mail;

use App\Models\EfacturaInvite;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class EfacturaInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EfacturaInvite $invite)
    {
        $this->invite->loadMissing('company.owner');
        $this->locale(MailLocale::forUser($this->invite->company?->owner));
    }

    public function envelope(): Envelope
    {
        MailLocale::apply((string) $this->locale);
        $brand = config('dateconta.brand_name', 'DateConta Facturare');
        $companyName = $this->invite->company?->name ?: __('societate');
        $reply = (string) config('dateconta.contact_email', 'contact.facturare@dateconta.ro');

        return new Envelope(
            subject: __(':brand: invitație autorizare SPV pentru :company', [
                'brand' => $brand,
                'company' => $companyName,
            ]),
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name', $brand)
            ),
            replyTo: [new Address($reply, $brand)],
        );
    }

    public function content(): Content
    {
        $url = URL::route('anaf.invite', ['token' => $this->invite->token]);

        return new Content(
            html: 'emails.efactura-invite',
            text: 'emails.efactura-invite-text',
            with: [
                'invite' => $this->invite,
                'company' => $this->invite->company,
                'url' => $url,
            ],
        );
    }
}
