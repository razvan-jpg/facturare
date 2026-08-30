<?php

namespace App\Services;

use App\Mail\DailyOpsReportMail;
use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use App\Models\VisitorSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Raport recapitular zilnic (toată platforma) — email seara către ops.
 */
class DailyOpsReportService
{
    public function __construct(private ReliableMail $mail) {}

    /**
     * @return array{
     *   date: Carbon,
     *   documents: Collection<int, Document>,
     *   documents_manual: Collection<int, Document>,
     *   documents_recurring: Collection<int, Document>,
     *   documents_by_user: Collection<int, object>,
     *   recurring_by_company: Collection<int, object>,
     *   payments: Collection<int, Payment>,
     *   payments_by_company: Collection<int, object>,
     *   payments_total: float,
     *   payments_count: int,
     *   new_users: Collection<int, User>,
     *   new_companies: Collection<int, Company>,
     *   visitors: array{new: int, returning: int, total: int, page_views: int},
     *   totals: array{documents: int, manual: int, recurring: int, invoices: int, proformas: int, other: int}
     * }
     */
    public function collect(?Carbon $date = null): array
    {
        $date = ($date ?? now('Europe/Bucharest'))->copy()->timezone('Europe/Bucharest')->startOfDay();
        $day = $date->toDateString();
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        $documents = Document::query()
            ->with(['company:id,name,cui', 'client:id,name', 'creator:id,name,email'])
            ->whereIn('status', ['issued', 'storno'])
            ->whereDate('issue_date', $day)
            ->orderBy('company_id')
            ->orderBy('type')
            ->orderBy('number')
            ->get();

        $manual = $documents->filter(fn (Document $d) => ! $d->recurring_invoice_id)->values();
        $recurring = $documents->filter(fn (Document $d) => (bool) $d->recurring_invoice_id)->values();

        $documentsByUser = $manual
            ->groupBy(fn (Document $d) => ($d->created_by ?: 0).'|'.($d->company_id ?: 0))
            ->map(function (Collection $group) {
                /** @var Document $first */
                $first = $group->first();

                return (object) [
                    'user_name' => $first->creator?->name ?: '—',
                    'user_email' => $first->creator?->email ?: '—',
                    'company_name' => $first->company?->name ?: '—',
                    'company_cui' => $first->company?->cui ?: '—',
                    'count' => $group->count(),
                    'invoices' => $group->where('type', 'invoice')->count(),
                    'proformas' => $group->where('type', 'proforma')->count(),
                    'total_ron' => round((float) $group->sum('total'), 2),
                ];
            })
            ->sortBy([
                ['company_name', 'asc'],
                ['user_name', 'asc'],
            ])
            ->values();

        $recurringByCompany = $recurring
            ->groupBy('company_id')
            ->map(function (Collection $group) {
                /** @var Document $first */
                $first = $group->first();

                return (object) [
                    'company_name' => $first->company?->name ?: '—',
                    'company_cui' => $first->company?->cui ?: '—',
                    'count' => $group->count(),
                    'invoices' => $group->where('type', 'invoice')->count(),
                    'proformas' => $group->where('type', 'proforma')->count(),
                    'total_ron' => round((float) $group->sum('total'), 2),
                ];
            })
            ->sortBy('company_name')
            ->values();

        $payments = Payment::query()
            ->with(['company:id,name,cui', 'client:id,name', 'document:id,number_full,type'])
            ->whereDate('paid_at', $day)
            ->orderBy('company_id')
            ->orderBy('paid_at')
            ->get();

        $paymentsByCompany = $payments
            ->groupBy('company_id')
            ->map(function (Collection $group) {
                /** @var Payment $first */
                $first = $group->first();

                return (object) [
                    'company_name' => $first->company?->name ?: '—',
                    'company_cui' => $first->company?->cui ?: '—',
                    'count' => $group->count(),
                    'amount' => round((float) $group->sum('amount'), 2),
                ];
            })
            ->sortBy('company_name')
            ->values();

        $newUsers = User::query()
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get(['id', 'name', 'email', 'created_at']);

        $newCompanies = Company::query()
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get(['id', 'name', 'cui', 'created_at']);

        $visitorsNew = (int) VisitorSession::query()
            ->whereBetween('first_seen_at', [$from, $to])
            ->count();

        $visitorsTotal = (int) VisitorSession::query()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('last_seen_at', [$from, $to])
                    ->orWhereBetween('first_seen_at', [$from, $to]);
            })
            ->count();

        $visitorsReturning = max(0, $visitorsTotal - $visitorsNew);

        $pageViews = (int) VisitorSession::query()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('last_seen_at', [$from, $to])
                    ->orWhereBetween('first_seen_at', [$from, $to]);
            })
            ->sum('page_views');

        $totals = [
            'documents' => $documents->count(),
            'manual' => $manual->count(),
            'recurring' => $recurring->count(),
            'invoices' => $documents->where('type', 'invoice')->count(),
            'proformas' => $documents->where('type', 'proforma')->count(),
            'other' => $documents->whereNotIn('type', ['invoice', 'proforma'])->count(),
        ];

        $efactura = $this->collectEfactura($documents);

        return [
            'date' => $date,
            'documents' => $documents,
            'documents_manual' => $manual,
            'documents_recurring' => $recurring,
            'documents_by_user' => $documentsByUser,
            'recurring_by_company' => $recurringByCompany,
            'payments' => $payments,
            'payments_by_company' => $paymentsByCompany,
            'payments_total' => round((float) $payments->sum('amount'), 2),
            'payments_count' => $payments->count(),
            'new_users' => $newUsers,
            'new_companies' => $newCompanies,
            'visitors' => [
                'new' => $visitorsNew,
                'returning' => $visitorsReturning,
                'total' => $visitorsTotal,
                'page_views' => $pageViews,
            ],
            'totals' => $totals,
            'efactura' => $efactura,
        ];
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return array{
     *   eligible: int,
     *   sent: int,
     *   ok: int,
     *   errors: int,
     *   pending: int,
     *   none: int,
     *   error_rows: Collection<int, object>
     * }
     */
    private function collectEfactura(Collection $documents): array
    {
        $invoices = $documents
            ->filter(fn (Document $d) => $d->type === 'invoice')
            ->values();

        $sent = 0;
        $ok = 0;
        $errors = 0;
        $pending = 0;
        $none = 0;
        $errorRows = collect();

        foreach ($invoices as $doc) {
            $status = strtolower(trim((string) ($doc->efactura_status ?: 'none')));
            if ($status === '' || $status === 'not_sent') {
                $status = 'none';
            }

            $wasSent = $status !== 'none'
                || filled($doc->efactura_upload_id)
                || $doc->efactura_sent_at !== null;

            if (! $wasSent) {
                $none++;

                continue;
            }

            $sent++;

            if ($status === 'ok') {
                $ok++;
            } elseif (in_array($status, ['nok', 'error'], true)) {
                $errors++;
                $reason = trim((string) ($doc->efactura_error ?: $doc->efactura_auto_last_error ?: ''));
                if ($reason === '') {
                    $reason = $status === 'nok'
                        ? 'Respinsă de ANAF (nok) — fără detaliu salvat.'
                        : 'Eroare e-Factura — fără detaliu salvat.';
                }
                $errorRows->push((object) [
                    'company_name' => $doc->company?->name ?: '—',
                    'company_cui' => $doc->company?->cui ?: '—',
                    'number_full' => (string) ($doc->number_full ?: ('#'.$doc->id)),
                    'client_name' => $doc->client?->name ?: ($doc->client_name ?: '—'),
                    'status' => $status,
                    'reason' => $reason,
                ]);
            } else {
                // queued / uploaded / processing / altele în curs
                $pending++;
            }
        }

        $errorRows = $errorRows
            ->sortBy([
                ['company_name', 'asc'],
                ['number_full', 'asc'],
            ])
            ->values();

        return [
            'eligible' => $invoices->count(),
            'sent' => $sent,
            'ok' => $ok,
            'errors' => $errors,
            'pending' => $pending,
            'none' => $none,
            'error_rows' => $errorRows,
        ];
    }

    public function buildPdf(array $report): string
    {
        return Pdf::loadView('reports.daily-ops-pdf', $report)
            ->setPaper('a4', 'portrait')
            ->output();
    }

    public function sentCacheKey(Carbon $date): string
    {
        return 'daily_ops_report_sent:'.$date->copy()->timezone('Europe/Bucharest')->toDateString();
    }

    public function wasSent(Carbon $date): bool
    {
        return Cache::has($this->sentCacheKey($date));
    }

    public function markSent(Carbon $date): void
    {
        Cache::put($this->sentCacheKey($date), true, now()->addDays(3));
    }

    /**
     * @param  string|list<string>|null  $to
     */
    public function send(?Carbon $date = null, string|array|null $to = null, bool $force = false): bool
    {
        $recipients = $this->resolveRecipients($to);
        $report = $this->collect($date);
        $day = $report['date'];

        if (! $force && $this->wasSent($day)) {
            Log::info('Daily ops report already sent (skip)', [
                'date' => $day->toDateString(),
                'to' => $recipients,
            ]);

            return true;
        }

        try {
            $pdf = $this->buildPdf($report);
            $filename = 'raport-dateconta-'.$day->format('Y-m-d').'.pdf';

            $this->mail->send(
                new DailyOpsReportMail($report, $pdf, $filename),
                $recipients
            );

            $this->markSent($day);

            Log::info('Daily ops report email sent', [
                'to' => $recipients,
                'date' => $day->toDateString(),
                'documents' => $report['totals']['documents'],
                'payments' => $report['payments_count'],
                'visitors' => $report['visitors']['total'],
                'pdf' => $filename,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Daily ops report email failed', [
                'to' => $recipients,
                'date' => $day->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  string|list<string>|null  $to
     * @return list<string>
     */
    private function resolveRecipients(string|array|null $to): array
    {
        if ($to !== null) {
            if (is_array($to)) {
                $raw = $to;
            } else {
                $parts = preg_split('/\s*,\s*/', (string) $to);
                $raw = is_array($parts) ? $parts : [];
            }
        } else {
            $raw = config('dateconta.daily_ops_report_emails');
            if (! is_array($raw) || $raw === []) {
                $raw = config('dateconta.recurring_daily_report_emails', ['razvan@fly-david.ro']);
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', (array) $raw))));
    }
}
