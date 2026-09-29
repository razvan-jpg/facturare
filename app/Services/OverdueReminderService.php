<?php

namespace App\Services;

use App\Mail\OverdueReminderMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\OverdueReminderLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OverdueReminderService
{
    public function __construct(private readonly ReliableMail $mail) {}

    public function processDue(?Company $onlyCompany = null, int $limit = 100): int
    {
        $query = Company::query()->where('overdue_reminders_enabled', true);
        if ($onlyCompany) {
            $query->where('id', $onlyCompany->id);
        }

        $sent = 0;
        foreach ($query->get() as $company) {
            $sent += $this->processCompany($company, $limit - $sent);
            if ($sent >= $limit) {
                break;
            }
        }

        return $sent;
    }

    public function processCompany(Company $company, int $limit = 50): int
    {
        if (! $company->overdue_reminders_enabled) {
            return 0;
        }

        $clients = Client::query()
            ->where('company_id', $company->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->get();

        $sent = 0;
        foreach ($clients as $client) {
            if ($sent >= $limit) {
                break;
            }

            try {
                if ($this->sendForClient($company, $client)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::warning('Overdue reminder failed', [
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    public function sendForClient(Company $company, Client $client, bool $force = false): bool
    {
        $overdue = $this->overdueInvoicesForClient($company, $client);
        if ($overdue->isEmpty()) {
            return false;
        }

        $recipients = $client->emailAddresses();
        if ($recipients === []) {
            return false;
        }

        if (! $force && ! $this->isDueForReminder($company, $client)) {
            return false;
        }

        $openInvoices = $this->openInvoicesForClient($company, $client);
        $balances = app(ClientBalanceService::class);
        $openRemaining = $balances->openInvoicesRemaining($client, $openInvoices);
        $opening = $balances->openingBalance($client);
        $currentBalance = $balances->currentBalance($client, $openInvoices);
        $balance = round($overdue->sum(fn (Document $d) => $d->remainingAmount()) + max(0, $opening), 2);
        $scope = $company->overdue_reminder_scope ?: 'both';
        $includeStatement = (bool) $company->overdue_reminder_include_statement;

        $statementPdf = null;
        if ($includeStatement) {
            $penalties = app(ClientPenaltyService::class);
            $statementPdf = Pdf::loadView('documents.client-statement-pdf', [
                'company' => $company,
                'client' => $client,
                'invoices' => $openInvoices,
                'overdueIds' => $overdue->pluck('id')->all(),
                'openingBalance' => $opening,
                'openingBalanceDate' => $client->opening_balance_date,
                'openRemaining' => $openRemaining,
                'balance' => $currentBalance,
                'penaltyRows' => $penalties->statementRowsForClient($client),
                'penaltySummary' => $penalties->summaryForClient($client),
            ])->output();
        }

        $this->deliver(
            company: $company,
            client: $client,
            overdue: $overdue,
            balance: $balance,
            scope: $scope,
            includeStatement: $includeStatement,
            statementPdf: $statementPdf,
            statementKind: 'client',
            recipients: $recipients,
            viaCompanyMail: false,
        );

        return true;
    }

    /**
     * Trimitere manuală către un client, indiferent de frecvență și de zilele de grație.
     * Fișa de cont (4111) se atașează doar dacă e cerută.
     *
     * @return array{ok: bool, message: string}
     */
    public function sendManual(Company $company, Client $client, bool $attachAccountStatement, ?string $ccEmail = null): array
    {
        if ((int) $client->company_id !== (int) $company->id) {
            return ['ok' => false, 'message' => 'Clientul nu aparține acestei societăți.'];
        }

        $recipients = $client->emailAddresses();
        if ($recipients === []) {
            return ['ok' => false, 'message' => 'Clientul nu are adresă de email. Completeaz-o pe fișa clientului, apoi încearcă din nou.'];
        }

        $overdue = $this->overdueInvoicesForClient($company, $client, 0);
        $opening = app(ClientBalanceService::class)->remainingOpeningBalance($client);
        $balance = round($overdue->sum(fn (Document $d) => $d->remainingAmount()) + $opening, 2);

        if ($overdue->isEmpty() && $balance < 0.01) {
            return ['ok' => false, 'message' => 'Clientul nu are sold restant acum (fără facturi scadente neachitate și fără sold inițial).'];
        }

        $scope = $company->overdue_reminder_scope ?: 'both';
        if ($overdue->isEmpty()) {
            $scope = 'balance';
        }

        $statementPdf = null;
        if ($attachAccountStatement) {
            $statementPdf = $this->accountStatementPdf($company, $client);
        }

        $cc = $this->ccAddress($ccEmail, $recipients);

        $this->deliver(
            company: $company,
            client: $client,
            overdue: $overdue,
            balance: $balance,
            scope: $scope,
            includeStatement: $attachAccountStatement,
            statementPdf: $statementPdf,
            statementKind: 'account',
            recipients: $recipients,
            viaCompanyMail: true,
            cc: $cc,
        );

        $who = $client->name.' ('.implode(', ', $recipients).')';
        $message = $attachAccountStatement
            ? "Notificarea de sold restant a fost trimisă către {$who}, cu fișa de cont atașată."
            : "Notificarea de sold restant a fost trimisă către {$who}.";
        if ($cc !== []) {
            $message .= ' O copie a plecat în CC către '.$cc[0].'.';
        }

        return ['ok' => true, 'message' => $message];
    }

    /**
     * @param  list<string>  $recipients
     * @param  list<string>  $cc
     */
    private function deliver(
        Company $company,
        Client $client,
        Collection $overdue,
        float $balance,
        string $scope,
        bool $includeStatement,
        ?string $statementPdf,
        string $statementKind,
        array $recipients,
        bool $viaCompanyMail,
        array $cc = [],
    ): void {
        $this->mail->send(new OverdueReminderMail(
            company: $company,
            client: $client,
            overdueInvoices: $overdue,
            balance: $balance,
            scope: $scope,
            includeStatement: $includeStatement && $statementPdf !== null,
            statementPdf: $statementPdf,
            statementKind: $statementKind,
        ), $recipients, $viaCompanyMail ? $company : null, $cc !== [] ? $cc : null);

        OverdueReminderLog::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'email' => implode(', ', $recipients),
            'scope' => $scope,
            'included_statement' => $includeStatement && $statementPdf !== null,
            'document_ids' => $overdue->pluck('id')->values()->all(),
            'balance_total' => $balance,
            'invoice_count' => $overdue->count(),
            'sent_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $recipients
     * @return list<string>
     */
    private function ccAddress(?string $email, array $recipients): array
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        $already = array_map(fn (string $address) => strtolower(trim($address)), $recipients);
        if (in_array($email, $already, true)) {
            return [];
        }

        return [$email];
    }

    private function accountStatementPdf(Company $company, Client $client): string
    {
        $from = $client->effectiveOpeningBalanceDate();
        $to = now()->toDateString();
        $data = app(PartnerLedgerService::class)->build($company, $client, $from, $to);

        return Pdf::loadView('reports.partner-ledger-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->output();
    }

    public function overdueInvoicesForClient(Company $company, Client $client, ?int $graceDays = null): Collection
    {
        $grace = max(0, $graceDays ?? (int) ($company->overdue_reminder_grace_days ?? 0));
        $cutoff = now()->subDays($grace)->toDateString();

        return $company->documents()
            ->where('client_id', $client->id)
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $cutoff)
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Document $d) => $d->remainingAmount() > 0.009)
            ->values();
    }

    public function openInvoicesForClient(Company $company, Client $client): Collection
    {
        return $company->documents()
            ->where('client_id', $client->id)
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Document $d) => $d->remainingAmount() > 0.009)
            ->values();
    }

    private function isDueForReminder(Company $company, Client $client): bool
    {
        $freq = max(1, (int) ($company->overdue_reminder_frequency_days ?: 7));
        $last = OverdueReminderLog::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->orderByDesc('sent_at')
            ->value('sent_at');

        if (! $last) {
            return true;
        }

        return now()->greaterThanOrEqualTo($last->copy()->addDays($freq)->startOfDay());
    }
}
