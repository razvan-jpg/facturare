<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralRecommendMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public User $sender,
        public ?User $recipientUser = null,
    ) {
        // Limba de lucru a destinatarului (dacă e utilizator), altfel a expeditorului.
        $this->locale(MailLocale::forUser($this->recipientUser ?? $this->sender));
    }

    public function envelope(): Envelope
    {
        MailLocale::apply((string) $this->locale);
        $brand = config('dateconta.brand_name', 'DateConta Facturare');
        $companyName = $this->company->name ?: __('o firmă');
        $replyEmail = filled($this->sender->email)
            ? (string) $this->sender->email
            : (string) config('dateconta.contact_email', 'contact.facturare@dateconta.ro');
        $replyName = trim($this->sender->name) !== ''
            ? $this->sender->name
            : $companyName;

        return new Envelope(
            subject: __(':company îți recomandă DateConta Facturare — cod :code', [
                'company' => $companyName,
                'code' => $this->company->promo_code,
            ]),
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name', $brand)
            ),
            replyTo: [new Address($replyEmail, $replyName)],
        );
    }

    public function content(): Content
    {
        MailLocale::apply((string) $this->locale);
        $appUrl = rtrim((string) config('app.url'), '/');

        return new Content(
            html: 'emails.referral-recommend',
            text: 'emails.referral-recommend-text',
            with: [
                'company' => $this->company,
                'sender' => $this->sender,
                'promoCode' => (string) $this->company->promo_code,
                'registerUrl' => $appUrl.'/register',
                'inviteeBonusDays' => (int) config('dateconta.referral.invitee_bonus_days', 14),
                'referrerEvery' => (int) config('dateconta.referral.referrer_every', 2),
                'referrerBonusMonths' => (int) config('dateconta.referral.referrer_bonus_months', 1),
                'promoFreeUntil' => config('dateconta.promo_free_until', '2027-03-31'),
                'showPromise' => false,
            ],
        );
    }
}
