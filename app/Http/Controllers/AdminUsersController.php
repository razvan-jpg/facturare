<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Document;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Models\VisitorSession;
use App\Services\AccessGate;
use App\Services\CompanyContext;
use App\Services\GeoIpLookup;
use App\Services\UserAgentParser;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminUsersController extends Controller
{
    public function __construct(
        private GeoIpLookup $geoIp,
        private UserAgentParser $userAgents,
        private AccessGate $accessGate,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $activeSince = now()->subMinutes(5);
        $perPage = 40;
        $page = max(1, (int) $request->query('page', 1));

        $users = User::query()
            ->withCount(['companies', 'ownedCompanies'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->get(['id', 'name', 'email', 'created_at', 'plan', 'is_admin', 'access_until', 'trial_ends_at']);

        $this->attachUserActivity($users, $activeSince);

        $sorted = $users
            ->sortByDesc(fn (User $user) => $user->last_activity_at?->getTimestamp() ?? 0)
            ->values();

        $total = $sorted->count();
        $pageItems = $sorted->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $usersLogged = (int) VisitorSession::query()
            ->whereNotNull('user_id')
            ->where('last_seen_at', '>=', $activeSince)
            ->distinct()
            ->count('user_id');

        return view('admin.users.index', [
            'users' => $paginator,
            'q' => $q,
            'usersTotal' => $total,
            'usersLogged' => $usersLogged,
        ]);
    }

    public function show(User $user): View
    {
        $user->load([
            'ownedCompanies' => fn ($q) => $q->orderBy('name'),
            'companies' => fn ($q) => $q->orderBy('companies.name'),
        ]);

        $membershipIds = $user->companies->pluck('id');
        $ownedIds = $user->ownedCompanies->pluck('id');
        $allCompanyIds = $membershipIds->merge($ownedIds)->unique()->values();

        $companies = Company::query()
            ->whereIn('id', $allCompanyIds->all() ?: [0])
            ->orderBy('name')
            ->get()
            ->map(function (Company $company) use ($user) {
                $member = $user->companies->firstWhere('id', $company->id);
                $isOwned = (int) $company->owner_id === (int) $user->id;
                $company->setAttribute('is_owned', $isOwned);
                $company->setAttribute('membership_role', $member?->pivot?->role);
                $company->setAttribute('is_member', $member !== null);

                return $company;
            });

        $attachableCompanies = Company::query()
            ->whereNotIn('id', $allCompanyIds->all() ?: [0])
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'cui']);

        $orders = SubscriptionOrder::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $lastSession = VisitorSession::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_seen_at')
            ->first();

        $currentCompany = $user->current_company_id
            ? Company::query()->find($user->current_company_id)
            : null;

        return view('admin.users.show', [
            'user' => $user,
            'companies' => $companies,
            'attachableCompanies' => $attachableCompanies,
            'orders' => $orders,
            'lastSession' => $lastSession,
            'accessEffectiveUntil' => $this->accessGate->effectiveAccessUntil($user),
            'accessLabel' => $this->accessGate->accessLabel($user),
            'currentCompany' => $currentCompany,
            'isOnline' => $lastSession
                && $lastSession->last_seen_at
                && $lastSession->last_seen_at->gte(now()->subMinutes(5)),
        ]);
    }

    public function enterCompany(User $user, Company $company, CompanyContext $companyContext): RedirectResponse
    {
        $linked = (int) $company->owner_id === (int) $user->id
            || $user->companies()->where('companies.id', $company->id)->exists();

        abort_unless($linked, 404);

        $companyContext->set($company);
        session([
            'admin_support_company_id' => $company->id,
            'admin_support_user_id' => $user->id,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Mod suport: lucrezi în '.$company->name.' (utilizator '.$user->email.').');
    }

    public function attachCompany(Request $request, User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->with('warning', 'Nu asocia firme pe conturi de administrator din această pagină.');
        }

        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'role' => ['required', Rule::in(['owner', 'operator'])],
        ]);

        $company = Company::query()->findOrFail($data['company_id']);

        if ($user->companies()->where('companies.id', $company->id)->exists()) {
            $user->companies()->updateExistingPivot($company->id, ['role' => $data['role']]);

            return back()->with('status', 'Rol actualizat: '.$company->name.' → '.$data['role'].'.');
        }

        $user->companies()->attach($company->id, ['role' => $data['role']]);

        return back()->with('status', 'Acces acordat la '.$company->name.' (rol '.$data['role'].').');
    }

    public function detachCompany(User $user, Company $company): RedirectResponse
    {
        if (! $user->companies()->where('companies.id', $company->id)->exists()) {
            return back()->with('warning', 'Utilizatorul nu are acest acces pe pivot.');
        }

        // Proprietarul (owner_id) trebuie să rămână membru — altfel rămâne owner fără acces.
        if ((int) $company->owner_id === (int) $user->id) {
            return back()->with('warning', 'Nu poți revoca accesul proprietarului firmei. Transferă owner_id sau șterge societatea.');
        }

        $user->companies()->detach($company->id);

        return back()->with('status', 'Acces revocat pentru '.$company->name.'.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->with('warning', 'Conturile de administrator nu pot fi șterse.');
        }

        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('warning', 'Nu poți șterge propriul cont din această listă.');
        }

        $email = $user->email;
        $ownedCount = $user->ownedCompanies()->count();

        try {
            DB::transaction(function () use ($user) {
                // Pivot memberships (dacă FK nu e cascade pe toate mediile).
                $user->companies()->detach();
                $user->delete();
            });
        } catch (Throwable $e) {
            report($e);

            return back()->with('warning', 'Nu am putut șterge utilizatorul: '.$e->getMessage());
        }

        $msg = 'Utilizatorul '.$email.' a fost șters.';
        if ($ownedCount > 0) {
            $msg .= ' Au fost eliminate și '.$ownedCount.' societăți proprii (cu datele aferente).';
        }

        return redirect()->route('admin.users')->with('status', $msg);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function attachUserActivity(Collection $users, CarbonInterface $activeSince): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $userIds = $users->pluck('id');

        $sessionsByUser = VisitorSession::query()
            ->whereIn('user_id', $userIds)
            ->orderByDesc('last_seen_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $this->backfillMissingCountries($sessionsByUser->values());

        $companyActivity = Company::query()
            ->whereIn('owner_id', $userIds)
            ->selectRaw('owner_id, MAX(updated_at) as last_at')
            ->groupBy('owner_id')
            ->pluck('last_at', 'owner_id');

        $documentActivity = Document::query()
            ->whereIn('created_by', $userIds)
            ->selectRaw('created_by, MAX(updated_at) as last_at')
            ->groupBy('created_by')
            ->pluck('last_at', 'created_by');

        foreach ($users as $user) {
            $session = $sessionsByUser->get($user->id);
            $parsed = $this->userAgents->parse($session?->user_agent);
            $derivedAt = $this->latestTimestamp([
                $companyActivity->get($user->id),
                $documentActivity->get($user->id),
            ]);
            $lastActivityAt = $session?->last_seen_at && $derivedAt
                ? ($session->last_seen_at->greaterThan($derivedAt) ? $session->last_seen_at : $derivedAt)
                : ($session?->last_seen_at ?: $derivedAt);

            $hasActivity = $session !== null
                || (int) $user->owned_companies_count > 0
                || (int) $user->companies_count > 0
                || $derivedAt !== null;

            $user->setAttribute('last_session', $session);
            $user->setAttribute('browser_name', $session ? $parsed['browser'] : null);
            $user->setAttribute('os_name', $session ? $parsed['platform'] : null);
            $user->setAttribute('is_logged_now', $session && $session->last_seen_at && $session->last_seen_at->gte($activeSince));
            $user->setAttribute('has_activity', $hasActivity);
            $user->setAttribute('last_activity_at', $lastActivityAt);
            $user->setAttribute('access_effective_until', $this->accessGate->effectiveAccessUntil($user));
        }
    }

    private function backfillMissingCountries(Collection $visitors): void
    {
        $checked = 0;

        foreach ($visitors as $visitor) {
            if (! $visitor || $visitor->country_code || blank($visitor->ip) || $checked >= 8) {
                continue;
            }

            $geo = $this->geoIp->resolve($visitor->ip);
            $checked++;

            if (! ($geo['code'] ?? null)) {
                continue;
            }

            $visitor->forceFill([
                'country_code' => $geo['code'],
                'country' => $geo['name'],
            ])->save();
        }
    }

    private function latestTimestamp(array $values): ?CarbonInterface
    {
        $latest = null;
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $at = $value instanceof CarbonInterface ? $value : Carbon::parse($value);
            if ($latest === null || $at->greaterThan($latest)) {
                $latest = $at;
            }
        }

        return $latest;
    }
}
