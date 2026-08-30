<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientPenaltyCharge;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Payment;
use App\Models\RecurringInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Penalități cf. contract: calcul pe zi pe principal restant (fără compounding pe linii de tip penalizare).
 * Facturare pe următoarea factură doar dacă clients.penalty_billing_enabled = ON.
 */
class ClientPenaltyService
{
    public const OPENING_DUE_DATE = '2026-08-11';

    public function __construct(
        private ClientBalanceService $balances,
    ) {}

    public function percentFor(Client $client): float
    {
        return round((float) ($client->penalty_percent ?? 0), 4);
    }

    public function isBillingEnabled(Client $client): bool
    {
        return (bool) ($client->penalty_billing_enabled ?? false) && $this->percentFor($client) > 0;
    }

    public function canAccrue(Client $client): bool
    {
        return $this->percentFor($client) > 0;
    }

    /**
     * Total fără linii de tip penalizare (include TVA pe liniile normale).
     */
    public function principalForDocument(Document $document): float
    {
        $document->loadMissing('items');
        $items = $document->items;
        if ($items->isEmpty()) {
            return round((float) $document->total, 2);
        }

        $sum = 0.0;
        foreach ($items as $item) {
            if ($this->itemIsPenalty($item)) {
                continue;
            }
            $sum += (float) $item->line_total;
        }

        return round($sum, 2);
    }

    public function penaltyTotalForDocument(Document $document): float
    {
        $document->loadMissing('items');
        $sum = 0.0;
        foreach ($document->items as $item) {
            if ($this->itemIsPenalty($item)) {
                $sum += (float) $item->line_total;
            }
        }

        return round($sum, 2);
    }

    public function itemIsPenalty(DocumentItem $item): bool
    {
        return (bool) data_get($item->details ?? [], 'is_penalty', false);
    }

    /**
     * Rest din principalul non-penalty încă neîncasat (plățile acoperă întâi principalul).
     */
    public function remainingNonPenalty(Document $document): float
    {
        $principal = $this->principalForDocument($document);
        $paid = (float) $document->paid_amount;

        return round(max(0, $principal - min($paid, $principal)), 2);
    }

    /**
     * Penalități calculate (accrued) și încă nefacturate, până la data curentă.
     * Independent de toggle-ul „Se calculeaza / factureaza”.
     *
     * @param  Collection<int, Client>  $clients
     * @return array<int, float> client_id => amount
     */
    public function unbilledByClientIds(Collection $clients): array
    {
        $out = [];
        foreach ($clients as $client) {
            $id = (int) $client->id;
            if (! $this->canAccrue($client)) {
                $out[$id] = 0.0;

                continue;
            }

            try {
                $this->accrueForClient($client);
                $out[$id] = round((float) ClientPenaltyCharge::query()
                    ->where('client_id', $id)
                    ->where('status', ClientPenaltyCharge::STATUS_ACCRUED)
                    ->sum('amount'), 2);
            } catch (Throwable $e) {
                Log::warning('penalty unbilled batch failed', [
                    'client_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                $out[$id] = 0.0;
            }
        }

        return $out;
    }

    /**
     * @return array{accrued: float, billed: float, paid: float, unbilled: float}
     */
    public function summaryForClient(Client $client): array
    {
        if (! $this->canAccrue($client)) {
            return ['accrued' => 0.0, 'billed' => 0.0, 'paid' => 0.0, 'unbilled' => 0.0];
        }

        try {
            $this->accrueForClient($client);
        } catch (Throwable $e) {
            Log::warning('penalty summary accrue failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
        }

        $rows = ClientPenaltyCharge::query()
            ->where('client_id', $client->id)
            ->whereIn('status', [
                ClientPenaltyCharge::STATUS_ACCRUED,
                ClientPenaltyCharge::STATUS_BILLED,
                ClientPenaltyCharge::STATUS_PAID,
            ])
            ->get(['status', 'amount']);

        $accrued = round((float) $rows->where('status', ClientPenaltyCharge::STATUS_ACCRUED)->sum('amount'), 2);
        $billed = round((float) $rows->where('status', ClientPenaltyCharge::STATUS_BILLED)->sum('amount'), 2);
        $paid = round((float) $rows->where('status', ClientPenaltyCharge::STATUS_PAID)->sum('amount'), 2);

        return [
            'accrued' => $accrued,
            'billed' => $billed,
            'paid' => $paid,
            'unbilled' => $accrued,
        ];
    }

    /**
     * Rânduri pentru fișa de cont / PDF: penalități nefacturate + facturate (cu factura sursă).
     *
     * @return list<array{
     *   id: int,
     *   label: string,
     *   period_from: ?string,
     *   period_to: ?string,
     *   days: int,
     *   percent: float,
     *   amount: float,
     *   status: string,
     *   status_label: string,
     *   is_unbilled: bool,
     *   billed_document_id: ?int,
     *   billed_document_number: ?string
     * }>
     */
    public function statementRowsForClient(Client $client): array
    {
        if (! $this->canAccrue($client)) {
            return [];
        }

        try {
            $this->accrueForClient($client);
        } catch (Throwable $e) {
            Log::warning('penalty statement accrue failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }

        $charges = ClientPenaltyCharge::query()
            ->with('billedDocument:id,number_full,issue_date')
            ->where('client_id', $client->id)
            ->whereIn('status', [
                ClientPenaltyCharge::STATUS_ACCRUED,
                ClientPenaltyCharge::STATUS_BILLED,
                ClientPenaltyCharge::STATUS_PAID,
            ])
            ->where('amount', '>=', 0.01)
            ->orderByRaw("CASE status WHEN 'accrued' THEN 0 WHEN 'billed' THEN 1 ELSE 2 END")
            ->orderBy('period_from')
            ->orderBy('id')
            ->get();

        $out = [];
        foreach ($charges as $charge) {
            $isUnbilled = $charge->status === ClientPenaltyCharge::STATUS_ACCRUED;
            $billedDoc = $charge->billedDocument;
            $sourceLabel = $charge->source_type === ClientPenaltyCharge::SOURCE_OPENING
                ? 'Penalitate sold inițial'
                : 'Penalitate factură';
            if ($charge->source_type === ClientPenaltyCharge::SOURCE_INVOICE && $charge->source_document_id) {
                $src = Document::query()->find($charge->source_document_id, ['id', 'number_full']);
                if ($src) {
                    $sourceLabel = 'Penalitate pe '.$src->number_full;
                }
            }

            $period = '';
            if ($charge->period_from && $charge->period_to) {
                $period = dc_date($charge->period_from).' – '.dc_date($charge->period_to);
            }

            $statusLabel = match ($charge->status) {
                ClientPenaltyCharge::STATUS_ACCRUED => 'Nefacturate',
                ClientPenaltyCharge::STATUS_BILLED => 'Facturate',
                ClientPenaltyCharge::STATUS_PAID => 'Încasate',
                default => (string) $charge->status,
            };

            $out[] = [
                'id' => (int) $charge->id,
                'label' => trim($sourceLabel.($period !== '' ? ' ('.$period.')' : '')),
                'period_from' => $charge->period_from?->toDateString(),
                'period_to' => $charge->period_to?->toDateString(),
                'days' => (int) $charge->days,
                'percent' => (float) $charge->percent,
                'amount' => round((float) $charge->amount, 2),
                'status' => (string) $charge->status,
                'status_label' => $statusLabel,
                'is_unbilled' => $isUnbilled,
                'billed_document_id' => $billedDoc?->id,
                'billed_document_number' => $billedDoc?->number_full,
            ];
        }

        return $out;
    }

    /**
     * Reconstruiește charge-urile `accrued` pentru client (calcul continuu, indiferent de toggle).
     */
    public function accrueForClient(Client $client, ?Carbon $asOf = null): void
    {
        if (! $this->canAccrue($client)) {
            return;
        }

        $asOf = ($asOf ?? now('Europe/Bucharest'))->copy()->timezone('Europe/Bucharest')->startOfDay();
        $percent = $this->percentFor($client);
        $minDue = Carbon::parse(self::OPENING_DUE_DATE, 'Europe/Bucharest')->startOfDay();

        DB::transaction(function () use ($client, $asOf, $percent, $minDue) {
            // Șterge accrued vechi — le recalculăm din istoric + principal restant.
            ClientPenaltyCharge::query()
                ->where('client_id', $client->id)
                ->where('status', ClientPenaltyCharge::STATUS_ACCRUED)
                ->delete();

            $frozenCovered = $this->frozenCoveredAmountBySource($client);

            // Sold inițial
            $openingPrincipal = round((float) ($client->opening_balance ?? 0), 2);
            if ($openingPrincipal >= 0.01) {
                $orphanPays = Payment::query()
                    ->where('company_id', $client->company_id)
                    ->where('client_id', $client->id)
                    ->whereNull('document_id')
                    ->orderBy('paid_at')
                    ->orderBy('id')
                    ->get(['id', 'paid_at', 'amount']);

                $frozenOpening = $frozenCovered['opening'] ?? 0.0;
                $installments = $this->openingInstallmentsFor($client);

                if ($installments !== []) {
                    $frozenLeft = $frozenOpening;
                    foreach ($installments as $installment) {
                        $segments = $this->buildPrincipalSegments(
                            $installment['principal'],
                            $installment['due'],
                            $orphanPays,
                            $asOf
                        );
                        // Plățile pe sold se aplică FIFO pe tranșe (cea mai veche întâi):
                        // consumă din listă doar cât a acoperit această tranșă.
                        $orphanPays = $this->consumePaymentsForPrincipal(
                            $orphanPays,
                            $installment['principal']
                        );

                        $dueAmount = $this->accrualAmountFromSegments($segments, $percent);
                        $frozenTake = round(min($dueAmount, $frozenLeft), 2);
                        $frozenLeft = round(max(0, $frozenLeft - $frozenTake), 2);

                        $this->persistOpenAccrual(
                            $client,
                            ClientPenaltyCharge::SOURCE_OPENING,
                            null,
                            $segments,
                            $percent,
                            $frozenTake
                        );
                    }
                } else {
                    $openingDue = Carbon::parse(self::OPENING_DUE_DATE, 'Europe/Bucharest')->startOfDay();
                    if ($openingDue->gte($minDue)) {
                        $segments = $this->buildPrincipalSegments($openingPrincipal, $openingDue, $orphanPays, $asOf);
                        $this->persistOpenAccrual(
                            $client,
                            ClientPenaltyCharge::SOURCE_OPENING,
                            null,
                            $segments,
                            $percent,
                            $frozenOpening
                        );
                    }
                }
            }

            // Facturi emise cu scadență ≥ 11.08.2026
            $invoices = Document::query()
                ->with('items')
                ->where('company_id', $client->company_id)
                ->where('client_id', $client->id)
                ->where('type', 'invoice')
                ->whereIn('status', ['issued', 'storno'])
                ->whereDate('due_date', '>=', self::OPENING_DUE_DATE)
                ->orderBy('due_date')
                ->orderBy('id')
                ->get();

            foreach ($invoices as $invoice) {
                if ($invoice->isClosedByStorno()) {
                    continue;
                }
                $principal = $this->principalForDocument($invoice);
                if ($principal < 0.01) {
                    continue;
                }
                $due = Carbon::parse($invoice->due_date)->timezone('Europe/Bucharest')->startOfDay();
                if ($due->lt($minDue)) {
                    continue;
                }

                $pays = Payment::query()
                    ->where('document_id', $invoice->id)
                    ->orderBy('paid_at')
                    ->orderBy('id')
                    ->get(['id', 'paid_at', 'amount']);

                // Plățile acoperă întâi principalul non-penalty.
                $segments = $this->buildPrincipalSegments($principal, $due, $pays, $asOf);
                $key = 'invoice:'.$invoice->id;
                $this->persistOpenAccrual(
                    $client,
                    ClientPenaltyCharge::SOURCE_INVOICE,
                    (int) $invoice->id,
                    $segments,
                    $percent,
                    $frozenCovered[$key] ?? 0.0
                );
            }
        });
    }

    /**
     * @return array<string, float> sourceKey => sum billed+paid amounts
     */
    private function frozenCoveredAmountBySource(Client $client): array
    {
        $rows = ClientPenaltyCharge::query()
            ->where('client_id', $client->id)
            ->whereIn('status', [ClientPenaltyCharge::STATUS_BILLED, ClientPenaltyCharge::STATUS_PAID])
            ->get(['source_type', 'source_document_id', 'amount']);

        $out = [];
        foreach ($rows as $row) {
            $key = $row->source_type === ClientPenaltyCharge::SOURCE_OPENING
                ? 'opening'
                : 'invoice:'.(int) $row->source_document_id;
            $out[$key] = round(($out[$key] ?? 0) + (float) $row->amount, 2);
        }

        return $out;
    }

    /**
     * Tranșe lunare pentru soldul inițial.
     * Prioritate: (1) sumă+nr. setate pe client; (2) valoarea lunară din abonamentul recurent.
     * Ultima scadență = OPENING_DUE_DATE (11.08.2026), anterioare lunar pe aceeași zi.
     *
     * @return list<array{principal: float, due: Carbon}>
     */
    private function openingInstallmentsFor(Client $client): array
    {
        $opening = round((float) ($client->opening_balance ?? 0), 2);
        if ($opening < 0.01) {
            return [];
        }

        $lastDue = Carbon::parse(self::OPENING_DUE_DATE, 'Europe/Bucharest')->startOfDay();

        $manualAmount = round((float) ($client->opening_installment_amount ?? 0), 2);
        $manualCount = (int) ($client->opening_installment_count ?? 0);
        if ($manualAmount >= 0.01 && $manualCount >= 1) {
            return $this->buildEqualInstallmentSchedule($manualAmount, $manualCount, $lastDue);
        }

        $monthly = $this->monthlyRecurringTotal($client);
        if ($monthly >= 0.01) {
            return $this->buildMonthlyOpeningSchedule($opening, $monthly, $lastDue);
        }

        return [];
    }

    /**
     * Total lunar din abonamentele recurente lunare ale clientului (preferă cele active).
     */
    public function monthlyRecurringTotal(Client $client): float
    {
        $recs = RecurringInvoice::query()
            ->with('items')
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->where('frequency', 'monthly')
            ->orderByDesc('active')
            ->orderBy('id')
            ->get();

        if ($recs->isEmpty()) {
            return 0.0;
        }

        $active = $recs->where('active', true);
        $pool = $active->isNotEmpty() ? $active : $recs;

        $sum = 0.0;
        foreach ($pool as $rec) {
            $sum += round((float) $rec->estimatedTotal(), 2);
        }

        return round($sum, 2);
    }

    /**
     * @return list<array{principal: float, due: Carbon}>
     */
    private function buildEqualInstallmentSchedule(float $amount, int $count, Carbon $lastDue): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $monthsBack = $count - 1 - $i;
            $out[] = [
                'principal' => $amount,
                'due' => $lastDue->copy()->subMonthsNoOverflow($monthsBack)->startOfDay(),
            ];
        }

        return $out;
    }

    /**
     * Împarte soldul inițial în tranșe de valoarea lunară din recurentă.
     * Dacă restul nu e multiplu exact: o tranșă parțială (cea mai veche) + N tranșe pline.
     *
     * @return list<array{principal: float, due: Carbon}>
     */
    private function buildMonthlyOpeningSchedule(float $opening, float $monthly, Carbon $lastDue): array
    {
        if ($opening <= $monthly + 0.009) {
            return [[
                'principal' => $opening,
                'due' => $lastDue->copy()->startOfDay(),
            ]];
        }

        $fullCount = (int) floor(($opening + 0.00001) / $monthly);
        $remainder = round($opening - ($fullCount * $monthly), 2);
        $parts = [];
        if ($remainder >= 0.01) {
            $parts[] = $remainder;
        }
        for ($i = 0; $i < $fullCount; $i++) {
            $parts[] = $monthly;
        }

        $n = count($parts);
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $monthsBack = $n - 1 - $i;
            $out[] = [
                'principal' => $parts[$i],
                'due' => $lastDue->copy()->subMonthsNoOverflow($monthsBack)->startOfDay(),
            ];
        }

        return $out;
    }

    /**
     * Consumă din plățile pe sold (FIFO) cât acoperă principalul unei tranșe.
     *
     * @param  Collection<int, object{paid_at: mixed, amount: mixed}>  $payments
     * @return Collection<int, object{paid_at: mixed, amount: mixed}>
     */
    private function consumePaymentsForPrincipal(Collection $payments, float $principal): Collection
    {
        $left = round($principal, 2);
        $out = collect();

        foreach ($payments as $pay) {
            $amt = round((float) $pay->amount, 2);
            if ($amt < 0.01) {
                continue;
            }
            if ($left < 0.01) {
                $out->push($pay);

                continue;
            }
            $take = round(min($amt, $left), 2);
            $left = round(max(0, $left - $take), 2);
            $rest = round($amt - $take, 2);
            if ($rest >= 0.01) {
                $clone = clone $pay;
                $clone->amount = $rest;
                $out->push($clone);
            }
        }

        return $out->values();
    }

    /**
     * @param  list<array{principal: float, from: Carbon, to: Carbon}>  $segments
     */
    private function accrualAmountFromSegments(array $segments, float $percent): float
    {
        $total = 0.0;
        foreach ($segments as $seg) {
            $d = max(0, $seg['from']->diffInDays($seg['to']) + 1);
            if ($d < 1 || $seg['principal'] < 0.01) {
                continue;
            }
            $amt = round($seg['principal'] * ($percent / 100) * $d, 2);
            if ($amt >= 0.01) {
                $total = round($total + $amt, 2);
            }
        }

        return $total;
    }

    /**
     * Segmente de principal restant pe intervale de date (după scadență → plăți → asOf).
     *
     * @param  Collection<int, object{paid_at: mixed, amount: mixed}>  $payments
     * @return list<array{principal: float, from: Carbon, to: Carbon}>
     */
    private function buildPrincipalSegments(
        float $principal,
        Carbon $dueDate,
        Collection $payments,
        Carbon $asOf,
    ): array {
        $segments = [];
        $remaining = round($principal, 2);
        $cursor = $dueDate->copy()->addDay()->startOfDay(); // prima zi de întârziere

        $applied = 0.0;
        foreach ($payments as $pay) {
            $payAmount = round((float) $pay->amount, 2);
            if ($payAmount < 0.01 || $remaining < 0.01) {
                continue;
            }

            // Doar cât acoperă încă principalul (nu porțiunea de pe linii penalty ale facturii).
            $apply = round(min($payAmount, max(0, $principal - $applied)), 2);
            if ($apply < 0.01) {
                continue;
            }

            $paidAt = Carbon::parse($pay->paid_at)->timezone('Europe/Bucharest')->startOfDay();
            if ($paidAt->gt($dueDate) && $cursor->lte($paidAt) && $remaining >= 0.01) {
                $to = $paidAt->copy()->subDay()->startOfDay();
                if ($to->gte($cursor)) {
                    $segments[] = [
                        'principal' => $remaining,
                        'from' => $cursor->copy(),
                        'to' => $to,
                    ];
                }
                $cursor = $paidAt->copy();
            }

            $remaining = round(max(0, $remaining - $apply), 2);
            $applied = round($applied + $apply, 2);
        }

        if ($remaining >= 0.01 && $cursor->lte($asOf)) {
            $segments[] = [
                'principal' => $remaining,
                'from' => $cursor->copy(),
                'to' => $asOf->copy(),
            ];
        }

        return $segments;
    }

    /**
     * @param  list<array{principal: float, from: Carbon, to: Carbon}>  $segments
     */
    private function persistOpenAccrual(
        Client $client,
        string $sourceType,
        ?int $sourceDocumentId,
        array $segments,
        float $percent,
        float $alreadyFrozenAmount,
    ): void {
        $totalDue = 0.0;
        $aggPrincipal = 0.0;
        $from = null;
        $to = null;
        $days = 0;

        foreach ($segments as $seg) {
            $d = max(0, $seg['from']->diffInDays($seg['to']) + 1);
            if ($d < 1 || $seg['principal'] < 0.01) {
                continue;
            }
            $amt = round($seg['principal'] * ($percent / 100) * $d, 2);
            if ($amt < 0.01) {
                continue;
            }
            $totalDue = round($totalDue + $amt, 2);
            $aggPrincipal = max($aggPrincipal, $seg['principal']);
            $from = $from ? min($from, $seg['from']->toDateString()) : $seg['from']->toDateString();
            $to = $to ? max($to, $seg['to']->toDateString()) : $seg['to']->toDateString();
            $days += $d;
        }

        $open = round(max(0, $totalDue - $alreadyFrozenAmount), 2);
        if ($open < 0.01 || $from === null || $to === null) {
            return;
        }

        ClientPenaltyCharge::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'source_type' => $sourceType,
            'source_document_id' => $sourceDocumentId,
            'principal_base' => $aggPrincipal,
            'period_from' => $from,
            'period_to' => $to,
            'days' => $days,
            'percent' => $percent,
            'amount' => $open,
            'status' => ClientPenaltyCharge::STATUS_ACCRUED,
        ]);
    }

    /**
     * @return Collection<int, ClientPenaltyCharge>
     */
    public function pendingBillableCharges(Client $client): Collection
    {
        if (! $this->isBillingEnabled($client)) {
            return collect();
        }

        $this->accrueForClient($client);

        return ClientPenaltyCharge::query()
            ->where('client_id', $client->id)
            ->where('status', ClientPenaltyCharge::STATUS_ACCRUED)
            ->where('amount', '>=', 0.01)
            ->orderBy('source_type')
            ->orderBy('source_document_id')
            ->get();
    }

    /**
     * Adaugă linii TVA 0 pe factură și marchează charges ca billed.
     */
    public function appendPenaltyLinesToInvoice(Document $invoice, ?DocumentService $documents = null): void
    {
        if ($invoice->type !== 'invoice' || ! $invoice->client_id) {
            return;
        }

        $client = Client::query()->find($invoice->client_id);
        if (! $client || ! $this->isBillingEnabled($client)) {
            return;
        }

        // Nu dubla pe același draft dacă există deja linii penalty.
        $invoice->loadMissing('items');
        if ($invoice->items->contains(fn (DocumentItem $i) => $this->itemIsPenalty($i))) {
            return;
        }

        $charges = $this->pendingBillableCharges($client);
        if ($charges->isEmpty()) {
            return;
        }

        $documents = $documents ?? app(DocumentService::class);
        $position = (int) $invoice->items()->max('position');

        // Agregare pe sursă → o linie / sursă.
        $groups = $charges->groupBy(function (ClientPenaltyCharge $c) {
            return $c->source_type === ClientPenaltyCharge::SOURCE_OPENING
                ? 'opening'
                : 'invoice:'.$c->source_document_id;
        });

        foreach ($groups as $key => $group) {
            $amount = round((float) $group->sum('amount'), 2);
            if ($amount < 0.01) {
                continue;
            }

            /** @var ClientPenaltyCharge $first */
            $first = $group->first();
            $days = (int) $group->sum('days');
            $percent = (float) $first->percent;
            $percentLabel = rtrim(rtrim(number_format($percent, 4, ',', ''), '0'), ',');

            if ($first->source_type === ClientPenaltyCharge::SOURCE_OPENING) {
                $name = 'Penalități cf. contract — sold inițial';
                $desc = sprintf(
                    'Întârziere după scadența %s · %d zile × %s%% / zi',
                    Carbon::parse(self::OPENING_DUE_DATE)->format('d.m.Y'),
                    $days,
                    $percentLabel
                );
            } else {
                $src = Document::query()->find($first->source_document_id);
                $num = $src?->number_full ?: ('#'.$first->source_document_id);
                $name = 'Penalități cf. contract — '.$num;
                $dueLabel = $src?->due_date ? Carbon::parse($src->due_date)->format('d.m.Y') : '—';
                $desc = sprintf(
                    'Întârziere după scadența %s · %d zile × %s%% / zi',
                    $dueLabel,
                    $days,
                    $percentLabel
                );
            }

            $position++;
            $item = DocumentItem::create([
                'document_id' => $invoice->id,
                'product_id' => null,
                'position' => $position,
                'name' => $name,
                'description' => $desc,
                'unit' => 'buc',
                'quantity' => 1,
                'unit_price' => $amount,
                'vat_rate' => 0,
                'line_subtotal' => $amount,
                'line_vat' => 0,
                'line_total' => $amount,
                'details' => [
                    'is_penalty' => true,
                    'penalty_charge_ids' => $group->pluck('id')->values()->all(),
                ],
            ]);

            ClientPenaltyCharge::query()
                ->whereIn('id', $group->pluck('id'))
                ->update([
                    'status' => ClientPenaltyCharge::STATUS_BILLED,
                    'billed_document_id' => $invoice->id,
                    'billed_item_id' => $item->id,
                    'updated_at' => now(),
                ]);
        }

        $documents->recalculate($invoice->fresh('items'));
    }

    /**
     * După o plată: marchează charges billed pe factura plătită / pe sold.
     */
    public function onPaymentRecorded(?Payment $payment): void
    {
        if (! $payment || ! $payment->client_id) {
            return;
        }

        $client = Client::query()->find($payment->client_id);
        if (! $client || ! $this->canAccrue($client)) {
            return;
        }

        try {
            if ($payment->document_id) {
                $this->allocatePaymentOnInvoice($payment);
            } else {
                $this->allocatePaymentOnOpening($payment);
            }
            $this->accrueForClient($client);
        } catch (Throwable $e) {
            Log::warning('penalty onPayment failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function allocatePaymentOnInvoice(Payment $payment): void
    {
        $doc = Document::query()->with('items')->find($payment->document_id);
        if (! $doc) {
            return;
        }

        $principal = $this->principalForDocument($doc);
        $penaltyPart = $this->penaltyTotalForDocument($doc);
        $paid = (float) $doc->payments()->sum('amount');

        // Cât din plăți a trecut pe porțiunea de tip penalizare a acestei facturi.
        $paidTowardPenalty = round(max(0, $paid - $principal), 2);
        if ($paidTowardPenalty < 0.01 || $penaltyPart < 0.01) {
            return;
        }

        $charges = ClientPenaltyCharge::query()
            ->where('billed_document_id', $doc->id)
            ->where('status', ClientPenaltyCharge::STATUS_BILLED)
            ->orderBy('id')
            ->get();

        $left = $paidTowardPenalty;
        foreach ($charges as $charge) {
            if ($left < 0.01) {
                break;
            }
            $need = round((float) $charge->amount, 2);
            if ($need <= $left + 0.009) {
                $charge->forceFill([
                    'status' => ClientPenaltyCharge::STATUS_PAID,
                    'paid_at' => $payment->paid_at,
                    'paid_payment_id' => $payment->id,
                ])->save();
                $left = round($left - $need, 2);
            }
        }
    }

    private function allocatePaymentOnOpening(Payment $payment): void
    {
        // Când soldul e acoperit integral, marchează billed opening charges ca paid.
        $client = Client::query()->find($payment->client_id);
        if (! $client) {
            return;
        }

        $remaining = $this->balances->remainingOpeningBalance($client);
        if ($remaining >= 0.01) {
            return;
        }

        ClientPenaltyCharge::query()
            ->where('client_id', $client->id)
            ->where('source_type', ClientPenaltyCharge::SOURCE_OPENING)
            ->where('status', ClientPenaltyCharge::STATUS_BILLED)
            ->update([
                'status' => ClientPenaltyCharge::STATUS_PAID,
                'paid_at' => $payment->paid_at,
                'paid_payment_id' => $payment->id,
                'updated_at' => now(),
            ]);
    }

    public function accrueAllEligible(?Carbon $asOf = null): int
    {
        $clients = Client::query()
            ->whereNotNull('penalty_percent')
            ->where('penalty_percent', '>', 0)
            ->orderBy('id')
            ->get();

        $n = 0;
        foreach ($clients as $client) {
            try {
                $this->accrueForClient($client, $asOf);
                $n++;
            } catch (Throwable $e) {
                Log::warning('penalties:accrue client failed', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $n;
    }

    /**
     * Clienți cu penalități calculate și încă nefacturate (până la $asOf / azi),
     * sortați descrescător după sumă. Clienții cu 0 nu apar.
     *
     * @return array{rows: list<array{client_id: int, name: string, amount: float, pct: float}>, total: float}
     */
    public function unbilledRankingForCompany(Company $company, int $limit = 10, ?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $limit = max(1, min(50, $limit));

        $clients = Client::query()
            ->where('company_id', $company->id)
            ->whereNotNull('penalty_percent')
            ->where('penalty_percent', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($clients as $client) {
            try {
                $this->accrueForClient($client, $asOf);
            } catch (Throwable $e) {
                Log::warning('penalty dashboard accrue failed', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $aggregated = ClientPenaltyCharge::query()
            ->where('company_id', $company->id)
            ->where('status', ClientPenaltyCharge::STATUS_ACCRUED)
            ->selectRaw('client_id, SUM(amount) as total')
            ->groupBy('client_id')
            ->havingRaw('SUM(amount) > 0.009')
            ->orderByDesc('total')
            ->get();

        $grandTotal = round((float) $aggregated->sum('total'), 2);
        $rows = $aggregated->take($limit);

        if ($rows->isEmpty()) {
            return ['rows' => [], 'total' => 0.0];
        }

        $names = Client::query()
            ->whereIn('id', $rows->pluck('client_id'))
            ->pluck('name', 'id');

        $max = max(0.01, (float) $rows->max('total'));

        $list = $rows->map(function ($row) use ($names, $max) {
            $amount = round((float) $row->total, 2);

            return [
                'client_id' => (int) $row->client_id,
                'name' => (string) ($names[$row->client_id] ?? ('#'.$row->client_id)),
                'amount' => $amount,
                'pct' => round(($amount / $max) * 100, 1),
            ];
        })->values()->all();

        return ['rows' => $list, 'total' => $grandTotal];
    }
}
