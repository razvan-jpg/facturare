<?php

namespace App\Http\Controllers;

use App\Mail\SubuserCreatedMail;
use App\Mail\SubuserInvitedMail;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\CompanyPermission;
use App\Services\ReliableMail;
use App\Services\SubuserAccessPresenter;
use App\Services\SubuserSeatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class CompanyUserController extends Controller
{
    public function __construct(
        private CompanyPermission $permissions,
        private SubuserSeatService $seats,
        private SubuserAccessPresenter $access,
        private ReliableMail $mail,
    ) {}

    public function index(Request $request, CompanyContext $context): View
    {
        $this->ensureCanManage($request->user());

        $users = $this->seats->collaborators($request->user());
        $ownedIds = $request->user()->ownedCompanies()->pluck('id');
        $users->each(function (User $user) use ($ownedIds, $request) {
            $user->setAttribute(
                'companies_count',
                $user->companies()->whereIn('companies.id', $ownedIds)->count()
            );
            $user->setAttribute('is_invited_collaborator', ! $user->isCreatedBy($request->user()));
        });

        $seatSummary = $this->seats->summary($request->user());
        $billingCompany = $context->current($request->user())
            ?: $request->user()->ownedCompanies()->orderBy('id')->first();

        return view('company-users.index', compact('users', 'seatSummary', 'billingCompany'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->ensureCanManage($request->user());

        if (! $this->seats->canCreateSubuser($request->user())) {
            return redirect()
                ->route('company-users.index')
                ->with('warning', 'Nu mai ai locuri disponibile pentru subuseri. Cumpără locuri din Abonament utilizatori (1 EUR / loc / lună, de la 01.04.2027).');
        }

        return view('company-users.create');
    }

    public function lookup(Request $request): JsonResponse
    {
        $owner = $request->user();
        $this->ensureCanManage($owner);

        $email = strtolower(trim((string) $request->query('email', '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['exists' => false]);
        }

        $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $existing) {
            return response()->json(['exists' => false]);
        }

        $blockedSubuser = $existing->isSubUser() && ! $existing->isCreatedBy($owner);
        $alreadyYours = $existing->isCreatedBy($owner) || $existing->isInvitedBy($owner);

        return response()->json([
            'exists' => true,
            'name' => (string) $existing->name,
            'email' => (string) $existing->email,
            'is_admin' => (bool) $existing->is_admin,
            'is_self' => (int) $existing->id === (int) $owner->id,
            'blocked_subuser' => $blockedSubuser,
            'already_yours' => $alreadyYours,
            'mode' => $existing->is_admin ? 'admin_invite' : 'invite',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = $request->user();
        $this->ensureCanManage($owner);

        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);

        $existing = $email !== ''
            ? User::query()->whereRaw('LOWER(email) = ?', [$email])->first()
            : null;

        if ($existing) {
            $request->validate([
                'email' => ['required', 'email', 'max:190'],
            ]);

            return $this->inviteExisting($owner, $existing);
        }

        if (! $this->seats->canCreateSubuser($owner)) {
            return redirect()
                ->route('company-users.index')
                ->with('warning', 'Nu mai ai locuri disponibile pentru subuseri. Cumpără locuri din Abonament utilizatori.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $plainPassword = (string) $data['password'];

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $plainPassword,
            'created_by_user_id' => $owner->id,
            'ui_locale' => $owner->ui_locale ?: 'ro',
            'plan' => $owner->plan ?: 'free_promo',
            'access_until' => $owner->access_until,
            'trial_ends_at' => $owner->trial_ends_at,
        ]);

        $this->rememberPendingNotify($user, 'created', $plainPassword);

        return redirect()
            ->route('company-users.edit', $user)
            ->with('status', 'Utilizatorul a fost creat. Alocă-i societățile și drepturile — la salvare primește email cu datele de acces.');
    }

    public function edit(Request $request, User $user): View
    {
        $owner = $request->user();
        $this->ensureManages($owner, $user);

        $ownedCompanies = $owner->ownedCompanies()->orderBy('name')->get();
        $memberships = $user->companies()
            ->whereIn('companies.id', $ownedCompanies->pluck('id'))
            ->get()
            ->keyBy('id');

        $abilities = $this->permissions->abilities();
        $actionKeys = $this->permissions->actionKeys();

        $matrix = $ownedCompanies->map(function (Company $company) use ($memberships, $actionKeys) {
            $member = $memberships->get($company->id);
            $hasAccess = $member !== null;
            $perms = $hasAccess
                ? $this->permissions->normalizePermissions($member->pivot->permissions ?? null, $member->pivot->role ?? null)
                : [];

            return [
                'company' => $company,
                'access' => $hasAccess,
                'permissions' => collect($actionKeys)->mapWithKeys(
                    fn (string $key) => [$key => in_array($key, $perms, true)]
                )->all(),
            ];
        });

        $isCreatedSubuser = $user->isCreatedBy($owner);
        $isInvited = ! $isCreatedSubuser;
        $isAdminInvite = (bool) $user->is_admin;

        return view('company-users.edit', [
            'managedUser' => $user,
            'matrix' => $matrix,
            'categories' => $this->permissions->categories(),
            'abilities' => $abilities,
            'actionKeys' => $actionKeys,
            'isCreatedSubuser' => $isCreatedSubuser,
            'isInvited' => $isInvited,
            'isAdminInvite' => $isAdminInvite,
        ]);
    }

    public function update(Request $request, User $user, CompanyContext $context): RedirectResponse
    {
        $owner = $request->user();
        $this->ensureManages($owner, $user);

        $isCreated = $user->isCreatedBy($owner);

        $rules = [
            'companies' => ['nullable', 'array'],
            'companies.*.access' => ['nullable', 'boolean'],
            'companies.*.permissions' => ['nullable', 'array'],
            'companies.*.permissions.*' => ['string'],
        ];

        if ($isCreated) {
            $rules['name'] = ['required', 'string', 'max:120'];
            $rules['email'] = ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)];
            $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
        }

        $data = $request->validate($rules);

        $ownedIds = $owner->ownedCompanies()->pluck('id')->all();
        $companiesInput = $data['companies'] ?? [];
        $lockedCompanyIds = $user->is_admin
            ? $user->companies()->whereIn('companies.id', $ownedIds)->pluck('companies.id')->map(fn ($id) => (int) $id)->all()
            : [];

        DB::transaction(function () use ($user, $data, $ownedIds, $companiesInput, $isCreated, $lockedCompanyIds) {
            if ($isCreated) {
                $user->fill([
                    'name' => $data['name'],
                    'email' => $data['email'],
                ]);
                if (! empty($data['password'])) {
                    $user->password = $data['password'];
                    $this->rememberPendingNotify($user, 'created', (string) $data['password']);
                }
                $user->save();
            }

            foreach ($ownedIds as $companyId) {
                $companyId = (int) $companyId;
                $row = $companiesInput[(string) $companyId] ?? $companiesInput[$companyId] ?? null;
                $hasAccess = is_array($row) && ! empty($row['access']);
                $alreadyLocked = in_array($companyId, $lockedCompanyIds, true);

                // Admin invitat: odată pe o societate, nu mai poate fi scos.
                if ($user->is_admin && $alreadyLocked) {
                    $hasAccess = true;
                }

                if (! $hasAccess) {
                    if ($user->is_admin) {
                        continue;
                    }
                    $user->companies()->detach($companyId);
                    continue;
                }

                // Admin: pivot pentru vizibilitate/invitație; drepturile reale rămân de admin.
                if ($user->is_admin) {
                    $checked = $this->permissions->actionKeys();
                } else {
                    $checked = $this->permissions->filterChecked($row['permissions'] ?? []);
                }

                $payload = [
                    'role' => 'operator',
                    'permissions' => json_encode(array_values($checked)),
                ];

                if ($user->companies()->where('companies.id', $companyId)->exists()) {
                    $user->companies()->updateExistingPivot($companyId, $payload);
                } else {
                    $user->companies()->attach($companyId, $payload);
                }
            }
        });

        $user->refresh();
        $this->sendPendingNotifyIfReady($owner, $user, $context);

        return back()->with('status', 'Utilizatorul și drepturile au fost actualizate.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $owner = $request->user();
        $this->ensureManages($owner, $user);

        if ($user->is_admin) {
            return back()->withErrors([
                'delete' => 'Contul de administrator invitat nu poate fi scos de pe societățile tale.',
            ]);
        }

        $email = $user->email;
        $ownedIds = $owner->ownedCompanies()->pluck('id');
        $user->companies()->detach($ownedIds->all());

        // Doar subuserii creați de owner pot fi închiși. Invitații: doar revocare acces.
        if ($user->isCreatedBy($owner)) {
            $user->closeAccount();
            $msg = 'Utilizatorul '.$email.' a fost șters.';
        } else {
            $msg = 'Accesul la societățile tale a fost revocat pentru '.$email.'. Contul utilizatorului rămâne activ.';
        }

        session()->forget([
            $this->notifyKey($user),
            $this->passwordKey($user),
            'inviting_user_id',
        ]);

        return redirect()->route('company-users.index')->with('status', $msg);
    }

    private function inviteExisting(User $owner, User $existing): RedirectResponse
    {
        if ((int) $existing->id === (int) $owner->id) {
            return back()->withErrors(['email' => 'Nu te poți invita pe tine însuți.'])->withInput();
        }

        if ($existing->isSubUser() && ! $existing->isCreatedBy($owner)) {
            return back()->withErrors([
                'email' => 'Acest email aparține unui subuser creat de alt cont și nu poate fi invitat.',
            ])->withInput();
        }

        if ($existing->isCreatedBy($owner) || $existing->isInvitedBy($owner)) {
            return redirect()
                ->route('company-users.edit', $existing)
                ->with('status', 'Utilizatorul există deja în lista ta. Actualizează societățile și drepturile.');
        }

        // Admin invitat: fără loc plătit; rămâne tot timpul cu comportament de admin.
        if (! $existing->is_admin && ! $this->seats->canAddCollaborator($owner, $existing)) {
            return redirect()
                ->route('company-users.index')
                ->with('warning', 'Nu mai ai locuri disponibile. Cumpără locuri din Abonament utilizatori.');
        }

        session(['inviting_user_id' => $existing->id]);
        $this->rememberPendingNotify($existing, 'invited');

        $status = $existing->is_admin
            ? 'Administrator invitat: alege societățile. Păstrează drepturi complete de admin și, odată alocat, nu mai poate fi scos de pe acele firme.'
            : 'Utilizator existent: alocă societățile și drepturile. La salvare primește invitația pe email (fără parolă nouă).';

        return redirect()
            ->route('company-users.edit', $existing)
            ->with('status', $status);
    }

    private function rememberPendingNotify(User $user, string $type, ?string $plainPassword = null): void
    {
        session([
            $this->notifyKey($user) => $type,
        ]);
        if ($plainPassword !== null && $plainPassword !== '') {
            session([$this->passwordKey($user) => $plainPassword]);
        }
    }

    private function sendPendingNotifyIfReady(User $owner, User $user, CompanyContext $context): void
    {
        $type = session($this->notifyKey($user));
        if (! in_array($type, ['created', 'invited'], true)) {
            return;
        }

        $summary = $this->access->accessSummary($owner, $user);
        if ($summary === []) {
            return;
        }

        $companyName = $this->access->primaryCompanyName(
            $owner,
            $context->current($owner)
        );

        try {
            if ($type === 'created') {
                $plain = (string) session($this->passwordKey($user), '');
                if ($plain === '') {
                    return;
                }
                $this->mail->send(new SubuserCreatedMail(
                    recipient: $user,
                    creator: $owner,
                    creatorCompanyName: $companyName,
                    plainPassword: $plain,
                    accessSummary: $summary,
                ), $user->email);
            } else {
                $this->mail->send(new SubuserInvitedMail(
                    recipient: $user,
                    inviter: $owner,
                    inviterCompanyName: $companyName,
                    accessSummary: $summary,
                ), $user->email);
            }

            session()->forget([
                $this->notifyKey($user),
                $this->passwordKey($user),
                'inviting_user_id',
            ]);
        } catch (Throwable $e) {
            Log::warning('Subuser notify mail failed', [
                'type' => $type,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyKey(User $user): string
    {
        return 'subuser_notify_'.$user->id;
    }

    private function passwordKey(User $user): string
    {
        return 'subuser_plain_password_'.$user->id;
    }

    private function ensureCanManage(?User $user): void
    {
        abort_unless($user && $user->canManageCompanyUsers(), 403);
    }

    private function ensureManages(User $owner, User $managed): void
    {
        $this->ensureCanManage($owner);

        if ($managed->isCreatedBy($owner)) {
            return;
        }

        if ($managed->isInvitedBy($owner)) {
            return;
        }

        if ((int) session('inviting_user_id') === (int) $managed->id) {
            return;
        }

        abort(404);
    }
}
