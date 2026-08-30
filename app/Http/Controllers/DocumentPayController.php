<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\CardProcessors;
use App\Services\DocumentCardPaymentService;
use App\Services\MolliePaymentService;
use App\Services\NetopiaPaymentService;
use App\Services\StripePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentPayController extends Controller
{
    public function show(
        Request $request,
        Document $document,
        DocumentCardPaymentService $payments,
        CardProcessors $processors,
    ): View|RedirectResponse {
        abort_unless($request->hasValidSignature(), 403);

        $document->loadMissing('company');

        if ($document->payment_status === 'paid' || $document->remainingAmount() <= 0.009) {
            return view('documents.pay-done', [
                'document' => $document,
                'alreadyPaid' => true,
            ]);
        }

        if (! $document->allow_card_payment || ! $processors->anyActive($document->company)) {
            abort(404, 'Plata cu cardul nu este disponibilă pentru acest document.');
        }

        return view('documents.pay', [
            'document' => $document,
            'processors' => $processors->active($document->company),
            'links' => $payments->paymentLinks($document),
            'amount' => $document->remainingAmount(),
        ]);
    }

    public function start(
        Request $request,
        Document $document,
        string $processor,
        DocumentCardPaymentService $payments,
    ): View|RedirectResponse {
        abort_unless($request->hasValidSignature(), 403);

        try {
            $result = $payments->start($document, $processor);
        } catch (\Throwable $e) {
            return redirect()->to($payments->hubUrl($document) ?: url('/'))
                ->with('warning', $e->getMessage());
        }

        if ($result['type'] === 'redirect') {
            $isStripe = $processor === 'stripe';

            return view('documents.pay-gateway-redirect', [
                'type' => 'redirect',
                'checkoutUrl' => $result['checkoutUrl'],
                'title' => $isStripe ? 'Redirecționare Stripe…' : 'Redirecționare Mollie…',
                'message' => $isStripe
                    ? 'Te redirecționăm către plata Stripe…'
                    : 'Te redirecționăm către plata Mollie…',
            ]);
        }

        $isEp = $processor === 'euplatesc';

        return view('documents.pay-gateway-redirect', [
            'type' => 'form',
            'form' => $result['form'],
            'title' => $isEp ? 'Redirecționare Eu Plătesc…' : 'Redirecționare NETOPIA…',
            'message' => $isEp
                ? 'Te redirecționăm către plata Eu Plătesc…'
                : 'Te redirecționăm către plata NETOPIA…',
        ]);
    }

    public function returnPage(
        Request $request,
        string $checkout,
        DocumentCardPaymentService $payments,
        MolliePaymentService $mollie,
        StripePaymentService $stripe,
        NetopiaPaymentService $netopia,
    ): View {
        $model = $payments->findByCheckoutNumber($checkout);
        abort_unless($model, 404);

        if ($model->processor === 'mollie' && $model->mollie_payment_id) {
            try {
                $mollie->handleWebhook($model->mollie_payment_id);
            } catch (\Throwable) {
                // pagina de return trebuie să se afișeze oricum
            }
            $model->refresh();
        }

        if ($model->processor === 'stripe') {
            try {
                $sessionId = (string) $request->query('session_id', $model->external_ref ?? '');
                if ($sessionId !== '') {
                    $stripe->syncDocumentSession($sessionId, $model);
                }
            } catch (\Throwable) {
                // pagina de return trebuie să se afișeze oricum
            }
            $model->refresh();
        }

        if ($model->processor === 'netopia') {
            try {
                $model = $netopia->syncDocumentCheckout($model, $request);
            } catch (\Throwable) {
                // pagina de return trebuie să se afișeze oricum
            }
            $model->refresh();
        }

        $document = $model->document()->with('company')->firstOrFail();

        return view('documents.pay-done', [
            'document' => $document,
            'checkout' => $model->fresh(),
            'alreadyPaid' => $document->payment_status === 'paid' || $model->isPaid(),
        ]);
    }
}
