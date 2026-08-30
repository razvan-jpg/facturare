<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\Company;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class OverdueReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public Client $client,
        public Collection $overdueInvoices,
        public float $balance,
        public string $scope,
        public bool $includeStatement,
        public ?string $statementPdf = null,
    ) {
        $this->company->loadMissing('owner');
        $this->locale(MailLocale::forUser($this->company->owner));
    }

    public function envelope(): Envelope
    {
        MailLocale::apply((string) $this->locale);
        $brand = config('dateconta.brand_name', 'DateConta Facturare');

        $envelope = [
            'subject' => __('Notificare restanțe — :company · :brand', [
                'company' => $this->company->name,
                'brand' => $brand,
            ]),
        ];

        return new Envelope(
            subject: $envelope['subject'],
            replyTo: filled($this->company->email) ? [$this->company->email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.overdue-reminder',
            with: [
                'company' => $this->company,
                'client' => $this->client,
                'overdueInvoices' => $this->overdueInvoices,
                'balance' => $this->balance,
                'scope' => $this->scope,
                'includeStatement' => $this->includeStatement,
                'logo' => config('dateconta.logo_url'),
                'brand' => config('dateconta.brand_name', 'DateConta Facturare'),
            ],
        );
    }

    public function attachments(): array
    {
        if (! $this->includeStatement || ! $this->statementPdf) {
            return [];
        }

        $name = 'fisa-client-'.preg_replace('/[^a-zA-Z0-9_-]+/', '-', $this->client->name).'.pdf';

        return [
            Attachment::fromData(fn () => $this->statementPdf, $name)
                ->withMime('application/pdf'),
        ];
    }
}
