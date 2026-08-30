<?php

namespace App\Http\Controllers;

use App\Services\AccessGate;
use App\Services\AdvancedReportService;
use App\Services\CompanyContext;
use App\Services\DashboardLayout;
use App\Services\DashboardMetrics;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        CompanyContext $companyContext,
        AccessGate $accessGate,
        AdvancedReportService $reports,
        DashboardMetrics $metrics,
        DashboardLayout $layout,
    ): View {
        $company = $companyContext->current();
        $user = auth()->user();
        $now = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $settings = $layout->allSettings($user);
        $topClientsOpts = $settings['top_clients'] ?? [];
        $topProductsOpts = $settings['top_products'] ?? [];
        $unpaidOpts = $settings['unpaid'] ?? [];
        $balancesOpts = $settings['client_balances'] ?? [];
        $activityOpts = $settings['activity'] ?? [];
        $cashOpts = $settings['cash'] ?? [];

        $sales = $metrics->salesAndCollections($company, $now);
        $receivables = $metrics->receivablesSummary($company, $now);
        $dueTotals = $metrics->dueTotals($company, $now);
        $dailySales = $metrics->dailyInvoiceSeries($company, $monthStart, $monthEnd);
        $dailyPayments = $metrics->dailyPaymentSeries($company, $monthStart, $monthEnd);

        $draftCount = $company->documents()->where('status', 'draft')->count();
        $widgetKeys = $layout->forUser($user);
        $catalog = $layout->catalog($user);
        $maxSlots = $layout->maxSlots();

        $unbilledPenalties = in_array('unbilled_penalties', $widgetKeys, true)
            ? $metrics->unbilledPenalties($company, 10)
            : [];

        return view('dashboard', [
            'company' => $company,
            'accessLabel' => $accessGate->accessLabel($user),
            'sales' => $sales,
            'receivables' => $receivables,
            'dueTotals' => $dueTotals,
            'unpaidTotal' => $reports->unpaidTotal($company),
            'unpaidInvoices' => $metrics->unpaidInvoices($company, 8, $unpaidOpts),
            'topClients' => $metrics->topClients($company, $monthStart, $monthEnd, 5, $topClientsOpts),
            'topProducts' => $metrics->topProducts($company, $monthStart, $monthEnd, 5, $topProductsOpts),
            'topClientBalances' => $metrics->topClientBalances($company, 5, $balancesOpts),
            'unbilledPenalties' => $unbilledPenalties,
            'activity' => $metrics->recentActivity($company, 8, $activityOpts),
            'upcomingRecurring' => $metrics->upcomingRecurring($company, 8),
            'cashMonth' => $metrics->cashMonthTotal($company, $now),
            'cashCurrency' => strtoupper((string) ($cashOpts['currency'] ?? 'RON')),
            'dailySales' => $dailySales,
            'dailyPayments' => $dailyPayments,
            'draftCount' => $draftCount,
            'monthLabel' => Carbon::parse($monthStart)->locale('ro')->translatedFormat('F Y'),
            'widgetKeys' => $widgetKeys,
            'widgetSettings' => $settings,
            'widgetCatalog' => $catalog,
            'widgetCategories' => config('dashboard.categories', []),
            'widgetCategoryCounts' => $layout->categoryCounts(),
            'dashboardSlotsUsed' => count($widgetKeys),
            'dashboardSlotsMax' => $maxSlots,
            'dashboardFull' => count($widgetKeys) >= $maxSlots,
        ]);
    }
}