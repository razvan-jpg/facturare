<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCompanyPermission;
use App\Services\ClientBalanceService;
use App\Services\CompanyContext;
use App\Services\PartnerLedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ChecksCompanyPermission;

    public function index(Request $request, CompanyContext $context): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'reports_view');
        $from = $request->date('from')?->toDateString() ?: now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?: now()->toDateString();

        $salesBase = $company->documents()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereBetween('issue_date', [$from, $to]);

        $salesTotal = (float) (clone $salesBase)->sum('total');
        $paymentsTotal = (float) $company->payments()
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $unpaidQuery = $company->documents()
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial']);

        $unpaidInvoices = (float) (clone $unpaidQuery)->sum(DB::raw('total - paid_amount'));
        $openingBalances = app(ClientBalanceService::class)->companyOpeningBalancesTotal($company);
        $unpaidTotal = round($unpaidInvoices + $openingBalances, 2);

        $byClient = (clone $salesBase)
            ->select([
                'client_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total'),
                DB::raw('SUM(paid_amount) as paid'),
            ])
            ->groupBy('client_name')
            ->orderByDesc('total')
            ->limit(100)
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) ($row->client_name ?: '—') => [
                    'count' => (int) $row->count,
                    'total' => (float) $row->total,
                    'paid' => (float) $row->paid,
                ],
            ]);

        $unpaid = (clone $unpaidQuery)
            ->select([
                'id', 'number_full', 'client_name', 'due_date', 'total', 'paid_amount', 'currency',
            ])
            ->orderBy('due_date')
            ->limit(50)
            ->get();

        // Compat UI: obiecte cu sum() ca înainte.
        $sales = collect([(object) ['total' => $salesTotal]]);
        $payments = collect([(object) ['amount' => $paymentsTotal]]);

        return view('reports.index', [
            'company' => $company,
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'payments' => $payments,
            'unpaid' => $unpaid,
            'byClient' => $byClient,
            'unpaidTotal' => $unpaidTotal,
        ]);
    }

    public function clients(Request $request, CompanyContext $context, ClientBalanceService $balances): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'reports_view');

        $asOfRaw = trim((string) $request->input('as_of', ''));
        $asOf = $asOfRaw !== '' ? (dc_parse_date($asOfRaw) ?: now()->toDateString()) : now()->toDateString();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOf)) {
            $asOf = now()->toDateString();
        }

        $clientId = $request->integer('client_id') ?: null;
        if ($clientId) {
            $owned = $company->clients()->whereKey($clientId)->exists();
            if (! $owned) {
                $clientId = null;
            }
        }

        $rows = $balances->balancesAsOf($company, $asOf, $clientId);
        // Afișăm și sold 0 doar dacă e filtru pe un client; altfel doar solduri ≠ 0 (+ opțional toți)
        $showZero = $request->boolean('show_zero') || $clientId !== null;
        if (! $showZero) {
            $rows = $rows->filter(fn (array $row) => abs($row['balance']) > 0.009)->values();
        }

        $total = round($rows->sum(fn (array $row) => $row['balance']), 2);
        $clients = $company->clients()->orderBy('name')->get(['id', 'name', 'cui', 'cnp']);

        $ledgerFromRaw = trim((string) $request->input('ledger_from', ''));
        $ledgerToRaw = trim((string) $request->input('ledger_to', ''));
        $ledgerFrom = $ledgerFromRaw !== '' ? (dc_parse_date($ledgerFromRaw) ?: now()->startOfMonth()->toDateString()) : now()->startOfMonth()->toDateString();
        $ledgerTo = $ledgerToRaw !== '' ? (dc_parse_date($ledgerToRaw) ?: now()->toDateString()) : now()->toDateString();

        // „Toată perioada”: de la data soldului inițial (implicit = data creării clientului).
        $clientPeriodFrom = [];
        foreach ($company->clients()->get(['id', 'opening_balance_date', 'created_at']) as $c) {
            $clientPeriodFrom[(int) $c->id] = $c->effectiveOpeningBalanceDate();
        }

        $openingPeriodFrom = collect($clientPeriodFrom)->filter()->min()
            ?: now()->startOfYear()->toDateString();

        return view('reports.clients', [
            'company' => $company,
            'asOf' => $asOf,
            'clientId' => $clientId,
            'showZero' => $showZero,
            'rows' => $rows,
            'total' => $total,
            'clients' => $clients,
            'ledgerFrom' => $ledgerFrom,
            'ledgerTo' => $ledgerTo,
            'openingPeriodFrom' => $openingPeriodFrom,
            'clientOpeningDates' => $clientPeriodFrom,
        ]);
    }

    public function partnerLedger(Request $request, CompanyContext $context, PartnerLedgerService $ledger): View
    {
        [$company, $client, $from, $to, $data] = $this->partnerLedgerData($request, $context, $ledger);

        return view('reports.partner-ledger', array_merge($data, [
            'embed' => $request->boolean('embed'),
            'pdfUrl' => route('reports.clients.partner-pdf', [
                'client_id' => $client->id,
                'from' => $from,
                'to' => $to,
            ]),
        ]));
    }

    public function partnerLedgerPdf(Request $request, CompanyContext $context, PartnerLedgerService $ledger): Response
    {
        [$company, $client, $from, $to, $data] = $this->partnerLedgerData($request, $context, $ledger);
        $pdf = Pdf::loadView('reports.partner-ledger-pdf', $data)->setPaper('a4', 'portrait');
        $safe = preg_replace('/[^\pL\pN\-]+/u', '-', $client->name) ?: 'partener';

        return $pdf->download('fisa-partener-'.$safe.'-'.$from.'-'.$to.'.pdf');
    }

    public function partnersBalance(Request $request, CompanyContext $context, PartnerLedgerService $ledger): View
    {
        [$company, $from, $to, $data] = $this->partnersBalanceData($request, $context, $ledger);

        $hideZeroSold = $request->boolean('hide_zero_sold');
        $hideZeroLines = $request->boolean('hide_zero_lines');

        return view('reports.partners-balance', array_merge($data, [
            'embed' => $request->boolean('embed'),
            'pdfUrl' => route('reports.clients.balance-pdf', array_filter([
                'from' => $from,
                'to' => $to,
                'hide_zero_sold' => $hideZeroSold ? 1 : null,
                'hide_zero_lines' => $hideZeroLines ? 1 : null,
            ], fn ($v) => $v !== null)),
        ]));
    }

    public function partnersBalancePdf(Request $request, CompanyContext $context, PartnerLedgerService $ledger): Response
    {
        [$company, $from, $to, $data] = $this->partnersBalanceData($request, $context, $ledger);
        $pdf = Pdf::loadView('reports.partners-balance-pdf', $data)->setPaper('a4', 'landscape');

        return $pdf->download('balanta-parteneri-'.$from.'-'.$to.'.pdf');
    }

    /** @return array{0: \App\Models\Company, 1: \App\Models\Client, 2: string, 3: string, 4: array<string, mixed>} */
    private function partnerLedgerData(Request $request, CompanyContext $context, PartnerLedgerService $ledger): array
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'reports_view');

        $clientId = $request->integer('client_id');
        abort_unless($clientId > 0, 422, 'Selectează un client pentru fișa de partener.');

        $client = $company->clients()->whereKey($clientId)->firstOrFail();
        [$from, $to] = $this->parseReportPeriod($request);
        $data = $ledger->build($company, $client, $from, $to);

        return [$company, $client, $from, $to, $data];
    }

    /** @return array{0: \App\Models\Company, 1: string, 2: string, 3: array<string, mixed>} */
    private function partnersBalanceData(Request $request, CompanyContext $context, PartnerLedgerService $ledger): array
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'reports_view');
        [$from, $to] = $this->parseReportPeriod($request);
        $data = $ledger->buildTertiBalance(
            $company,
            $from,
            $to,
            $request->boolean('hide_zero_sold'),
            $request->boolean('hide_zero_lines'),
        );

        return [$company, $from, $to, $data];
    }

    /** @return array{0: string, 1: string} */
    private function parseReportPeriod(Request $request): array
    {
        $fromRaw = trim((string) $request->input('from', ''));
        $toRaw = trim((string) $request->input('to', ''));
        $from = $fromRaw !== '' ? (dc_parse_date($fromRaw) ?: now()->startOfMonth()->toDateString()) : now()->startOfMonth()->toDateString();
        $to = $toRaw !== '' ? (dc_parse_date($toRaw) ?: now()->toDateString()) : now()->toDateString();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = now()->startOfMonth()->toDateString();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = now()->toDateString();
        }

        return [$from, $to];
    }

    public function export(Request $request, CompanyContext $context): StreamedResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'reports_view');
        $from = $request->date('from')?->toDateString() ?: now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?: now()->toDateString();

        $query = $company->documents()
            ->select([
                'number_full', 'issue_date', 'client_name', 'total', 'paid_amount', 'payment_status', 'currency',
            ])
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereBetween('issue_date', [$from, $to])
            ->orderBy('issue_date');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Număr', 'Data', 'Client', 'Total', 'Încasat', 'Status plată', 'Monedă']);
            foreach ($query->cursor() as $row) {
                fputcsv($out, [
                    $row->number_full,
                    $row->issue_date?->format('Y-m-d'),
                    $row->client_name,
                    $row->total,
                    $row->paid_amount,
                    $row->payment_status,
                    $row->currency,
                ]);
            }
            fclose($out);
        }, 'raport-vanzari-'.$from.'-'.$to.'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
