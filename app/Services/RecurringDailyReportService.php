<?php

namespace App\Services;

use App\Mail\RecurringDailyReportMail;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecurringDailyReportService
{
    public function __construct(private ReliableMail $mail) {}

    /**
     * Agregă documentele recurente emise în ziua dată (toate societățile).
     *
     * @return array{
     *     date: Carbon,
     *     rows: Collection<int, object>,
     *     documents: Collection<int, Document>,
     *     totals: array<string, int>,
     *     email_totals: array{sent: int, failed: int, skipped: int, pending: int, none: int},
     *     grand_total: int,
     *     platform_cc: string
     * }
     */
    public function collect(?Carbon $date = null): array
    {
        $date = ($date ?? now('Europe/Bucharest'))->copy()->startOfDay();

        $rows = Document::query()
            ->selectRaw('companies.id as company_id')
            ->selectRaw('companies.name as company_name')
            ->selectRaw('companies.cui as company_cui')
            ->selectRaw('documents.type as document_type')
            ->selectRaw('COALESCE(documents.efactura_status, \'none\') as efactura_status')
            ->selectRaw('COUNT(*) as documents_count')
            ->join('companies', 'companies.id', '=', 'documents.company_id')
            ->whereNotNull('documents.recurring_invoice_id')
            ->whereIn('documents.status', ['issued', 'storno'])
            ->whereDate('documents.issue_date', $date->toDateString())
            ->groupBy('companies.id', 'companies.name', 'companies.cui', 'documents.type', 'documents.efactura_status')
            ->orderBy('companies.name')
            ->orderBy('documents.type')
            ->get();

        $documents = Document::query()
            ->with(['company', 'client'])
            ->whereNotNull('recurring_invoice_id')
            ->whereIn('type', ['invoice', 'proforma'])
            ->whereIn('status', ['issued', 'storno'])
            ->whereDate('issue_date', $date->toDateString())
            ->orderBy('company_id')
            ->orderBy('type')
            ->orderBy('number')
            ->get();

        $totals = [
            'invoice' => 0,
            'proforma' => 0,
            'other' => 0,
        ];
        foreach ($rows as $row) {
            $type = (string) $row->document_type;
            if (isset($totals[$type])) {
                $totals[$type] += (int) $row->documents_count;
            } else {
                $totals['other'] += (int) $row->documents_count;
            }
        }

        $emailTotals = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'pending' => 0,
            'none' => 0,
        ];
        foreach ($documents as $doc) {
            $status = (string) ($doc->client_email_status ?: 'none');
            if (isset($emailTotals[$status])) {
                $emailTotals[$status]++;
            } else {
                $emailTotals['none']++;
            }
        }

        return [
            'date' => $date,
            'rows' => $rows,
            'documents' => $documents,
            'totals' => $totals,
            'email_totals' => $emailTotals,
            'grand_total' => (int) $rows->sum('documents_count'),
            'platform_cc' => (string) config('dateconta.recurring_document_email_cc', 'facturare@fly-david.ro'),
        ];
    }

    public function buildPdf(array $report): string
    {
        return Pdf::loadView('reports.recurring-daily-pdf', $report)
            ->setPaper('a4', 'landscape')
            ->output();
    }

    /**
     * @param  string|list<string>|null  $to
     */
    public function send(?Carbon $date = null, string|array|null $to = null): bool
    {
        $recipients = $this->resolveRecipients($to);
        $report = $this->collect($date);

        // Nu trimite PDF gol (ex. sloturi 14:15 / 20:00 fără emitere în ziua respectivă).
        if ($report['grand_total'] <= 0) {
            Log::info('Recurring daily report skipped (no documents)', [
                'to' => $recipients,
                'date' => $report['date']->toDateString(),
            ]);

            return true;
        }

        try {
            $pdf = $this->buildPdf($report);
            $filename = 'raport-recurente-'.$report['date']->format('Y-m-d').'.pdf';

            $this->mail->send(
                new RecurringDailyReportMail($report, $pdf, $filename),
                $recipients
            );

            Log::info('Recurring daily report email sent', [
                'to' => $recipients,
                'date' => $report['date']->toDateString(),
                'documents' => $report['grand_total'],
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Recurring daily report email failed', [
                'to' => $recipients,
                'date' => $report['date']->toDateString(),
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
            $raw = config('dateconta.recurring_daily_report_emails');
            if (! is_array($raw) || $raw === []) {
                $legacy = (string) config('dateconta.recurring_daily_report_email', 'razvan@fly-david.ro');
                $parts = preg_split('/\s*,\s*/', $legacy);
                $raw = is_array($parts) ? $parts : [];
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $raw))));
    }
}
