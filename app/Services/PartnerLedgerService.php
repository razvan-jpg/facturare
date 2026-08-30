<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use Carbon\Carbon;

/**
 * Fișă de partener (client / 411) pe model contabil tip NextUp.
 * Debit = facturi; Credit = încasări / note de creditare; Sold = Debit − Credit (pozitiv = de încasat).
 */
class PartnerLedgerService
{
    public function __construct(private readonly ClientBalanceService $balances) {}

    /**
     * @return array{
     *   company: Company,
     *   client: Client,
     *   from: string,
     *   to: string,
     *   account: string,
     *   branch: string,
     *   currency: string,
     *   lines: list<array{branch: string, tip: string, date: ?string, number: string, debit: float, credit: float, sold: float}>,
     *   totals: array<string, float>
     * }
     */
    public function build(Company $company, Client $client, string $from, string $to): array
    {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $branch = 'Sediu social';
        $account = '4111-Clienți';

        $openingAmount = round((float) ($client->opening_balance ?? 0), 2);
        $openingDate = $client->effectiveOpeningBalanceDate();
        $openingPrior = (abs($openingAmount) >= 0.009 && $openingDate < $from) ? $openingAmount : 0.0;
        $openingInPeriod = (abs($openingAmount) >= 0.009 && $openingDate >= $from && $openingDate <= $to)
            ? $openingAmount
            : 0.0;

        $priorDebit = round($this->priorInvoiceTotal($company, $client, $from) + $openingPrior, 2);
        $priorCredit = $this->priorPaymentTotal($company, $client, $from);
        $startSold = round($priorDebit - $priorCredit, 2);

        $movements = $this->periodMovements($company, $client, $from, $to, $branch);
        if ($openingInPeriod >= 0.009 || $openingInPeriod <= -0.009) {
            array_unshift($movements, [
                'branch' => $branch,
                'tip' => '',
                'date' => $openingDate,
                'number' => 'Sold initial',
                'debit' => $openingInPeriod > 0 ? $openingInPeriod : 0.0,
                'credit' => $openingInPeriod < 0 ? abs($openingInPeriod) : 0.0,
            ]);
        }

        $lines = [];
        $sold = $startSold;
        $openingDebit = $startSold > 0.009 ? round($startSold, 2) : 0.0;
        $openingCredit = $startSold < -0.009 ? round(abs($startSold), 2) : 0.0;

        $lines[] = [
            'branch' => '',
            'tip' => '',
            'date' => null,
            'number' => 'Sold initial',
            'debit' => $priorDebit,
            'credit' => $priorCredit,
            'sold' => round($sold, 2),
            'is_opening' => true,
        ];

        $periodDebit = 0.0;
        $periodCredit = 0.0;

        foreach ($movements as $mov) {
            $sold = round($sold + $mov['debit'] - $mov['credit'], 2);
            $periodDebit = round($periodDebit + $mov['debit'], 2);
            $periodCredit = round($periodCredit + $mov['credit'], 2);
            $lines[] = [
                'branch' => $mov['branch'],
                'tip' => $mov['tip'],
                'date' => $mov['date'],
                'number' => $mov['number'],
                'debit' => $mov['debit'],
                'credit' => $mov['credit'],
                'sold' => $sold,
                'is_opening' => false,
            ];
        }

        $totalDebit = round($priorDebit + $periodDebit, 2);
        $totalCredit = round($priorCredit + $periodCredit, 2);
        $finalSold = round($totalDebit - $totalCredit, 2);
        $finalDebit = $finalSold > 0.009 ? $finalSold : 0.0;
        $finalCredit = $finalSold < -0.009 ? abs($finalSold) : 0.0;

        $lines[] = [
            'branch' => '',
            'tip' => '',
            'date' => null,
            'number' => 'Total cont:',
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'sold' => $finalSold,
            'is_total' => true,
        ];

        return [
            'company' => $company,
            'client' => $client,
            'from' => $from,
            'to' => $to,
            'account' => $account,
            'branch' => $branch,
            'currency' => 'RON',
            'lines' => $lines,
            'totals' => [
                'opening_debit' => $openingDebit,
                'opening_credit' => $openingCredit,
                'prior_debit' => $priorDebit,
                'prior_credit' => $priorCredit,
                'period_debit' => $periodDebit,
                'period_credit' => $periodCredit,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'final_debit' => round($finalDebit, 2),
                'final_credit' => round($finalCredit, 2),
                'final_sold' => $finalSold,
            ],
        ];
    }

    /**
     * Balanță terți / parteneri (4111-Clienți) pe interval — model NextUp „BALANTA TERTI”.
     *
     * @return array{
     *   company: Company,
     *   from: string,
     *   to: string,
     *   currency: string,
     *   account: string,
     *   rows: list<array{
     *     account: string,
     *     name: string,
     *     vat_collection: string,
     *     prior_debit: float,
     *     prior_credit: float,
     *     period_debit: float,
     *     period_credit: float,
     *     total_debit: float,
     *     total_credit: float,
     *     final_debit: float,
     *     final_credit: float,
     *     is_account_header?: bool,
     *     is_total?: bool
     *   }>
     * }
     */
    public function buildTertiBalance(
        Company $company,
        string $from,
        string $to,
        bool $hideZeroSold = false,
        bool $hideZeroLines = false,
    ): array {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $account = '4111';
        $clients = $company->clients()->orderBy('name')->get();
        $clientIds = $clients->pluck('id')->all();

        $priorInv = $this->sumInvoicesByClient($company, $clientIds, null, $from);
        $periodInv = $this->sumInvoicesByClient($company, $clientIds, $from, $to, true);
        $priorPay = $this->sumPaymentsByClient($company, $clientIds, null, $from);
        $periodPay = $this->sumPaymentsByClient($company, $clientIds, $from, $to, true);
        $priorNc = $this->sumCreditNotesByClient($company, $clientIds, null, $from);
        $periodNc = $this->sumCreditNotesByClient($company, $clientIds, $from, $to, true);
        $periodStorno = $this->sumStornoByClient($company, $clientIds, $from, $to);

        $partnerRows = [];
        $sum = [
            'prior_debit' => 0.0, 'prior_credit' => 0.0,
            'period_debit' => 0.0, 'period_credit' => 0.0,
            'total_debit' => 0.0, 'total_credit' => 0.0,
            'final_debit' => 0.0, 'final_credit' => 0.0,
        ];

        foreach ($clients as $client) {
            $id = (int) $client->id;

            // Sold inițial: înainte de perioadă → rulaj precedent; în perioadă → rulaj curent.
            $openingAmount = round((float) ($client->opening_balance ?? 0), 2);
            $openingDate = $client->effectiveOpeningBalanceDate();
            $openingPrior = 0.0;
            $openingPeriod = 0.0;
            if (abs($openingAmount) >= 0.009) {
                if ($openingDate < $from) {
                    $openingPrior = $openingAmount;
                } elseif ($openingDate <= $to) {
                    $openingPeriod = $openingAmount;
                }
            }

            $priorDebit = round(($priorInv[$id] ?? 0) + $openingPrior, 2);
            $priorCredit = round(($priorPay[$id] ?? 0) + ($priorNc[$id] ?? 0), 2);
            $periodDebit = round(($periodInv[$id] ?? 0) + $openingPeriod, 2);
            $periodCredit = round(($periodPay[$id] ?? 0) + ($periodNc[$id] ?? 0) + ($periodStorno[$id] ?? 0), 2);
            $totalDebit = round($priorDebit + $periodDebit, 2);
            $totalCredit = round($priorCredit + $periodCredit, 2);
            $net = round($totalDebit - $totalCredit, 2);
            // Ca în modelul 4111: soldul net semnat în coloana Debitoare
            $finalDebit = $net;
            $finalCredit = 0.0;

            $row = [
                'account' => '',
                'name' => $client->name,
                'vat_collection' => 'Nu',
                'prior_debit' => $priorDebit,
                'prior_credit' => $priorCredit,
                'period_debit' => $periodDebit,
                'period_credit' => $periodCredit,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'final_debit' => $finalDebit,
                'final_credit' => $finalCredit,
            ];

            $lineAllZero = abs($priorDebit) < 0.009 && abs($priorCredit) < 0.009
                && abs($periodDebit) < 0.009 && abs($periodCredit) < 0.009
                && abs($totalDebit) < 0.009 && abs($totalCredit) < 0.009
                && abs($finalDebit) < 0.009 && abs($finalCredit) < 0.009;
            $soldZero = abs($net) < 0.009;

            if ($hideZeroLines && $lineAllZero) {
                continue;
            }
            if ($hideZeroSold && $soldZero) {
                continue;
            }

            $partnerRows[] = $row;

            foreach (array_keys($sum) as $key) {
                $sum[$key] = round($sum[$key] + ($row[$key] ?? 0), 2);
            }
        }

        $rows = [];
        $rows[] = [
            'account' => $account,
            'name' => 'Clienți',
            'vat_collection' => '',
            'prior_debit' => $sum['prior_debit'],
            'prior_credit' => $sum['prior_credit'],
            'period_debit' => $sum['period_debit'],
            'period_credit' => $sum['period_credit'],
            'total_debit' => $sum['total_debit'],
            'total_credit' => $sum['total_credit'],
            'final_debit' => $sum['final_debit'],
            'final_credit' => $sum['final_credit'],
            'is_account_header' => true,
        ];
        foreach ($partnerRows as $row) {
            $rows[] = $row;
        }
        $rows[] = [
            'account' => '',
            'name' => 'TOTAL BALANTA',
            'vat_collection' => '',
            'prior_debit' => $sum['prior_debit'],
            'prior_credit' => $sum['prior_credit'],
            'period_debit' => $sum['period_debit'],
            'period_credit' => $sum['period_credit'],
            'total_debit' => $sum['total_debit'],
            'total_credit' => $sum['total_credit'],
            'final_debit' => $sum['final_debit'],
            'final_credit' => $sum['final_credit'],
            'is_total' => true,
        ];

        return [
            'company' => $company,
            'from' => $from,
            'to' => $to,
            'currency' => 'RON',
            'account' => $account,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<int>  $clientIds
     * @return array<int, float>
     */
    private function sumInvoicesByClient(Company $company, array $clientIds, ?string $from, string $toOrBefore, bool $inclusiveTo = false): array
    {
        if ($clientIds === []) {
            return [];
        }
        $q = Document::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $clientIds)
            ->where('type', 'invoice')
            ->where('status', 'issued');
        if ($from === null) {
            $q->whereDate('issue_date', '<', $toOrBefore);
        } else {
            $q->whereDate('issue_date', '>=', $from);
            $q->whereDate('issue_date', $inclusiveTo ? '<=' : '<', $toOrBefore);
        }

        $out = [];
        foreach ($q->selectRaw('client_id, SUM(total) as s')->groupBy('client_id')->get() as $row) {
            $out[(int) $row->client_id] = round((float) $row->s, 2);
        }

        return $out;
    }

    /**
     * @param  list<int>  $clientIds
     * @return array<int, float>
     */
    private function sumStornoByClient(Company $company, array $clientIds, string $from, string $to): array
    {
        if ($clientIds === []) {
            return [];
        }
        $q = Document::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $clientIds)
            ->where('type', 'invoice')
            ->where('status', 'storno')
            ->whereDate('issue_date', '>=', $from)
            ->whereDate('issue_date', '<=', $to);

        $out = [];
        foreach ($q->selectRaw('client_id, SUM(total) as s')->groupBy('client_id')->get() as $row) {
            $out[(int) $row->client_id] = round((float) $row->s, 2);
        }

        return $out;
    }

    /**
     * @param  list<int>  $clientIds
     * @return array<int, float>
     */
    private function sumCreditNotesByClient(Company $company, array $clientIds, ?string $from, string $toOrBefore, bool $inclusiveTo = false): array
    {
        if ($clientIds === []) {
            return [];
        }
        $q = Document::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $clientIds)
            ->where('type', 'credit_note')
            ->whereIn('status', ['issued', 'storno']);
        if ($from === null) {
            $q->whereDate('issue_date', '<', $toOrBefore);
        } else {
            $q->whereDate('issue_date', '>=', $from);
            $q->whereDate('issue_date', $inclusiveTo ? '<=' : '<', $toOrBefore);
        }

        $out = [];
        foreach ($q->selectRaw('client_id, SUM(total) as s')->groupBy('client_id')->get() as $row) {
            $out[(int) $row->client_id] = round((float) $row->s, 2);
        }

        return $out;
    }

    /**
     * @param  list<int>  $clientIds
     * @return array<int, float>
     */
    private function sumPaymentsByClient(Company $company, array $clientIds, ?string $from, string $toOrBefore, bool $inclusiveTo = false): array
    {
        if ($clientIds === []) {
            return [];
        }

        $docs = Document::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $clientIds)
            ->pluck('client_id', 'id'); // id => client_id

        $q = Payment::query()->where('company_id', $company->id);
        if ($from === null) {
            $q->whereDate('paid_at', '<', $toOrBefore);
        } else {
            $q->whereDate('paid_at', '>=', $from);
            $q->whereDate('paid_at', $inclusiveTo ? '<=' : '<', $toOrBefore);
        }
        $q->where(function ($w) use ($clientIds, $docs) {
            $w->whereIn('client_id', $clientIds);
            $docIds = $docs->keys()->all();
            if ($docIds !== []) {
                $w->orWhereIn('document_id', $docIds);
            }
        });

        $out = array_fill_keys($clientIds, 0.0);
        foreach ($q->get(['client_id', 'document_id', 'amount']) as $pay) {
            $cid = (int) ($pay->client_id ?: 0);
            if ($cid <= 0 && $pay->document_id) {
                $cid = (int) ($docs[(int) $pay->document_id] ?? 0);
            }
            if ($cid <= 0 || ! array_key_exists($cid, $out)) {
                continue;
            }
            $out[$cid] = round($out[$cid] + (float) $pay->amount, 2);
        }

        return $out;
    }

    private function priorInvoiceTotal(Company $company, Client $client, string $from): float
    {
        return round((float) Document::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereDate('issue_date', '<', $from)
            ->sum('total'), 2);
    }

    private function priorPaymentTotal(Company $company, Client $client, string $from): float
    {
        $docIds = Document::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->pluck('id');

        $payments = (float) Payment::query()
            ->where('company_id', $company->id)
            ->whereDate('paid_at', '<', $from)
            ->where(function ($q) use ($client, $docIds) {
                $q->where('client_id', $client->id);
                if ($docIds->isNotEmpty()) {
                    $q->orWhereIn('document_id', $docIds);
                }
            })
            ->sum('amount');

        $creditNotes = (float) Document::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->where('type', 'credit_note')
            ->whereIn('status', ['issued', 'storno'])
            ->whereDate('issue_date', '<', $from)
            ->sum('total');

        return round($payments + $creditNotes, 2);
    }

    /**
     * @return list<array{branch: string, tip: string, date: string, number: string, debit: float, credit: float}>
     */
    private function periodMovements(Company $company, Client $client, string $from, string $to, string $branch): array
    {
        $items = collect();

        $invoices = Document::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->where('type', 'invoice')
            ->whereIn('status', ['issued', 'storno'])
            ->whereDate('issue_date', '>=', $from)
            ->whereDate('issue_date', '<=', $to)
            ->orderBy('issue_date')
            ->get(['id', 'number_full', 'issue_date', 'total', 'status']);

        foreach ($invoices as $doc) {
            $date = $doc->issue_date?->toDateString() ?? $from;
            $total = round((float) $doc->total, 2);
            if ($doc->status === 'storno') {
                $items->push([
                    'sort' => $date.'-2-'.$doc->id,
                    'branch' => $branch,
                    'tip' => 'ST',
                    'date' => $date,
                    'number' => $doc->number_full ?: ('#'.$doc->id),
                    'debit' => 0.0,
                    'credit' => $total,
                ]);
            } else {
                $items->push([
                    'sort' => $date.'-1-'.$doc->id,
                    'branch' => $branch,
                    'tip' => 'FC',
                    'date' => $date,
                    'number' => $doc->number_full ?: ('#'.$doc->id),
                    'debit' => $total,
                    'credit' => 0.0,
                ]);
            }
        }

        $creditNotes = Document::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->where('type', 'credit_note')
            ->whereIn('status', ['issued', 'storno'])
            ->whereDate('issue_date', '>=', $from)
            ->whereDate('issue_date', '<=', $to)
            ->orderBy('issue_date')
            ->get(['id', 'number_full', 'issue_date', 'total']);

        foreach ($creditNotes as $doc) {
            $date = $doc->issue_date?->toDateString() ?? $from;
            $items->push([
                'sort' => $date.'-2-'.$doc->id,
                'branch' => $branch,
                'tip' => 'NC',
                'date' => $date,
                'number' => $doc->number_full ?: ('#'.$doc->id),
                'debit' => 0.0,
                'credit' => round((float) $doc->total, 2),
            ]);
        }

        $docIds = Document::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->pluck('id');

        $payments = Payment::query()
            ->where('payments.company_id', $company->id)
            ->whereDate('payments.paid_at', '>=', $from)
            ->whereDate('payments.paid_at', '<=', $to)
            ->where(function ($q) use ($client, $docIds) {
                $q->where('payments.client_id', $client->id);
                if ($docIds->isNotEmpty()) {
                    $q->orWhereIn('payments.document_id', $docIds);
                }
            })
            ->leftJoin('documents', 'documents.id', '=', 'payments.document_id')
            ->orderBy('payments.paid_at')
            ->get([
                'payments.id',
                'payments.paid_at',
                'payments.amount',
                'payments.method',
                'payments.reference',
                'documents.number_full as document_number',
            ]);

        foreach ($payments as $pay) {
            $date = $pay->paid_at
                ? Carbon::parse($pay->paid_at)->toDateString()
                : $from;
            $tip = strtolower((string) ($pay->method ?? '')) === 'cash' ? 'CH' : 'INC';
            $number = $pay->reference
                ?: ($pay->document_number ? 'Plată '.$pay->document_number : 'Plată #'.$pay->id);
            $items->push([
                'sort' => $date.'-3-'.$pay->id,
                'branch' => $branch,
                'tip' => $tip,
                'date' => $date,
                'number' => $number,
                'debit' => 0.0,
                'credit' => round((float) $pay->amount, 2),
            ]);
        }

        return $items->sortBy('sort')->values()->map(fn ($row) => [
            'branch' => $row['branch'],
            'tip' => $row['tip'],
            'date' => $row['date'],
            'number' => $row['number'],
            'debit' => $row['debit'],
            'credit' => $row['credit'],
        ])->all();
    }
}
