<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionOrder;
use App\Services\SubscriptionInvoiceService;
use App\Services\SubscriptionOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSubscriptionOrdersController extends Controller
{
    public function index(Request $request, SubscriptionInvoiceService $invoices): View
    {
        try {
            $invoices->ensureSchema();
        } catch (\Throwable) {
            // continue without invoice column UI
        }

        $hasInvoiceCol = \Illuminate\Support\Facades\Schema::hasColumn('subscription_orders', 'invoice_document_id');

        $status = (string) $request->query('status', 'awaiting_op');
        $allowed = ['awaiting_op', 'paid', 'pending', 'failed', 'all'];
        if (! in_array($status, $allowed, true)) {
            $status = 'awaiting_op';
        }

        $with = ['company:id,name,cui,promo_code', 'user:id,name,email'];
        if ($hasInvoiceCol) {
            $with[] = 'invoiceDocument:id,number_full';
        }

        $orders = SubscriptionOrder::query()
            ->with($with)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($status === 'all', fn ($q) => $q->whereIn('status', ['awaiting_op', 'paid', 'pending', 'failed', 'cancelled']))
            ->orderByRaw("CASE WHEN status = 'awaiting_op' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        $awaitingCount = SubscriptionOrder::query()->where('status', 'awaiting_op')->count();
        $missingInvoiceCount = $hasInvoiceCol
            ? SubscriptionOrder::query()->where('status', 'paid')->whereNull('invoice_document_id')->count()
            : SubscriptionOrder::query()->where('status', 'paid')->count();

        return view('admin.orders', compact('orders', 'status', 'awaitingCount', 'missingInvoiceCount'));
    }

    public function confirmOp(SubscriptionOrder $order, SubscriptionOrderService $orders): RedirectResponse
    {
        if ($order->payment_method !== 'op') {
            return back()->with('warning', 'Comanda '.$order->number.' nu este plată OP.');
        }

        if ($order->isPaid()) {
            return back()->with('status', 'Comanda '.$order->number.' era deja confirmată.');
        }

        if ($order->status !== 'awaiting_op') {
            return back()->with('warning', 'Comanda '.$order->number.' nu e în așteptare OP (status: '.$order->status.').');
        }

        $paid = $orders->markPaid($order, 'OP-ADMIN-'.auth()->id());
        $paid->loadMissing('invoiceDocument');

        $msg = 'Plată OP confirmată pentru '.$paid->number.' — abonament activat până la '.dc_date($paid->access_until_after).'.';
        if ($paid->invoiceDocument) {
            $msg .= ' Factură '.$paid->invoiceDocument->number_full.' emisă și trimisă pe email.';
        }

        return back()->with('status', $msg);
    }

    public function issueMissingInvoices(SubscriptionInvoiceService $invoices): RedirectResponse
    {
        $result = $invoices->issueMissing(100);
        $msg = 'Facturi emise: '.$result['issued'].'.';
        if ($result['errors'] !== []) {
            $msg .= ' Erori: '.implode(' | ', array_slice($result['errors'], 0, 5));

            return back()->with('warning', $msg);
        }

        return back()->with('status', $msg);
    }
}
