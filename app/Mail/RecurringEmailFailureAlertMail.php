<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecurringEmailFailureAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, array<string, mixed>>  $failures
     */
    public function __construct(
        public Carbon $date,
        public Collection $failures,
    ) {
        $this->locale('ro');
    }

    public function envelope(): Envelope
    {
        $date = $this->date->format('d.m.Y');
        $brand = config('dateconta.brand_name', 'DateConta Facturare');

        return new Envelope(
            subject: "Alertă email recurente netrimise {$date} — {$brand}",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.recurring-email-failure-alert',
            with: [
                'date' => $this->date,
                'failures' => $this->failures,
            ],
        );
    }
}
