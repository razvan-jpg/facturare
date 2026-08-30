<?php

namespace App\Services;

use App\Mail\EfacturaStuckAlertMail;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class EfacturaReconcileService
{
    public const MAX_ATTEMPTS_PER_DAY = 5;

    public function __construct(
        private EfacturaService $efactura,
        private EfacturaAutoRepairService $repair,
        private ReliableMail $mail,
    ) {}

    /**
     * @return array{polled: int, catch_up: int, retried: int, ok: int, alerted: int}
     */
    public function run(int $limit = 40): array
    {
        $stats = ['polled' => 0, 'catch_up' => 0, 'retried' => 0, 'ok' => 0, 'alerted' => 0];

        // 1) Poll uploaded/processing → ok / nok
        $stats['polled'] = $this->pollPending($limit);
        // 2) Storno / NC / facturi rămase pe none (automate ratate) — programează / trimite
        $stats['catch_up'] = $this->catchUpMissedAutoSends($limit);
        // 3) nok/error (+ none cu eroare): repair + retrimitere până ok
        [$retried, $becameOk] = $this->retryRejected($limit);
        $stats['retried'] = $retried;
        $stats['ok'] = $becameOk;
        $stats['alerted'] = $this->alertStuck();

        return $stats;
    }

    /** Facturi emise, storno și note de creditare eligibile e-Factura. */
    private function eligibleQuery(): Builder
    {
        return Document::query()
            ->whereIn('type', ['invoice', 'credit_note'])
            ->whereIn('status', ['issued', 'storno']);
    }

    private function pollPending(int $limit): int
    {
        $documents = $this->eligibleQuery()
            ->whereIn('efactura_status', ['uploaded', 'processing'])
            ->whereNotNull('efactura_upload_id')
            ->with('company')
            ->orderBy('efactura_checked_at')
            ->limit($limit)
            ->get();

        $n = 0;
        foreach ($documents as $document) {
            if (! $document->company?->isAnafAuthorized()) {
                continue;
            }
            try {
                $before = $document->efactura_status;
                $doc = $this->efactura->refreshStatus($document);
                $n++;
                if ($doc->efactura_status === 'ok') {
                    $this->clearAutoState($doc);
                } elseif (in_array($doc->efactura_status, ['nok', 'error'], true)
                    && $before !== $doc->efactura_status) {
                    $doc->forceFill([
                        'efactura_auto_next_at' => now(),
                    ])->save();
                }
            } catch (Throwable $e) {
                Log::warning('e-Factura reconcile poll failed', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $n;
    }

    /**
     * Documente automate (on_save / delay) rămase pe none — inclusiv storno & NC.
     */
    private function catchUpMissedAutoSends(int $limit): int
    {
        $documents = $this->eligibleQuery()
            ->where(function ($q) {
                $q->whereNull('efactura_status')
                    ->orWhere('efactura_status', 'none');
            })
            ->whereNull('efactura_upload_id')
            ->with(['company', 'client', 'items'])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $n = 0;
        foreach ($documents as $document) {
            $company = $document->company;
            if (! $company?->isAnafAuthorized() || ! $company->shouldQueueEfacturaOnIssue()) {
                continue;
            }

            try {
                $this->efactura->scheduleAfterIssue($document->fresh(['company', 'client', 'items']));
                $n++;
                $fresh = $document->fresh();
                if (($fresh->efactura_status ?: 'none') !== 'ok') {
                    $this->markForReconcile($fresh);
                }
            } catch (Throwable $e) {
                Log::warning('e-Factura catch-up schedule failed', [
                    'document_id' => $document->id,
                    'type' => $document->type,
                    'status' => $document->status,
                    'error' => $e->getMessage(),
                ]);
                $document->refresh();
                $document->forceFill([
                    'efactura_status' => $document->efactura_status ?: 'error',
                    'efactura_error' => $document->efactura_error ?: $e->getMessage(),
                    'efactura_auto_next_at' => now(),
                ])->save();
            }
        }

        return $n;
    }

    /**
     * @return array{0: int, 1: int} [retried, becameOk]
     */
    private function retryRejected(int $limit): array
    {
        $documents = $this->eligibleQuery()
            ->where(function ($q) {
                $q->whereIn('efactura_status', ['nok', 'error'])
                    ->orWhere(function ($q2) {
                        // none + mesaj eroare (ex. upload eșuat înainte de status error)
                        $q2->where(function ($q3) {
                            $q3->whereNull('efactura_status')
                                ->orWhere('efactura_status', 'none');
                        })->whereNotNull('efactura_error')
                            ->where('efactura_error', '!=', '');
                    });
            })
            ->where(function ($q) {
                $q->whereNull('efactura_auto_next_at')
                    ->orWhere('efactura_auto_next_at', '<=', now());
            })
            ->with(['company', 'client', 'items'])
            ->orderBy('efactura_auto_next_at')
            ->limit($limit)
            ->get();

        $retried = 0;
        $ok = 0;

        foreach ($documents as $document) {
            if (! $document->company?->isAnafAuthorized()) {
                continue;
            }

            $this->maybeResetDailyAttempts($document);
            $document->refresh();

            if ((int) $document->efactura_auto_attempts >= self::MAX_ATTEMPTS_PER_DAY) {
                continue;
            }

            $diagnosis = $this->repair->diagnose($document->efactura_error);
            $sameFingerprint = filled($document->efactura_auto_last_error)
                && $document->efactura_auto_last_error === $diagnosis['fingerprint']
                && (int) $document->efactura_auto_attempts > 0;

            try {
                $this->repair->repair($document->fresh(['client', 'company', 'items']), $diagnosis);
            } catch (Throwable $e) {
                Log::warning('e-Factura auto-repair failed', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $attempt = (int) $document->fresh()->efactura_auto_attempts + 1;
            $backoffMinutes = match (true) {
                $attempt <= 1 => 2,
                $attempt === 2 => 5,
                $attempt === 3 => 10,
                $attempt === 4 => 15,
                default => 30,
            };

            try {
                $doc = $this->efactura->send($document->fresh(['items', 'company', 'client']));
                $retried++;

                $doc->forceFill([
                    'efactura_auto_attempts' => $attempt,
                    'efactura_auto_last_error' => $diagnosis['fingerprint'],
                    'efactura_auto_next_at' => $doc->efactura_status === 'ok'
                        ? null
                        : now()->addMinutes($backoffMinutes),
                ])->save();

                if ($doc->efactura_status === 'ok') {
                    $this->clearAutoState($doc);
                    $ok++;
                }
            } catch (Throwable $e) {
                $retried++;
                $document->refresh();
                $document->forceFill([
                    'efactura_status' => in_array($document->efactura_status, ['nok', 'error'], true)
                        ? $document->efactura_status
                        : 'error',
                    'efactura_error' => $document->efactura_error ?: $e->getMessage(),
                    'efactura_auto_attempts' => $attempt,
                    'efactura_auto_last_error' => $diagnosis['fingerprint'],
                    'efactura_auto_next_at' => now()->addMinutes($backoffMinutes),
                ])->save();

                Log::warning('e-Factura reconcile resend failed', [
                    'document_id' => $document->id,
                    'type' => $document->type,
                    'doc_status' => $document->status,
                    'attempt' => $attempt,
                    'same_fingerprint' => $sameFingerprint,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [$retried, $ok];
    }

    private function alertStuck(): int
    {
        $stuck = $this->eligibleQuery()
            ->whereIn('efactura_status', ['nok', 'error'])
            ->where('efactura_auto_attempts', '>=', self::MAX_ATTEMPTS_PER_DAY)
            ->where(function ($q) {
                $q->whereNull('efactura_auto_alerted_at')
                    ->orWhere('efactura_auto_alerted_at', '<', now('Europe/Bucharest')->startOfDay());
            })
            ->with('company')
            ->limit(30)
            ->get();

        if ($stuck->isEmpty()) {
            return 0;
        }

        $to = trim((string) config('dateconta.recurring_email_alert_to', 'razvan@dateconta.ro'));
        if ($to === '') {
            return 0;
        }

        try {
            $this->mail->send(new EfacturaStuckAlertMail($stuck), $to);
            $now = now();
            foreach ($stuck as $doc) {
                $doc->forceFill(['efactura_auto_alerted_at' => $now])->save();
            }

            return $stuck->count();
        } catch (Throwable $e) {
            Log::error('e-Factura stuck alert failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    private function maybeResetDailyAttempts(Document $document): void
    {
        $alerted = $document->efactura_auto_alerted_at;
        if (! $alerted instanceof Carbon) {
            return;
        }

        $today = now('Europe/Bucharest')->startOfDay();
        if ($alerted->copy()->timezone('Europe/Bucharest')->lt($today)
            && (int) $document->efactura_auto_attempts >= self::MAX_ATTEMPTS_PER_DAY) {
            $document->forceFill([
                'efactura_auto_attempts' => 0,
                'efactura_auto_next_at' => now(),
                'efactura_auto_alerted_at' => null,
            ])->save();
        }
    }

    public function clearAutoState(Document $document): void
    {
        $document->forceFill([
            'efactura_auto_attempts' => 0,
            'efactura_auto_last_error' => null,
            'efactura_auto_next_at' => null,
            'efactura_auto_alerted_at' => null,
        ])->save();
    }

    public function markForReconcile(Document $document): void
    {
        if (($document->efactura_status ?: 'none') === 'ok') {
            $this->clearAutoState($document);

            return;
        }

        $document->forceFill([
            'efactura_auto_next_at' => now(),
        ])->save();
    }
}
