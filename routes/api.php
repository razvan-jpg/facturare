<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CompanyUserController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\EfacturaController;
use App\Http\Controllers\Api\V1\IosSubscriptionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RecurringInvoiceController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\WebSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // Legal public (fără autentificare).
    Route::get('legal', [ContentController::class, 'legalIndex']);
    Route::get('legal/{page}', [ContentController::class, 'legalShow'])
        ->where('page', 'termeni|confidentialitate|livrare|anulare|gdpr');

    // App Store Server Notifications V2 (fără Sanctum — verifică JWS Apple).
    Route::post('ios/subscription/notifications', [IosSubscriptionController::class, 'notifications']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('web-session', [WebSessionController::class, 'store']);

        Route::get('help', [ContentController::class, 'helpIndex']);
        Route::get('help/ce-este-nou', [ContentController::class, 'helpWhatsNew']);
        Route::get('help/{section}', [ContentController::class, 'helpShow']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::delete('profile', [ProfileController::class, 'destroy']);

        Route::get('ios/subscription/status', [IosSubscriptionController::class, 'status']);
        Route::post('ios/subscription/verify', [IosSubscriptionController::class, 'verify']);

        Route::get('companies', [CompanyController::class, 'index']);
        Route::post('companies', [CompanyController::class, 'store']);
        Route::post('companies/anaf-lookup', [CompanyController::class, 'anafLookup']);
        Route::post('companies/{company}/switch', [CompanyController::class, 'switch']);
        Route::delete('companies/{company}/leave', [CompanyController::class, 'leave']);

        Route::get('company-users', [CompanyUserController::class, 'index']);
        Route::post('company-users', [CompanyUserController::class, 'store']);
        Route::put('company-users/{user}/permissions', [CompanyUserController::class, 'updatePermissions']);

        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('stats', [AdminController::class, 'stats']);
            Route::get('companies', [AdminController::class, 'companies']);
            Route::post('promo-mail', [AdminController::class, 'sendPromoMail']);
        });

        Route::middleware(['api.subscription', 'api.company'])->group(function () {
            Route::get('dashboard', [DashboardController::class, 'show']);

            Route::get('sync', [SyncController::class, 'pull']);
            Route::post('sync/push', [SyncController::class, 'push']);

            Route::get('companies/{company}', [CompanyController::class, 'show']);
            Route::put('companies/{company}', [CompanyController::class, 'update']);
            Route::post('companies/{company}/referral-recommend', [CompanyController::class, 'sendReferralRecommend']);
            Route::get('series', [CompanyController::class, 'series']);
            Route::post('series', [CompanyController::class, 'storeSeries']);
            Route::put('series/{series}', [CompanyController::class, 'updateSeries']);
            Route::delete('series/{series}', [CompanyController::class, 'destroySeries']);

            Route::get('clients', [ClientController::class, 'index']);
            Route::post('clients', [ClientController::class, 'store']);
            Route::get('clients/{client}', [ClientController::class, 'show']);
            Route::put('clients/{client}', [ClientController::class, 'update']);
            Route::delete('clients/{client}', [ClientController::class, 'destroy']);
            Route::post('clients/anaf-lookup', [ClientController::class, 'anafLookup']);

            Route::get('products', [ProductController::class, 'index']);
            Route::post('products', [ProductController::class, 'store']);
            Route::get('products/{product}', [ProductController::class, 'show']);
            Route::put('products/{product}', [ProductController::class, 'update']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);

            Route::get('documents', [DocumentController::class, 'index']);
            Route::post('documents', [DocumentController::class, 'store']);
            Route::get('documents/{document}', [DocumentController::class, 'show']);
            Route::put('documents/{document}', [DocumentController::class, 'update']);
            Route::post('documents/{document}/issue', [DocumentController::class, 'issue']);
            Route::post('documents/{document}/reserve-number', [DocumentController::class, 'reserveNumber']);
            Route::get('documents/{document}/available-numbers', [DocumentController::class, 'availableNumbers']);
            Route::post('documents/{document}/release-number', [DocumentController::class, 'releaseNumber']);
            Route::post('documents/{document}/touch-number', [DocumentController::class, 'touchNumber']);
            Route::post('documents/{document}/cancel', [DocumentController::class, 'cancel']);
            Route::delete('documents/{document}', [DocumentController::class, 'destroy']);
            Route::post('documents/{document}/storno', [DocumentController::class, 'storno']);
            Route::post('documents/{document}/credit-note', [DocumentController::class, 'creditNote']);
            Route::get('documents/{document}/pdf', [DocumentController::class, 'pdf']);
            Route::post('documents/{document}/efactura/send', [DocumentController::class, 'sendEfactura']);
            Route::post('documents/{document}/efactura/refresh', [DocumentController::class, 'refreshEfactura']);

            Route::get('payments', [PaymentController::class, 'index']);
            Route::post('payments', [PaymentController::class, 'store']);
            Route::post('payments/collect', [PaymentController::class, 'collect']);

            Route::get('recurring', [RecurringInvoiceController::class, 'index']);
            Route::post('recurring', [RecurringInvoiceController::class, 'store']);
            Route::get('recurring/{recurring}', [RecurringInvoiceController::class, 'show']);
            Route::put('recurring/{recurring}', [RecurringInvoiceController::class, 'update']);
            Route::delete('recurring/{recurring}', [RecurringInvoiceController::class, 'destroy']);
            Route::post('recurring/{recurring}/toggle', [RecurringInvoiceController::class, 'toggle']);
            Route::post('recurring/{recurring}/generate', [RecurringInvoiceController::class, 'generateNow']);

            Route::get('reports/summary', [ReportController::class, 'summary']);
            Route::get('reports/partner-ledger', [ReportController::class, 'partnerLedger']);
            Route::get('reports/partners-balance', [ReportController::class, 'partnersBalance']);
            Route::get('reports/export', [ReportController::class, 'export']);

            Route::get('efactura/status', [EfacturaController::class, 'status']);
            Route::get('efactura/oauth-url', [EfacturaController::class, 'oauthUrl']);
        });
    });
});
