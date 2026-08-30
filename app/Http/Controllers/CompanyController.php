<?php

namespace App\Http\Controllers;

use App\Mail\EfacturaInviteMail;
use App\Mail\ReferralRecommendMail;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\DocumentSeries;
use App\Models\EfacturaInvite;
use App\Models\User;
use App\Http\Controllers\Concerns\ChecksCompanyPermission;
use App\Services\AccessGate;
use App\Services\AnafClient;
use App\Services\AnafOAuthService;
use App\Services\CompanyBankService;
use App\Services\CompanyBrandingService;
use App\Services\CompanyContext;
use App\Services\CompanyIntegrations;
use App\Services\DocumentService;
use App\Services\OverdueReminderService;
use App\Services\ReferralService;
use App\Services\ReliableMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CompanyController extends Controller
{
    use ChecksCompanyPermission;

    public function index(Request $request, CompanyContext $context, AccessGate $accessGate): View|RedirectResponse
    {
        $companies = $request->user()->companies()
            ->with('owner:id,name,email,plan,access_until,trial_ends_at,is_admin')
            ->orderBy('name')
            ->get();
        $current = $context->current($request->user());

        if (! $request->boolean('all') && $current && $companies->contains('id', $current->id)) {
            return redirect()->route('companies.edit', ['company' => $current, 'tab' => 'generale']);
        }

        // Asigură mereu o societate activă (prima, dacă nu a ales încă).
        if (! $current && $companies->isNotEmpty()) {
            $current = $companies->sortBy('id')->first();
            $context->set($current);
        }

        foreach ($companies as $company) {
            $owner = $company->owner ?: $request->user();
            $until = $accessGate->effectiveAccessUntil($owner);
            $summary = $accessGate->subscriptionSummary($owner);
            $company->setAttribute('access_until_effective', $until);
            $company->setAttribute('access_promotions', $summary['promotions'] ?? []);
            $company->setAttribute('is_active_company', $current && (int) $current->id === (int) $company->id);
        }

        return view('companies.index', [
            'companies' => $companies,
            'currentCompanyId' => $current?->id,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isSubUser()) {
            return redirect()->route('dashboard')
                ->with('warning', 'Doar proprietarul contului poate adăuga societăți noi.');
        }

        return view('companies.create');
    }

    public function store(
        Request $request,
        DocumentService $documents,
        CompanyContext $context,
        CompanyBankService $banks,
        ReferralService $referrals,
    ): RedirectResponse {
        if ($request->user()?->isSubUser()) {
            abort(403, 'Doar proprietarul contului poate adăuga societăți noi.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cui' => ['nullable', 'string', 'max:20'],
            'reg_com' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'county' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'iban' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'vat_payer' => ['nullable', 'boolean'],
            'default_vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'has_referral_code' => ['nullable', 'boolean'],
            'referral_code' => ['nullable', 'string', 'max:20'],
        ]);

        $referrer = null;
        if ($request->boolean('has_referral_code') || filled($data['referral_code'] ?? null)) {
            $referrer = $referrals->validateForCreator($data['referral_code'] ?? null, $request->user());
            if (! $referrer) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Introdu un cod promoțional valid sau debifează opțiunea.',
                ]);
            }
        }

        $data['owner_id'] = $request->user()->id;
        $data['vat_payer'] = $request->boolean('vat_payer', true);
        $data['default_vat_rate'] = $data['default_vat_rate'] ?? 21;
        $data['country'] = $data['country'] ?? 'România';
        $data['county'] = dc_normalize_county($data['county'] ?? null);
        $data['cui'] = dc_format_cui($data['cui'] ?? null, $data['vat_payer']);
        $data['document_languages'] = ['ro'];
        $data['preferences'] = [
            'show_cui_on_docs' => true,
            'show_reg_com_on_docs' => true,
            'show_bank_on_docs' => true,
            'show_product_code' => false,
            'default_due_days' => 15,
            'documents_per_page' => 25,
        ];
        unset($data['has_referral_code'], $data['referral_code']);

        $company = Company::create($data);
        $company->users()->attach($request->user()->id, ['role' => 'owner']);
        $documents->ensureDefaultSeries($company);

        if (filled($data['iban'] ?? null) || filled($data['bank_name'] ?? null)) {
            $bankName = $data['bank_name']
                ?: $banks->bankNameFromIban($data['iban'] ?? null)
                ?: 'Bancă principală';
            $banks->sync($company, [[
                'name' => $bankName,
                'accounts' => [[
                    'iban' => $data['iban'] ?? '',
                    'currency' => 'RON',
                    'show_on_invoice' => '1',
                ]],
            ]]);
        }

        $status = 'Societatea a fost adăugată.';
        if ($referrer) {
            $result = $referrals->apply($company, $referrer, $request->user());
            $status = 'Societatea a fost adăugată. Ai primit +'.$result['invitee_days'].' zile la abonament';
            if ($result['referrer_months'] > 0) {
                $status .= '; firma care te-a recomandat a primit +'.$result['referrer_months'].' lună bonus';
            }
            $status .= '.';
        }

        $context->set($company);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'generale'])
            ->with('status', $status);
    }

    public function edit(Request $request, Company $company, AnafOAuthService $oauth, DocumentService $documents): View
    {
        $this->authorizeCompany($company, 'access');
        $tabs = config('company_tabs');
        $tab = $request->query('tab', 'generale');
        if (! array_key_exists($tab, $tabs)) {
            $tab = 'generale';
        }
        $this->authorizeCompanyTab($company, $tab, false);

        $documents->ensureDefaultSeries($company);
        $company->load(['banks.accounts', 'branches', 'series' => fn ($q) => $q->orderBy('type')->orderBy('prefix')]);

        return view('companies.edit', [
            'company' => $company,
            'tab' => $tab,
            'tabs' => $tabs,
            'anafConfigured' => $oauth->isConfigured(),
            'pendingInvites' => $company->efacturaInvites()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->limit(5)
                ->get(),
            'recentReminders' => $company->overdueReminderLogs()
                ->with('client:id,name,email')
                ->latest('sent_at')
                ->limit(8)
                ->get(),
            'seriesList' => $company->series,
            'branches' => $company->branches,
        ]);
    }

    public function update(
        Request $request,
        Company $company,
        CompanyBankService $bankService,
        CompanyBrandingService $branding,
    ): RedirectResponse {
        $tab = $request->input('tab', 'generale');
        $this->authorizeCompanyTab($company, $tab, true);

        return match ($tab) {
            'generale' => $this->updateGenerale($request, $company),
            'conturi' => $this->updateConturi($request, $company, $bankService),
            'personalizare' => $this->updatePersonalizare($request, $company, $branding),
            'cote-tva' => $this->updateCoteTva($request, $company),
            'efactura' => $this->updateEfactura($request, $company),
            'limbi' => $this->updateLimbi($request, $company),
            'preferinte-personale', 'preferinte-generale' => $this->updatePreferinte($request, $company, $tab),
            'email' => $this->updateEmail($request, $company),
            'notificari' => $this->updateNotificari($request, $company),
            default => back()->with('status', 'Salvat.'),
        };
    }

    public function updateIntegrations(
        Request $request,
        Company $company,
        string $processor,
        CompanyIntegrations $integrations,
    ): RedirectResponse {
        $this->authorizeCompany($company);
        abort_unless(in_array($processor, ['netopia', 'euplatesc', 'mollie', 'stripe'], true), 404);

        return match ($processor) {
            'netopia' => $this->updateCompanyNetopia($request, $company, $integrations),
            'euplatesc' => $this->updateCompanyEuPlatesc($request, $company, $integrations),
            'mollie' => $this->updateCompanyMollie($request, $company, $integrations),
            'stripe' => $this->updateCompanyStripe($request, $company, $integrations),
            default => abort(404),
        };
    }

    public function storeBranch(Request $request, Company $company): RedirectResponse
    {
        $this->authorizeCompany($company);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'county' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_main' => ['nullable', 'boolean'],
        ]);
        $data['is_main'] = $request->boolean('is_main');
        if ($data['is_main']) {
            $company->branches()->update(['is_main' => false]);
        }
        $company->branches()->create($data);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'sedii'])
            ->with('status', 'Sediul a fost adăugat.');
    }

    public function destroyBranch(Company $company, CompanyBranch $branch): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($branch->company_id === $company->id, 404);
        $branch->delete();

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'sedii'])
            ->with('status', 'Sediul a fost șters.');
    }

    public function storeSeries(Request $request, Company $company): RedirectResponse
    {
        $this->authorizeCompany($company);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(DocumentSeries::TYPES))],
            'prefix' => ['required', 'string', 'max:20'],
            'first_number' => ['required', 'integer', 'min:1'],
            'next_number' => ['required', 'integer', 'min:1'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $firstNumber = (int) $data['first_number'];
        $nextNumber = (int) $data['next_number'];
        if ($nextNumber < $firstNumber) {
            $nextNumber = $firstNumber;
        }

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $data['type'])
                ->where('year', $data['year'])
                ->update(['is_default' => false]);
        }

        DocumentSeries::create([
            'company_id' => $company->id,
            'type' => $data['type'],
            'prefix' => strtoupper($data['prefix']),
            'first_number' => $firstNumber,
            'next_number' => $nextNumber,
            'year' => $data['year'],
            'description' => $data['description'] ?? null,
            'active' => true,
            'is_default' => $isDefault,
        ]);

        // Dacă e prima serie activă pe tip+an, o facem implicită.
        if (! $isDefault) {
            $hasDefault = DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $data['type'])
                ->where('year', $data['year'])
                ->where('active', true)
                ->where('is_default', true)
                ->exists();
            if (! $hasDefault) {
                DocumentSeries::query()
                    ->where('company_id', $company->id)
                    ->where('type', $data['type'])
                    ->where('year', $data['year'])
                    ->where('prefix', strtoupper($data['prefix']))
                    ->update(['is_default' => true]);
            }
        }

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'serii'])
            ->with('status', 'Seria a fost adăugată.');
    }

    public function updateSeries(Request $request, Company $company, DocumentSeries $series): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($series->company_id === $company->id, 404);

        $data = $request->validate([
            'first_number' => ['required', 'integer', 'min:1'],
            'next_number' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $firstNumber = (int) $data['first_number'];
        $nextNumber = (int) $data['next_number'];
        if ($nextNumber < $firstNumber) {
            $nextNumber = $firstNumber;
        }

        // Checkbox-urile debifate nu apar în request — boolean() fără default = false.
        $active = $request->boolean('active');
        $isDefault = $request->boolean('is_default');

        // Serie inactivă nu poate fi implicită.
        if (! $active) {
            $isDefault = false;
        }

        if ($isDefault) {
            DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $series->type)
                ->where('year', $series->year)
                ->where('id', '!=', $series->id)
                ->update(['is_default' => false]);
        }

        $series->update([
            'first_number' => $firstNumber,
            'next_number' => $nextNumber,
            'description' => $data['description'] ?? null,
            'active' => $active,
            'is_default' => $isDefault,
        ]);

        // Păstrează o serie implicită activă pe tip+an, dacă e posibil.
        $hasDefault = DocumentSeries::query()
            ->where('company_id', $company->id)
            ->where('type', $series->type)
            ->where('year', $series->year)
            ->where('active', true)
            ->where('is_default', true)
            ->exists();

        if (! $hasDefault) {
            $fallback = DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $series->type)
                ->where('year', $series->year)
                ->where('active', true)
                ->orderBy('id')
                ->first();
            if ($fallback) {
                $fallback->update(['is_default' => true]);
            }
        }

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'serii'])
            ->with('status', 'Seria a fost actualizată.');
    }

    public function destroySeries(Company $company, DocumentSeries $series): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($series->company_id === $company->id, 404);

        $remainingSameType = DocumentSeries::query()
            ->where('company_id', $company->id)
            ->where('type', $series->type)
            ->where('year', $series->year)
            ->where('id', '!=', $series->id)
            ->count();

        if ($remainingSameType === 0) {
            return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'serii'])
                ->with('status', 'Nu poți șterge ultima serie pentru tipul „'.$series->typeLabel().'” ('.$series->year.'). Adaugă mai întâi o altă serie, apoi poți șterge pe aceasta.');
        }

        $used = $company->documents()
            ->where('type', $series->type)
            ->where('series', $series->prefix)
            ->whereYear('issue_date', $series->year)
            ->whereIn('status', ['issued', 'storno'])
            ->exists();

        if ($used) {
            return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'serii'])
                ->with('status', 'Seria '.$series->prefix.' ('.$series->year.') nu poate fi ștearsă: există documente emise pe ea.');
        }

        $wasDefault = $series->is_default;
        $type = $series->type;
        $year = $series->year;
        $series->delete();

        if ($wasDefault) {
            DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $type)
                ->where('year', $year)
                ->where('active', true)
                ->orderBy('id')
                ->limit(1)
                ->update(['is_default' => true]);
        }

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'serii'])
            ->with('status', 'Seria a fost ștearsă.');
    }

    /** Decizie de inseriere serii (OMFP 2634/2015) — doar serii active. */
    public function seriesDecision(Request $request, Company $company)
    {
        $this->authorizeCompany($company);

        $data = $request->validate([
            'responsible_name' => ['required', 'string', 'max:255'],
            'responsible_role' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ], [
            'responsible_name.required' => 'Completează persoana responsabilă.',
        ]);

        $year = (int) ($data['year'] ?? date('Y'));
        $series = $company->series()
            ->where('active', true)
            ->where('year', $year)
            ->orderBy('type')
            ->orderBy('prefix')
            ->get();

        if ($series->isEmpty()) {
            return redirect()
                ->route('companies.edit', ['company' => $company, 'tab' => 'serii'])
                ->with('status', 'Nu există serii active pentru anul '.$year.'.');
        }

        $responsibleName = trim($data['responsible_name']);
        $responsibleRole = trim((string) ($data['responsible_role'] ?? '')) ?: 'Administrator';

        $company->forceFill([
            'series_responsible_name' => $responsibleName,
            'series_responsible_role' => $responsibleRole,
        ])->save();

        $pdf = Pdf::loadView('companies.series-decision-pdf', [
            'company' => $company,
            'series' => $series,
            'year' => $year,
            'responsibleName' => $responsibleName,
            'responsibleRole' => $responsibleRole,
            'decisionDate' => now(),
        ]);

        $filename = 'Decizie-inseriere-serii-'.$year.'-'.$company->id.'.pdf';

        return $pdf->download($filename);
    }

    public function runOverdueReminders(Company $company, OverdueReminderService $reminders): RedirectResponse
    {
        $this->authorizeCompany($company);

        if (! $company->overdue_reminders_enabled) {
            return back()->with('status', 'Activează mai întâi notificările de restanțe și salvează.');
        }

        $sent = $reminders->processCompany($company, 100);

        return back()->with('status', $sent > 0
            ? "Au fost trimise {$sent} notificări de restanțe."
            : 'Nu există clienți eligibili acum (fără restanțe, fără email sau deja notificați în intervalul setat).');
    }

    public function extendAnaf(Company $company, AnafOAuthService $oauth): RedirectResponse
    {
        $this->authorizeCompanyTab($company, 'efactura', true);

        if (! $company->isAnafAuthorized()) {
            return back()->withErrors(['efactura' => 'Firma nu este autorizată în SPV. Autorizează mai întâi conectarea.']);
        }

        try {
            $oauth->refresh($company);
            $company->refresh();
        } catch (\Throwable $e) {
            return back()->withErrors([
                'efactura' => 'Nu am putut reîmprospăta tokenul ANAF. Folosește „Reautorizează SPV” cu certificatul digital. '.$e->getMessage(),
            ]);
        }

        $company->extendAnafConnection(90);

        return back()->with(
            'status',
            'Conectarea SPV a fost prelungită cu 90 de zile, până la '.dc_datetime($company->fresh()->anaf_token_expires_at).'.'
        );
    }

    public function revokeAnaf(Company $company): RedirectResponse
    {
        $this->authorizeCompanyTab($company, 'efactura', true);
        $company->clearAnafAuthorization();

        return back()->with('status', 'Conectarea SPV a fost revocată pentru această societate.');
    }

    public function sendReferralRecommend(Request $request, Company $company, ReliableMail $mail): RedirectResponse
    {
        $this->authorizeCompany($company);

        abort_unless(filled($company->promo_code), 422, 'Societatea nu are cod promoțional.');

        $data = $request->validate([
            'emails' => ['required', 'string', 'max:2000'],
        ]);

        $recipients = collect(preg_split('/[\s,;]+/', $data['emails']) ?: [])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'emails' => 'Introdu cel puțin o adresă de email.',
            ]);
        }

        if ($recipients->count() > 10) {
            throw ValidationException::withMessages([
                'emails' => 'Poți trimite către maximum 10 adrese odată.',
            ]);
        }

        foreach ($recipients as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'emails' => 'Adresa „'.$email.'” nu este validă.',
                ]);
            }
        }

        try {
            $sender = $request->user();
            $usersByEmail = User::query()
                ->whereIn('email', $recipients->all())
                ->get()
                ->keyBy(fn (User $u) => strtolower((string) $u->email));

            foreach ($recipients as $email) {
                $mail->send(
                    new ReferralRecommendMail($company, $sender, $usersByEmail->get($email)),
                    $email,
                    $company
                );
            }
        } catch (Throwable $e) {
            Log::error('Referral recommend mail failed', [
                'company_id' => $company->id,
                'emails' => $recipients->all(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'emails' => 'Emailul nu a putut fi trimis: '.$e->getMessage(),
                ]);
        }

        $count = $recipients->count();

        return back()->with(
            'status',
            $count === 1
                ? 'Mailul de recomandare a fost trimis către '.$recipients->first().'.'
                : 'Mailul de recomandare a fost trimis către '.$count.' adrese.'
        );
    }

    public function inviteEfactura(Request $request, Company $company, ReliableMail $mail): RedirectResponse
    {
        $this->authorizeCompanyTab($company, 'efactura', true);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $invite = EfacturaInvite::createFor($company, $data['email'], $request->user());
        $invite->load('company');
        $inviteUrl = URL::route('anaf.invite', ['token' => $invite->token]);
        $sentAt = now();

        try {
            $mail->send(new EfacturaInviteMail($invite), $data['email']);
            // update pe coloană (nu save full) — evită side-effect pe expires_at în MySQL vechi
            $invite->newQuery()->whereKey($invite->id)->update(['sent_at' => $sentAt]);
            $invite->sent_at = $sentAt;
        } catch (Throwable $e) {
            Log::error('Efactura invite mail failed', [
                'invite_id' => $invite->id,
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Emailul nu a putut fi trimis automat: '.$e->getMessage().' Folosește linkul de mai jos.',
                ])
                ->with('efactura_invite_url', $inviteUrl)
                ->with('efactura_invite_email', $data['email']);
        }

        $when = dc_datetime($sentAt);

        return back()
            ->with('status', "Email de autorizare SPV trimis către {$data['email']} la {$when}.")
            ->with('efactura_invite_sent_at', $when)
            ->with('efactura_invite_email', $data['email'])
            ->with('efactura_invite_url', $inviteUrl);
    }

    public function switch(Request $request, Company $company, CompanyContext $context): RedirectResponse
    {
        $this->authorizeCompany($company, 'access');
        $context->set($company);

        if ($request->input('return') === 'list') {
            return redirect()->route('companies.index', ['all' => 1])
                ->with('status', 'Societate activă: '.$company->name);
        }

        if ($this->companyPermission()->can(auth()->user(), $company, 'settings_view')) {
            return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'generale'])
                ->with('status', 'Societate activă: '.$company->name);
        }

        return redirect()->route('dashboard')
            ->with('status', 'Societate activă: '.$company->name);
    }

    public function lookup(Request $request, AnafClient $anaf)
    {
        $request->validate(['cui' => ['required', 'string']]);
        $data = $anaf->lookup($request->string('cui'));

        if (! $data) {
            return response()->json(['message' => 'Nu am găsit firma în ANAF.'], 404);
        }

        return response()->json($data);
    }

    private function updateGenerale(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cui' => ['nullable', 'string', 'max:20'],
            'reg_com' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'county' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'capital_social' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);
        $data['county'] = dc_normalize_county($data['county'] ?? null);
        $data['cui'] = dc_format_cui($data['cui'] ?? null, (bool) $company->vat_payer);
        $company->update($data);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'generale'])
            ->with('status', 'Datele generale au fost salvate.');
    }

    private function updateConturi(Request $request, Company $company, CompanyBankService $bankService): RedirectResponse
    {
        $request->validate([
            'banks' => ['nullable', 'array'],
            'banks.*.name' => ['nullable', 'string', 'max:100'],
            'banks.*.accounts' => ['nullable', 'array'],
            'banks.*.accounts.*.iban' => ['nullable', 'string', 'max:64'],
            'banks.*.accounts.*.currency' => ['nullable', 'string', 'size:3'],
            'banks.*.accounts.*.show_on_invoice' => ['nullable'],
        ]);
        $bankService->sync($company, $request->input('banks', []));

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'conturi'])
            ->with('status', 'Conturile bancare au fost salvate.');
    }

    private function updatePersonalizare(Request $request, Company $company, CompanyBrandingService $branding): RedirectResponse
    {
        $templates = array_keys($company->availableInvoiceTemplates());

        $scaleRule = ['required', 'string', Rule::in(array_keys(Company::BRANDING_SCALES))];

        $data = $request->validate([
            'invoice_color' => ['nullable', 'string', 'max:20'],
            'invoice_notes' => ['nullable', 'string'],
            'invoice_template' => ['required', 'string', Rule::in($templates)],
            'signature_text' => ['nullable', 'string', 'max:500'],
            'show_signature_text' => ['nullable', 'boolean'],
            'logo_scale' => $scaleRule,
            'signature_scale' => $scaleRule,
            'stamp_scale' => $scaleRule,
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'signature' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'stamp' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_signature' => ['nullable', 'boolean'],
            'remove_stamp' => ['nullable', 'boolean'],
        ], [
            'invoice_template.required' => 'Alege o machetă de factură.',
            'invoice_template.in' => 'Macheta selectată nu este validă.',
        ]);

        $payload = [
            'invoice_color' => $data['invoice_color'] ?? $company->invoice_color,
            'invoice_notes' => $data['invoice_notes'] ?? null,
            'invoice_template' => $company->forcedInvoiceTemplateKey() ?: $data['invoice_template'],
            'logo_scale' => $data['logo_scale'],
            'signature_scale' => $data['signature_scale'],
            'stamp_scale' => $data['stamp_scale'],
            'signature_text' => $data['signature_text'] ?: Company::DEFAULT_SIGNATURE_TEXT,
            'show_signature_text' => $request->boolean('show_signature_text'),
        ];

        foreach ([
            'logo' => 'logo_path',
            'signature' => 'signature_path',
            'stamp' => 'stamp_path',
        ] as $input => $column) {
            if ($request->boolean('remove_'.$input)) {
                $branding->deleteIfExists($company->{$column});
                $payload[$column] = null;
            }

            if ($request->hasFile($input)) {
                $branding->deleteIfExists($payload[$column] ?? $company->{$column});
                $payload[$column] = $branding->storeImage($company, $request->file($input), $input);
            }
        }

        $company->update($payload);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'personalizare'])
            ->with('status', 'Personalizarea a fost salvată.');
    }

    private function updateCoteTva(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'vat_status' => ['required', Rule::in(['payer', 'non_payer'])],
            'vat_on_collection' => ['nullable', 'boolean'],
            'default_vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'vat_status.required' => 'Selectează dacă societatea este plătitor sau neplătitor de TVA.',
            'vat_status.in' => 'Selectează dacă societatea este plătitor sau neplătitor de TVA.',
        ]);

        $isPayer = $data['vat_status'] === 'payer';
        $rate = $isPayer
            ? (float) ($data['default_vat_rate'] ?? 21)
            : 0.0;

        if ($isPayer && $rate <= 0) {
            $rate = 21.0;
        }

        $company->update([
            'vat_payer' => $isPayer,
            'vat_on_collection' => $isPayer && $request->boolean('vat_on_collection'),
            'default_vat_rate' => $rate,
            'cui' => dc_format_cui($company->cui, $isPayer),
        ]);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'cote-tva'])
            ->with('status', 'Cotele TVA au fost salvate.');
    }

    private function updateEfactura(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'efactura_send_mode' => ['required', Rule::in(array_keys(Company::EFACTURA_SEND_MODES))],
        ]);
        $company->update($data);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'efactura'])
            ->with('status', 'Setările e-Factura au fost salvate.');
    }

    private function updateLimbi(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'document_languages' => ['nullable', 'array'],
            'document_languages.*' => ['string', Rule::in(array_keys(config('document_languages', ['ro' => 'Română'])))],
        ]);
        $langs = array_values(array_unique($data['document_languages'] ?? ['ro']));
        if (! in_array('ro', $langs, true)) {
            array_unshift($langs, 'ro');
        }
        if ($langs === []) {
            $langs = ['ro'];
        }
        $company->update(['document_languages' => $langs]);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'limbi'])
            ->with('status', __('Limbile documentelor au fost salvate.'));
    }

    private function updatePreferinte(Request $request, Company $company, string $tab): RedirectResponse
    {
        $prefs = $company->preferences ?? [];

        if ($tab === 'preferinte-generale') {
            $perPage = (int) $request->input('documents_per_page', 25);
            if (! in_array($perPage, [10, 25, 50, 100], true)) {
                $perPage = 25;
            }
            $prefs = array_merge($prefs, [
                'show_cui_on_docs' => $request->boolean('show_cui_on_docs'),
                'show_reg_com_on_docs' => $request->boolean('show_reg_com_on_docs'),
                'show_bank_on_docs' => $request->boolean('show_bank_on_docs'),
                'show_product_code' => $request->boolean('show_product_code'),
                'default_due_days' => (int) $request->input('default_due_days', 15),
                'documents_per_page' => $perPage,
            ]);
        } else {
            $prefs = array_merge($prefs, [
                'personal_default_list' => $request->input('personal_default_list', 'issued'),
                'personal_show_drafts_first' => $request->boolean('personal_show_drafts_first'),
                'personal_confirm_issue' => $request->boolean('personal_confirm_issue', true),
            ]);

            $uiLocale = \App\Support\UiLocales::normalize((string) $request->input('ui_locale', 'ro'));
            $request->user()?->update(['ui_locale' => $uiLocale]);
            $request->session()->put('ui_locale', $uiLocale);
            app()->setLocale($uiLocale);
        }

        $company->update(['preferences' => $prefs]);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => $tab])
            ->with('status', __('Preferințele au fost salvate.'));
    }

    private function updateEmail(Request $request, Company $company): RedirectResponse
    {
        $section = $request->input('email_section', 'text');

        if ($section === 'smtp') {
            $data = $request->validate([
                'mail_smtp_username' => ['nullable', 'email', 'max:255'],
                'mail_smtp_password' => ['nullable', 'string', 'max:255'],
                'mail_smtp_host' => ['nullable', 'string', 'max:255'],
                'mail_smtp_port' => ['nullable', 'integer', Rule::in([25, 465, 587])],
            ]);

            $useCustom = $request->boolean('mail_use_custom_smtp');
            $payload = [
                'mail_use_custom_smtp' => $useCustom,
                'mail_smtp_username' => $data['mail_smtp_username'] ?? null,
                'mail_smtp_host' => $data['mail_smtp_host'] ?? null,
                'mail_smtp_port' => $data['mail_smtp_port'] ?? null,
                'mail_smtp_tls' => $request->boolean('mail_smtp_tls'),
            ];

            if (filled($data['mail_smtp_password'] ?? null)) {
                $payload['mail_smtp_password'] = $data['mail_smtp_password'];
            }

            if ($useCustom) {
                $hasStoredPassword = filled($company->getRawOriginal('mail_smtp_password'));
                $request->validate([
                    'mail_smtp_username' => ['required', 'email', 'max:255'],
                    'mail_smtp_host' => ['required', 'string', 'max:255'],
                    'mail_smtp_port' => ['required', 'integer', Rule::in([25, 465, 587])],
                    'mail_smtp_password' => [
                        $hasStoredPassword ? 'nullable' : 'required',
                        'string',
                        'max:255',
                    ],
                ]);
            }

            $company->update($payload);

            return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'email'])
                ->with('status', 'Configurarea serverului de email a fost salvată.');
        }

        $data = $request->validate([
            'email_invoice_subject' => ['nullable', 'string', 'max:255'],
            'email_invoice_body' => ['nullable', 'string', 'max:20000'],
        ]);
        $company->update($data);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'email'])
            ->with('status', 'Textul emailului a fost salvat.');
    }

    private function updateNotificari(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'overdue_reminder_frequency_days' => ['nullable', 'integer', Rule::in(array_keys(Company::OVERDUE_REMINDER_FREQUENCIES))],
            'overdue_reminder_scope' => ['nullable', Rule::in(array_keys(Company::OVERDUE_REMINDER_SCOPES))],
            'overdue_reminder_grace_days' => ['nullable', 'integer', 'min:0', 'max:90'],
        ]);
        $company->update([
            'overdue_reminders_enabled' => $request->boolean('overdue_reminders_enabled'),
            'overdue_reminder_include_statement' => $request->boolean('overdue_reminder_include_statement'),
            'overdue_reminder_frequency_days' => (int) ($data['overdue_reminder_frequency_days'] ?? 7),
            'overdue_reminder_scope' => $data['overdue_reminder_scope'] ?? 'both',
            'overdue_reminder_grace_days' => (int) ($data['overdue_reminder_grace_days'] ?? 0),
        ]);

        return redirect()->route('companies.edit', ['company' => $company, 'tab' => 'notificari'])
            ->with('status', 'Notificările au fost salvate.');
    }

    private function updateCompanyNetopia(
        Request $request,
        Company $company,
        CompanyIntegrations $integrations,
    ): RedirectResponse {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'sandbox' => ['nullable', 'boolean'],
            'signature' => ['nullable', 'string', 'max:64'],
            'public_cer' => ['nullable', 'file', 'max:512'],
            'private_key' => ['nullable', 'file', 'max:512'],
        ]);

        $signature = trim((string) ($data['signature'] ?? ''));
        if ($signature === '') {
            $signature = trim((string) $integrations->get($company, 'netopia', 'signature', ''));
        }

        $integrations->put($company, 'netopia', [
            'enabled' => $request->boolean('enabled'),
            'sandbox' => $request->boolean('sandbox'),
            'signature' => $signature,
        ]);

        $dir = $integrations->netopiaDir($company);
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        if ($request->hasFile('public_cer')) {
            $request->file('public_cer')->move($dir, 'public.cer');
        }
        if ($request->hasFile('private_key')) {
            $request->file('private_key')->move($dir, 'private.key');
        }

        $ready = $integrations->isNetopiaReady($company->fresh());
        $msg = $ready
            ? 'NETOPIA e activă pentru această firmă. Pe factură/proformă bifează „Permite plata cu cardul online” ca clienții să poată plăti.'
            : 'Setările NETOPIA au fost salvate, dar configurația e încă incompletă (semnătură / certificate).';

        return redirect()
            ->route('companies.edit', ['company' => $company, 'tab' => 'integrari', 'processor' => 'netopia'])
            ->with('status', $msg);
    }

    private function updateCompanyEuPlatesc(
        Request $request,
        Company $company,
        CompanyIntegrations $integrations,
    ): RedirectResponse {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'sandbox' => ['nullable', 'boolean'],
            'mid' => ['nullable', 'string', 'max:64'],
            'key' => ['nullable', 'string', 'max:128'],
        ]);

        $payload = [
            'enabled' => $request->boolean('enabled'),
            'sandbox' => $request->boolean('sandbox'),
            'mid' => trim((string) ($data['mid'] ?? '')),
        ];
        if (array_key_exists('key', $data) && filled($data['key'] ?? null)) {
            $payload['key'] = trim((string) $data['key']);
        }
        $integrations->put($company, 'euplatesc', $payload);

        return redirect()
            ->route('companies.edit', ['company' => $company, 'tab' => 'integrari', 'processor' => 'euplatesc'])
            ->with('status', 'Setările Eu Plătesc au fost salvate pentru această firmă.');
    }

    private function updateCompanyMollie(
        Request $request,
        Company $company,
        CompanyIntegrations $integrations,
    ): RedirectResponse {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'key' => ['nullable', 'string', 'max:128', Rule::when(
                filled($request->input('key')),
                ['regex:/^(test_|live_)[A-Za-z0-9]+$/']
            )],
        ]);

        $payload = [
            'enabled' => $request->boolean('enabled'),
        ];
        if (array_key_exists('key', $data) && filled($data['key'] ?? null)) {
            $payload['key'] = trim((string) $data['key']);
        }
        $integrations->put($company, 'mollie', $payload);

        return redirect()
            ->route('companies.edit', ['company' => $company, 'tab' => 'integrari', 'processor' => 'mollie'])
            ->with('status', 'Setările Mollie au fost salvate pentru această firmă.');
    }

    private function updateCompanyStripe(
        Request $request,
        Company $company,
        CompanyIntegrations $integrations,
    ): RedirectResponse {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'key' => ['nullable', 'string', 'max:255', Rule::when(
                filled($request->input('key')),
                ['regex:/^pk_(test|live)_[A-Za-z0-9]+$/']
            )],
            'secret' => ['nullable', 'string', 'max:255', Rule::when(
                filled($request->input('secret')),
                ['regex:/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/']
            )],
            'webhook_secret' => ['nullable', 'string', 'max:255', Rule::when(
                filled($request->input('webhook_secret')),
                ['regex:/^whsec_[A-Za-z0-9]+$/']
            )],
        ]);

        $payload = [
            'enabled' => $request->boolean('enabled'),
        ];
        if (array_key_exists('key', $data) && filled($data['key'] ?? null)) {
            $payload['key'] = trim((string) $data['key']);
        }
        if (array_key_exists('secret', $data) && filled($data['secret'] ?? null)) {
            $payload['secret'] = trim((string) $data['secret']);
        }
        if (array_key_exists('webhook_secret', $data) && filled($data['webhook_secret'] ?? null)) {
            $payload['webhook_secret'] = trim((string) $data['webhook_secret']);
        }
        $integrations->put($company, 'stripe', $payload);

        return redirect()
            ->route('companies.edit', ['company' => $company, 'tab' => 'integrari', 'processor' => 'stripe'])
            ->with('status', 'Setările Stripe au fost salvate pentru această firmă.');
    }

    private function authorizeCompany(Company $company, string $ability = 'settings_manage'): void
    {
        $this->authorizeCompanyAbility($company, $ability);
    }

    private function authorizeCompanyTab(Company $company, string $tab, bool $write = false): void
    {
        if ($tab === 'preferinte-personale') {
            $this->authorizeCompany($company, 'access');

            return;
        }

        $suffix = $write ? '_manage' : '_view';

        if ($tab === 'efactura') {
            // e-Factura: fie settings, fie dreptul dedicat.
            $user = auth()->user();
            $ok = $this->companyPermission()->can($user, $company, 'efactura'.$suffix)
                || $this->companyPermission()->can($user, $company, 'settings'.$suffix);
            abort_unless($ok, 403);

            return;
        }

        $this->authorizeCompany($company, 'settings'.$suffix);
    }
}
