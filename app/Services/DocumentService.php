<?php

namespace App\Services;

use App\Mail\DocumentSentMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentSeries;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Support\DocumentFooterFields;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class DocumentService
{
    /** TTL rezervare număr (minute) fără heartbeat. */
    public const NUMBER_RESERVATION_TTL_MINUTES = 60;

    public function ensureDefaultSeries(Company $company): void
    {
        $year = (int) now()->format('Y');
        $defaults = [
            'invoice' => 'FCT',
            'proforma' => 'PRF',
            'delivery' => 'AVZ',
            'receipt' => 'CHT',
            'credit_note' => 'NC',
        ];

        foreach ($defaults as $type => $prefix) {
            // Creează seria implicită doar dacă tipul nu are deloc serii pe anul curent.
            // Altfel, după ce utilizatorul șterge FCT/PRF/… și păstrează propria serie,
            // firstOrCreate ar recrea prefixele implicite la fiecare vizită.
            $existsForType = DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $type)
                ->where('year', $year)
                ->exists();

            if (! $existsForType) {
                DocumentSeries::create([
                    'company_id' => $company->id,
                    'type' => $type,
                    'prefix' => $prefix,
                    'year' => $year,
                    'first_number' => 1,
                    'next_number' => 1,
                    'active' => true,
                    'is_default' => true,
                ]);

                continue;
            }

            $hasDefault = DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $type)
                ->where('year', $year)
                ->where('active', true)
                ->where('is_default', true)
                ->exists();

            if (! $hasDefault) {
                DocumentSeries::query()
                    ->where('company_id', $company->id)
                    ->where('type', $type)
                    ->where('year', $year)
                    ->where('active', true)
                    ->orderBy('id')
                    ->limit(1)
                    ->update(['is_default' => true]);
            }
        }
    }

    public function recalculate(Document $document): void
    {
        $subtotal = 0;
        $vat = 0;

        foreach ($document->items as $item) {
            $lineSub = round((float) $item->quantity * (float) $item->unit_price, 2);
            $lineVat = round($lineSub * ((float) $item->vat_rate / 100), 2);
            $item->update([
                'line_subtotal' => $lineSub,
                'line_vat' => $lineVat,
                'line_total' => $lineSub + $lineVat,
            ]);
            $subtotal += $lineSub;
            $vat += $lineVat;
        }

        $document->update([
            'subtotal' => $subtotal,
            'vat_total' => $vat,
            'total' => $subtotal + $vat,
        ]);
    }

    public function issue(Document $document): Document
    {
        if (in_array($document->status, ['issued', 'storno'], true) && filled($document->number)) {
            return $document;
        }

        return DB::transaction(function () use ($document) {
            $document->refresh();

            if (! $document->hasNumberReservation()) {
                $this->reserveNumberLocked($document);
                $document->refresh();
            }

            if (! filled($document->number) || ! filled($document->series)) {
                throw new RuntimeException('Nu s-a putut rezerva un număr de serie pentru emitere.');
            }

            $year = (int) $document->issue_date->format('Y');
            $series = DocumentSeries::query()
                ->where('company_id', $document->company_id)
                ->where('type', $document->type)
                ->where('year', $year)
                ->where('prefix', $document->series)
                ->lockForUpdate()
                ->first();

            if (! $series) {
                throw new RuntimeException(
                    'Nu există o serie activă pentru '.$document->type.' / '.$year.'. Configurează seriile în Societate → Serii.'
                );
            }

            $conflict = Document::query()
                ->where('company_id', $document->company_id)
                ->where('type', $document->type)
                ->where('series', $document->series)
                ->where('issue_year', $year)
                ->where('number', $document->number)
                ->where('id', '!=', $document->id)
                ->whereIn('status', ['issued', 'storno'])
                ->exists();

            if ($conflict) {
                throw new RuntimeException(
                    'Numărul '.$document->number_full.' este deja folosit. Reîncearcă emiterea.'
                );
            }

            $document->update([
                'status' => 'issued',
                'series' => $series->prefix,
                'number' => (int) $document->number,
                'number_full' => $series->prefix.'-'.str_pad((string) $document->number, 4, '0', STR_PAD_LEFT),
                'issue_year' => $year,
                'number_reserved_at' => null,
                'efactura_status' => in_array($document->type, ['invoice', 'credit_note'], true)
                    ? ($document->efactura_status ?: 'none')
                    : $document->efactura_status,
            ]);

            return $document->fresh(['items', 'client', 'company']);
        });
    }

    /**
     * Rezervă un număr din serie pentru o ciornă.
     * Preferă automat cel mai mic gol liber; opțional $preferredNumber forțează un număr anume.
     */
    public function reserveNumber(Document $document, ?string $prefix = null, ?int $preferredNumber = null): Document
    {
        return DB::transaction(function () use ($document, $prefix, $preferredNumber) {
            return $this->reserveNumberLocked($document->fresh(), $prefix, $preferredNumber);
        });
    }

    public function touchReservation(Document $document): Document
    {
        return DB::transaction(function () use ($document) {
            $document = $document->fresh();
            if (! $document->hasNumberReservation()) {
                return $this->reserveNumberLocked($document);
            }
            $document->update(['number_reserved_at' => now()]);

            return $document->fresh(['items', 'client', 'company']);
        });
    }

    /**
     * Numere libere (goluri + următorul) pentru o serie/an.
     *
     * @return array{gaps: list<int>, next: int, available: list<int>}
     */
    public function availableNumbers(
        Company $company,
        string $type,
        string $prefix,
        int $year,
        ?int $exceptDocumentId = null,
    ): array {
        $this->ensureReservationSchema();
        $this->ensureDefaultSeries($company);

        $series = DocumentSeries::query()
            ->where('company_id', $company->id)
            ->where('type', $type)
            ->where('prefix', $prefix)
            ->where('year', $year)
            ->where('active', true)
            ->first();

        if (! $series) {
            return ['gaps' => [], 'next' => 1, 'available' => [1]];
        }

        $taken = $this->takenNumbers($company->id, $type, $prefix, $year, $exceptDocumentId);
        $takenSet = array_fill_keys($taken, true);
        $floor = max(1, (int) ($series->first_number ?? 1));
        $maxScan = max((int) $series->next_number - 1, $taken === [] ? 0 : max($taken), 0);
        $gaps = [];
        // Golurile se caută doar de la primul număr folosit în DateConta, nu înapoi în istoric.
        for ($i = $floor; $i <= $maxScan; $i++) {
            if (! isset($takenSet[$i])) {
                $gaps[] = $i;
                if (count($gaps) >= 200) {
                    break;
                }
            }
        }

        $next = max((int) $series->next_number, $floor);
        while (isset($takenSet[$next])) {
            $next++;
        }

        $available = $gaps;
        if (! in_array($next, $available, true)) {
            $available[] = $next;
        }
        sort($available);

        return [
            'gaps' => $gaps,
            'next' => $next,
            'available' => $available,
        ];
    }

    public function releaseReservation(Document $document): void
    {
        DB::transaction(function () use ($document) {
            $this->releaseReservationLocked($document->fresh());
        });
    }

    /**
     * Eliberează rezervările expirate (fără heartbeat).
     */
    public function expireStaleReservations(): int
    {
        $this->ensureReservationSchema();
        $cutoff = now()->subMinutes(self::NUMBER_RESERVATION_TTL_MINUTES);
        $ids = Document::query()
            ->where('status', 'draft')
            ->whereNotNull('number_reserved_at')
            ->where('number_reserved_at', '<', $cutoff)
            ->pluck('id');

        $count = 0;
        foreach ($ids as $id) {
            $doc = Document::query()->find($id);
            if ($doc) {
                $this->releaseReservation($doc);
                $count++;
            }
        }

        return $count;
    }

    private function reserveNumberLocked(Document $document, ?string $prefix = null, ?int $preferredNumber = null): Document
    {
        if ($document->status !== 'draft') {
            return $document;
        }

        $this->ensureReservationSchema();

        $document->loadMissing('company');
        $year = (int) $document->issue_date->format('Y');
        $wantedPrefix = filled($prefix) ? $prefix : $document->series;

        if ($document->hasNumberReservation()
            && (int) $document->issue_year === $year
            && (! filled($wantedPrefix) || $document->series === $wantedPrefix)
            && ($preferredNumber === null || (int) $document->number === $preferredNumber)
        ) {
            $floor = max(1, (int) (DocumentSeries::query()
                ->where('company_id', $document->company_id)
                ->where('type', $document->type)
                ->where('prefix', $document->series)
                ->where('year', $year)
                ->value('first_number') ?: 1));
            if ((int) $document->number >= $floor) {
                $document->update(['number_reserved_at' => now()]);

                return $document->fresh(['items', 'client', 'company']);
            }
            // Rezervare sub pragul DateConta (ex. SM-0001 la serie care începe de la 306) → reia.
        }

        if ($document->hasNumberReservation()) {
            $this->releaseReservationLocked($document);
            $document->refresh();
        }

        $this->ensureDefaultSeries($document->company);

        $base = DocumentSeries::query()
            ->where('company_id', $document->company_id)
            ->where('type', $document->type)
            ->where('year', $year)
            ->where('active', true);

        $series = null;
        if (filled($wantedPrefix)) {
            $series = (clone $base)->where('prefix', $wantedPrefix)->lockForUpdate()->first();
        }
        if (! $series) {
            $series = (clone $base)->where('is_default', true)->orderBy('id')->lockForUpdate()->first();
        }
        if (! $series) {
            $series = (clone $base)->orderByDesc('is_default')->orderBy('id')->lockForUpdate()->first();
        }

        if (! $series) {
            throw new RuntimeException(
                'Nu există o serie activă pentru '.$document->type.' / '.$year.'. Configurează seriile în Societate → Serii.'
            );
        }

        $availability = $this->availableNumbers(
            $document->company,
            $document->type,
            $series->prefix,
            $year,
            $document->id
        );

        $floor = max(1, (int) ($series->first_number ?? 1));

        if ($preferredNumber !== null) {
            if ($preferredNumber < $floor) {
                throw new RuntimeException(
                    'Numărul trebuie să fie cel puțin '.$floor.' (primul număr folosit în DateConta pentru această serie).'
                );
            }
            if ($this->numberIsTaken($document->company_id, $document->type, $series->prefix, $year, $preferredNumber, $document->id)) {
                throw new RuntimeException(
                    'Numărul '.$series->prefix.'-'.str_pad((string) $preferredNumber, 4, '0', STR_PAD_LEFT).' este deja folosit sau rezervat.'
                );
            }
            $number = $preferredNumber;
        } else {
            // Preferă cel mai mic gol (≥ first_number); altfel următorul liber de la capăt.
            $number = $availability['gaps'][0] ?? $availability['next'];
            if ($number < $floor) {
                $number = $floor;
            }
            while ($this->numberIsTaken($document->company_id, $document->type, $series->prefix, $year, $number, $document->id)) {
                $number++;
            }
        }

        // Avansează contorul doar dacă rezervăm la/capătul seriei.
        if ($number >= (int) $series->next_number) {
            $series->update(['next_number' => $number + 1]);
        }

        $document->update([
            'series' => $series->prefix,
            'number' => $number,
            'number_full' => $series->prefix.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'issue_year' => $year,
            'number_reserved_at' => now(),
        ]);

        return $document->fresh(['items', 'client', 'company']);
    }

    private function releaseReservationLocked(Document $document): void
    {
        $this->ensureReservationSchema();

        if ($document->status !== 'draft' || ! $document->hasNumberReservation()) {
            return;
        }

        $year = (int) ($document->issue_year ?: $document->issue_date->format('Y'));
        $number = (int) $document->number;
        $prefix = $document->series;

        $series = DocumentSeries::query()
            ->where('company_id', $document->company_id)
            ->where('type', $document->type)
            ->where('prefix', $prefix)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $document->update([
            'number' => null,
            'number_full' => null,
            'number_reserved_at' => null,
            'issue_year' => $year,
        ]);

        if (! $series) {
            return;
        }

        // Reutilizează doar dacă e capătul seriei și nu e ținut de alt document.
        if ((int) $series->next_number === $number + 1
            && ! $this->numberIsTaken($document->company_id, $document->type, $prefix, $year, $number, $document->id)
        ) {
            $higherHeld = Document::query()
                ->where('company_id', $document->company_id)
                ->where('type', $document->type)
                ->where('series', $prefix)
                ->where('issue_year', $year)
                ->where('number', '>', $number)
                ->where(function ($q) {
                    $q->whereIn('status', ['issued', 'storno'])
                        ->orWhere(function ($q2) {
                            $q2->where('status', 'draft')->whereNotNull('number_reserved_at');
                        });
                })
                ->exists();

            if (! $higherHeld) {
                $series->update(['next_number' => $number]);
            }
        }
    }

    /** Coloane rezervare — fallback dacă migrarea artisan nu a putut rula pe host. */
    private function ensureReservationSchema(): void
    {
        if (Schema::hasColumn('documents', 'number_reserved_at')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('number_reserved_at')->nullable()->after('number_full');
            $table->unsignedSmallInteger('issue_year')->nullable()->after('issue_date');
            $table->index(['company_id', 'type', 'series', 'number', 'status'], 'documents_series_number_status_idx');
        });

        DB::table('documents')
            ->whereNotNull('issue_date')
            ->whereNull('issue_year')
            ->update(['issue_year' => DB::raw('YEAR(issue_date)')]);

        try {
            Schema::table('documents', function (Blueprint $table) {
                $table->unique(
                    ['company_id', 'type', 'series', 'issue_year', 'number'],
                    'documents_series_number_unique'
                );
            });
        } catch (Throwable $e) {
            Log::warning('documents_series_number_unique not created', ['error' => $e->getMessage()]);
        }
    }

    private function numberIsTaken(
        int $companyId,
        string $type,
        string $prefix,
        int $year,
        int $number,
        ?int $exceptDocumentId = null,
    ): bool {
        $q = Document::query()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->where('series', $prefix)
            ->where('issue_year', $year)
            ->where('number', $number)
            ->where(function ($inner) {
                $inner->whereIn('status', ['issued', 'storno'])
                    ->orWhere(function ($draft) {
                        $draft->where('status', 'draft')->whereNotNull('number_reserved_at');
                    });
            });

        if ($exceptDocumentId) {
            $q->where('id', '!=', $exceptDocumentId);
        }

        return $q->exists();
    }

    /**
     * @return list<int>
     */
    private function takenNumbers(
        int $companyId,
        string $type,
        string $prefix,
        int $year,
        ?int $exceptDocumentId = null,
    ): array {
        $q = Document::query()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->where('series', $prefix)
            ->where('issue_year', $year)
            ->whereNotNull('number')
            ->where(function ($inner) {
                $inner->whereIn('status', ['issued', 'storno'])
                    ->orWhere(function ($draft) {
                        $draft->where('status', 'draft')->whereNotNull('number_reserved_at');
                    });
            });

        if ($exceptDocumentId) {
            $q->where('id', '!=', $exceptDocumentId);
        }

        return $q->pluck('number')->map(fn ($n) => (int) $n)->unique()->sort()->values()->all();
    }

    public function issueAndMaybeSendEfactura(Document $document, ?EfacturaService $efactura = null): Document
    {
        $issued = $this->issue($document);
        $efactura ??= app(EfacturaService::class);
        $efactura->scheduleAfterIssue($issued);
        $this->maybeAutoEmailDocument($issued);

        return $issued->fresh(['items', 'client', 'company']);
    }

    /**
     * @param  list<string>|null  $platformCc  CC MIME suplimentar (ex. facturare@fly-david.ro pentru recurente)
     * @return bool true dacă emailul a fost trimis
     */
    public function maybeAutoEmailDocument(Document $document, ?array $platformCc = null): bool
    {
        $document->loadMissing(['client', 'company']);

        if (! $document->wantsClientEmail()) {
            if (($document->client_email_status ?: 'none') === 'none') {
                $document->forceFill(['client_email_status' => 'skipped'])->save();
            }

            return false;
        }

        $recipients = [];
        if ($document->auto_email_client) {
            $recipients = array_merge(
                $recipients,
                dc_parse_emails($document->client_email ?: $document->client?->email)
            );
        }
        if ($document->auto_email_cc && filled($document->auto_email_cc_address)) {
            $recipients = array_merge($recipients, dc_parse_emails($document->auto_email_cc_address));
        }
        $recipients = array_values(array_unique($recipients));

        $cc = array_values(array_unique(array_filter(array_map(
            'trim',
            $platformCc ?? []
        ))));
        if ($document->recurring_invoice_id) {
            $platform = trim((string) config('dateconta.recurring_document_email_cc', ''));
            if ($platform !== '') {
                $cc[] = $platform;
                $cc = array_values(array_unique($cc));
            }
        }

        if ($recipients === []) {
            $document->forceFill([
                'client_email_status' => 'failed',
                'client_email_error' => 'Lipsește adresa de email a clientului (sau CC document).',
                'client_email_attempts' => (int) $document->client_email_attempts + 1,
            ])->save();

            return false;
        }

        $document->forceFill([
            'client_email_status' => 'pending',
            'client_email_attempts' => (int) $document->client_email_attempts + 1,
        ])->save();

        try {
            $pdf = app(InvoicePdfService::class)->output($document);
            app(ReliableMail::class)->send(
                new DocumentSentMail($document, $pdf),
                $recipients,
                $document->company,
                $cc !== [] ? $cc : null
            );

            $document->forceFill([
                'client_email_status' => 'sent',
                'client_email_sent_at' => now(),
                'client_email_error' => null,
            ])->save();

            return true;
        } catch (Throwable $e) {
            Log::warning('Auto-email document failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            $document->forceFill([
                'client_email_status' => 'failed',
                'client_email_error' => mb_substr($e->getMessage(), 0, 2000),
            ])->save();

            return false;
        }
    }

    /**
     * Reîncearcă emailul documentului către beneficiar (aceleași reguli ca la emitere).
     *
     * @param  list<string>|null  $platformCc
     */
    public function retryClientEmail(Document $document, ?array $platformCc = null): bool
    {
        return $this->maybeAutoEmailDocument($document->fresh(['client', 'company']), $platformCc);
    }

    public function syncClientSnapshot(Document $document, ?Client $client): void
    {
        if (! $client) {
            return;
        }

        $document->update([
            'client_id' => $client->id,
            'client_name' => $client->name,
            // Pe document: CUI pentru firme; CNP (sau gol) pentru persoane fizice.
            'client_cui' => $client->type === 'person'
                ? ($client->cnp ?: null)
                : $client->cui,
            'client_reg_com' => $client->type === 'person' ? null : $client->reg_com,
            'client_address' => $client->fullAddress(),
            'client_email' => $client->email,
        ]);
    }

    public function replaceItems(Document $document, array $items): void
    {
        $document->loadMissing(['company', 'items']);

        // Dacă se rescriu liniile unui draft cu penalități deja atașate, eliberează charges-urile.
        if ($document->status === 'draft' && $document->type === 'invoice') {
            $hadPenalty = $document->items->contains(
                fn (DocumentItem $i) => (bool) data_get($i->details ?? [], 'is_penalty', false)
            );
            if ($hadPenalty) {
                \App\Models\ClientPenaltyCharge::query()
                    ->where('billed_document_id', $document->id)
                    ->where('status', \App\Models\ClientPenaltyCharge::STATUS_BILLED)
                    ->update([
                        'status' => \App\Models\ClientPenaltyCharge::STATUS_ACCRUED,
                        'billed_document_id' => null,
                        'billed_item_id' => null,
                        'updated_at' => now(),
                    ]);
            }
        }

        $document->items()->delete();

        foreach (array_values($items) as $index => $row) {
            if (blank($row['name'] ?? null)) {
                continue;
            }

            $qty = round((float) ($row['quantity'] ?? 1), 2);
            $price = round((float) ($row['unit_price'] ?? 0), 2);
            $vatRate = round((float) ($row['vat_rate'] ?? 21), 2);
            $lineSub = round($qty * $price, 2);
            $lineVat = round($lineSub * ($vatRate / 100), 2);
            $unit = app(MeasureUnitService::class)->ensure($document->company, $row['unit'] ?? null);
            $name = trim((string) $row['name']);
            $description = filled($row['description'] ?? null) ? trim((string) $row['description']) : null;
            $details = $this->normalizeItemDetails($row['details'] ?? null);

            $productId = $row['product_id'] ?? null;
            if ($productId) {
                $exists = Product::query()
                    ->where('company_id', $document->company_id)
                    ->where('id', $productId)
                    ->exists();
                if (! $exists) {
                    $productId = null;
                }
            }

            if (! $productId) {
                $product = Product::query()
                    ->where('company_id', $document->company_id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])
                    ->first();

                if (! $product) {
                    $product = Product::create([
                        'company_id' => $document->company_id,
                        'name' => $name,
                        'unit' => $unit,
                        'price' => $price,
                        'vat_rate' => $vatRate,
                        'description' => $description,
                        'type' => 'service',
                        'active' => true,
                    ]);
                } else {
                    $product->update([
                        'unit' => $unit,
                        'price' => $price,
                        'vat_rate' => $vatRate,
                        'description' => $description ?: $product->description,
                        'active' => true,
                    ]);
                }
                $productId = $product->id;
            }

            DocumentItem::create([
                'document_id' => $document->id,
                'product_id' => $productId,
                'position' => $index,
                'name' => $name,
                'description' => $description,
                'unit' => $unit,
                'quantity' => $qty,
                'unit_price' => $price,
                'vat_rate' => $vatRate,
                'line_subtotal' => $lineSub,
                'line_vat' => $lineVat,
                'line_total' => $lineSub + $lineVat,
                'details' => $details,
            ]);
        }

        $this->recalculate($document->fresh('items'));
    }

    /** @param  mixed  $raw */
    private function normalizeItemDetails($raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $keys = [
            'buyer_item_id', 'standard_item_id', 'standard_item_scheme',
            'nc_code', 'cpv_code', 'origin_country', 'note',
            'sellers_item_id', 'sellers_item_scheme', 'order_reference',
            'buyer_accounting_ref', 'period_start', 'period_end',
        ];
        $out = [];
        foreach ($keys as $key) {
            $val = isset($raw[$key]) ? trim((string) $raw[$key]) : '';
            if ($val !== '') {
                $out[$key] = $val;
            }
        }

        return $out === [] ? null : $out;
    }

    /**
     * După încasarea integrală a unei proforme: emite factura fiscală cu data încasării,
     * înregistrează plata pe factură (nu pe proformă) și programează e-Factura după setări.
     */
    public function issueInvoiceFromPaidProforma(
        Document $proforma,
        string $paidAt,
        float $amount,
        string $reference,
        string $payNotes,
        string $method = 'card',
    ): Document {
        if ($proforma->type !== 'proforma') {
            throw new RuntimeException('Documentul nu este o proformă.');
        }

        $method = in_array($method, ['cash', 'op', 'card', 'other', 'receipt'], true) ? $method : 'other';

        $proforma->loadMissing(['items', 'company', 'client', 'payments']);
        $user = User::query()->find($proforma->created_by)
            ?: User::query()->find($proforma->company?->owner_id)
            ?: $proforma->company?->users()?->first();
        if (! $user) {
            throw new RuntimeException('Nu am găsit utilizatorul emitent pentru factura din proformă.');
        }

        // Evită dublarea dacă s-a mai emis o factură din această proformă.
        $existing = Document::query()
            ->where('company_id', $proforma->company_id)
            ->where('related_document_id', $proforma->id)
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->latest('id')
            ->first();
        if ($existing && $existing->remainingAmount() <= 0.009) {
            $proforma->forceFill([
                'payment_status' => 'paid',
                'paid_amount' => $proforma->total,
            ])->save();

            return $existing;
        }

        $items = $proforma->items->map(fn (DocumentItem $item) => [
            'product_id' => $item->product_id,
            'name' => $item->name,
            'unit' => $item->unit,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'vat_rate' => (float) $item->vat_rate,
        ])->all();

        /** @var list<array{method: string, paid_at: string, amount: float, currency: string}> $collections */
        $collections = $proforma->payments
            ->sortBy(fn (Payment $p) => [$p->paid_at?->format('Y-m-d') ?? '', $p->id])
            ->values()
            ->map(fn (Payment $p) => [
                'method' => (string) $p->method,
                'paid_at' => $p->paid_at?->toDateString() ?? $paidAt,
                'amount' => round((float) $p->amount, 2),
                'currency' => (string) ($p->currency ?: $proforma->currency ?: 'RON'),
            ])
            ->all();

        $footer = DocumentFooterFields::persistable([
            'notes' => null,
            'allow_card_payment' => false,
            'contract_number' => $proforma->contract_number,
            'despatch_advice' => $proforma->despatch_advice,
            'prepared_by' => $proforma->prepared_by,
            'prepared_by_cnp' => $proforma->prepared_by_cnp,
            'delegate_name' => $proforma->delegate_name,
            'delegate_id_card' => $proforma->delegate_id_card,
            'vehicle_reg' => $proforma->vehicle_reg,
            'auto_email_client' => (bool) $proforma->auto_email_client,
            'auto_email_cc' => (bool) $proforma->auto_email_cc,
            'auto_email_cc_address' => $proforma->auto_email_cc_address,
        ]);

        $draft = $this->createDraft($proforma->company, $user, 'invoice', array_merge([
            'issue_date' => $paidAt,
            'due_date' => $paidAt,
            'payment_term' => 0,
            'currency' => $proforma->currency,
            'exchange_rate' => $proforma->exchange_rate,
            'document_language' => $proforma->document_language ?: 'ro',
            'client_id' => $proforma->client_id,
            'related_document_id' => $proforma->id,
            'recurring_invoice_id' => $proforma->recurring_invoice_id,
        ], $footer), $items);

        $draft->forceFill([
            'client_name' => $proforma->client_name,
            'client_cui' => $proforma->client_cui,
            'client_reg_com' => $proforma->client_reg_com,
            'client_address' => $proforma->client_address,
            'client_email' => $proforma->client_email,
        ])->save();

        $invoice = $this->issueAndMaybeSendEfactura($draft->fresh(['items', 'client', 'company']));

        $finalAmount = 0.0;
        if ($invoice->remainingAmount() > 0.009) {
            $finalAmount = min($amount, round($invoice->remainingAmount(), 2));
            Payment::create([
                'company_id' => $invoice->company_id,
                'document_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'method' => $method,
                'paid_at' => $paidAt,
                'amount' => $finalAmount,
                'currency' => $invoice->currency,
                'reference' => $reference,
                'notes' => trim($payNotes.' · din proforma '.($proforma->number_full ?: '#'.$proforma->id)),
            ]);
            $invoice->refreshPaymentStatus();
            $collections[] = [
                'method' => $method,
                'paid_at' => $paidAt,
                'amount' => $finalAmount,
                'currency' => (string) ($invoice->currency ?: 'RON'),
            ];
        } elseif ($collections === []) {
            // Fallback: fără plăți anterioare înregistrate pe proformă.
            $collections[] = [
                'method' => $method,
                'paid_at' => $paidAt,
                'amount' => round((float) $amount, 2),
                'currency' => (string) ($proforma->currency ?: 'RON'),
            ];
        }

        $conversionNote = $this->proformaConversionNote($proforma, $collections);
        $invoice->forceFill(['notes' => $conversionNote])->save();

        $methodLabel = $this->paymentMethodLabelRo($method);
        $proforma->forceFill([
            'payment_status' => 'paid',
            'paid_amount' => $proforma->total,
            'notes' => trim(implode("\n", array_filter([
                $proforma->notes,
                'Încasată ('.$methodLabel.') · factură fiscală '.$invoice->number_full.' din '.$paidAt.'.',
            ]))),
        ])->save();

        return $invoice->fresh(['items', 'client', 'company']);
    }

    /**
     * @param  list<array{method: string, paid_at: string, amount: float, currency: string}>  $collections
     */
    private function proformaConversionNote(Document $proforma, array $collections): string
    {
        $proformaNumber = $proforma->number_full ?: ('#'.$proforma->id);
        $proformaDate = $proforma->issue_date
            ? $proforma->issue_date->format('d.m.Y')
            : '—';

        if ($collections === []) {
            return sprintf(
                'Factură emisă în baza proformei nr. %s din data de %s.',
                $proformaNumber,
                $proformaDate
            );
        }

        $parts = [];
        $fractional = count($collections) > 1;
        foreach ($collections as $row) {
            $label = $this->paymentMethodLabelRo((string) ($row['method'] ?? 'other'));
            $date = \Illuminate\Support\Carbon::parse($row['paid_at'])->format('d.m.Y');
            if ($fractional) {
                $parts[] = sprintf(
                    '%s în data de %s (%s %s)',
                    $label,
                    $date,
                    number_format((float) $row['amount'], 2, ',', '.'),
                    $row['currency'] ?: 'RON'
                );
            } else {
                $parts[] = sprintf('%s în data de %s', $label, $date);
            }
        }

        return sprintf(
            'Factură emisă în baza proformei nr. %s din data de %s, încasată cu %s.',
            $proformaNumber,
            $proformaDate,
            implode('; ', $parts)
        );
    }

    private function paymentMethodLabelRo(string $method): string
    {
        return match ($method) {
            'receipt' => 'chitanță',
            'card' => 'card',
            'op' => 'OP',
            'cash' => 'numerar',
            default => 'altă metodă',
        };
    }

    public function createDraft(Company $company, User $user, string $type, array $data, array $items): Document
    {
        $currency = strtoupper($data['currency'] ?? 'RON');
        $footer = DocumentFooterFields::persistable($data);
        if (empty($footer['prepared_by'])) {
            $footer['prepared_by'] = $company->seriesResponsibleName();
        }
        $document = Document::create(array_merge([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'type' => $type,
            'status' => 'draft',
            'issue_date' => $data['issue_date'] ?? now()->toDateString(),
            'due_date' => $data['due_date'] ?? null,
            'payment_term' => $data['payment_term'] ?? null,
            'currency' => $currency,
            'exchange_rate' => $currency === 'RON' ? 1 : (float) ($data['exchange_rate'] ?? 1),
            'series' => $data['series'] ?? null,
            'document_language' => $data['document_language'] ?? 'ro',
            'client_id' => $data['client_id'] ?? null,
            'related_document_id' => $data['related_document_id'] ?? null,
            'recurring_invoice_id' => $data['recurring_invoice_id'] ?? null,
        ], $footer));

        if (! empty($data['client_id'])) {
            $this->syncClientSnapshot($document, Client::find($data['client_id']));
        }

        $this->replaceItems($document, $items);

        if ($type === 'invoice' && ! empty($data['client_id'])) {
            try {
                app(ClientPenaltyService::class)->appendPenaltyLinesToInvoice($document->fresh(['items', 'client']), $this);
            } catch (Throwable $e) {
                Log::warning('appendPenaltyLinesToInvoice failed', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            return $this->reserveNumber($document->fresh(), $data['series'] ?? null);
        } catch (Throwable $e) {
            // Ciornă fără serie activă — rămâne fără număr până la configurare.
            Log::warning('reserveNumber after createDraft failed', ['error' => $e->getMessage()]);

            return $document->fresh(['items', 'client']);
        }
    }

    public function createStorno(Document $original, User $user): Document
    {
        if (! $original->canStorno()) {
            throw new \RuntimeException('Această factură nu poate fi stornată.');
        }

        $original->loadMissing('items');

        $items = $original->items->map(fn (DocumentItem $item) => [
            'product_id' => $item->product_id,
            'name' => $item->name,
            'unit' => $item->unit,
            'quantity' => -1 * abs((float) $item->quantity),
            'unit_price' => (float) $item->unit_price,
            'vat_rate' => (float) $item->vat_rate,
        ])->all();

        $storno = Document::create([
            'company_id' => $original->company_id,
            'client_id' => $original->client_id,
            'created_by' => $user->id,
            'type' => 'invoice',
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'currency' => $original->currency,
            'document_language' => $original->document_language ?: 'ro',
            'notes' => 'Storno factură '.($original->number_full ?: '#'.$original->id),
            'client_name' => $original->client_name,
            'client_cui' => $original->client_cui,
            'client_reg_com' => $original->client_reg_com,
            'client_address' => $original->client_address,
            'client_email' => $original->client_email,
            'related_document_id' => $original->id,
        ]);

        $this->replaceItems($storno, $items);

        // Emite (număr), marchează storno (UBL 384), achită storno + original, apoi e-Factura.
        $issued = $this->issue($storno->fresh('items'));
        $issued->update(['status' => 'storno']);
        $fresh = $issued->fresh(['items', 'client', 'company']);
        $this->settleStornoPair($original->fresh(), $fresh);
        $fresh = $fresh->fresh(['items', 'client', 'company']);
        app(EfacturaService::class)->scheduleAfterIssue($fresh);
        $fresh = $fresh->fresh();
        if (($fresh->efactura_status ?: 'none') !== 'ok') {
            app(EfacturaReconcileService::class)->markForReconcile($fresh);
        }

        return $fresh->fresh(['items', 'client']);
    }

    /**
     * Storno integral: marchează achitate storno-ul și factura originală.
     * Idempotent — safe la backfill / reapel (nu duplică plata compensatoare).
     */
    public function settleStornoPair(Document $original, Document $storno): void
    {
        DB::transaction(function () use ($original, $storno) {
            $storno = $storno->fresh() ?? $storno;
            $storno->forceFill([
                'payment_status' => 'paid',
                'paid_amount' => abs((float) $storno->total),
            ])->save();

            $original = $original->fresh() ?? $original;
            $notes = 'Stornare integrală · '.($storno->number_full ?: '#'.$storno->id);
            $alreadySettled = Payment::query()
                ->where('document_id', $original->id)
                ->where(function ($q) use ($storno, $notes) {
                    $q->where('notes', $notes);
                    if ($storno->number_full) {
                        $q->orWhere('reference', $storno->number_full);
                    }
                    $q->orWhere('notes', 'like', 'Stornare integrală ·%');
                })
                ->exists();

            // remainingAmount pe original cu storno deja legat e 0 — folosim suma plăților reale.
            $paidSum = round((float) $original->payments()->sum('amount'), 2);
            $target = round(abs((float) $original->total), 2);
            $remaining = round(max(0, $target - $paidSum), 2);

            if (! $alreadySettled && $remaining > 0.009) {
                Payment::create([
                    'company_id' => $original->company_id,
                    'document_id' => $original->id,
                    'client_id' => $original->client_id,
                    'method' => 'other',
                    'paid_at' => now()->toDateString(),
                    'amount' => $remaining,
                    'currency' => $original->currency ?: 'RON',
                    'reference' => $storno->number_full,
                    'notes' => $notes,
                ]);
            }

            $original->refreshPaymentStatus();
            $storno->refreshPaymentStatus();
        });
    }

    public function createCreditNote(Document $original, User $user): Document
    {
        if (! $original->canCreditNote()) {
            throw new \RuntimeException('Această factură nu poate primi notă de creditare.');
        }

        $original->loadMissing('items');
        $this->ensureDefaultSeries($original->company);

        $items = $original->items->map(fn (DocumentItem $item) => [
            'product_id' => $item->product_id,
            'name' => $item->name,
            'unit' => $item->unit,
            'quantity' => -1 * abs((float) $item->quantity),
            'unit_price' => (float) $item->unit_price,
            'vat_rate' => (float) $item->vat_rate,
        ])->all();

        $note = Document::create([
            'company_id' => $original->company_id,
            'client_id' => $original->client_id,
            'created_by' => $user->id,
            'type' => 'credit_note',
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'currency' => $original->currency,
            'document_language' => $original->document_language ?: 'ro',
            'notes' => 'Notă de creditare pentru factura '.($original->number_full ?: '#'.$original->id),
            'client_name' => $original->client_name,
            'client_cui' => $original->client_cui,
            'client_reg_com' => $original->client_reg_com,
            'client_address' => $original->client_address,
            'client_email' => $original->client_email,
            'related_document_id' => $original->id,
            'efactura_status' => 'none',
        ]);

        $this->replaceItems($note, $items);
        $issued = $this->issue($note->fresh('items'));
        $fresh = $issued->fresh(['items', 'client', 'company']);
        app(EfacturaService::class)->scheduleAfterIssue($fresh);
        $fresh = $fresh->fresh();
        if (($fresh->efactura_status ?: 'none') !== 'ok') {
            app(EfacturaReconcileService::class)->markForReconcile($fresh);
        }

        return $fresh->fresh(['items', 'client']);
    }

    public function deleteDocument(Document $document): void
    {
        DB::transaction(function () use ($document) {
            $document = $document->fresh();
            if ($document->status === 'draft' && $document->hasNumberReservation()) {
                $this->releaseReservationLocked($document);
            } elseif (in_array($document->status, ['issued', 'storno'], true)) {
                $this->releaseSeriesNumber($document);
            }
            $document->payments()->delete();
            $document->items()->delete();
            $document->delete();
        });
    }

    public function cancelDocument(Document $document): Document
    {
        return DB::transaction(function () use ($document) {
            $document = $document->fresh();
            $wasDraft = $document->status === 'draft';

            if ($wasDraft && $document->hasNumberReservation()) {
                $this->releaseReservationLocked($document);
                $document->refresh();
            } elseif (in_array($document->status, ['issued', 'storno'], true)) {
                $this->releaseSeriesNumber($document);
            }

            $document->update([
                'status' => 'cancelled',
                'number' => $wasDraft ? null : $document->number,
                'number_full' => $wasDraft ? null : $document->number_full,
                'number_reserved_at' => null,
                'efactura_status' => $document->efactura_status === 'queued' ? 'none' : $document->efactura_status,
                'efactura_scheduled_at' => null,
            ]);

            return $document->fresh();
        });
    }

    /**
     * Eliberează numărul înapoi în serie dacă e cel mai mare număr activ pe serie/an.
     * Astfel următoarea emitere îl poate reutiliza.
     */
    public function releaseSeriesNumber(Document $document): void
    {
        if (! filled($document->series) || ! filled($document->number) || ! $document->issue_date) {
            return;
        }

        $year = (int) ($document->issue_year ?: $document->issue_date->format('Y'));
        $number = (int) $document->number;

        $series = DocumentSeries::query()
            ->where('company_id', $document->company_id)
            ->where('type', $document->type)
            ->where('prefix', $document->series)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if (! $series) {
            return;
        }

        $maxRemaining = Document::query()
            ->where('company_id', $document->company_id)
            ->where('type', $document->type)
            ->where('series', $document->series)
            ->where('issue_year', $year)
            ->where('id', '!=', $document->id)
            ->whereNotNull('number')
            ->where(function ($q) {
                $q->whereIn('status', ['issued', 'storno'])
                    ->orWhere(function ($draft) {
                        $draft->where('status', 'draft')->whereNotNull('number_reserved_at');
                    });
            })
            ->max('number');

        $maxRemaining = $maxRemaining !== null ? (int) $maxRemaining : 0;
        $next = $maxRemaining > 0 ? $maxRemaining + 1 : $number;

        // Dacă documentul curent avea cel mai mare număr, următorul nr. revine la el.
        if ($number >= $maxRemaining) {
            $next = $number;
        }

        if ($next < 1) {
            $next = 1;
        }

        if ((int) $series->next_number !== $next) {
            $series->update(['next_number' => $next]);
        }
    }
}
