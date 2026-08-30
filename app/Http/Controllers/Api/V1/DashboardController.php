<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Services\AccessGate;
use App\Services\ClientBalanceService;
use App\Services\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesApiCompany;

    public function show(
        Request $request,
        AccessGate $accessGate,
        ClientBalanceService $balances,
        DashboardMetrics $metrics,
    ): JsonResponse {
        $company = $this->apiCompany($request);
        $user = $request->user();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $today = now()->toDateString();

        $listCols = [
            'id', 'type', 'status', 'number_full', 'issue_date', 'due_date',
            'client_name', 'total', 'paid_amount', 'currency', 'payment_status',
        ];

        $unpaid = $company->documents()
            ->select($listCols)
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        $drafts = $company->documents()
            ->select($listCols)
            ->where('status', 'draft')
            ->latest('id')
            ->limit(8)
            ->get();

        $sales = $metrics->salesAndCollections($company);
        $clientsReceivableToday = $balances->companyReceivableAsOf($company, $today);

        $mapDoc = static function ($d) {
            return [
                'id' => $d->id,
                'type' => $d->type,
                'status' => $d->status,
                'number_full' => $d->number_full,
                'client_name' => $d->client_name,
                'total' => (float) $d->total,
                'remaining' => round(max(0, (float) $d->total - (float) $d->paid_amount), 2),
                'due_date' => optional($d->due_date)?->toDateString(),
                'issue_date' => optional($d->issue_date)?->toDateString(),
                'payment_status' => $d->payment_status,
                'type_label' => method_exists($d, 'typeLabel') ? $d->typeLabel() : $d->type,
            ];
        };

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'cui' => $company->cui,
            ],
            'access_label' => $accessGate->accessLabel($user),
            'stats' => [
                'clients_receivable_today' => round((float) $clientsReceivableToday, 2),
                'invoices_today_total' => $sales['today_total'],
                'invoices_month_count' => $company->documents()
                    ->where('type', 'invoice')
                    ->where('status', 'issued')
                    ->whereBetween('issue_date', [$monthStart, $monthEnd])
                    ->count(),
                'invoices_month_total' => $sales['month_total'],
                'payments_today_total' => $sales['today_paid'],
                'payments_month_total' => $sales['month_paid'],
                'payments_today_by_method' => $sales['today_paid_by_method'],
                'payments_month_by_method' => $sales['month_paid_by_method'],
                'unpaid_count' => $company->documents()
                    ->whereIn('type', ['invoice', 'proforma'])
                    ->where('status', 'issued')
                    ->whereIn('payment_status', ['unpaid', 'partial'])
                    ->count(),
                'drafts_count' => $company->documents()->where('status', 'draft')->count(),
                'clients_count' => $company->clients()->count(),
                'products_count' => $company->products()->count(),
            ],
            'unpaid' => $unpaid->map($mapDoc)->values(),
            'drafts' => $drafts->map($mapDoc)->values(),
            // Păstrat pentru clienți API mai vechi; panoul iOS folosește drafts.
            'recent_documents' => $drafts->map($mapDoc)->values(),
        ]);
    }
}
