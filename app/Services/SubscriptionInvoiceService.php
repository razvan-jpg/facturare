<?php

namespace App\Services;

use App\Mail\DocumentSentMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Support\DocumentFooterFields;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Factură fiscală FLY DAVID pentru abonamente DateConta — emitere + email după plată.
 */
class SubscriptionInvoiceService
{
    public function __construct(
        private DocumentService $documents,
        private ExchangeRateService $fx,
    ) {}

    public function issueForPaidOrder(SubscriptionOrder $order): ?Document
    {
        $this->ensureSchema();
        $order->refresh();
        if (! $order->isPaid()) {
            return null;
        }

        if ($order->invoice_document_id) {
            return Document::query()->find($order->invoice_document_id);
        }

        $issuer = $this->issuerCompany();
        if (! $issuer) {
            throw new \RuntimeException(
                'Nu am găsit firma emitentă FLY DAVID (CUI '.config('dateconta.platform_operator.cui').') în Societăți.'
            );
        }

        $user = $this->issuerUser($issuer);
        if (! $user) {
            throw new \RuntimeException('Nu am găsit utilizator emitent pentru FLY DAVID.');
        }

        $document = DB::transaction(function () use ($order, $issuer, $user) {
            $order = SubscriptionOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->invoice_document_id) {
                return Document::query()->find($order->invoice_document_id);
            }

            $client = $this->findOrCreateClient($issuer, $order);
            $paidAt = ($order->paid_at ?? now())->toDateString();
            $currency = strtoupper((string) ($order->currency ?: 'EUR'));
            $exchangeRate = $this->exchangeRate($currency);
            $processor = $order->payment_processor ?: ($order->payment_method === 'op' ? 'op' : 'card');
            $payLabel = $order->payment_method === 'op'
                ? 'OP'
                : 'card '.strtoupper((string) $processor);

            $lineName = trim($order->productName().' — '.$order->periodLabel());
            if (! $order->isSubuserSeats()) {
                $bonus = (string) (config('dateconta.subscription.periods.'.$order->period_key.'.bonus_label') ?? '');
                if ($bonus !== '') {
                    $lineName .= ' ('.$bonus.')';
                }
            }

            $fxNote = null;
            if ($currency === 'RON' && ($order->payment_processor ?? '') === 'netopia') {
                try {
                    if ($order->isSubuserSeats()) {
                        $catalog = app(SubuserSeatService::class)->priceBreakdown(
                            (string) $order->period_key,
                            max(1, (int) $order->seats),
                        );
                    } else {
                        $catalog = app(SubscriptionOrderService::class)->priceBreakdown((string) $order->period_key);
                    }
                    if (strtoupper((string) $catalog['currency']) !== 'RON') {
                        $markup = (float) config('dateconta.subscription.netopia_ron_markup', 1.02);
                        $pct = round(($markup - 1) * 100, 2);
                        $fxNote = sprintf(
                            'Catalog %s %s; încasare NETOPIA în RON (curs BNR + %s%%).',
                            number_format((float) $catalog['amount_total'], 2, ',', '.'),
                            $catalog['currency'],
                            rtrim(rtrim(number_format($pct, 2, ',', ''), '0'), ',')
                        );
                    }
                } catch (Throwable) {
                    $fxNote = 'Încasare NETOPIA în RON (curs BNR + markup).';
                }
            }

            $notes = implode("\n", array_filter([
                $order->isSubuserSeats()
                    ? 'Abonament locuri utilizatori DateConta Facturare'
                    : 'Abonament DateConta Facturare',
                'Comandă '.$order->number,
                'Plată: '.$payLabel,
                $fxNote,
                $order->access_until_after
                    ? 'Acces până la '.dc_date($order->access_until_after)
                    : null,
            ]));

            $footer = DocumentFooterFields::persistable([
                'notes' => $notes,
                'allow_card_payment' => false,
                // Emailul îl trimitem explicit la billing_email (nu dublăm via auto-email).
                'auto_email_client' => false,
                'prepared_by' => $issuer->seriesResponsibleName(),
            ]);

            $draft = $this->documents->createDraft($issuer, $user, 'invoice', array_merge([
                'issue_date' => $paidAt,
                'due_date' => $paidAt,
                'payment_term' => 0,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'document_language' => 'ro',
                'client_id' => $client->id,
            ], $footer), [
                [
                    'name' => $lineName,
                    'unit' => 'buc',
                    'quantity' => 1,
                    'unit_price' => round((float) $order->amount_net, 2),
                    'vat_rate' => round((float) $order->vat_rate, 2),
                    'description' => 'Servicii abonament platformă — comandă '.$order->number,
                ],
            ]);

            $address = collect([
                $order->billing_address,
                $order->billing_city,
                $order->billing_county,
            ])->filter()->implode(', ');

            $draft->forceFill([
                'client_name' => $order->billing_name ?: $client->name,
                'client_cui' => $order->billing_cui ?: $client->cui,
                'client_address' => $address !== '' ? $address : $client->fullAddress(),
                'client_email' => $order->billing_email ?: $client->email,
                'payment_status' => 'unpaid',
            ])->save();

            // e-Factura: scheduleAfterIssue respectă efactura_send_mode pe FLY DAVID.
            $invoice = $this->documents->issueAndMaybeSendEfactura($draft->fresh(['items', 'client', 'company']));

            $ref = $order->stripe_payment_intent
                ?: $order->mollie_payment_id
                ?: $order->netopia_ref
                ?: $order->number;

            Payment::create([
                'company_id' => $issuer->id,
                'document_id' => $invoice->id,
                'client_id' => $client->id,
                'method' => $order->payment_method === 'op' ? 'op' : 'card',
                'paid_at' => $paidAt,
                'amount' => round((float) $order->amount_total, 2),
                'currency' => $currency,
                'reference' => (string) $ref,
                'notes' => 'Încasare abonament '.$order->number.' ('.$payLabel.')',
            ]);
            $invoice->refreshPaymentStatus();

            $order->forceFill(['invoice_document_id' => $invoice->id])->save();

            return $invoice->fresh(['items', 'client', 'company']);
        });

        // Asigură emailul către billing_email chiar dacă auto-email din issue a eșuat / fără adresă pe client.
        $this->ensureEmailSent($document, $order);

        return $document;
    }

    /**
     * Emite facturi pentru comenzi plătite fără factură (backfill).
     *
     * @return array{issued:int, skipped:int, errors:list<string>}
     */
    public function issueMissing(int $limit = 100): array
    {
        $issued = 0;
        $skipped = 0;
        $errors = [];

        $orders = SubscriptionOrder::query()
            ->where('status', 'paid')
            ->whereNull('invoice_document_id')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($orders as $order) {
            try {
                $doc = $this->issueForPaidOrder($order);
                if ($doc) {
                    $issued++;
                } else {
                    $skipped++;
                }
            } catch (Throwable $e) {
                $errors[] = $order->number.': '.$e->getMessage();
                Log::error('Subscription invoice backfill failed', [
                    'order' => $order->number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('issued', 'skipped', 'errors');
    }

    public function ensureSchema(): void
    {
        if (! Schema::hasTable('subscription_orders')) {
            return;
        }
        if (Schema::hasColumn('subscription_orders', 'invoice_document_id')) {
            return;
        }

        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->foreignId('invoice_document_id')
                ->nullable()
                ->after('company_id')
                ->constrained('documents')
                ->nullOnDelete();
        });
    }

    public function issuerCompany(): ?Company
    {
        $cui = preg_replace('/\D+/', '', (string) config('dateconta.platform_operator.cui', '')) ?: '';
        if ($cui === '') {
            return null;
        }

        return Company::query()
            ->where(function ($q) use ($cui) {
                $q->where('cui', $cui)
                    ->orWhere('cui', 'RO'.$cui)
                    ->orWhere('cui', 'ro'.$cui);
            })
            ->orderBy('id')
            ->first();
    }

    private function issuerUser(Company $issuer): ?User
    {
        if ($issuer->owner_id) {
            $owner = User::query()->find($issuer->owner_id);
            if ($owner) {
                return $owner;
            }
        }

        return User::query()->where('is_admin', true)->orderBy('id')->first()
            ?: $issuer->users()->orderBy('users.id')->first();
    }

    private function findOrCreateClient(Company $issuer, SubscriptionOrder $order): Client
    {
        $cui = preg_replace('/\D+/', '', (string) ($order->billing_cui ?? '')) ?: '';
        $email = trim((string) ($order->billing_email ?? ''));
        $name = trim((string) ($order->billing_name ?? '')) ?: 'Client abonament';

        $client = null;
        if ($cui !== '') {
            $client = Client::query()
                ->where('company_id', $issuer->id)
                ->where(function ($q) use ($cui) {
                    $q->where('cui', $cui)
                        ->orWhere('cui', 'RO'.$cui)
                        ->orWhere('cui', 'ro'.$cui);
                })
                ->orderBy('id')
                ->first();
        }

        if (! $client && $email !== '') {
            $client = Client::query()
                ->where('company_id', $issuer->id)
                ->where('email', $email)
                ->orderBy('id')
                ->first();
        }

        if ($client) {
            $client->fill(array_filter([
                'name' => $name,
                'cui' => $cui !== '' ? $cui : $client->cui,
                'address' => $order->billing_address ?: $client->address,
                'city' => $order->billing_city ?: $client->city,
                'county' => $order->billing_county ?: $client->county,
                'phone' => $order->billing_phone ?: $client->phone,
                'email' => $email !== '' ? $email : $client->email,
                'type' => $cui !== '' ? 'company' : ($client->type ?: 'company'),
                'country' => $client->country ?: 'România',
            ], fn ($v) => $v !== null && $v !== ''))->save();

            return $client->fresh();
        }

        return Client::query()->create([
            'company_id' => $issuer->id,
            'name' => $name,
            'type' => $cui !== '' ? 'company' : 'company',
            'cui' => $cui !== '' ? $cui : null,
            'address' => $order->billing_address,
            'city' => $order->billing_city,
            'county' => $order->billing_county,
            'country' => 'România',
            'phone' => $order->billing_phone,
            'email' => $email !== '' ? $email : null,
        ]);
    }

    private function exchangeRate(string $currency): float
    {
        $currency = strtoupper($currency);
        if ($currency === 'RON') {
            return 1.0;
        }

        try {
            return $this->fx->rateToRon($currency);
        } catch (Throwable) {
            if ($currency === 'EUR') {
                return round((float) config('dateconta.subscription.eur_ron_approx', 5.0), 4);
            }

            return 1.0;
        }
    }

    private function ensureEmailSent(?Document $document, SubscriptionOrder $order): void
    {
        if (! $document) {
            return;
        }

        $document->loadMissing(['client', 'company']);
        $recipients = dc_parse_emails($order->billing_email ?: $document->client_email ?: $document->client?->email);
        if ($recipients === []) {
            Log::warning('Subscription invoice: no email recipient', [
                'order' => $order->number,
                'document_id' => $document->id,
            ]);

            return;
        }

        try {
            $pdf = app(InvoicePdfService::class)->output($document);
            app(ReliableMail::class)->send(
                new DocumentSentMail($document, $pdf),
                $recipients,
                $document->company
            );
        } catch (Throwable $e) {
            Log::warning('Subscription invoice email failed', [
                'order' => $order->number,
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
