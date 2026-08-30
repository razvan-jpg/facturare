<?php

namespace App\Services;

use App\Mail\RecurringEmailFailureAlertMail;
use App\Models\Document;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecurringEmailFollowUpService
{
    public const MAX_RETRIES = 3;

    public function __construct(
        private DocumentService $documents,
        private ReliableMail $mail,
    ) {}

    /**
     * Verifică emailurile documentelor recurente din ziua dată, alertează, reîncearcă.
     *
     * @return array{
     *     date: Carbon,
     *     documents: Collection<int, Document>,
     *     alerted: int,
     *     retried: int,
     *     sent_after_retry: int,
     *     still_failed: int
     * }
     */
    public function run(?Carbon $date = null): array
    {
        $date = ($date ?? now('Europe/Bucharest'))->copy()->startOfDay();
        $docs = $this->documentsForDay($date);

        // Marchează ca skipped cele fără intenție de email.
        foreach ($docs as $doc) {
            if (! $doc->wantsClientEmail() && ($doc->client_email_status ?: 'none') === 'none') {
                $doc->forceFill(['client_email_status' => 'skipped'])->save();
            }
        }

        $docs = $this->documentsForDay($date);
        $needing = $docs->filter(fn (Document $d) => $d->wantsClientEmail() && ($d->client_email_status ?: '') !== 'sent');

        $failuresForAlert = $needing->map(fn (Document $d) => [
            'number' => $d->number_full ?: ($d->series.'-'.$d->number),
            'client' => $d->client_name ?: ($d->client?->name ?: '—'),
            'company' => $d->company?->name ?: '—',
            'cause' => $this->diagnose($d),
        ])->values();

        $alerted = 0;
        if ($failuresForAlert->isNotEmpty()) {
            $alerted = $this->sendFailureAlert($date, $failuresForAlert) ? $failuresForAlert->count() : 0;
        }

        $retried = 0;
        $sentAfter = 0;
        foreach ($needing as $doc) {
            $doc->refresh();
            if (($doc->client_email_status ?: '') === 'sent') {
                continue;
            }

            for ($i = 1; $i <= self::MAX_RETRIES; $i++) {
                $retried++;
                $ok = $this->documents->retryClientEmail($doc);
                $doc->refresh();
                if ($ok || ($doc->client_email_status ?: '') === 'sent') {
                    $sentAfter++;
                    break;
                }
                usleep(400000);
            }
        }

        $docs = $this->documentsForDay($date)->load(['company', 'client']);
        $stillFailed = $docs->filter(
            fn (Document $d) => $d->wantsClientEmail() && ($d->client_email_status ?: '') !== 'sent'
        )->count();

        Log::info('Recurring email follow-up finished', [
            'date' => $date->toDateString(),
            'alerted' => $alerted,
            'retried' => $retried,
            'sent_after_retry' => $sentAfter,
            'still_failed' => $stillFailed,
        ]);

        return [
            'date' => $date,
            'documents' => $docs,
            'alerted' => $alerted,
            'retried' => $retried,
            'sent_after_retry' => $sentAfter,
            'still_failed' => $stillFailed,
        ];
    }

    /**
     * @return Collection<int, Document>
     */
    public function documentsForDay(Carbon $date): Collection
    {
        return Document::query()
            ->with(['company', 'client'])
            ->whereNotNull('recurring_invoice_id')
            ->whereIn('type', ['invoice', 'proforma'])
            ->whereIn('status', ['issued', 'storno'])
            ->whereDate('issue_date', $date->toDateString())
            ->orderBy('company_id')
            ->orderBy('type')
            ->orderBy('number')
            ->get();
    }

    public function diagnose(Document $document): string
    {
        if (filled($document->client_email_error)) {
            return (string) $document->client_email_error;
        }

        if (! $document->wantsClientEmail()) {
            return 'Email automat neconfigurat pe recurentă.';
        }

        $to = [];
        if ($document->auto_email_client) {
            $to = dc_parse_emails($document->client_email ?: $document->client?->email);
        }
        if ($document->auto_email_cc && filled($document->auto_email_cc_address)) {
            $to = array_merge($to, dc_parse_emails($document->auto_email_cc_address));
        }
        if ($to === []) {
            return 'Lipsește adresa de email a clientului (sau CC document).';
        }

        if (($document->client_email_status ?: 'none') === 'none') {
            return 'Emailul nu a fost încercat la emitere (sau status necunoscut).';
        }

        if (($document->client_email_status ?: '') === 'pending') {
            return 'Trimitere întreruptă (status pending).';
        }

        return 'Email netrimis (cauză necunoscută).';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $failures
     */
    private function sendFailureAlert(Carbon $date, Collection $failures): bool
    {
        $to = trim((string) config('dateconta.recurring_email_alert_to', 'razvan@dateconta.ro'));
        if ($to === '') {
            return false;
        }

        try {
            $this->mail->send(
                new RecurringEmailFailureAlertMail($date, $failures),
                $to
            );

            return true;
        } catch (Throwable $e) {
            Log::error('Recurring email failure alert failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
