<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class RecurringDailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{date: Carbon, rows: mixed, totals: array<string, int>, grand_total: int}  $report
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
        $brand = config('dateconta.brand_name', 'DateConta Facturare');

        return new Envelope(
            subject: "Raport emitere recurente {$date} — {$brand}",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.recurring-daily-report',
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
