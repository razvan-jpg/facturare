<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCompanyPermission;
use App\Models\Client;
use App\Models\Company;
use App\Services\AnafClient;
use App\Services\ClientBalanceService;
use App\Services\ClientPenaltyService;
use App\Services\CompanyBankService;
use App\Services\CompanyContext;
use App\Support\ListPerPage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ClientController extends Controller
{
    use ChecksCompanyPermission;

    public function index(Request $request, CompanyContext $context, ClientBalanceService $balances, ClientPenaltyService $penalties): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'clients_view');
        $perPage = ListPerPage::resolve($request, $company);
        $hideZero = $request->boolean('hide_zero');

        $allClients = $company->clients()->orderBy('name')->get();
        $openByClient = $balances->openRemainingByClientIds($company, $allClients);
        $unallocatedByClient = $balances->unallocatedByClientIds($company, $allClients);
        $clientBalances = [];
        foreach ($allClients as $client) {
            $open = $openByClient[$client->id] ?? 0.0;
            $unallocated = $unallocatedByClient[$client->id] ?? 0.0;
            $clientBalances[$client->id] = round($balances->openingBalance($client) + $open - $unallocated, 2);
        }

        $filtered = $hideZero
            ? $allClients->filter(fn (Client $c) => abs($clientBalances[$c->id] ?? 0) > 0.009)->values()
            : $allClients->values();

        $clients = ListPerPage::paginateCollection($filtered, $perPage, $request);
        $anafSyncableCount = $this->anafSyncableClients($company)->count();
        $penaltyUnbilled = $penalties->unbilledByClientIds($clients->getCollection());

        return view('clients.index', compact(
            'clients',
            'company',
            'anafSyncableCount',
            'clientBalances',
            'penaltyUnbilled',
            'perPage',
            'hideZero'
        ));
    }

    public function openingBalancesEdit(CompanyContext $context, ClientBalanceService $balances): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'clients_manage');
        $clients = $company->clients()->orderBy('name')->get();
        $openByClient = $balances->openRemainingByClientIds($company, $clients);

        return view('clients.opening-balances', compact('clients', 'company', 'openByClient'));
    }

    public function openingBalancesUpdate(Request $request, CompanyContext $context): RedirectResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'clients_manage');

        $rowsIn = $request->input('clients', []);
        if (is_array($rowsIn)) {
            foreach ($rowsIn as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $raw = trim((string) ($row['opening_balance'] ?? ''));
                $rowsIn[$i]['opening_balance'] = $raw === '' ? 0 : str_replace(',', '.', $raw);
            }
            $request->merge(['clients' => $rowsIn]);
        }

        $data = $request->validate([
            'clients' => ['required', 'array'],
            'clients.*.id' => ['required', 'integer'],
            'clients.*.opening_balance' => ['nullable', 'numeric'],
            'clients.*.opening_balance_date' => ['nullable', 'string', 'max:20'],
        ]);

        $ids = collect($data['clients'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $owned = $company->clients()->whereIn('id', $ids)->get()->keyBy('id');
        $updated = 0;

        foreach ($data['clients'] as $row) {
            $client = $owned[(int) $row['id']] ?? null;
            if (! $client) {
                continue;
            }

            $rawAmount = trim((string) ($row['opening_balance'] ?? ''));
            $amount = $rawAmount === ''
                ? 0.0
                : round((float) str_replace(',', '.', $rawAmount), 2);
            if (abs($amount) < 0.009) {
                $amount = 0.0;
            }
            $dateRaw = trim((string) ($row['opening_balance_date'] ?? ''));
            if ($dateRaw === '') {
                // Necompletat → data creării clientului.
                $date = $client->effectiveOpeningBalanceDate();
            } else {
                $date = dc_parse_date($dateRaw);
                if ($date === null) {
                    return back()->withErrors([
                        'clients' => 'Dată invalidă pentru '.$client->name.' (folosește zz/ll/aaaa).',
                    ])->withInput();
                }
            }

            $client->update([
                'opening_balance' => $amount,
                'opening_balance_date' => $date,
            ]);
            $updated++;
        }

        return redirect()
            ->route('clients.index')
            ->with('status', "Solduri inițiale actualizate pentru {$updated} clienți.");
    }

    public function show(Client $client, CompanyContext $context, ClientBalanceService $balances, ClientPenaltyService $penalties): View
    {
        $this->authorizeClient($client, $context, 'clients_view');
        $openInvoices = $balances->openInvoices($client);
        $opening = $balances->openingBalance($client);
        $openingRemaining = $balances->remainingOpeningBalance($client);
        $openRemaining = $balances->openInvoicesRemaining($client, $openInvoices);
        $current = $balances->currentBalance($client, $openInvoices);
        $penaltySummary = $penalties->summaryForClient($client);
        $penaltyRows = $penalties->statementRowsForClient($client);

        return view('clients.show', compact(
            'client',
            'openInvoices',
            'opening',
            'openingRemaining',
            'openRemaining',
            'current',
            'penaltySummary',
            'penaltyRows'
        ));
    }

    public function statementPdf(Client $client, CompanyContext $context, ClientBalanceService $balances, ClientPenaltyService $penalties): Response
    {
        $this->authorizeClient($client, $context, 'clients_view');
        $company = $context->current();
        $openInvoices = $balances->openInvoices($client);
        $openingRemaining = $balances->remainingOpeningBalance($client);
        $openRemaining = $balances->openInvoicesRemaining($client, $openInvoices);
        $current = $balances->currentBalance($client, $openInvoices);
        $penaltyRows = $penalties->statementRowsForClient($client);
        $penaltySummary = $penalties->summaryForClient($client);

        $pdf = Pdf::loadView('documents.client-statement-pdf', [
            'company' => $company,
            'client' => $client,
            'invoices' => $openInvoices,
            'overdueIds' => [],
            'openingBalance' => $openingRemaining,
            'openingBalanceDate' => $client->opening_balance_date,
            'openRemaining' => $openRemaining,
            'balance' => $current,
            'penaltyRows' => $penaltyRows,
            'penaltySummary' => $penaltySummary,
        ]);

        $safe = preg_replace('/[^\pL\pN\-]+/u', '-', $client->name) ?: 'client';

        return $pdf->download('fisa-client-'.$safe.'.pdf');
    }

    /**
     * Actualizează din ANAF toți clienții firmă cu CUI din societatea curentă.
     * Persoanele fizice / fără CUI valid / negăsite în ANAF sunt omise fără eroare.
     * Nu modifică email, IBAN, note; telefonul vechi e păstrat dacă ANAF nu returnează unul.
     */
    public function syncAnafBulk(CompanyContext $context, AnafClient $anaf): JsonResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'clients_manage');

        @set_time_limit(120);

        $allClients = $company->clients()->orderBy('name')->get();
        $success = 0;
        $modified = 0;
        $ignored = 0;
        $ignoredReasons = [
            'person' => 0,
            'no_cui' => 0,
            'not_found' => 0,
        ];

        $syncable = [];
        foreach ($allClients as $client) {
            if (($client->type ?? 'company') === 'person') {
                $ignored++;
                $ignoredReasons['person']++;

                continue;
            }

            $digits = $this->clientCuiDigits($client);
            if ($digits === null) {
                $ignored++;
                $ignoredReasons['no_cui']++;

                continue;
            }

            $syncable[] = [$client, $digits];
        }

        if ($syncable === []) {
            return response()->json([
                'message' => 'Nu există clienți firmă cu CUI de actualizat din ANAF.',
                'success' => 0,
                'modified' => 0,
                'ignored' => $ignored,
                'ignored_reasons' => $ignoredReasons,
                // compat
                'updated' => 0,
                'skipped' => $ignored,
            ]);
        }

        $cuis = array_values(array_unique(array_map(fn ($row) => $row[1], $syncable)));
        $found = $anaf->lookupMany($cuis);

        foreach ($syncable as [$client, $digits]) {
            $row = $found[$digits] ?? null;
            if (! $row || trim((string) ($row['name'] ?? '')) === '') {
                $ignored++;
                $ignoredReasons['not_found']++;

                continue;
            }

            $payload = [
                'type' => 'company',
                'name' => $row['name'],
                'cui' => dc_format_cui($row['cui'] ?: $digits, (bool) ($row['vat_payer'] ?? false)),
            ];

            if (filled($row['reg_com'] ?? null)) {
                $payload['reg_com'] = $row['reg_com'];
            }
            if (filled($row['address'] ?? null)) {
                $payload['address'] = $row['address'];
            }
            if (filled($row['city'] ?? null)) {
                $payload['city'] = $row['city'];
            }
            if (filled($row['county'] ?? null)) {
                $payload['county'] = dc_normalize_county($row['county']);
            }
            if (filled($row['phone'] ?? null)) {
                $payload['phone'] = $row['phone'];
            }

            $changed = false;
            foreach ($payload as $key => $value) {
                $current = $client->{$key};
                if ((string) ($current ?? '') !== (string) ($value ?? '')) {
                    $changed = true;
                    break;
                }
            }

            $client->update($payload);
            $success++;
            if ($changed) {
                $modified++;
            }
        }

        return response()->json([
            'message' => $success > 0
                ? "Actualizați {$success} clienți din ANAF ({$modified} fișe modificate)."
                : 'Nicio firmă nu a putut fi actualizată din ANAF.',
            'success' => $success,
            'modified' => $modified,
            'ignored' => $ignored,
            'ignored_reasons' => $ignoredReasons,
            // compat
            'updated' => $success,
            'skipped' => $ignored,
        ]);
    }

    public function create(CompanyContext $context): View
    {
        $this->authorizeCompanyAbility($context->current(), 'clients_manage');

        return view('clients.create', ['company' => $context->current()]);
    }

    public function store(Request $request, CompanyContext $context): RedirectResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'clients_manage');
        $data = $this->validated($request);
        $data['company_id'] = $company->id;
        Client::create($data);

        return redirect()->route('clients.index')->with('status', 'Client adăugat.');
    }

    /**
     * Creare / selectare rapidă din formular factură (JSON).
     * - CUI (firme): preluare ANAF + reutilizare dacă există
     * - CNP (13 cifre): persoană existentă după CNP sau creare nouă
     * - „-”: creează ÎNTOTDEAUNA o persoană fizică nouă (nu reutilizează)
     */
    public function quickStore(Request $request, CompanyContext $context, AnafClient $anaf): JsonResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'clients_manage');
        $request->validate([
            'identifier' => ['nullable', 'string', 'max:32'],
            'cui' => ['nullable', 'string', 'max:20'],
            'cnp' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'from_anaf' => ['nullable', 'boolean'],
            'type' => ['nullable', 'in:company,person'],
        ]);

        $raw = trim((string) (
            $request->input('identifier')
            ?? $request->input('cnp')
            ?? $request->input('cui')
            ?? ''
        ));
        $name = trim((string) $request->input('name', ''));

        // „-” / en-dash / em-dash → persoană fizică nouă, mereu
        if ($this->isAnonymousPersonMarker($raw)) {
            if ($name === '') {
                return response()->json([
                    'message' => 'Pentru persoană fizică fără CNP, completează numele.',
                    'need_name' => true,
                    'mode' => 'person_anonymous',
                ], 422);
            }

            $client = Client::create([
                'company_id' => $company->id,
                'type' => 'person',
                'name' => $name,
                'cnp' => null,
                'cui' => null,
                'country' => 'România',
            ]);

            return response()->json([
                'client' => $this->clientPayload($client),
                'existing' => false,
                'mode' => 'person_anonymous',
            ], 201);
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        // CNP persoană fizică (13 cifre)
        if (strlen($digits) === 13) {
            $existing = $company->clients()
                ->where('type', 'person')
                ->where('cnp', $digits)
                ->first();
            if ($existing) {
                return response()->json([
                    'client' => $this->clientPayload($existing),
                    'existing' => true,
                    'mode' => 'person_cnp',
                ]);
            }

            if ($name === '') {
                return response()->json([
                    'message' => 'Client nou cu acest CNP — completează numele persoanei.',
                    'need_name' => true,
                    'mode' => 'person_cnp',
                    'cnp' => $digits,
                ], 422);
            }

            $client = Client::create([
                'company_id' => $company->id,
                'type' => 'person',
                'name' => $name,
                'cnp' => $digits,
                'cui' => null,
                'country' => 'România',
            ]);

            return response()->json([
                'client' => $this->clientPayload($client),
                'existing' => false,
                'mode' => 'person_cnp',
            ], 201);
        }

        // Firmă după CUI (2–10 cifre tipic)
        $cui = $digits;
        $payload = [
            'company_id' => $company->id,
            'type' => 'company',
            'country' => 'România',
        ];

        if ($request->boolean('from_anaf') && $cui !== '') {
            $found = $anaf->lookup($cui);
            if (! $found) {
                return response()->json(['message' => 'Nu am găsit firma în ANAF.'], 404);
            }
            $payload = array_merge($payload, [
                'name' => $found['name'],
                'cui' => $found['cui'] ?: $cui,
                'reg_com' => $found['reg_com'] ?: null,
                'address' => $found['address'] ?: null,
                'city' => $found['city'] ?: null,
                'county' => $found['county'] ?: null,
                'phone' => $found['phone'] ?: null,
            ]);
        } elseif ($request->filled('name') || $request->filled('type')) {
            $data = $this->validated($request);
            $payload = array_merge($payload, $data, ['company_id' => $company->id]);
        } elseif ($cui !== '') {
            // Doar CUI fără from_anaf: încearcă ANAF automat
            $found = $anaf->lookup($cui);
            if (! $found) {
                return response()->json(['message' => 'Nu am găsit firma în ANAF. Folosește + ANAF sau adaugă clientul manual.'], 404);
            }
            $payload = array_merge($payload, [
                'name' => $found['name'],
                'cui' => $found['cui'] ?: $cui,
                'reg_com' => $found['reg_com'] ?: null,
                'address' => $found['address'] ?: null,
                'city' => $found['city'] ?: null,
                'county' => $found['county'] ?: null,
                'phone' => $found['phone'] ?: null,
            ]);
        }

        if (empty($payload['name'])) {
            return response()->json(['message' => 'Denumirea clientului este obligatorie.'], 422);
        }

        if ($cui !== '') {
            $existing = $company->clients()
                ->where(function ($q) use ($cui) {
                    $q->where('cui', $cui)->orWhere('cui', 'RO'.$cui);
                })
                ->first();
            if ($existing) {
                return response()->json([
                    'client' => $this->clientPayload($existing),
                    'existing' => true,
                    'mode' => 'company',
                ]);
            }
        }

        $client = Client::create($payload);

        return response()->json([
            'client' => $this->clientPayload($client),
            'existing' => false,
            'mode' => 'company',
        ], 201);
    }

    private function isAnonymousPersonMarker(string $raw): bool
    {
        $raw = trim($raw);

        return in_array($raw, ['-', '–', '—', '−'], true);
    }

    private function clientPayload(Client $client): array
    {
        $idLabel = $client->type === 'person'
            ? ($client->cnp ?: '-')
            : ($client->cui ?: '');

        return [
            'id' => $client->id,
            'name' => $client->name,
            'type' => $client->type,
            'cui' => $client->cui,
            'cnp' => $client->cnp,
            'reg_com' => $client->reg_com,
            'address' => $client->fullAddress(),
            'id_label' => $idLabel,
        ];
    }

    public function edit(Client $client, CompanyContext $context, ClientBalanceService $balances): View
    {
        $this->authorizeClient($client, $context, 'clients_manage');
        $openInvoices = $balances->openInvoices($client);
        $currentBalance = $balances->currentBalance($client, $openInvoices);
        $openRemaining = $balances->openInvoicesRemaining($client, $openInvoices);

        return view('clients.edit', compact('client', 'currentBalance', 'openRemaining'));
    }

    public function update(Request $request, Client $client, CompanyContext $context): RedirectResponse
    {
        $this->authorizeClient($client, $context, 'clients_manage');
        $client->update($this->validated($request, $client));

        return redirect()->route('clients.index')->with('status', 'Client actualizat.');
    }

    /**
     * Comutator ON/OFF „Se calculeaza / factureaza” de pe fișa clientului.
     */
    public function updatePenaltyBilling(Request $request, Client $client, CompanyContext $context): RedirectResponse
    {
        $this->authorizeClient($client, $context, 'clients_manage');

        $enabled = $request->boolean('penalty_billing_enabled');
        $client->forceFill(['penalty_billing_enabled' => $enabled])->save();

        return back()->with(
            'status',
            $enabled
                ? __('Facturarea penalităților este ON — apar pe următoarea factură.')
                : __('Facturarea penalităților este OFF — calculul continuă, fără linii pe facturi.')
        );
    }

    public function destroy(Client $client, CompanyContext $context): RedirectResponse
    {
        $this->authorizeClient($client, $context, 'clients_manage');
        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Client șters.');
    }

    private function validated(Request $request, ?Client $client = null): array
    {
        // Sold inițial necompletat = 0 (înainte de validare numerică).
        $rawOpening = trim((string) $request->input('opening_balance', ''));
        $request->merge([
            'opening_balance' => $rawOpening === ''
                ? 0
                : str_replace(',', '.', $rawOpening),
        ]);

        $rawPenalty = trim((string) $request->input('penalty_percent', ''));
        $rawInstAmount = trim((string) $request->input('opening_installment_amount', ''));
        $rawInstCount = trim((string) $request->input('opening_installment_count', ''));
        $request->merge([
            'penalty_percent' => $rawPenalty === ''
                ? null
                : str_replace(',', '.', $rawPenalty),
            'penalty_billing_enabled' => $request->boolean('penalty_billing_enabled'),
            'opening_installment_amount' => $rawInstAmount === ''
                ? null
                : str_replace(',', '.', $rawInstAmount),
            'opening_installment_count' => $rawInstCount === '' ? null : $rawInstCount,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:company,person'],
            'cui' => ['nullable', 'string', 'max:20'],
            'reg_com' => ['nullable', 'string', 'max:50'],
            'admin_last_name' => ['nullable', 'string', 'max:100'],
            'admin_first_name' => ['nullable', 'string', 'max:100'],
            'cnp' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'county' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                foreach (dc_parse_emails(is_string($value) ? $value : null) as $email) {
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $fail('Adresă email invalidă: '.$email);
                    }
                }
            }],
            'iban' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'string', 'max:20'],
            'opening_installment_amount' => ['nullable', 'numeric', 'min:0'],
            'opening_installment_count' => ['nullable', 'integer', 'min:1', 'max:120'],
            'penalty_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'penalty_billing_enabled' => ['sometimes', 'boolean'],
        ]);

        $data['opening_balance'] = round((float) ($data['opening_balance'] ?? 0), 2);
        if (abs($data['opening_balance']) < 0.009) {
            $data['opening_balance'] = 0.0;
        }

        if ($data['penalty_percent'] === null || $data['penalty_percent'] === '') {
            $data['penalty_percent'] = null;
        } else {
            $data['penalty_percent'] = round((float) $data['penalty_percent'], 4);
            if ($data['penalty_percent'] <= 0) {
                $data['penalty_percent'] = null;
            }
        }
        $data['penalty_billing_enabled'] = (bool) ($data['penalty_billing_enabled'] ?? false);

        if ($data['opening_installment_amount'] === null || $data['opening_installment_amount'] === '') {
            $data['opening_installment_amount'] = null;
        } else {
            $data['opening_installment_amount'] = round((float) $data['opening_installment_amount'], 2);
            if ($data['opening_installment_amount'] < 0.01) {
                $data['opening_installment_amount'] = null;
            }
        }
        if ($data['opening_installment_count'] === null || $data['opening_installment_count'] === '') {
            $data['opening_installment_count'] = null;
        } else {
            $data['opening_installment_count'] = (int) $data['opening_installment_count'];
            if ($data['opening_installment_count'] < 1) {
                $data['opening_installment_count'] = null;
            }
        }
        if ($data['opening_installment_amount'] === null || $data['opening_installment_count'] === null) {
            $data['opening_installment_amount'] = null;
            $data['opening_installment_count'] = null;
        }

        $rawDate = trim((string) ($data['opening_balance_date'] ?? ''));
        if ($rawDate === '') {
            // Implicit: data creării clientului (la creare = azi).
            $data['opening_balance_date'] = $client
                ? $client->effectiveOpeningBalanceDate()
                : now()->toDateString();
        } else {
            $parsed = dc_parse_date($rawDate);
            if ($parsed === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $parsed)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'opening_balance_date' => 'Data soldului inițial trebuie să fie zz/ll/aaaa.',
                ]);
            }
            $data['opening_balance_date'] = $parsed;
        }

        if (array_key_exists('email', $data)) {
            $emails = dc_parse_emails($data['email'] ?? null);
            $data['email'] = $emails === [] ? null : implode(', ', $emails);
        }

        if (filled($data['iban'] ?? null)) {
            $data['iban'] = mb_strtoupper(preg_replace('/\s+/', '', (string) $data['iban']) ?? '', 'UTF-8');
            if (! filled($data['bank_name'] ?? null)) {
                $data['bank_name'] = app(CompanyBankService::class)->bankNameFromIban($data['iban']);
            }
        } else {
            $data['iban'] = null;
        }

        if (array_key_exists('bank_name', $data)) {
            $data['bank_name'] = filled($data['bank_name'] ?? null)
                ? mb_strtoupper(trim((string) $data['bank_name']), 'UTF-8')
                : null;
        }

        // Nu stocăm „-” ca CNP — altfel toate facturile PF fără CNP se lipesc de același client.
        if (isset($data['cnp']) && in_array(trim((string) $data['cnp']), ['-', '–', '—', '−', ''], true)) {
            $data['cnp'] = null;
        }

        return $data;
    }

    private function authorizeClient(Client $client, CompanyContext $context, string $ability = 'clients_view'): void
    {
        abort_unless($client->company_id === $context->current()?->id, 403);
        $this->authorizeCompanyAbility($context->current(), $ability);
    }

    /** @return Collection<int, Client> */
    private function anafSyncableClients(Company $company): Collection
    {
        return $company->clients()
            ->where(function ($q) {
                $q->where('type', 'company')->orWhereNull('type');
            })
            ->whereNotNull('cui')
            ->where('cui', '!=', '')
            ->orderBy('name')
            ->get()
            ->filter(fn (Client $client) => $this->clientCuiDigits($client) !== null)
            ->values();
    }

    /** CUI valid pentru ANAF (2–10 cifre). Null pentru PF / CNP / fără CUI. */
    private function clientCuiDigits(Client $client): ?string
    {
        if (($client->type ?? 'company') === 'person') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $client->cui) ?? '';
        if (strlen($digits) < 2 || strlen($digits) > 10) {
            return null;
        }

        // CNP greșit pus în CUI are 13 cifre — deja exclus; 10 e max CUI.
        return $digits;
    }
}
