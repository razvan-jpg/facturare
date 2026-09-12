<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use Illuminate\Support\Collection;

class ClientBalanceService
{
    public function __construct(private readonly RonEquivalentService $ron) {}

    /**
     * Facturi emise neachitate/parțiale cu rest > 0.
     *
     * @return Collection<int, Document>
     */
    public function openInvoices(Client $client): Collection
    {
        return Document::query()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Document $d) => $d->remainingAmount() > 0.009)
            ->values();
    }

    public function openingBalance(Client $client): float
    {
        return round((float) ($client->opening_balance ?? 0), 2);
    }

    /**
     * Încasări nealocate pe facturi (document_id null) — în echivalent RON.
     */
    public function unallocatedPaymentsTotal(Client $client): float
    {
        $payments = Payment::query()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->whereNull('document_id')
            ->get(['id', 'amount', 'currency', 'paid_at', 'document_id']);

        $total = 0.0;
        foreach ($payments as $payment) {
            $ron = $this->ron->fromPayment($payment);
            if ($ron !== null) {
                $total += $ron;
            }
        }

        return round($total, 2);
    }

    public function remainingOpeningBalance(Client $client): float
    {
        return round(max(0, $this->openingBalance($client) - $this->unallocatedPaymentsTotal($client)), 2);
    }

    public function openInvoicesRemaining(Client $client, ?Collection $openInvoices = null): float
    {
        $invoices = $openInvoices ?? $this->openInvoices($client);
        $total = 0.0;
        foreach ($invoices as $doc) {
            $ron = $this->ron->fromDocument($doc, $doc->remainingAmount());
            if ($ron !== null) {
                $total += $ron;
            }
        }

        return round($total, 2);
    }

    public function currentBalance(Client $client, ?Collection $openInvoices = null): float
    {
        return round(
            $this->openingBalance($client)
            + $this->openInvoicesRemaining($client, $openInvoices)
            - $this->unallocatedPaymentsTotal($client),
            2
        );
    }

    /**
     * @param  Collection<int, Client>  $clients
     * @return array<int, float> client_id => rest facturi în RON
     */
    public function openRemainingByClientIds(Company $company, Collection $clients): array
    {
        $ids = $clients->pluck('id')->filter()->all();
        if ($ids === []) {
            return [];
        }

        $docs = Document::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $ids)
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get(['id', 'client_id', 'total', 'paid_amount', 'currency', 'exchange_rate', 'issue_date']);

        $out = array_fill_keys($ids, 0.0);
        foreach ($docs as $doc) {
            $rest = $doc->remainingAmount();
            if ($rest > 0.009) {
                $ron = $this->ron->fromDocument($doc, $rest);
                if ($ron !== null) {
                    $out[(int) $doc->client_id] = round($out[(int) $doc->client_id] + $ron, 2);
                }
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, Client>  $clients
     * @return array<int, float> client_id => încasări nealocate în RON
     */
    public function unallocatedByClientIds(Company $company, Collection $clients): array
    {
        $ids = $clients->pluck('id')->filter()->all();
        if ($ids === []) {
            return [];
        }

        $payments = Payment::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $ids)
            ->whereNull('document_id')
            ->get(['id', 'client_id', 'amount', 'currency', 'paid_at', 'document_id']);

        $out = array_fill_keys($ids, 0.0);
        foreach ($payments as $payment) {
            $ron = $this->ron->fromPayment($payment);
            if ($ron !== null) {
                $cid = (int) $payment->client_id;
                $out[$cid] = round($out[$cid] + $ron, 2);
            }
        }

        return $out;
    }

    public function companyOpeningBalancesTotal(Company $company): float
    {
        $clients = $company->clients()->get(['id', 'company_id', 'opening_balance']);
        if ($clients->isEmpty()) {
            return 0.0;
        }

        $unallocated = $this->unallocatedByClientIds($company, $clients);
        $total = 0.0;
        foreach ($clients as $client) {
            $opening = round((float) ($client->opening_balance ?? 0), 2);
            $rest = round(max(0, $opening - ($unallocated[$client->id] ?? 0.0)), 2);
            $total += $rest;
        }

        return round($total, 2);
    }

    /**
     * Solduri clienți la o dată — toate sumele în echivalent RON (curs document / BNR).
     *
     * @param  list<int>  $exceptDocumentIds  Documente excluse (ex. factura curentă + proforma sursă pe PDF)
     * @return Collection<int, array{
     *   client: Client,
     *   opening: float,
     *   invoices: float,
     *   balance: float
     * }>
     */
    public function balancesAsOf(
        Company $company,
        string $asOfDate,
        ?int $clientId = null,
        array $exceptDocumentIds = [],
    ): Collection {
        $clientsQuery = $company->clients()->orderBy('name');
        if ($clientId) {
            $clientsQuery->where('id', $clientId);
        }
        $clients = $clientsQuery->get();
        if ($clients->isEmpty()) {
            return collect();
        }

        $clientIds = $clients->pluck('id')->all();
        $exceptDocumentIds = array_values(array_unique(array_filter(array_map('intval', $exceptDocumentIds))));

        $invoices = Document::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $clientIds)
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereDate('issue_date', '<=', $asOfDate)
            ->when($exceptDocumentIds !== [], fn ($q) => $q->whereNotIn('id', $exceptDocumentIds))
            ->get(['id', 'client_id', 'type', 'total', 'currency', 'exchange_rate', 'issue_date']);

        // Proforme deja convertite în factură fiscală (emisă până la data soldului):
        // obligația e pe factură — nu dublăm soldul.
        $proformaIds = $invoices->where('type', 'proforma')->pluck('id')->all();
        $convertedProformaIds = [];
        if ($proformaIds !== []) {
            $convertedProformaIds = Document::query()
                ->where('company_id', $company->id)
                ->whereIn('related_document_id', $proformaIds)
                ->where('type', 'invoice')
                ->whereIn('status', ['issued', 'storno'])
                ->whereDate('issue_date', '<=', $asOfDate)
                ->pluck('related_document_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $convertedProformaIds = array_fill_keys($convertedProformaIds, true);
        }

        $invoiceIds = $invoices
            ->reject(fn (Document $d) => isset($convertedProformaIds[(int) $d->id]))
            ->pluck('id')
            ->all();
        $paidByDoc = [];
        if ($invoiceIds !== []) {
            $paidRows = Payment::query()
                ->where('company_id', $company->id)
                ->whereIn('document_id', $invoiceIds)
                ->whereDate('paid_at', '<=', $asOfDate)
                ->selectRaw('document_id, SUM(amount) as paid')
                ->groupBy('document_id')
                ->get();
            foreach ($paidRows as $row) {
                $paidByDoc[(int) $row->document_id] = (float) $row->paid;
            }
        }

        $invoiceRestRonByClient = array_fill_keys($clientIds, 0.0);
        foreach ($invoices as $inv) {
            if (isset($convertedProformaIds[(int) $inv->id])) {
                continue;
            }
            $paid = $paidByDoc[(int) $inv->id] ?? 0.0;
            $rest = max(0, round((float) $inv->total - $paid, 2));
            if ($rest > 0.009) {
                $ron = $this->ron->fromDocument($inv, $rest);
                if ($ron !== null) {
                    $cid = (int) $inv->client_id;
                    $invoiceRestRonByClient[$cid] = round($invoiceRestRonByClient[$cid] + $ron, 2);
                }
            }
        }

        $orphanRonByClient = array_fill_keys($clientIds, 0.0);
        $orphanPayments = Payment::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $clientIds)
            ->whereNull('document_id')
            ->whereDate('paid_at', '<=', $asOfDate)
            ->get(['id', 'client_id', 'amount', 'currency', 'paid_at', 'document_id']);
        foreach ($orphanPayments as $payment) {
            $ron = $this->ron->fromPayment($payment);
            if ($ron !== null) {
                $cid = (int) $payment->client_id;
                $orphanRonByClient[$cid] = round($orphanRonByClient[$cid] + $ron, 2);
            }
        }

        return $clients->map(function (Client $client) use ($asOfDate, $invoiceRestRonByClient, $orphanRonByClient) {
            $openingRegistered = $this->openingBalanceAsOf($client, $asOfDate);
            $orphansRon = (float) ($orphanRonByClient[$client->id] ?? 0);
            $invoicesRon = (float) ($invoiceRestRonByClient[$client->id] ?? 0);

            $openingRemaining = round(max(0, $openingRegistered - min($openingRegistered, $orphansRon)), 2);
            $orphanExcess = round(max(0, $orphansRon - $openingRegistered), 2);
            $balanceRon = round($openingRemaining + max(0, $invoicesRon - $orphanExcess), 2);

            return [
                'client' => $client,
                'opening' => $openingRemaining,
                'invoices' => $invoicesRon,
                'balance' => $balanceRon,
            ];
        })->values();
    }

    public function openingBalanceAsOf(Client $client, string $asOfDate): float
    {
        $amount = round((float) ($client->opening_balance ?? 0), 2);
        if (abs($amount) < 0.009) {
            return 0.0;
        }

        $dateStr = $client->effectiveOpeningBalanceDate();
        if ($dateStr > $asOfDate) {
            return 0.0;
        }

        return $amount;
    }

    /**
     * @param  list<int>  $exceptDocumentIds
     */
    public function balanceAsOfForClient(Client $client, string $asOfDate, array $exceptDocumentIds = []): float
    {
        $row = $this->balancesAsOf($client->company, $asOfDate, $client->id, $exceptDocumentIds)->first();

        return round((float) ($row['balance'] ?? 0), 2);
    }

    public function companyReceivableAsOf(Company $company, ?string $asOfDate = null): float
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        $rows = $this->balancesAsOf($company, $asOfDate);

        return round((float) $rows->sum('balance'), 2);
    }
}
