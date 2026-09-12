<?php

namespace App\Services;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Facades\App;

class InvoicePdfService
{
    public function make(Document $document): DomPdf
    {
        $document->loadMissing(['items', 'company', 'client']);

        $locale = $document->documentLocale();
        $previous = App::getLocale();
        App::setLocale($locale);
        $labels = trans('invoice');
        if (! is_array($labels)) {
            $labels = [];
        }
        App::setLocale($previous);

        $cardPaymentLinks = [];
        $cardPaymentHubUrl = null;
        if ($document->allow_card_payment) {
            $cardPay = app(DocumentCardPaymentService::class);
            $cardPaymentLinks = $cardPay->paymentLinks($document);
            $cardPaymentHubUrl = $cardPay->hubUrl($document);
        }

        $clientBalanceAsOf = null;
        $clientBalanceAsOfDate = null;
        if (
            $document->client
            && $document->client->showsOutstandingBalanceOnInvoice()
            && $document->status === 'issued'
            && in_array($document->type, ['invoice', 'proforma'], true)
            && $document->issue_date
        ) {
            // Soldul pe PDF = anterior facturii curente (la data emiterii − 1 zi).
            // Excludem documentul curent și proforma sursă (aceeași obligație).
            $clientBalanceAsOfDate = $document->issue_date->copy()->subDay()->toDateString();
            $exceptIds = array_values(array_filter([
                (int) $document->id,
                $document->related_document_id ? (int) $document->related_document_id : null,
            ]));
            $clientBalanceAsOf = app(ClientBalanceService::class)->balanceAsOfForClient(
                $document->client,
                $clientBalanceAsOfDate,
                $exceptIds
            );
        }

        $isReceiptPdf = $document->type === 'receipt';
        $view = $isReceiptPdf
            ? 'documents.pdf.receipt'
            : $document->company->invoicePdfView();

        return Pdf::loadView($view, [
            'document' => $document,
            'locale' => $locale,
            'labels' => $labels,
            'cardPaymentLinks' => $cardPaymentLinks,
            'cardPaymentHubUrl' => $cardPaymentHubUrl,
            'logo' => $document->company->logoDataUri(),
            'clientBalanceAsOf' => $clientBalanceAsOf,
            'clientBalanceAsOfDate' => $clientBalanceAsOfDate,
            'isReceiptPdf' => $isReceiptPdf,
        ])
            // Chitanțele: A5 peisaj (mai compacte la tipărire); restul rămân A4 portret.
            ->setPaper($isReceiptPdf ? 'a5' : 'a4', $isReceiptPdf ? 'landscape' : 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    public function output(Document $document): string
    {
        return $this->make($document)->output();
    }
}
