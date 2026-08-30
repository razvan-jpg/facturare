<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Services\PartnerLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ResolvesApiCompany;

    public function summary(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'reports_view');
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

        $unpaid = $company->documents()
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get(['id', 'total', 'paid_amount']);

        $unpaidTotal = round($unpaid->sum(fn ($d) => max(0, (float) $d->total - (float) $d->paid_amount)), 2);

        return response()->json([
            'from' => $from,
            'to' => $to,
            'sales_total' => $salesTotal,
            'payments_total' => $paymentsTotal,
            'unpaid_total' => $unpaidTotal,
            'documents_count' => (clone $salesBase)->count(),
        ]);
    }

    public function partnerLedger(Request $request, PartnerLedgerService $ledger): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'reports_view');
        $clientId = $request->integer('client_id');
        abort_unless($clientId > 0, 422, 'Selectează un client.');
        $client = $company->clients()->whereKey($clientId)->firstOrFail();
        $from = $request->date('from')?->toDateString() ?: now()->startOfYear()->toDateString();
        $to = $request->date('to')?->toDateString() ?: now()->toDateString();
        $data = $ledger->build($company, $client, $from, $to);

        return response()->json([
            'client' => ['id' => $client->id, 'name' => $client->name],
            'from' => $from,
            'to' => $to,
            'data' => $data,
        ]);
    }

    public function partnersBalance(Request $request, PartnerLedgerService $ledger): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'reports_view');
        $from = $request->date('from')?->toDateString() ?: now()->startOfYear()->toDateString();
        $to = $request->date('to')?->toDateString() ?: now()->toDateString();
        $data = $ledger->buildTertiBalance(
            $company,
            $from,
            $to,
            $request->boolean('hide_zero_sold'),
            $request->boolean('hide_zero_lines'),
        );

        return response()->json([
            'from' => $from,
            'to' => $to,
            'data' => $data,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $company = $this->authorizeAbility($request, 'reports_view');
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

        $filename = 'facturi-'.$company->id.'-'.$from.'-'.$to.'.csv';

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
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
