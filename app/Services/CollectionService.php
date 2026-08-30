<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CollectionService
{
    public const CASH_DAILY_LIMIT_RON = 5000.0;

    public function __construct(
        private DocumentService $documents,
        private ClientBalanceService $balances,
    ) {}

    /**
     * Suma chitanțelor emise + încasări numerar libere pe client în ziua dată (RON).
     */
    public function cashCollectedToday(Company $company, int $clientId, Carbon|string $date): float
    {
        $day = Carbon::parse($date)->toDateString();

        $fromReceipts = (float) $company->documents()
            ->where('type', 'receipt')
            ->where('status', 'issued')
            ->where('client_id', $clientId)
            ->whereDate('issue_date', $day)
            ->where('currency', 'RON')
            ->sum('total');

        // Plăți numerar/chitanță fără factură (nu dublăm plățile alocate create odată cu chitanța).
        $orphanCash = (float) $company->payments()
            ->where('client_id', $clientId)
            ->whereIn('method', ['receipt', 'cash'])
            ->whereDate('paid_at', $day)
            ->where('currency', 'RON')
            ->whereNull('document_id')
            ->where(function ($q) {
                $q->whereNull('reference')->orWhere('reference', '');
            })
            ->sum('amount');

        return round($fromReceipts + $orphanCash, 2);
    }

    /**
     * @param  array<int, int>  $invoiceIds
     * @return array{receipt: ?Document, payments: array<int, Payment>}
     */
    public function collect(
        Company $company,
        User $user,
        Client $client,
        string $instrument,
        float $amount,
        string $paidAt,
        string $currency,
        string $reprezentand,
        ?string $series,
        string $documentLanguage,
        array $invoiceIds = [],
        bool $applyOpening = true,
    ): array {
        $instrument = $instrument === 'op' ? 'op' : 'receipt';
        $amount = round($amount, 2);
        $currency = strtoupper($currency);
        $paidAt = Carbon::parse($paidAt)->toDateString();

        if ($amount < 0.01) {
            throw ValidationException::withMessages(['amount' => 'Valoarea trebuie să fie cel puțin 0,01.']);
        }

        if ($currency === 'RON' && $amount > self::CASH_DAILY_LIMIT_RON) {
            $instrument = 'op';
        }

        if ($instrument === 'receipt' && $currency === 'RON') {
            $already = $this->cashCollectedToday($company, (int) $client->id, $paidAt);
            if ($already + $amount > self::CASH_DAILY_LIMIT_RON + 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Chitanța nu poate depăși %.0f RON / client / zi. Deja încasat azi: %s RON. Folosește OP sau reduce suma.',
                        self::CASH_DAILY_LIMIT_RON,
                        number_format($already, 2, ',', '.')
                    ),
                ]);
            }
        }

        $invoices = collect();
        if ($invoiceIds !== []) {
            $invoices = $company->documents()
                ->whereIn('type', ['invoice', 'proforma'])
                ->where('status', 'issued')
                ->where('client_id', $client->id)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereIn('id', $invoiceIds)
                ->orderBy('due_date')
                ->orderBy('id')
                ->get();

            if ($invoices->count() !== count(array_unique(array_map('intval', $invoiceIds)))) {
                throw ValidationException::withMessages([
                    'invoice_ids' => 'Unele documente selectate nu sunt valabile pentru acest client.',
                ]);
            }
        }

        return DB::transaction(function () use (
            $company, $user, $client, $instrument, $amount, $paidAt, $currency,
            $reprezentand, $series, $documentLanguage, $invoices, $applyOpening
        ) {
            $receipt = null;
            $method = $instrument === 'op' ? 'op' : 'receipt';
            $reference = null;

            if ($instrument === 'receipt') {
                $this->documents->ensureDefaultSeries($company);
                $lineName = filled($reprezentand)
                    ? Str::limit($reprezentand, 255, '')
                    : 'Încasare';

                $receipt = $this->documents->createDraft($company, $user, 'receipt', [
                    'client_id' => $client->id,
                    'issue_date' => $paidAt,
                    'due_date' => $paidAt,
                    'currency' => $currency,
                    'exchange_rate' => 1,
                    'series' => $series,
                    'document_language' => $documentLanguage ?: 'ro',
                    'notes' => $reprezentand ?: null,
                ], [[
                    'name' => $lineName,
                    'description' => $reprezentand ?: null,
                    'unit' => 'buc',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'vat_rate' => 0,
                ]]);

                $receipt = $this->documents->issue($receipt->fresh('items'));
                $reference = $receipt->number_full;
            }

            $payments = [];
            $remainingToAllocate = $amount;

            // 1) Întâi soldul inițial rămas (încasare fără factură), dacă e activ.
            $openingDue = $applyOpening ? $this->balances->remainingOpeningBalance($client) : 0.0;
            if ($openingDue >= 0.01 && $remainingToAllocate >= 0.01) {
                $payOpening = round(min($openingDue, $remainingToAllocate), 2);
                $openingNote = $reprezentand !== ''
                    ? $reprezentand
                    : 'sold inițial';
                $payments[] = Payment::create([
                    'company_id' => $company->id,
                    'document_id' => null,
                    'client_id' => $client->id,
                    'method' => $method,
                    'paid_at' => $paidAt,
                    'amount' => $payOpening,
                    'currency' => $currency,
                    'reference' => $reference,
                    'notes' => $openingNote,
                ]);
                $remainingToAllocate = round($remainingToAllocate - $payOpening, 2);
            }

            // 2) Apoi facturile / proformele selectate (FIFO după scadență).
            if ($invoices->isNotEmpty()) {
                foreach ($invoices as $invoice) {
                    if ($remainingToAllocate < 0.01) {
                        break;
                    }
                    $due = round($invoice->remainingAmount(), 2);
                    if ($due < 0.01) {
                        continue;
                    }
                    $pay = round(min($due, $remainingToAllocate), 2);

                    // Proformă încasată integral → factură fiscală + plată pe factură.
                    if ($invoice->type === 'proforma' && $pay + 0.009 >= $due) {
                        $fiscal = $this->documents->issueInvoiceFromPaidProforma(
                            $invoice,
                            $paidAt,
                            $pay,
                            (string) ($reference ?? ''),
                            $reprezentand !== '' ? $reprezentand : 'Încasare proformă',
                            $method,
                        );
                        $created = $fiscal->payments()->latest('id')->first();
                        if ($created) {
                            $payments[] = $created;
                        }
                        $remainingToAllocate = round($remainingToAllocate - $pay, 2);

                        continue;
                    }

                    $payments[] = Payment::create([
                        'company_id' => $company->id,
                        'document_id' => $invoice->id,
                        'client_id' => $client->id,
                        'method' => $method,
                        'paid_at' => $paidAt,
                        'amount' => $pay,
                        'currency' => $currency,
                        'reference' => $reference,
                        'notes' => $reprezentand ?: null,
                    ]);
                    $invoice->refreshPaymentStatus();
                    $remainingToAllocate = round($remainingToAllocate - $pay, 2);
                }
            }

            // 3) Surplus (avans) — tot ca încasare liberă.
            if ($remainingToAllocate >= 0.01) {
                $payments[] = Payment::create([
                    'company_id' => $company->id,
                    'document_id' => null,
                    'client_id' => $client->id,
                    'method' => $method,
                    'paid_at' => $paidAt,
                    'amount' => $remainingToAllocate,
                    'currency' => $currency,
                    'reference' => $reference,
                    'notes' => $reprezentand ?: null,
                ]);
            }

            return ['receipt' => $receipt, 'payments' => $payments];
        });
    }
}
