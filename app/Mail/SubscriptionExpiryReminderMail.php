<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MailLocale;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Carbon $accessUntil,
        public int $daysBefore,
    ) {
        $this->locale(MailLocale::forUser($this->user));
    }

    public function envelope(): Envelope
    {
        MailLocale::apply((string) $this->locale);
        $brand = config('dateconta.brand_name', 'DateConta Facturare');
        $when = $this->daysBefore === 1
            ? __('mâine')
            : __('în :days zile', ['days' => $this->daysBefore]);

        return new Envelope(
            subject: __('Abonamentul expiră :when — :brand', [
                'when' => $when,
                'brand' => $brand,
            ]),
        );
    }

    public function content(): Content
    {
        $company = null;
        if ($this->user->current_company_id) {
            $company = $this->user->companies()
                ->where('companies.id', $this->user->current_company_id)
                ->first();
        }
        $company ??= $this->user->companies()->orderBy('companies.name')->first();

        $orderUrl = $company
            ? route('billing.order', $company)
            : url('/companies');

        return new Content(
            html: 'emails.subscription-expiry',
            with: [
                'user' => $this->user,
                'accessUntil' => $this->accessUntil,
                'daysBefore' => $this->daysBefore,
                'orderUrl' => $orderUrl,
                'hasCompany' => (bool) $company,
                'contact' => config('dateconta.contact_email'),
                'logo' => config('dateconta.logo_url'),
                'brand' => config('dateconta.brand_name', 'DateConta Facturare'),
            ],
        );
    }
}
