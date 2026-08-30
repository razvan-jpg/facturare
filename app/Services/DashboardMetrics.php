<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use App\Models\RecurringInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicatori și widget-uri dashboard pentru societatea activă.
 */
class DashboardMetrics
{
    public function __construct(
        private readonly ClientBalanceService $balances,
        private readonly ClientPenaltyService $penalties,
    ) {}

    /**
     * @return array{
     *     today_total: float,
     *     month_total: float,
     *     today_paid: float,
     *     month_paid: float,
     *     today_paid_by_method: array{cash: float, card: float, op: float, other: float},
     *     month_paid_by_method: array{cash: float, card: float, op: float, other: float}
     * }
     */
    public function salesAndCollections(Company $company, ?Carbon $now = null): array
    {
        $now ??= now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $todayTotal = (float) $company->documents()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereDate('issue_date', $today)
            ->sum('total');

        $monthTotal = (float) $company->documents()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereBetween('issue_date', [$monthStart, $monthEnd])
            ->sum('total');

        $todayPaidBy = $this->paymentsByMethod($company, $today, $today);
        $monthPaidBy = $this->paymentsByMethod($company, $monthStart, $monthEnd);

        return [
            'today_total' => round($todayTotal, 2),
            'month_total' => round($monthTotal, 2),
            'today_paid' => round(array_sum($todayPaidBy), 2),
            'month_paid' => round(array_sum($monthPaidBy), 2),
            'today_paid_by_method' => $todayPaidBy,
            'month_paid_by_method' => $monthPaidBy,
        ];
    }

    /**
     * @return array{cash: float, card: float, op: float, other: float}
     */
    public function paymentsByMethod(Company $company, string $from, string $to): array
    {
        $rows = $company->payments()
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        $out = ['cash' => 0.0, 'card' => 0.0, 'op' => 0.0, 'other' => 0.0];

        foreach ($rows as $method => $total) {
            $bucket = match (strtolower((string) $method)) {
                'cash', 'receipt' => 'cash',
                'card' => 'card',
                'op' => 'op',
                default => 'other',
            };
            $out[$bucket] += (float) $total;
        }

        foreach ($out as $k => $v) {
            $out[$k] = round($v, 2);
        }

        return $out;
    }

    /**
     * Sume de încasat — total, depășit vs în termen, buckets pe vechime restanță.
     *
     * @return array{
     *   total: float,
     *   overdue: float,
     *   on_time: float,
     *   buckets: array{
     *     overdue_total: float,
     *     due_today: float,
     *     overdue_1_7: float,
     *     overdue_8_14: float,
     *     overdue_15_30: float,
     *     overdue_over_30: float
     *   }
     * }
     */
    public function receivablesSummary(Company $company, ?Carbon $now = null): array
    {
        $now ??= now();
        $today = $now->copy()->startOfDay();

        $overdue = 0.0;
        $onTime = 0.0;
        $dueToday = 0.0;
        $overdue1_7 = 0.0;
        $overdue8_14 = 0.0;
        $overdue15_30 = 0.0;
        $overdueOver30 = 0.0;

        $docs = $company->documents()
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get(['id', 'due_date', 'total', 'paid_amount']);

        foreach ($docs as $doc) {
            $rest = $doc->remainingAmount();
            if ($rest < 0.009) {
                continue;
            }

            $due = $doc->due_date?->copy()->startOfDay();

            if ($due && $due->equalTo($today)) {
                $onTime += $rest;
                $dueToday += $rest;

                continue;
            }

            if ($due && $due->gt($today)) {
                $onTime += $rest;

                continue;
            }

            $overdue += $rest;

            if (! $due) {
                $overdueOver30 += $rest;

                continue;
            }

            $days = (int) $due->diffInDays($today);
            if ($days <= 7) {
                $overdue1_7 += $rest;
            } elseif ($days <= 14) {
                $overdue8_14 += $rest;
            } elseif ($days <= 30) {
                $overdue15_30 += $rest;
            } else {
                $overdueOver30 += $rest;
            }
        }

        $opening = $this->balances->companyOpeningBalancesTotal($company);
        if ($opening > 0.009) {
            $overdue += $opening;
            $overdueOver30 += $opening;
        }

        $total = round($overdue + $onTime, 2);

        return [
            'total' => $total,
            'overdue' => round($overdue, 2),
            'on_time' => round($onTime, 2),
            'buckets' => [
                'overdue_total' => round($overdue, 2),
                'due_today' => round($dueToday, 2),
                'overdue_1_7' => round($overdue1_7, 2),
                'overdue_8_14' => round($overdue8_14, 2),
                'overdue_15_30' => round($overdue15_30, 2),
                'overdue_over_30' => round($overdueOver30, 2),
            ],
        ];
    }

    /**
     * Total scadent azi și în următoarele 7 zile (rest de încasat).
     *
     * @return array{today: float, next_7_days: float}
     */
    public function dueTotals(Company $company, ?Carbon $now = null): array
    {
        $now ??= now();
        $today = $now->toDateString();
        $in7 = $now->copy()->addDays(7)->toDateString();

        $docs = $company->documents()
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $in7])
            ->get(['total', 'paid_amount', 'due_date']);

        $sum = 0.0;
        $todaySum = 0.0;
        foreach ($docs as $doc) {
            $rest = $doc->remainingAmount();
            if ($rest < 0.009) {
                continue;
            }
            $sum += $rest;
            if ($doc->due_date && $doc->due_date->toDateString() === $today) {
                $todaySum += $rest;
            }
        }

        return [
            'today' => round($todaySum, 2),
            'next_7_days' => round($sum, 2),
        ];
    }

    /**
     * @param  array{sort?: string}  $options
     * @return list<array{name: string, total: float, pct: float}>
     */
    public function topClients(Company $company, string $from, string $to, int $limit = 5, array $options = []): array
    {
        $q = $company->documents()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereBetween('issue_date', [$from, $to])
            ->select(['client_name', DB::raw('SUM(total) as total')])
            ->groupBy('client_name');

        if (($options['sort'] ?? 'desc') === 'asc') {
            $q->orderBy(DB::raw('SUM(total)'));
        } else {
            $q->orderByDesc(DB::raw('SUM(total)'));
        }

        $rows = $q->limit($limit)->get();
        $max = max(0.01, (float) $rows->max('total'));

        return $rows->map(fn ($row) => [
            'name' => (string) ($row->client_name ?: '—'),
            'total' => round((float) $row->total, 2),
            'pct' => round(((float) $row->total / $max) * 100, 1),
        ])->values()->all();
    }

    /**
     * @param  array{sort?: string}  $options
     * @return list<array{name: string, balance: float, pct: float}>
     */
    public function topClientBalances(Company $company, int $limit = 5, array $options = []): array
    {
        $rows = $this->balances->balancesAsOf($company, now()->toDateString())
            ->filter(fn (array $row) => $row['balance'] > 0.009);

        $rows = (($options['sort'] ?? 'desc') === 'asc')
            ? $rows->sortBy('balance')
            : $rows->sortByDesc('balance');

        $rows = $rows->take($limit)->values();
        $max = max(0.01, (float) $rows->max('balance'));

        return $rows->map(fn (array $row) => [
            'name' => (string) $row['client']->name,
            'balance' => round((float) $row['balance'], 2),
            'pct' => round(((float) $row['balance'] / $max) * 100, 1),
        ])->values()->all();
    }

    /**
     * Penalități calculate și încă nefacturate, până azi — doar clienți cu sumă > 0, descrescător.
     *
     * @return array{rows: list<array{client_id: int, name: string, amount: float, pct: float}>, total: float}
     */
    public function unbilledPenalties(Company $company, int $limit = 10): array
    {
        return $this->penalties->unbilledRankingForCompany($company, $limit);
    }

    /**
     * @param  array{only_overdue?: bool, ignore_before_enabled?: bool, ignore_before?: ?string}  $options
     * @return list<array{
     *   id: int,
     *   client_name: string,
     *   number_full: string,
     *   issue_date: ?string,
     *   due_date: ?string,
     *   total: float,
     *   remaining: float,
     *   currency: string,
     *   days_overdue: ?int
     * }>
     */
    public function unpaidInvoices(Company $company, int $limit = 8, array $options = []): array
    {
        $today = now()->toDateString();
        $q = $company->documents()
            ->select([
                'id', 'client_name', 'number_full', 'issue_date', 'due_date',
                'total', 'paid_amount', 'currency',
            ])
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial']);

        if (! empty($options['ignore_before_enabled']) && ! empty($options['ignore_before'])) {
            $q->whereDate('issue_date', '>=', $options['ignore_before']);
        }

        if (! empty($options['only_overdue'])) {
            $q->whereNotNull('due_date')->whereDate('due_date', '<', $today);
        }

        return $q->orderByRaw('due_date IS NULL, due_date ASC')
            ->limit($limit * 3)
            ->get()
            ->map(function (Document $doc) use ($today) {
                $remaining = $doc->remainingAmount();
                $dueStr = $doc->due_date?->toDateString();
                $daysOverdue = ($dueStr && $dueStr < $today)
                    ? (int) Carbon::parse($dueStr)->diffInDays(Carbon::parse($today))
                    : null;

                return [
                    'id' => (int) $doc->id,
                    'client_name' => (string) ($doc->client_name ?: '—'),
                    'number_full' => (string) ($doc->number_full ?: ('#'.$doc->id)),
                    'issue_date' => $doc->issue_date?->toDateString(),
                    'due_date' => $dueStr,
                    'total' => round((float) $doc->total, 2),
                    'remaining' => round($remaining, 2),
                    'currency' => $doc->currencyCode(),
                    'days_overdue' => $daysOverdue,
                ];
            })
            ->filter(fn (array $row) => $row['remaining'] > 0.009)
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  array{sort?: string, sort_by?: string}  $options
     * @return list<array{name: string, total: float, pct: float}>
     */
    public function topProducts(Company $company, string $from, string $to, int $limit = 5, array $options = []): array
    {
        $metric = ($options['sort_by'] ?? 'value') === 'qty'
            ? 'SUM(i.quantity)'
            : 'SUM(i.line_total)';

        $q = DB::table('document_items as i')
            ->join('documents as d', 'd.id', '=', 'i.document_id')
            ->where('d.company_id', $company->id)
            ->where('d.type', 'invoice')
            ->where('d.status', 'issued')
            ->whereBetween('d.issue_date', [$from, $to])
            ->groupBy('i.product_id', 'i.name')
            ->select([
                'i.name',
                DB::raw($metric.' as total'),
            ]);

        if (($options['sort'] ?? 'desc') === 'asc') {
            $q->orderBy(DB::raw($metric));
        } else {
            $q->orderByDesc(DB::raw($metric));
        }

        $rows = $q->limit($limit)->get();
        $max = max(0.01, (float) $rows->max('total'));

        return $rows->map(fn ($row) => [
            'name' => (string) ($row->name ?: '—'),
            'total' => round((float) $row->total, 2),
            'pct' => round(((float) $row->total / $max) * 100, 1),
        ])->values()->all();
    }

    /**
     * @return array{
     *   labels: list<string>,
     *   values: list<float>,
     *   count: int,
     *   avg_per_day: float,
     *   total: float
     * }
     */
    public function dailyInvoiceSeries(Company $company, string $from, string $to): array
    {
        $map = $company->documents()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereBetween('issue_date', [$from, $to])
            ->select([
                DB::raw('DATE(issue_date) as d'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as cnt'),
            ])
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        return $this->buildDailySeries($from, $to, $map, 'total', 'cnt');
    }

    /**
     * @return array{
     *   labels: list<string>,
     *   values: list<float>,
     *   count: int,
     *   avg_per_day: float,
     *   total: float
     * }
     */
    public function dailyPaymentSeries(Company $company, string $from, string $to): array
    {
        $map = $company->payments()
            ->whereBetween('paid_at', [$from, $to])
            ->select([
                DB::raw('DATE(paid_at) as d'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as cnt'),
            ])
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        return $this->buildDailySeries($from, $to, $map, 'total', 'cnt');
    }

    /**
     * @param  array{show_issues?: bool, show_payments?: bool}  $options
     * @return list<array{
     *   at: string,
     *   label: string,
     *   detail: string,
     *   user: string,
     *   url: ?string
     * }>
     */
    public function recentActivity(Company $company, int $limit = 8, array $options = []): array
    {
        $showIssues = array_key_exists('show_issues', $options) ? (bool) $options['show_issues'] : true;
        $showPayments = array_key_exists('show_payments', $options) ? (bool) $options['show_payments'] : true;
        $events = collect();

        if ($showIssues) {
            $docs = $company->documents()
                ->where('status', 'issued')
                ->whereNotNull('number_full')
                ->latest('updated_at')
                ->limit($limit * 2)
                ->get(['id', 'type', 'number_full', 'updated_at', 'created_by', 'efactura_status']);

            $userIds = $docs->pluck('created_by')->filter()->unique()->values()->all();
            $names = $userIds === []
                ? collect()
                : User::query()->whereIn('id', $userIds)->pluck('name', 'id');

            foreach ($docs as $doc) {
                $typeLabel = $doc->typeLabel();
                $detail = $doc->number_full;
                if ($doc->efactura_status && ! in_array($doc->efactura_status, ['none', 'not_sent', ''], true)) {
                    $detail .= ' · e-Factura: '.$doc->efactura_status;
                }
                $events->push([
                    'at' => $doc->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                    'label' => __('Emitere :type', ['type' => $typeLabel]),
                    'detail' => $detail,
                    'user' => $doc->created_by ? (string) ($names[$doc->created_by] ?? '—') : '—',
                    'url' => route('documents.show', $doc),
                ]);
            }
        }

        if ($showPayments) {
            $payments = $company->payments()
                ->with('client:id,name')
                ->latest('paid_at')
                ->limit($limit * 2)
                ->get(['id', 'amount', 'paid_at', 'method', 'client_id', 'document_id']);

            foreach ($payments as $payment) {
                $events->push([
                    'at' => $payment->paid_at?->copy()->startOfDay()->toIso8601String()
                        ?? $payment->created_at?->toIso8601String()
                        ?? now()->toIso8601String(),
                    'label' => __('Încasare :method', ['method' => $payment->methodLabel()]),
                    'detail' => number_format((float) $payment->amount, 2, ',', '.').' RON'
                        .($payment->client ? ' · '.$payment->client->name : ''),
                    'user' => '—',
                    'url' => route('payments.index'),
                ]);
            }
        }

        return $events
            ->sortByDesc('at')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Abonamente recurente active cu următoarea emitere programată.
     *
     * @return list<array{
     *   id: int,
     *   title: string,
     *   client_name: string,
     *   document_type_label: string,
     *   frequency_label: string,
     *   next_run_date: string,
     *   days_until: int,
     *   is_due: bool,
     *   auto_issue: bool,
     *   total: float,
     *   currency: string,
     *   url: string
     * }>
     */
    public function upcomingRecurring(Company $company, int $limit = 8): array
    {
        $today = now()->startOfDay();

        return $company->recurringInvoices()
            ->with(['client:id,name', 'items'])
            ->where('active', true)
            ->whereNotNull('next_run_date')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereColumn('next_run_date', '<=', 'end_date');
            })
            ->orderBy('next_run_date')
            ->limit($limit)
            ->get()
            ->map(function (RecurringInvoice $row) use ($today) {
                $next = $row->next_run_date?->copy()->startOfDay();
                $daysUntil = $next ? (int) $today->diffInDays($next, false) : 0;

                return [
                    'id' => (int) $row->id,
                    'title' => $row->displayTitle(),
                    'client_name' => (string) ($row->client?->name ?: '—'),
                    'document_type_label' => $row->documentTypeLabel(),
                    'frequency_label' => $row->frequencyLabel(),
                    'next_run_date' => $next?->toDateString() ?? '',
                    'days_until' => $daysUntil,
                    'is_due' => $next !== null && $next->lte($today),
                    'auto_issue' => (bool) $row->auto_issue,
                    'total' => round($row->estimatedTotal(), 2),
                    'currency' => strtoupper((string) ($row->currency ?: 'RON')),
                    'url' => route('recurring.show', $row),
                ];
            })
            ->values()
            ->all();
    }

    /** Numerar + chitanțe luna curentă (proxy „sold casă”). */
    public function cashMonthTotal(Company $company, ?Carbon $now = null): float
    {
        $now ??= now();
        $by = $this->paymentsByMethod(
            $company,
            $now->copy()->startOfMonth()->toDateString(),
            $now->copy()->endOfMonth()->toDateString(),
        );

        return round($by['cash'], 2);
    }

    /**
     * @param  Collection<int, object>  $map
     * @return array{
     *   labels: list<string>,
     *   values: list<float>,
     *   count: int,
     *   avg_per_day: float,
     *   total: float
     * }
     */
    private function buildDailySeries(string $from, string $to, Collection $map, string $valueKey, string $countKey): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $labels = [];
        $values = [];
        $total = 0.0;
        $count = 0;
        $days = 0;

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $row = $map[$key] ?? null;
            $val = round((float) ($row->{$valueKey} ?? 0), 2);
            $cnt = (int) ($row->{$countKey} ?? 0);

            $labels[] = $cursor->locale('ro')->translatedFormat('j M.');
            $values[] = $val;
            $total += $val;
            $count += $cnt;
            $days++;
            $cursor->addDay();

            if ($days > 62) {
                break;
            }
        }

        $elapsed = max(1, (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1);

        return [
            'labels' => $labels,
            'values' => $values,
            'count' => $count,
            'avg_per_day' => round($total / $elapsed, 2),
            'total' => round($total, 2),
        ];
    }
}
