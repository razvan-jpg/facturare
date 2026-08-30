<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class EfacturaStuckAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(public Collection $documents)
    {
        $this->locale('ro');
    }

    public function envelope(): Envelope
    {
        $n = $this->documents->count();
        $brand = config('dateconta.brand_name', 'DateConta Facturare');

        return new Envelope(
            subject: "e-Factura: {$n} documente blocate (neacceptate ANAF) — {$brand}",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.efactura-stuck-alert',
            with: [
                'documents' => $this->documents,
            ],
        );
    }
}
