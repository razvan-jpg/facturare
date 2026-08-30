<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use Illuminate\Support\Collection;

class ClientBalanceService
{
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
     * Încasări nealocate pe facturi (document_id null) — se aplică întâi pe soldul inițial.
     */
    public function unallocatedPaymentsTotal(Client $client): float
    {
        return round((float) Payment::query()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->whereNull('document_id')
            ->sum('amount'), 2);
    }

    /**
     * Rest din soldul inițial încă de încasat (după încasările libere / pe sold).
     */
    public function remainingOpeningBalance(Client $client): float
    {
        return round(max(0, $this->openingBalance($client) - $this->unallocatedPaymentsTotal($client)), 2);
    }

    public function openInvoicesRemaining(Client $client, ?Collection $openInvoices = null): float
    {
        $invoices = $openInvoices ?? $this->openInvoices($client);

        return round($invoices->sum(fn (Document $d) => $d->remainingAmount()), 2);
    }

    /**
     * Sold real = sold inițial + rest facturi − încasări nealocate pe facturi.
     */
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
     * @return array<int, float> client_id => rest facturi
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
            ->get(['id', 'client_id', 'total', 'paid_amount']);

        $out = array_fill_keys($ids, 0.0);
        foreach ($docs as $doc) {
            $rest = $doc->remainingAmount();
            if ($rest > 0.009) {
                $cid = (int) $doc->client_id;
                $out[$cid] = round(($out[$cid] ?? 0) + $rest, 2);
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, Client>  $clients
     * @return array<int, float> client_id => sumă plăți fără factură
     */
    public function unallocatedByClientIds(Company $company, Collection $clients): array
    {
        $ids = $clients->pluck('id')->filter()->all();
        if ($ids === []) {
            return [];
        }

        $rows = Payment::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $ids)
            ->whereNull('document_id')
            ->selectRaw('client_id, SUM(amount) as paid')
            ->groupBy('client_id')
            ->get();

        $out = array_fill_keys($ids, 0.0);
        foreach ($rows as $row) {
            $out[(int) $row->client_id] = round((float) $row->paid, 2);
        }

        return $out;
    }

    /** Suma resturilor de sold inițial (> 0) pentru Neîncasat global. */
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
     * Solduri clienți la o dată (sold inițial aplicabil + rest facturi emise până la dată,
     * ținând cont de plățile cu paid_at ≤ dată, inclusiv încasările pe sold inițial).
     *
     * @return Collection<int, array{client: Client, opening: float, invoices: float, balance: float}>
     */
    public function balancesAsOf(Company $company, string $asOfDate, ?int $clientId = null): Collection
    {
        $clientsQuery = $company->clients()->orderBy('name');
        if ($clientId) {
            $clientsQuery->where('id', $clientId);
        }
        $clients = $clientsQuery->get();
        if ($clients->isEmpty()) {
            return collect();
        }

        $clientIds = $clients->pluck('id')->all();

        $invoices = Document::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $clientIds)
            ->whereIn('type', ['invoice', 'proforma'])
            ->where('status', 'issued')
            ->whereDate('issue_date', '<=', $asOfDate)
            ->get(['id', 'client_id', 'total']);

        $invoiceIds = $invoices->pluck('id')->all();
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

        $invoiceRestByClient = array_fill_keys($clientIds, 0.0);
        foreach ($invoices as $inv) {
            $paid = $paidByDoc[(int) $inv->id] ?? 0.0;
            $rest = max(0, round((float) $inv->total - $paid, 2));
            if ($rest > 0.009) {
                $cid = (int) $inv->client_id;
                $invoiceRestByClient[$cid] = round(($invoiceRestByClient[$cid] ?? 0) + $rest, 2);
            }
        }

        $orphanByClient = array_fill_keys($clientIds, 0.0);
        $orphanRows = Payment::query()
            ->where('company_id', $company->id)
            ->whereIn('client_id', $clientIds)
            ->whereNull('document_id')
            ->whereDate('paid_at', '<=', $asOfDate)
            ->selectRaw('client_id, SUM(amount) as paid')
            ->groupBy('client_id')
            ->get();
        foreach ($orphanRows as $row) {
            $orphanByClient[(int) $row->client_id] = round((float) $row->paid, 2);
        }

        return $clients->map(function (Client $client) use ($asOfDate, $invoiceRestByClient, $orphanByClient) {
            $openingRegistered = $this->openingBalanceAsOf($client, $asOfDate);
            $orphans = round((float) ($orphanByClient[$client->id] ?? 0), 2);
            $opening = round(max(0, $openingRegistered - $orphans), 2);
            $orphanExcess = round(max(0, $orphans - $openingRegistered), 2);
            $invoicesRest = round((float) ($invoiceRestByClient[$client->id] ?? 0), 2);
            $balance = round($opening + $invoicesRest - $orphanExcess, 2);

            return [
                'client' => $client,
                'opening' => $opening,
                'invoices' => $invoicesRest,
                'balance' => $balance,
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

    /** Total de încasat de la toți clienții la data dată (implicit azi). */
    public function companyReceivableAsOf(Company $company, ?string $asOfDate = null): float
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        $rows = $this->balancesAsOf($company, $asOfDate);

        return round($rows->sum(fn (array $row) => $row['balance']), 2);
    }
}
