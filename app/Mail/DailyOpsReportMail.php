<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DailyOpsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{date: Carbon}  $report
     */
    public function __construct(
        public array $report,
        public string $pdfBinary,
        public string $pdfFileName,
    ) {
        $this->locale('ro');
    }

    public function envelope(): Envelope
    {
        $date = $this->report['date']->format('d.m.Y');

        return new Envelope(
            subject: "Raport DateConta Facturare pentru ziua {$date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.daily-ops-report',
            with: [
                'report' => $this->report,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->pdfFileName)
                ->withMime('application/pdf'),
        ];
    }
}
