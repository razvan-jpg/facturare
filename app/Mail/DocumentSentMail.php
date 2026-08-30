<?php

namespace App\Mail;

use App\Models\Document;
use App\Services\DocumentCardPaymentService;
use App\Services\DocumentEmailTemplate;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class DocumentSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public string $pdfBinary,
    ) {
        $this->locale(MailLocale::forDocument($document));
    }

    public function envelope(): Envelope
    {
        MailLocale::apply((string) $this->locale);
        $subject = app(DocumentEmailTemplate::class)->subjectFor($this->document);

        return new Envelope(
            subject: $subject,
            replyTo: filled($this->document->company->email)
                ? [$this->document->company->email]
                : [config('dateconta.contact_email', 'contact.facturare@dateconta.ro')],
        );
    }

    public function content(): Content
    {
        MailLocale::apply((string) $this->locale);
        $tpl = app(DocumentEmailTemplate::class);
        $bodyText = $tpl->bodyFor($this->document);
        $documentLink = URL::temporarySignedRoute(
            'documents.pdf.signed',
            now()->addDays(30),
            ['document' => $this->document->id]
        );

        $cards = app(DocumentCardPaymentService::class);

        return new Content(
            html: 'emails.document-sent',
            text: 'emails.document-sent-text',
            with: [
                'document' => $this->document,
                'bodyText' => $bodyText,
                'documentLink' => $documentLink,
                'paymentLinks' => $cards->paymentLinks($this->document),
                'paymentHubUrl' => $cards->hubUrl($this->document),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->document->pdfFileName())
                ->withMime('application/pdf'),
        ];
    }
}
