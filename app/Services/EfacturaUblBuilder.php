<?php

namespace App\Services;

use App\Models\Document;
use App\Services\MeasureUnitService;
use DOMDocument;
use DOMElement;
use RuntimeException;

class EfacturaUblBuilder
{
    private ?Document $document = null;

    public function build(Document $document): string
    {
        $this->document = $document;
        $document->loadMissing(['items', 'company', 'client']);

        if (! in_array($document->type, ['invoice', 'credit_note'], true)) {
            throw new RuntimeException('Doar facturile, storno și notele de creditare pot fi convertite în UBL e-Factura.');
        }

        $company = $document->company;
        $sellerCui = preg_replace('/\D+/', '', (string) $company->cui);
        if ($sellerCui === '') {
            throw new RuntimeException('Firma emitentă nu are CUI valid pentru e-Factura.');
        }

        $buyerIsPerson = ($document->client?->type === 'person');
        $buyerCui = '';
        $buyerCnp = '';
        if ($buyerIsPerson) {
            $buyerCnp = preg_replace('/\D+/', '', (string) ($document->client?->cnp ?: $document->client_cui)) ?: '';
        } else {
            $buyerCui = preg_replace('/\D+/', '', (string) ($document->client_cui ?: $document->client?->cui)) ?: '';
        }
        $buyerName = $document->client_name ?: $document->client?->name;
        if (blank($buyerName)) {
            throw new RuntimeException('Factura nu are client completat.');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $invoice = $dom->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', 'Invoice');
        $invoice->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:cac',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2'
        );
        $invoice->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:cbc',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'
        );
        $dom->appendChild($invoice);

        $this->el($dom, $invoice, 'cbc:CustomizationID', 'urn:cen.eu:en16931:2017#compliant#urn:efactura.mfinante.ro:CIUS-RO:1.0.1');
        $this->el($dom, $invoice, 'cbc:ProfileID', 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');
        $this->el($dom, $invoice, 'cbc:ID', $document->number_full ?: ('TMP-'.$document->id));
        $this->el($dom, $invoice, 'cbc:IssueDate', $document->issue_date->format('Y-m-d'));

        // BR-CO-16: PayableAmount (BT-115) = TaxInclusiveAmount (BT-112) − PrepaidAmount (BT-113) + Rounding (BT-114).
        $taxInclusive = round((float) $document->total, 2);
        $isCorrective = $document->status === 'storno'
            || $document->type === 'credit_note'
            || $taxInclusive < -0.001;
        // Storno / NC: sume negative valide; nu clamp-uim PayableAmount la 0 (altfel BR-CO-16).
        // Statusul „achitat” din app NU se mapează pe PrepaidAmount la corecții (ar strica ecuația).
        if ($isCorrective) {
            $prepaid = 0.0;
            $payable = round($taxInclusive - $prepaid, 2);
        } else {
            $prepaid = max(0, round((float) ($document->paid_amount ?? 0), 2));
            if ($prepaid > $taxInclusive) {
                $prepaid = $taxInclusive;
            }
            $payable = max(0, round($taxInclusive - $prepaid, 2));
        }
        // BR-CO-25: dacă suma de plată > 0, e obligatoriu DueDate (BT-9) sau PaymentTerms (BT-20).
        $dueDate = $document->due_date;
        if (! $dueDate && $payable > 0.001 && $document->issue_date) {
            $days = max(0, (int) ($document->payment_term ?? 0));
            $dueDate = $document->issue_date->copy()->addDays($days);
        }
        // Pe storno/NC cu PayableAmount ≤ 0 nu e nevoie de scadență (BR-CO-25 e pentru sumă > 0).
        if ($dueDate && ($payable > 0.001 || ! $isCorrective)) {
            $this->el($dom, $invoice, 'cbc:DueDate', $dueDate->format('Y-m-d'));
        }
        $needsPaymentTerms = $payable > 0.001 && ! $dueDate;
        $typeCode = match (true) {
            $document->type === 'credit_note' => '381',
            $document->status === 'storno' => '384',
            default => '380',
        };
        $this->el($dom, $invoice, 'cbc:InvoiceTypeCode', $typeCode);
        if ($document->notes) {
            $this->el($dom, $invoice, 'cbc:Note', $document->notes);
        }
        $currency = strtoupper((string) ($document->currency ?: 'RON'));
        $this->el($dom, $invoice, 'cbc:DocumentCurrencyCode', $currency);
        // BR-RO-030: dacă BT-5 ≠ RON, BT-6 (TaxCurrencyCode) trebuie RON.
        $needsTaxCurrencyRon = $currency !== 'RON';
        if ($needsTaxCurrencyRon) {
            $this->el($dom, $invoice, 'cbc:TaxCurrencyCode', 'RON');
        }

        $document->loadMissing('relatedDocument');
        if ($document->relatedDocument && filled($document->relatedDocument->number_full)) {
            $billingRef = $this->el($dom, $invoice, 'cac:BillingReference');
            $invDocRef = $this->el($dom, $billingRef, 'cac:InvoiceDocumentReference');
            $this->el($dom, $invDocRef, 'cbc:ID', $document->relatedDocument->number_full);
            if ($document->relatedDocument->issue_date) {
                $this->el($dom, $invDocRef, 'cbc:IssueDate', $document->relatedDocument->issue_date->format('Y-m-d'));
            }
        }

        if ($contract = trim((string) ($document->contract_number ?? ''))) {
            $this->el($dom, $this->el($dom, $invoice, 'cac:ContractDocumentReference'), 'cbc:ID', $contract);
        }
        if ($despatch = trim((string) ($document->despatch_advice ?? ''))) {
            $this->el($dom, $this->el($dom, $invoice, 'cac:DespatchDocumentReference'), 'cbc:ID', $despatch);
        }

        $supplier = $this->el($dom, $invoice, 'cac:AccountingSupplierParty');
        $supplierParty = $this->el($dom, $supplier, 'cac:Party');
        // BR-CO-09 + BR-CL-10: prefix RO; schemeID 9947 = Romanian VAT (ISO 6523 ICD).
        $sellerVatId = 'RO'.$sellerCui;
        $sellerId = $this->el($dom, $supplierParty, 'cbc:EndpointID', $sellerVatId);
        $sellerId->setAttribute('schemeID', '9947');
        $this->el($dom, $this->el($dom, $supplierParty, 'cac:PartyIdentification'), 'cbc:ID', $sellerCui);
        $this->el($dom, $this->el($dom, $supplierParty, 'cac:PartyName'), 'cbc:Name', $company->name);
        $sellerAddress = $this->el($dom, $supplierParty, 'cac:PostalAddress');
        $this->el($dom, $sellerAddress, 'cbc:StreetName', $company->address ?: '-');
        $sellerCity = $this->cityNameForUbl(
            (string) ($company->city ?: ''),
            (string) ($company->county ?: ''),
            (string) ($company->address ?: '')
        );
        $this->el($dom, $sellerAddress, 'cbc:CityName', $sellerCity);
        $sellerCountyCode = $this->countyCode((string) ($company->county ?: ''));
        if ($sellerCountyCode !== '') {
            $this->el($dom, $sellerAddress, 'cbc:CountrySubentity', $sellerCountyCode);
        }
        $this->el($dom, $this->el($dom, $sellerAddress, 'cac:Country'), 'cbc:IdentificationCode', 'RO');
        $sellerTax = $this->el($dom, $supplierParty, 'cac:PartyTaxScheme');
        $this->el($dom, $sellerTax, 'cbc:CompanyID', $sellerVatId);
        $this->el($dom, $this->el($dom, $sellerTax, 'cac:TaxScheme'), 'cbc:ID', 'VAT');
        $sellerLegal = $this->el($dom, $supplierParty, 'cac:PartyLegalEntity');
        $this->el($dom, $sellerLegal, 'cbc:RegistrationName', $company->name);
        $this->el($dom, $sellerLegal, 'cbc:CompanyID', $company->reg_com ?: $sellerCui);
        if ($company->email || $company->phone) {
            $contact = $this->el($dom, $supplierParty, 'cac:Contact');
            if ($company->phone) {
                $this->el($dom, $contact, 'cbc:Telephone', $company->phone);
            }
            if ($company->email) {
                $this->el($dom, $contact, 'cbc:ElectronicMail', $company->email);
            }
        }

        $customer = $this->el($dom, $invoice, 'cac:AccountingCustomerParty');
        $customerParty = $this->el($dom, $customer, 'cac:Party');
        $buyerVatId = (! $buyerIsPerson && $buyerCui !== '') ? 'RO'.$buyerCui : '';
        if ($buyerVatId !== '') {
            $buyerId = $this->el($dom, $customerParty, 'cbc:EndpointID', $buyerVatId);
            $buyerId->setAttribute('schemeID', '9947');
            $this->el($dom, $this->el($dom, $customerParty, 'cac:PartyIdentification'), 'cbc:ID', $buyerCui);
        } elseif ($buyerIsPerson && $buyerCnp !== '') {
            $buyerId = $this->el($dom, $customerParty, 'cbc:EndpointID', $buyerCnp);
            $buyerId->setAttribute('schemeID', '9947');
            $this->el($dom, $this->el($dom, $customerParty, 'cac:PartyIdentification'), 'cbc:ID', $buyerCnp);
        } else {
            $buyerId = $this->el($dom, $customerParty, 'cbc:EndpointID', '0000000000000');
            $buyerId->setAttribute('schemeID', '9947');
        }
        $this->el($dom, $this->el($dom, $customerParty, 'cac:PartyName'), 'cbc:Name', $buyerName);
        $buyerAddress = $this->el($dom, $customerParty, 'cac:PostalAddress');
        $client = $document->client;
        $buyerStreet = $client?->address ?: ($document->client_address ?: '-');
        $this->el($dom, $buyerAddress, 'cbc:StreetName', $buyerStreet);
        $buyerCity = $this->cityNameForUbl(
            (string) ($client?->city ?: ''),
            (string) ($client?->county ?: ''),
            (string) $buyerStreet
        );
        $this->el($dom, $buyerAddress, 'cbc:CityName', $buyerCity);
        $buyerCountyCode = $this->countyCode((string) ($client?->county ?: ''));
        if ($buyerCountyCode !== '') {
            $this->el($dom, $buyerAddress, 'cbc:CountrySubentity', $buyerCountyCode);
        }
        $buyerCountry = strtoupper(substr($client?->country ?: 'RO', 0, 2));
        if (in_array(strtolower((string) ($client?->country ?: 'ro')), ['românia', 'romania', 'ro'], true)) {
            $buyerCountry = 'RO';
        }
        $this->el($dom, $this->el($dom, $buyerAddress, 'cac:Country'), 'cbc:IdentificationCode', $buyerCountry);
        if ($buyerVatId !== '') {
            $buyerTax = $this->el($dom, $customerParty, 'cac:PartyTaxScheme');
            $this->el($dom, $buyerTax, 'cbc:CompanyID', $buyerVatId);
            $this->el($dom, $this->el($dom, $buyerTax, 'cac:TaxScheme'), 'cbc:ID', 'VAT');
        }
        $buyerLegal = $this->el($dom, $customerParty, 'cac:PartyLegalEntity');
        $this->el($dom, $buyerLegal, 'cbc:RegistrationName', $buyerName);
        $this->el(
            $dom,
            $buyerLegal,
            'cbc:CompanyID',
            $buyerIsPerson
                ? ($buyerCnp !== '' ? $buyerCnp : 'N/A')
                : ($document->client_reg_com ?: ($buyerCui ?: 'N/A'))
        );

        $invoiceAccounts = $company->invoiceBankAccounts();
        if ($invoiceAccounts->isEmpty() && filled($company->iban)) {
            $paymentMeans = $this->el($dom, $invoice, 'cac:PaymentMeans');
            $this->el($dom, $paymentMeans, 'cbc:PaymentMeansCode', '42');
            $account = $this->el($dom, $paymentMeans, 'cac:PayeeFinancialAccount');
            $this->el($dom, $account, 'cbc:ID', preg_replace('/\s+/', '', (string) $company->iban));
            if ($company->bank_name) {
                $this->el($dom, $this->el($dom, $account, 'cac:FinancialInstitutionBranch'), 'cbc:ID', $company->bank_name);
            }
        } else {
            foreach ($invoiceAccounts as $bankAccount) {
                $paymentMeans = $this->el($dom, $invoice, 'cac:PaymentMeans');
                $this->el($dom, $paymentMeans, 'cbc:PaymentMeansCode', '42');
                $account = $this->el($dom, $paymentMeans, 'cac:PayeeFinancialAccount');
                $this->el($dom, $account, 'cbc:ID', $bankAccount->normalizedIban());
                $bankName = $bankAccount->bank?->name;
                if ($bankName) {
                    $this->el($dom, $this->el($dom, $account, 'cac:FinancialInstitutionBranch'), 'cbc:ID', $bankName);
                }
            }
        }

        if ($needsPaymentTerms) {
            $terms = $this->el($dom, $invoice, 'cac:PaymentTerms');
            $this->el($dom, $terms, 'cbc:Note', 'Plată la emitere');
        }

        $taxGroups = [];
        foreach ($document->items as $item) {
            $rate = number_format((float) $item->vat_rate, 2, '.', '');
            $taxGroups[$rate] ??= ['taxable' => 0.0, 'tax' => 0.0, 'rate' => (float) $item->vat_rate];
            $taxGroups[$rate]['taxable'] += (float) $item->line_subtotal;
            $taxGroups[$rate]['tax'] += (float) $item->line_vat;
        }

        // BR-53 / BT-111: dacă există TaxCurrencyCode, trebuie TaxTotal cu TVA în RON (înaintea celui în valuta facturii).
        if ($needsTaxCurrencyRon) {
            $fx = $this->exchangeRateToRon($document, $currency);
            $vatRon = round((float) $document->vat_total * $fx, 2);
            $taxTotalRon = $this->el($dom, $invoice, 'cac:TaxTotal');
            $this->money($dom, $taxTotalRon, 'cbc:TaxAmount', $vatRon, 'RON');
        }

        $taxTotal = $this->el($dom, $invoice, 'cac:TaxTotal');
        $this->money($dom, $taxTotal, 'cbc:TaxAmount', (float) $document->vat_total, $currency);
        foreach ($taxGroups as $group) {
            $subtotal = $this->el($dom, $taxTotal, 'cac:TaxSubtotal');
            $this->money($dom, $subtotal, 'cbc:TaxableAmount', $group['taxable'], $currency);
            $this->money($dom, $subtotal, 'cbc:TaxAmount', $group['tax'], $currency);
            $category = $this->el($dom, $subtotal, 'cac:TaxCategory');
            $this->el($dom, $category, 'cbc:ID', $group['rate'] > 0 ? 'S' : 'Z');
            $this->el($dom, $category, 'cbc:Percent', number_format($group['rate'], 2, '.', ''));
            $this->el($dom, $this->el($dom, $category, 'cac:TaxScheme'), 'cbc:ID', 'VAT');
        }

        $legal = $this->el($dom, $invoice, 'cac:LegalMonetaryTotal');
        $this->money($dom, $legal, 'cbc:LineExtensionAmount', (float) $document->subtotal, $currency);
        $this->money($dom, $legal, 'cbc:TaxExclusiveAmount', (float) $document->subtotal, $currency);
        $this->money($dom, $legal, 'cbc:TaxInclusiveAmount', $taxInclusive, $currency);
        // BT-113: obligatoriu în ecuația BR-CO-16 când factura e (parțial) achitată.
        if ($prepaid > 0.001) {
            $this->money($dom, $legal, 'cbc:PrepaidAmount', $prepaid, $currency);
        }
        $this->money($dom, $legal, 'cbc:PayableAmount', $payable, $currency);

        foreach ($document->items as $index => $item) {
            $line = $this->el($dom, $invoice, 'cac:InvoiceLine');
            $this->el($dom, $line, 'cbc:ID', (string) ($index + 1));
            $qty = $this->el($dom, $line, 'cbc:InvoicedQuantity', number_format((float) $item->quantity, 2, '.', ''));
            $qty->setAttribute('unitCode', $this->unitCode((string) $item->unit));
            $this->money($dom, $line, 'cbc:LineExtensionAmount', (float) $item->line_subtotal, $currency);
            if ($note = trim((string) data_get($item->details, 'note', ''))) {
                $this->el($dom, $line, 'cbc:Note', $note);
            }
            if ($orderRef = trim((string) data_get($item->details, 'order_reference', ''))) {
                $orderLine = $this->el($dom, $line, 'cac:OrderLineReference');
                $this->el($dom, $orderLine, 'cbc:LineID', $orderRef);
            }
            $periodStart = trim((string) data_get($item->details, 'period_start', ''));
            $periodEnd = trim((string) data_get($item->details, 'period_end', ''));
            if ($periodStart !== '' || $periodEnd !== '') {
                $period = $this->el($dom, $line, 'cac:InvoicePeriod');
                if ($periodStart !== '') {
                    $this->el($dom, $period, 'cbc:StartDate', $periodStart);
                }
                if ($periodEnd !== '') {
                    $this->el($dom, $period, 'cbc:EndDate', $periodEnd);
                }
            }

            $itemNode = $this->el($dom, $line, 'cac:Item');
            if (filled($item->description)) {
                $this->el($dom, $itemNode, 'cbc:Description', (string) $item->description);
            }
            $this->el($dom, $itemNode, 'cbc:Name', $item->name);

            if ($buyersId = trim((string) data_get($item->details, 'buyer_item_id', ''))) {
                $buyers = $this->el($dom, $itemNode, 'cac:BuyersItemIdentification');
                $this->el($dom, $buyers, 'cbc:ID', $buyersId);
            }
            if ($sellersId = trim((string) data_get($item->details, 'sellers_item_id', ''))) {
                $sellers = $this->el($dom, $itemNode, 'cac:SellersItemIdentification');
                $sid = $this->el($dom, $sellers, 'cbc:ID', $sellersId);
                if ($scheme = trim((string) data_get($item->details, 'sellers_item_scheme', ''))) {
                    $sid->setAttribute('schemeID', $scheme);
                }
            }
            if ($stdId = trim((string) data_get($item->details, 'standard_item_id', ''))) {
                $std = $this->el($dom, $itemNode, 'cac:StandardItemIdentification');
                $stid = $this->el($dom, $std, 'cbc:ID', $stdId);
                if ($scheme = trim((string) data_get($item->details, 'standard_item_scheme', ''))) {
                    $stid->setAttribute('schemeID', $scheme);
                }
            }
            foreach (['nc_code' => 'HS', 'cpv_code' => 'CPV'] as $detailKey => $listId) {
                $code = trim((string) data_get($item->details, $detailKey, ''));
                if ($code === '') {
                    continue;
                }
                $cls = $this->el($dom, $itemNode, 'cac:CommodityClassification');
                $cid = $this->el($dom, $cls, 'cbc:ItemClassificationCode', $code);
                $cid->setAttribute('listID', $listId);
            }
            if ($origin = trim((string) data_get($item->details, 'origin_country', ''))) {
                $originNode = $this->el($dom, $itemNode, 'cac:OriginCountry');
                $this->el($dom, $originNode, 'cbc:IdentificationCode', strtoupper($origin));
            }

            $cat = $this->el($dom, $itemNode, 'cac:ClassifiedTaxCategory');
            $this->el($dom, $cat, 'cbc:ID', (float) $item->vat_rate > 0 ? 'S' : 'Z');
            $this->el($dom, $cat, 'cbc:Percent', number_format((float) $item->vat_rate, 2, '.', ''));
            $this->el($dom, $this->el($dom, $cat, 'cac:TaxScheme'), 'cbc:ID', 'VAT');
            $price = $this->el($dom, $line, 'cac:Price');
            // BT-146: prețul unitar rămâne pozitiv; semnul e pe cantitate (storno).
            $this->money($dom, $price, 'cbc:PriceAmount', abs((float) $item->unit_price), $currency);
        }

        return $dom->saveXML() ?: throw new RuntimeException('Nu am putut genera XML-ul UBL.');
    }

    public function isB2c(Document $document): bool
    {
        $cui = preg_replace('/\D+/', '', (string) ($document->client_cui ?: $document->client?->cui));
        $type = $document->client?->type;

        return $cui === '' || $type === 'pf';
    }

    private function el(DOMDocument $dom, DOMElement $parent, string $name, ?string $value = null): DOMElement
    {
        if (str_contains($name, ':')) {
            [$prefix, $local] = explode(':', $name, 2);
            $ns = $prefix === 'cbc'
                ? 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'
                : 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
            $el = $dom->createElementNS($ns, $name);
        } else {
            $el = $dom->createElement($name);
        }

        if ($value !== null) {
            $el->appendChild($dom->createTextNode($value));
        }

        $parent->appendChild($el);

        return $el;
    }

    private function money(DOMDocument $dom, DOMElement $parent, string $name, float $amount, string $currency): DOMElement
    {
        $el = $this->el($dom, $parent, $name, number_format($amount, 2, '.', ''));
        $el->setAttribute('currencyID', strtoupper($currency ?: 'RON'));

        return $el;
    }

    /** Curs RON / 1 unitate valută — din factură, altfel BNR / fallback. */
    private function exchangeRateToRon(Document $document, string $currency): float
    {
        $rate = (float) ($document->exchange_rate ?? 0);
        if ($rate > 0.0001 && strtoupper($currency) !== 'RON') {
            return round($rate, 4);
        }

        try {
            return app(ExchangeRateService::class)->rateToRon($currency);
        } catch (\Throwable) {
            if (strtoupper($currency) === 'EUR') {
                return round((float) config('dateconta.subscription.eur_ron_approx', 5.0), 4);
            }

            return 1.0;
        }
    }

    private function unitCode(string $unit): string
    {
        $company = $this->document?->company;
        if ($company) {
            return app(MeasureUnitService::class)->uneceForXml($company, $unit);
        }

        return \App\Support\MeasureUnits::code($unit);
    }

    /**
     * BR-RO-100: pentru București (RO-B), localitatea trebuie SECTOR1…SECTOR6.
     */
    private function cityNameForUbl(string $city, string $county, string $address = ''): string
    {
        $haystack = mb_strtolower(trim($county.' '.$city.' '.$address));

        if (preg_match('/sector\s*([1-6])/u', $haystack, $m)) {
            return 'SECTOR'.$m[1];
        }

        $city = trim($city);
        $countyCode = $this->countyCode($county);

        // Nu trimitem „București” ca BT-52 când subdiviziunea e RO-B — ANAF respinge (BR-RO-100).
        if ($countyCode === 'RO-B') {
            throw new \RuntimeException(
                'Pentru clienți din București, localitatea trebuie să fie Sector 1–6 (ex. în județ/oraș). Completați sectorul și retrimiteți.'
            );
        }

        return $city !== '' ? $city : '-';
    }

    private function countyCode(string $county): string
    {
        $map = [
            'alba' => 'RO-AB', 'arad' => 'RO-AR', 'arges' => 'RO-AG', 'argeș' => 'RO-AG',
            'bacau' => 'RO-BC', 'bacău' => 'RO-BC', 'bihor' => 'RO-BH', 'bistrita-nasaud' => 'RO-BN',
            'bistrița-năsăud' => 'RO-BN', 'botosani' => 'RO-BT', 'botoșani' => 'RO-BT',
            'braila' => 'RO-BR', 'brăila' => 'RO-BR', 'brasov' => 'RO-BV', 'brașov' => 'RO-BV',
            'bucuresti' => 'RO-B', 'bucurești' => 'RO-B', 'buzau' => 'RO-BZ', 'buzău' => 'RO-BZ',
            'calarasi' => 'RO-CL', 'călărași' => 'RO-CL', 'caras-severin' => 'RO-CS',
            'caraș-severin' => 'RO-CS', 'cluj' => 'RO-CJ', 'constanta' => 'RO-CT', 'constanța' => 'RO-CT',
            'covasna' => 'RO-CV', 'dambovita' => 'RO-DB', 'dâmbovița' => 'RO-DB', 'dolj' => 'RO-DJ',
            'galati' => 'RO-GL', 'galați' => 'RO-GL', 'giurgiu' => 'RO-GR', 'gorj' => 'RO-GJ',
            'harghita' => 'RO-HR', 'hunedoara' => 'RO-HD', 'ialomita' => 'RO-IL', 'ialomița' => 'RO-IL',
            'iasi' => 'RO-IS', 'iași' => 'RO-IS', 'ilfov' => 'RO-IF', 'maramures' => 'RO-MM',
            'maramureș' => 'RO-MM', 'mehedinti' => 'RO-MH', 'mehedinți' => 'RO-MH', 'mures' => 'RO-MS',
            'mureș' => 'RO-MS', 'neamt' => 'RO-NT', 'neamț' => 'RO-NT', 'olt' => 'RO-OT',
            'prahova' => 'RO-PH', 'salaj' => 'RO-SJ', 'sălaj' => 'RO-SJ', 'satu mare' => 'RO-SM',
            'sibiu' => 'RO-SB', 'suceava' => 'RO-SV', 'teleorman' => 'RO-TR', 'timis' => 'RO-TM',
            'timiș' => 'RO-TM', 'tulcea' => 'RO-TL', 'valcea' => 'RO-VL', 'vâlcea' => 'RO-VL',
            'vaslui' => 'RO-VS', 'vrancea' => 'RO-VN',
            'sector 1' => 'RO-B', 'sector 2' => 'RO-B', 'sector 3' => 'RO-B',
            'sector 4' => 'RO-B', 'sector 5' => 'RO-B', 'sector 6' => 'RO-B',
            'bucuresti - sector 1' => 'RO-B', 'bucurești - sector 1' => 'RO-B',
            'bucuresti - sector 2' => 'RO-B', 'bucurești - sector 2' => 'RO-B',
            'bucuresti - sector 3' => 'RO-B', 'bucurești - sector 3' => 'RO-B',
            'bucuresti - sector 4' => 'RO-B', 'bucurești - sector 4' => 'RO-B',
            'bucuresti - sector 5' => 'RO-B', 'bucurești - sector 5' => 'RO-B',
            'bucuresti - sector 6' => 'RO-B', 'bucurești - sector 6' => 'RO-B',
        ];

        $key = mb_strtolower(trim($county));
        if (str_contains($key, 'sector') || str_contains($key, 'bucure')) {
            return $map[$key] ?? 'RO-B';
        }

        return $map[$key] ?? $county;
    }
}
