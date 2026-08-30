<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Mail\ReferralRecommendMail;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\User;
use App\Services\AnafClient;
use App\Services\CompanyContext;
use App\Services\CompanyPermission;
use App\Services\DocumentService;
use App\Services\ReliableMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CompanyController extends Controller
{
    use ResolvesApiCompany;

    public function index(Request $request, CompanyPermission $permissions): JsonResponse
    {
        $user = $request->user();
        $companies = $user->companies()->orderBy('companies.name')->get();

        return response()->json([
            'data' => $companies->map(fn (Company $company) => $this->serialize($company, $user, $permissions))->values(),
        ]);
    }

    public function store(Request $request, DocumentService $documents, CompanyContext $context): JsonResponse
    {
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
        ]);

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
        ];

        $company = Company::create($data);
        $company->users()->attach($request->user()->id, ['role' => 'owner']);
        $documents->ensureDefaultSeries($company);
        $context->set($company);

        return response()->json([
            'data' => $this->serialize($company->fresh(), $request->user(), app(CompanyPermission::class)),
        ], 201);
    }

    public function show(Request $request, Company $company, CompanyPermission $permissions): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $company, $permissions);

        return response()->json(['data' => $this->serializeDetailed($company, $request->user(), $permissions)]);
    }

    public function update(Request $request, Company $company, CompanyPermission $permissions): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $company, $permissions, 'settings_manage');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
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
            'iban' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'vat_payer' => ['nullable', 'boolean'],
            'vat_on_collection' => ['nullable', 'boolean'],
            'default_vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_notes' => ['nullable', 'string'],
            'invoice_color' => ['nullable', 'string', 'max:20'],
            'invoice_template' => ['nullable', 'string', Rule::in(array_keys($company->availableInvoiceTemplates()))],
            'document_languages' => ['nullable', 'array'],
            'document_languages.*' => ['string', Rule::in(array_keys(config('document_languages', ['ro' => 'Română'])))],
            'efactura_send_mode' => ['nullable', 'in:on_save,delay_1,delay_2,delay_3,manual'],
            'series_responsible_name' => ['nullable', 'string', 'max:255'],
            'series_responsible_role' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('county', $data)) {
            $data['county'] = dc_normalize_county($data['county']);
        }
        if (array_key_exists('cui', $data) || array_key_exists('vat_payer', $data)) {
            $vat = array_key_exists('vat_payer', $data) ? (bool) $data['vat_payer'] : (bool) $company->vat_payer;
            $data['cui'] = dc_format_cui($data['cui'] ?? $company->cui, $vat);
        }
        if (array_key_exists('document_languages', $data)) {
            $langs = array_values(array_unique($data['document_languages'] ?? ['ro']));
            if ($langs === []) {
                $langs = ['ro'];
            }
            if (! in_array('ro', $langs, true)) {
                array_unshift($langs, 'ro');
            }
            $data['document_languages'] = $langs;
        }

        if ($forced = $company->forcedInvoiceTemplateKey()) {
            $data['invoice_template'] = $forced;
        }

        $company->update($data);

        return response()->json(['data' => $this->serializeDetailed($company->fresh(), $request->user(), $permissions)]);
    }

    public function switch(Request $request, Company $company, CompanyContext $context, CompanyPermission $permissions): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $company, $permissions);
        $context->set($company);

        return response()->json([
            'data' => $this->serialize($company, $request->user(), $permissions),
            'current_company_id' => $company->id,
        ]);
    }

    /**
     * Părăsește o societate (ex. demo detașat de firma operator), dacă userul nu e owner_id.
     */
    public function leave(Request $request, Company $company, CompanyContext $context, CompanyPermission $permissions): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->companies()->where('companies.id', $company->id)->exists(),
            404
        );
        abort_if(
            (int) $company->owner_id === (int) $user->id,
            422,
            'Nu poți părăsi societatea pe care o deții. Transferă ownership sau șterge societatea.'
        );

        $user->companies()->detach($company->id);

        if ((int) $user->current_company_id === (int) $company->id) {
            $next = $user->companies()->orderBy('companies.id')->first();
            if ($next) {
                $context->set($next);
            } else {
                $user->forceFill(['current_company_id' => null])->save();
            }
        }

        return response()->json([
            'message' => 'Acces revocat pentru '.$company->name.'.',
            'current_company_id' => $user->fresh()->current_company_id,
        ]);
    }

    public function anafLookup(Request $request, AnafClient $anaf): JsonResponse
    {
        $request->validate(['cui' => ['required', 'string']]);
        $data = $anaf->lookup($request->string('cui'));
        if (! $data) {
            return response()->json(['message' => 'Nu am găsit firma în ANAF.'], 404);
        }

        return response()->json(['data' => $data]);
    }

    public function series(Request $request): JsonResponse
    {
        // Necesar la emitere (previzualizare număr), nu doar în Setări.
        $company = $this->authorizeAbility($request, 'documents_view');
        $series = $company->series()->orderBy('type')->orderBy('year', 'desc')->orderBy('prefix')->get();

        return response()->json([
            'data' => $series->map(fn ($s) => $this->serializeSeries($s))->values(),
        ]);
    }

    public function storeSeries(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'settings_manage');
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(DocumentSeries::TYPES))],
            'prefix' => ['required', 'string', 'max:20'],
            'first_number' => ['nullable', 'integer', 'min:1'],
            'next_number' => ['required', 'integer', 'min:1'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $firstNumber = (int) ($data['first_number'] ?? $data['next_number']);
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

        $series = DocumentSeries::create([
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

        if (! $isDefault) {
            $hasDefault = DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('type', $data['type'])
                ->where('year', $data['year'])
                ->where('active', true)
                ->where('is_default', true)
                ->exists();
            if (! $hasDefault) {
                $series->update(['is_default' => true]);
            }
        }

        return response()->json(['data' => $this->serializeSeries($series->fresh())], 201);
    }

    public function updateSeries(Request $request, DocumentSeries $series): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'settings_manage');
        abort_unless((int) $series->company_id === (int) $company->id, 404);

        $data = $request->validate([
            'first_number' => ['nullable', 'integer', 'min:1'],
            'next_number' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $firstNumber = (int) ($data['first_number'] ?? $series->first_number ?? $data['next_number']);
        $nextNumber = (int) $data['next_number'];
        if ($nextNumber < $firstNumber) {
            $nextNumber = $firstNumber;
        }

        $active = array_key_exists('active', $data) ? (bool) $data['active'] : (bool) $series->active;
        $isDefault = array_key_exists('is_default', $data) ? (bool) $data['is_default'] : (bool) $series->is_default;
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
            $fallback?->update(['is_default' => true]);
        }

        return response()->json(['data' => $this->serializeSeries($series->fresh())]);
    }

    public function destroySeries(Request $request, DocumentSeries $series): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'settings_manage');
        abort_unless((int) $series->company_id === (int) $company->id, 404);

        $remainingSameType = DocumentSeries::query()
            ->where('company_id', $company->id)
            ->where('type', $series->type)
            ->where('year', $series->year)
            ->where('id', '!=', $series->id)
            ->count();
        if ($remainingSameType === 0) {
            return response()->json([
                'message' => 'Nu poți șterge ultima serie pentru tipul „'.$series->typeLabel().'” ('.$series->year.').',
            ], 422);
        }

        $used = $company->documents()
            ->where('type', $series->type)
            ->where('series', $series->prefix)
            ->whereYear('issue_date', $series->year)
            ->whereIn('status', ['issued', 'storno'])
            ->exists();
        if ($used) {
            return response()->json([
                'message' => 'Seria '.$series->prefix.' nu poate fi ștearsă: există documente emise pe ea.',
            ], 422);
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

        return response()->json(['message' => 'Seria a fost ștearsă.']);
    }

    /** @return array<string, mixed> */
    private function serializeSeries(DocumentSeries $s): array
    {
        return [
            'id' => $s->id,
            'type' => $s->type,
            'prefix' => $s->prefix,
            'first_number' => (int) ($s->first_number ?? 1),
            'next_number' => $s->next_number,
            'year' => $s->year,
            'description' => $s->description,
            'active' => (bool) $s->active,
            'is_default' => (bool) $s->is_default,
            'updated_at' => optional($s->updated_at)?->toIso8601String(),
        ];
    }

    private function authorizeCompanyAccess(
        Request $request,
        Company $company,
        CompanyPermission $permissions,
        string $ability = 'access',
    ): void {
        $user = $request->user();
        if ($user->is_admin) {
            return;
        }
        $permissions->authorize($user, $company, $ability);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Company $company, $user, CompanyPermission $permissions): array
    {
        $membership = $user->companies()->where('companies.id', $company->id)->first();
        $role = $membership?->pivot?->role
            ?? ((int) $company->owner_id === (int) $user->id ? 'owner' : 'operator');
        $perms = $permissions->normalizePermissions($membership?->pivot?->permissions ?? null, $role);
        if ($role === 'owner' || (int) $company->owner_id === (int) $user->id || $user->is_admin) {
            $perms = $permissions->actionKeys();
        }

        return [
            'id' => $company->id,
            'name' => $company->name,
            'cui' => $company->cui,
            'promo_code' => $company->promo_code,
            'reg_com' => $company->reg_com,
            'address' => $company->address,
            'city' => $company->city,
            'county' => $company->county,
            'country' => $company->country,
            'phone' => $company->phone,
            'email' => $company->email,
            'iban' => $company->iban,
            'bank_name' => $company->bank_name,
            'vat_payer' => (bool) $company->vat_payer,
            'default_vat_rate' => (float) $company->default_vat_rate,
            'efactura_send_mode' => $company->efactura_send_mode,
            'anaf_authorized' => filled($company->anaf_access_token),
            'role' => $role,
            'permissions' => $perms,
            'updated_at' => optional($company->updated_at)?->toIso8601String(),
        ];
    }

    public function sendReferralRecommend(Request $request, Company $company, ReliableMail $mail, CompanyPermission $permissions): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $company, $permissions);
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
            Log::error('API referral recommend mail failed', [
                'company_id' => $company->id,
                'emails' => $recipients->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Emailul nu a putut fi trimis: '.$e->getMessage(),
            ], 422);
        }

        $count = $recipients->count();

        return response()->json([
            'message' => $count === 1
                ? 'Mailul de recomandare a fost trimis către '.$recipients->first().'.'
                : 'Mailul de recomandare a fost trimis către '.$count.' adrese.',
            'sent' => $count,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetailed(Company $company, $user, CompanyPermission $permissions): array
    {
        return array_merge($this->serialize($company, $user, $permissions), [
            'website' => $company->website,
            'capital_social' => $company->capital_social,
            'vat_on_collection' => (bool) $company->vat_on_collection,
            'invoice_notes' => $company->invoice_notes,
            'invoice_color' => $company->invoice_color,
            'invoice_template' => $company->invoiceTemplateKey(),
            'invoice_template_locked' => $company->invoiceTemplateLocked(),
            'series_responsible_name' => $company->series_responsible_name,
            'series_responsible_role' => $company->series_responsible_role,
            'document_languages' => $company->document_languages,
            'preferences' => $company->preferences,
            'anaf_cif' => $company->anaf_cif,
            'anaf_authorized_at' => optional($company->anaf_authorized_at)?->toIso8601String(),
        ]);
    }
}
