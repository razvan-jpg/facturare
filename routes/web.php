<?php

use App\Http\Controllers\AdminCompaniesController;
use App\Http\Controllers\AdminIntegrationsController;
use App\Http\Controllers\AdminPromoMailController;
use App\Http\Controllers\AdminStatsController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\AdminSubscriptionOrdersController;
use App\Http\Controllers\AnafOAuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardWidgetController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentPayController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\MobileWebLoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UiLocaleController;
use Illuminate\Support\Facades\Route;

$communityStats = static function (): array {
    try {
        return [
            'users' => (int) \App\Models\User::query()->count(),
            'companies' => (int) \App\Models\Company::query()->count(),
            // Aceeași fereastră „online” ca în statistici admin (ultimele 5 minute).
            'visitors' => (int) \App\Models\VisitorSession::query()
                ->where('last_seen_at', '>=', now()->subMinutes(5))
                ->count(),
        ];
    } catch (\Throwable) {
        return ['users' => 0, 'companies' => 0, 'visitors' => 0];
    }
};

Route::get('/', function () use ($communityStats) {
    $stats = $communityStats();

    return view('landing', [
        'activeUsersCount' => $stats['users'],
        'companiesCount' => $stats['companies'],
        'activeVisitorsCount' => $stats['visitors'],
    ]);
})->name('home');

// Limbă UI pentru vizitatori (session) și utilizatori autentificați.
Route::post('/ui-locale', [UiLocaleController::class, 'update'])
    ->middleware('throttle:60,1')
    ->name('ui-locale.update');

Route::get('/comunitate-stats', function () use ($communityStats) {
    return response()
        ->json($communityStats())
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->name('community.stats');
Route::get('/preturi', function (\App\Services\ExchangeRateService $fx) {
    $eurRon = null;
    $fxLabel = null;

    try {
        $eurRon = $fx->rateToRon('EUR');
        $fxLabel = 'BNR';
    } catch (\Throwable) {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)
                ->get('https://api.frankfurter.app/latest', ['from' => 'EUR', 'to' => 'RON']);
            $rate = (float) data_get($response->json(), 'rates.RON', 0);
            if ($response->successful() && $rate > 0) {
                $eurRon = round($rate, 4);
                $fxLabel = 'piață, aproximativ';
            }
        } catch (\Throwable) {
            // ignore — fallback below
        }
    }

    if (! $eurRon) {
        $eurRon = (float) config('dateconta.subscription.eur_ron_approx', 5.0);
        $fxLabel = 'aproximativ';
    }

    return view('pricing', [
        'eurRon' => $eurRon,
        'fxLabel' => $fxLabel,
    ]);
})->name('pricing');

Route::get('/legal', [LegalController::class, 'index'])->name('legal.index');
Route::get('/legal/{page}', [LegalController::class, 'show'])->name('legal.show')
    ->where('page', 'termeni|confidentialitate|livrare|anulare|gdpr');

Route::get('/intrebari-frecvente', [PublicContentController::class, 'faq'])->name('faq');
Route::redirect('/faq', '/intrebari-frecvente', 301);
Route::get('/ghid/{slug}', [PublicContentController::class, 'guide'])->name('guides.show')
    ->where('slug', 'e-factura|proforma-vs-factura');

Route::get('/auth/mobile-login', MobileWebLoginController::class)
    ->middleware('throttle:20,1')
    ->name('auth.mobile-login');


Route::view('/lansare', 'launch')->name('launch');
Route::view('/lansare/email', 'emails.launch')->name('launch.email');

Route::get('/anaf/oauth/callback', [AnafOAuthController::class, 'callback'])->name('anaf.oauth.callback');
Route::get('/anaf/invite/{token}', [AnafOAuthController::class, 'inviteStart'])->name('anaf.invite');

Route::get('/documents/{document}/pdf/signed', [DocumentController::class, 'pdfSigned'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('documents.pdf.signed');

Route::get('/plata/{document}', [DocumentPayController::class, 'show'])
    ->middleware(['signed', 'throttle:60,1'])
    ->name('documents.pay.show');
Route::get('/plata/{document}/{processor}', [DocumentPayController::class, 'start'])
    ->middleware(['signed', 'throttle:30,1'])
    ->whereIn('processor', ['netopia', 'euplatesc', 'mollie', 'stripe'])
    ->name('documents.pay.start');
Route::match(['get', 'post'], '/plata-return/{checkout}', [DocumentPayController::class, 'returnPage'])
    ->middleware('throttle:60,1')
    ->name('documents.pay.return');

// Callback-uri abonament DateConta (FLY DAVID / platformă)
Route::post('/billing/netopia/confirm', [BillingController::class, 'netopiaConfirm'])
    ->name('billing.netopia.confirm');
Route::post('/billing/mollie/webhook', [BillingController::class, 'mollieWebhook'])
    ->name('billing.mollie.webhook');
Route::post('/billing/euplatesc/silent', [BillingController::class, 'euplatescSilent'])
    ->name('billing.euplatesc.silent');
Route::post('/billing/stripe/webhook', [BillingController::class, 'stripeWebhook'])
    ->name('billing.stripe.webhook');

// Callback-uri încasare facturi clienți (chei per firmă) — aceleași handlere, URL-uri distincte
Route::post('/plata/netopia/confirm', [BillingController::class, 'netopiaConfirm'])
    ->name('plata.netopia.confirm');
Route::post('/plata/mollie/webhook', [BillingController::class, 'mollieWebhook'])
    ->name('plata.mollie.webhook');
Route::post('/plata/euplatesc/silent', [BillingController::class, 'euplatescSilent'])
    ->name('plata.euplatesc.silent');
Route::post('/plata/stripe/webhook', [BillingController::class, 'stripeWebhook'])
    ->name('plata.stripe.webhook');

// Return Netopia: GET/POST fără auth — payload-ul IPN pe return trebuie procesat chiar dacă sesiunea a expirat.
Route::match(['get', 'post'], '/billing/netopia/return/{order}', [BillingController::class, 'netopiaReturn'])
    ->middleware('throttle:60,1')
    ->name('billing.netopia.return');

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/billing/expired', [BillingController::class, 'expired'])->name('billing.expired');
    Route::get('/billing/comanda/{company}', [BillingController::class, 'order'])->name('billing.order');
    Route::post('/billing/comanda/{company}', [BillingController::class, 'placeOrder'])->name('billing.order.place');
    Route::get('/billing/locuri/{company}', [BillingController::class, 'seatsOrder'])->name('billing.seats');
    Route::post('/billing/locuri/{company}', [BillingController::class, 'placeSeatsOrder'])->name('billing.seats.place');
    Route::get('/billing/op/{order}', [BillingController::class, 'opPending'])->name('billing.op');
    Route::get('/billing/mollie/return/{order}', [BillingController::class, 'mollieReturn'])->name('billing.mollie.return');
    Route::get('/billing/euplatesc/return/{order}', [BillingController::class, 'euplatescReturn'])->name('billing.euplatesc.return');
    Route::get('/billing/stripe/return/{order}', [BillingController::class, 'stripeReturn'])->name('billing.stripe.return');
    Route::get('/billing/succes/{order}', [BillingController::class, 'success'])->name('billing.success');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

Route::middleware(['auth', 'auth.session', 'subscription', 'company'])->group(function () {
    Route::get('/utilizatori', [CompanyUserController::class, 'index'])->name('company-users.index');
    Route::get('/utilizatori/nou', [CompanyUserController::class, 'create'])->name('company-users.create');
    Route::get('/utilizatori/lookup', [CompanyUserController::class, 'lookup'])->name('company-users.lookup');
    Route::post('/utilizatori', [CompanyUserController::class, 'store'])->name('company-users.store');
    Route::get('/utilizatori/{user}', [CompanyUserController::class, 'edit'])->name('company-users.edit');
    Route::put('/utilizatori/{user}', [CompanyUserController::class, 'update'])->name('company-users.update');
    Route::delete('/utilizatori/{user}', [CompanyUserController::class, 'destroy'])->name('company-users.destroy');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::put('/companies/{company}/integrations/{processor}', [CompanyController::class, 'updateIntegrations'])
        ->whereIn('processor', ['netopia', 'euplatesc', 'mollie', 'stripe'])
        ->name('companies.integrations.update');
    Route::post('/companies/{company}/switch', [CompanyController::class, 'switch'])->name('companies.switch');
    Route::post('/companies/{company}/anaf/extend', [CompanyController::class, 'extendAnaf'])->name('companies.anaf.extend');
    Route::post('/companies/{company}/anaf/revoke', [CompanyController::class, 'revokeAnaf'])->name('companies.anaf.revoke');
    Route::post('/companies/{company}/efactura/invite', [CompanyController::class, 'inviteEfactura'])->name('companies.efactura.invite');
    Route::post('/companies/{company}/referral-recommend', [CompanyController::class, 'sendReferralRecommend'])
        ->middleware('throttle:10,1')
        ->name('companies.referral-recommend');
    Route::post('/companies/{company}/reminders/overdue', [CompanyController::class, 'runOverdueReminders'])->name('companies.reminders.overdue');
    Route::post('/companies/{company}/branches', [CompanyController::class, 'storeBranch'])->name('companies.branches.store');
    Route::delete('/companies/{company}/branches/{branch}', [CompanyController::class, 'destroyBranch'])->name('companies.branches.destroy');
    Route::post('/companies/{company}/series', [CompanyController::class, 'storeSeries'])->name('companies.series.store');
    Route::put('/companies/{company}/series/{series}', [CompanyController::class, 'updateSeries'])->name('companies.series.update');
    Route::delete('/companies/{company}/series/{series}', [CompanyController::class, 'destroySeries'])->name('companies.series.destroy');
    Route::post('/companies/{company}/series/decizie', [CompanyController::class, 'seriesDecision'])->name('companies.series.decision');
    Route::get('/anaf/oauth/redirect/{company}', [AnafOAuthController::class, 'redirect'])->name('anaf.oauth.redirect');
    Route::post('/anaf/lookup', [CompanyController::class, 'lookup'])
        ->middleware('throttle:30,1')
        ->name('anaf.lookup');

    Route::get('/in-curand/{feature?}', function (?string $feature = null) {
        $labels = [
            'bon-fiscal' => 'Bon fiscal',
            'voucher' => 'Voucher',
            'preluare-facturi-furnizori' => 'Preluare facturi furnizori',
            'cheltuiala' => 'Cheltuială',
            'plata-factura-furnizor' => 'Plată factură furnizor',
            'adauga-e-transport' => 'Adaugă e-Transport',
            'raport-e-transport' => 'Raport e-Transport',
            'preluare-e-facturi-primite' => 'Preluare e-Facturi primite',
            'case-de-marcat' => 'Case de marcat',
        ];
        $title = $labels[$feature ?? ''] ?? 'Funcționalitate în curând';

        return view('coming-soon', compact('title', 'feature'));
    })->name('coming-soon');

    Route::middleware('company')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/dashboard/widgets', [DashboardWidgetController::class, 'store'])->name('dashboard.widgets.store');
        Route::post('/dashboard/widgets/reorder', [DashboardWidgetController::class, 'reorder'])->name('dashboard.widgets.reorder');
        Route::post('/dashboard/widgets/{widget}/configure', [DashboardWidgetController::class, 'configure'])->name('dashboard.widgets.configure');
        Route::delete('/dashboard/widgets/{widget}', [DashboardWidgetController::class, 'destroy'])->name('dashboard.widgets.destroy');
        Route::post('/dashboard/widgets/reset', [DashboardWidgetController::class, 'reset'])->name('dashboard.widgets.reset');

        Route::post('/clients/quick', [ClientController::class, 'quickStore'])->name('clients.quick');
        Route::post('/clients/anaf-sync', [ClientController::class, 'syncAnafBulk'])
            ->middleware('throttle:3,1')
            ->name('clients.anaf-sync');
        Route::get('/clients/opening-balances', [ClientController::class, 'openingBalancesEdit'])
            ->name('clients.opening-balances.edit');
        Route::post('/clients/opening-balances', [ClientController::class, 'openingBalancesUpdate'])
            ->name('clients.opening-balances.update');
        Route::get('/clients/{client}/fisa.pdf', [ClientController::class, 'statementPdf'])
            ->name('clients.statement.pdf');
        Route::patch('/clients/{client}/penalty-billing', [ClientController::class, 'updatePenaltyBilling'])
            ->name('clients.penalty-billing');
        Route::resource('clients', ClientController::class);
        Route::post('/products/quick', [ProductController::class, 'quickStore'])->name('products.quick');
        Route::resource('products', ProductController::class)->except(['show']);

        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::get('/documents/corrections/{kind}', [DocumentController::class, 'createCorrection'])
            ->whereIn('kind', ['storno', 'credit_note'])
            ->name('documents.corrections.create');
        Route::post('/documents/corrections/{kind}', [DocumentController::class, 'storeCorrection'])
            ->whereIn('kind', ['storno', 'credit_note'])
            ->name('documents.corrections.store');
        Route::get('/documents/fx-rate', [DocumentController::class, 'fxRate'])->name('documents.fx-rate');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::post('/documents/efactura/send-bulk', [DocumentController::class, 'sendEfacturaBulk'])->name('documents.efactura.send-bulk');
        Route::post('/documents/email-bulk', [DocumentController::class, 'emailBulk'])->name('documents.email-bulk');
        Route::post('/documents/efactura/xml-export', [DocumentController::class, 'exportEfacturaXml'])->name('documents.efactura.xml-export');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::post('/documents/{document}/issue', [DocumentController::class, 'issue'])->name('documents.issue');
        Route::post('/documents/{document}/reserve-number', [DocumentController::class, 'reserveNumber'])->name('documents.reserve-number');
        Route::post('/documents/{document}/release-number', [DocumentController::class, 'releaseNumber'])->name('documents.release-number');
        Route::post('/documents/{document}/touch-number', [DocumentController::class, 'touchNumber'])->name('documents.touch-number');
        Route::post('/documents/{document}/cancel', [DocumentController::class, 'cancel'])->name('documents.cancel');
        Route::post('/documents/{document}/storno', [DocumentController::class, 'storno'])->name('documents.storno');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/documents/{document}/pdf', [DocumentController::class, 'pdf'])->name('documents.pdf');
        Route::post('/documents/{document}/email', [DocumentController::class, 'email'])->name('documents.email');
        Route::post('/documents/{document}/efactura/send', [DocumentController::class, 'sendEfactura'])->name('documents.efactura.send');
        Route::post('/documents/{document}/efactura/refresh', [DocumentController::class, 'refreshEfactura'])->name('documents.efactura.refresh');

        Route::get('/recurring', [RecurringInvoiceController::class, 'index'])->name('recurring.index');
        Route::get('/recurring/create', [RecurringInvoiceController::class, 'create'])->name('recurring.create');
        Route::post('/recurring', [RecurringInvoiceController::class, 'store'])->name('recurring.store');
        Route::get('/recurring/{recurring}', [RecurringInvoiceController::class, 'show'])->name('recurring.show');
        Route::get('/recurring/{recurring}/edit', [RecurringInvoiceController::class, 'edit'])->name('recurring.edit');
        Route::put('/recurring/{recurring}', [RecurringInvoiceController::class, 'update'])->name('recurring.update');
        Route::delete('/recurring/{recurring}', [RecurringInvoiceController::class, 'destroy'])->name('recurring.destroy');
        Route::post('/recurring/{recurring}/toggle', [RecurringInvoiceController::class, 'toggle'])->name('recurring.toggle');
        Route::post('/recurring/{recurring}/generate', [RecurringInvoiceController::class, 'generateNow'])->name('recurring.generate');
        Route::get('/recurring/{recurring}/preview.pdf', [RecurringInvoiceController::class, 'previewNext'])->name('recurring.preview');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::get('/payments/unpaid-invoices', [PaymentController::class, 'unpaidInvoices'])->name('payments.unpaid-invoices');
        Route::post('/payments/collect', [PaymentController::class, 'collect'])->name('payments.collect');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/clients', [ReportController::class, 'clients'])->name('reports.clients');
        Route::get('/reports/clients/fisa-partener', [ReportController::class, 'partnerLedger'])
            ->name('reports.clients.partner');
        Route::get('/reports/clients/fisa-partener.pdf', [ReportController::class, 'partnerLedgerPdf'])
            ->name('reports.clients.partner-pdf');
        Route::get('/reports/clients/balanta-parteneri', [ReportController::class, 'partnersBalance'])
            ->name('reports.clients.balance');
        Route::get('/reports/clients/balanta-parteneri.pdf', [ReportController::class, 'partnersBalancePdf'])
            ->name('reports.clients.balance-pdf');
        Route::get('/reports/unpaid', [ReportController::class, 'unpaid'])->name('reports.unpaid');
        Route::get('/reports/product-sales', [ReportController::class, 'productSales'])->name('reports.product-sales');
        Route::get('/reports/receivables-by-client', [ReportController::class, 'receivablesByClient'])->name('reports.receivables-by-client');
        Route::get('/reports/client-statement', [ReportController::class, 'clientStatement'])->name('reports.client-statement');
        Route::get('/reports/vat', [ReportController::class, 'vat'])->name('reports.vat');
        Route::get('/reports/sales-by-agent', [ReportController::class, 'salesByAgent'])->name('reports.sales-by-agent');
        Route::get('/reports/charts/invoices', [ReportController::class, 'chartInvoices'])->name('reports.charts.invoices');
        Route::get('/reports/charts/clients', [ReportController::class, 'chartClients'])->name('reports.charts.clients');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::get('/ajutor', [HelpController::class, 'index'])->name('help.index');
    Route::get('/ajutor/ce-este-nou', [HelpController::class, 'whatsNew'])->name('help.whats-new');
    Route::get('/ajutor/{section}', [HelpController::class, 'show'])->name('help.show')
        ->where('section', '^(?!ce-este-nou$).+');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->group(function () {
        Route::get('/admin/statistici', AdminStatsController::class)->name('admin.stats');
        Route::get('/admin/utilizatori', [AdminUsersController::class, 'index'])->name('admin.users');
        Route::get('/admin/utilizatori/{user}', [AdminUsersController::class, 'show'])
            ->name('admin.users.show');
        Route::post('/admin/utilizatori/{user}/firme/{company}/intrare', [AdminUsersController::class, 'enterCompany'])
            ->name('admin.users.enter-company');
        Route::post('/admin/utilizatori/{user}/firme', [AdminUsersController::class, 'attachCompany'])
            ->name('admin.users.attach-company');
        Route::delete('/admin/utilizatori/{user}/firme/{company}', [AdminUsersController::class, 'detachCompany'])
            ->name('admin.users.detach-company');
        Route::delete('/admin/utilizatori/{user}', [AdminUsersController::class, 'destroy'])
            ->name('admin.users.destroy');
        Route::get('/admin/societati', [AdminCompaniesController::class, 'index'])->name('admin.companies');
        Route::post('/admin/societati/{company}/promo', [AdminCompaniesController::class, 'grantPromo'])
            ->name('admin.companies.grant');
        Route::get('/admin/comenzi', [AdminSubscriptionOrdersController::class, 'index'])->name('admin.orders');
        Route::post('/admin/comenzi/{order}/confirm-op', [AdminSubscriptionOrdersController::class, 'confirmOp'])
            ->name('admin.orders.confirm-op');
        Route::post('/admin/comenzi/emite-facturi-lipsa', [AdminSubscriptionOrdersController::class, 'issueMissingInvoices'])
            ->name('admin.orders.issue-missing-invoices');
        Route::post('/admin/mail-reclama', [AdminPromoMailController::class, 'send'])
            ->middleware('throttle:10,1')
            ->name('admin.promo-mail');
        Route::get('/admin/integrari/{processor}', [AdminIntegrationsController::class, 'show'])
            ->whereIn('processor', ['netopia', 'euplatesc', 'mollie', 'stripe'])
            ->name('admin.integrari.show');
        Route::put('/admin/integrari/{processor}', [AdminIntegrationsController::class, 'update'])
            ->whereIn('processor', ['netopia', 'euplatesc', 'mollie', 'stripe'])
            ->name('admin.integrari.update');
    });
});

require __DIR__.'/auth.php';
