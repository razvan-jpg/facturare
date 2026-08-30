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

        return Pdf::loadView($document->company->invoicePdfView(), [
            'document' => $document,
            'locale' => $locale,
            'labels' => $labels,
            'cardPaymentLinks' => $cardPaymentLinks,
            'cardPaymentHubUrl' => $cardPaymentHubUrl,
            'logo' => $document->company->logoDataUri(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    public function output(Document $document): string
    {
        return $this->make($document)->output();
    }
}
