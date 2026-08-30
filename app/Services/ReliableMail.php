<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class ReliableMail
{
    /**
     * Trimite un email: SMTP-ul societății (dacă e configurat) sau failover-ul platformei.
     *
     * @param  string|array<int, string>  $to
     * @param  string|array<int, string>|null  $cc
     */
    public function send(Mailable $mailable, string|array $to, ?Company $company = null, string|array|null $cc = null): void
    {
        $recipients = array_values(array_filter(array_map('trim', (array) $to)));
        if ($recipients === []) {
            throw new RuntimeException('Nu există destinatari pentru email.');
        }

        $ccRecipients = array_values(array_unique(array_filter(array_map(
            'trim',
            (array) ($cc ?? [])
        ))));
        // Nu duplica în Cc adrese deja în To.
        $ccRecipients = array_values(array_diff($ccRecipients, $recipients));

        if ($company?->hasCustomSmtp()) {
            $this->sendViaCompanySmtp($mailable, $recipients, $company, $ccRecipients);

            return;
        }

        $mailers = config('mail.delivery_mailers', ['smtp', 'smtp_tls', 'sendmail']);
        $errors = [];

        foreach ($mailers as $mailer) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $pending = Mail::mailer($mailer)->to($recipients);
                    if ($ccRecipients !== []) {
                        $pending->cc($ccRecipients);
                    }
                    $pending->send($mailable);

                    return;
                } catch (Throwable $e) {
                    $errors[] = "{$mailer}#{$attempt}: ".$e->getMessage();
                    Log::warning('Outbound mail attempt failed', [
                        'mailer' => $mailer,
                        'attempt' => $attempt,
                        'to' => $recipients,
                        'cc' => $ccRecipients,
                        'mailable' => $mailable::class,
                        'error' => $e->getMessage(),
                    ]);
                    usleep(250000);
                }
            }
        }

        throw new RuntimeException(
            'Trimiterea emailului a eșuat pe toate canalele SMTP. '.implode(' | ', $errors)
        );
    }

    /**
     * @param  array<int, string>  $recipients
     * @param  array<int, string>  $ccRecipients
     */
    private function sendViaCompanySmtp(
        Mailable $mailable,
        array $recipients,
        Company $company,
        array $ccRecipients = [],
    ): void {
        $port = (int) $company->mail_smtp_port;
        $encryption = $port === 465
            ? 'ssl'
            : ($company->mail_smtp_tls ? 'tls' : null);

        Config::set('mail.mailers.company_smtp', [
            'transport' => 'smtp',
            'host' => $company->mail_smtp_host,
            'port' => $port,
            'encryption' => $encryption,
            'username' => $company->mail_smtp_username,
            'password' => $company->mail_smtp_password,
            'timeout' => (int) config('mail.mailers.smtp.timeout', 60),
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
        ]);

        try {
            app('mail.manager')->purge('company_smtp');
        } catch (Throwable) {
            // Mail manager may not expose purge in all versions — Config::set is enough for fresh resolve.
        }

        $fromAddress = filled($company->mail_smtp_username)
            ? $company->mail_smtp_username
            : (filled($company->email) ? $company->email : config('mail.from.address'));

        $mailable->from($fromAddress, $company->name ?: config('mail.from.name'));

        try {
            $pending = Mail::mailer('company_smtp')->to($recipients);
            if ($ccRecipients !== []) {
                $pending->cc($ccRecipients);
            }
            $pending->send($mailable);
        } catch (Throwable $e) {
            Log::warning('Company SMTP send failed', [
                'company_id' => $company->id,
                'host' => $company->mail_smtp_host,
                'port' => $port,
                'to' => $recipients,
                'cc' => $ccRecipients,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Trimiterea prin serverul tău de email a eșuat: '.$e->getMessage()
            );
        }
    }
}
