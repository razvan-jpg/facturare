<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LaunchPromoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'DateConta Facturare — lansare · gratuit până la 31.03.2027',
            replyTo: [config('dateconta.contact_email', 'contact.facturare@dateconta.ro')],
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.launch',
        );
    }
}
