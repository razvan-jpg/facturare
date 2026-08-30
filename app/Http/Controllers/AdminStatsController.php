<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use App\Models\VisitorSession;
use App\Services\AccessGate;
use App\Services\GeoIpLookup;
use App\Services\SubscriptionInvoiceService;
use App\Services\UserAgentParser;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminStatsController extends Controller
{
    public function __construct(
        private GeoIpLookup $geoIp,
        private UserAgentParser $userAgents,
        private AccessGate $accessGate,
        private SubscriptionInvoiceService $subscriptionInvoices,
    ) {}

    public function __invoke(): View
    {
        $now = now();
        $weekAgo = $now->copy()->subDays(7);
        $monthAgo = $now->copy()->subDays(30);
        $activeSince = $now->copy()->subMinutes(5);

        $visitorStats = [
            'all' => $this->visitorPeriodStats(null),
            'month' => $this->visitorPeriodStats($monthAgo),
            'week' => $this->visitorPeriodStats($weekAgo),
            'active' => $this->visitorPeriodStats($activeSince),
        ];

        $usersTotal = User::query()->count();
        $usersWeek = User::query()->where('created_at', '>=', $weekAgo)->count();
        $usersLogged = (int) VisitorSession::query()
            ->whereNotNull('user_id')
            ->where('last_seen_at', '>=', $activeSince)
            ->distinct()
            ->count('user_id');
        $companiesTotal = Company::query()->count();
        $operatorCompany = $this->subscriptionInvoices->issuerCompany();
        $crmClientsTotal = $operatorCompany
            ? Client::query()->where('company_id', $operatorCompany->id)->count()
            : 0;
        $invoicesIssued = Document::query()->where('type', 'invoice')->where('status', 'issued')->count();
        $invoicesMonth = Document::query()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->where('issue_date', '>=', $monthAgo->toDateString())
            ->count();
        $paymentsMonth = (float) Payment::query()->where('paid_at', '>=', $monthAgo->toDateString())->sum('amount');
        $invoiceTotalMonth = (float) Document::query()
            ->where('type', 'invoice')
            ->where('status', 'issued')
            ->where('issue_date', '>=', $monthAgo->toDateString())
            ->sum('total');

        $agentRows = $this->visitorsQuery()->get(['user_agent']);
        $topCountries = $this->visitorsQuery()
            ->select('country_code', 'country', DB::raw('COUNT(*) as visitors'))
            ->whereNotNull('country_code')
            ->groupBy('country_code', 'country')
            ->orderByDesc('visitors')
            ->limit(8)
            ->get();
        $topBrowsers = $this->agentBreakdown($agentRows, 'browser');
        $topOperatingSystems = $this->agentBreakdown($agentRows, 'platform');

        $recentVisitors = $this->visitorsQuery()
            ->with('user:id,name,email,is_admin')
            ->orderByDesc('last_seen_at')
            ->limit(25)
            ->get();
        $this->backfillMissingCountries($recentVisitors);
        $this->attachAgentLabels($recentVisitors);

        $activeVisitors = $this->visitorsQuery()
            ->with('user:id,name,email,is_admin')
            ->where('last_seen_at', '>=', $activeSince)
            ->orderByDesc('last_seen_at')
            ->limit(20)
            ->get();
        $this->backfillMissingCountries($activeVisitors);
        $this->attachAgentLabels($activeVisitors);

        $platformCompanies = $this->platformCompanies($operatorCompany);

        $registeredUsers = $this->topActiveUsers(5, $activeSince);

        return view('admin.stats', compact(
            'visitorStats',
            'usersTotal',
            'usersWeek',
            'usersLogged',
            'companiesTotal',
            'crmClientsTotal',
            'invoicesIssued',
            'invoicesMonth',
            'paymentsMonth',
            'invoiceTotalMonth',
            'recentVisitors',
            'activeVisitors',
            'platformCompanies',
            'operatorCompany',
            'registeredUsers',
            'topCountries',
            'topBrowsers',
            'topOperatingSystems',
        ));
    }

    /**
     * Top 5 societăți cele mai active (facturi emise pe platformă, apoi updated_at),
     * cu legătură opțională la clientul CRM FLY DAVID (după CUI)
     * și data de sfârșit a perioadei promoționale (access efectiv al proprietarului).
     *
     * @return Collection<int, Company>
     */
    private function platformCompanies(?Company $operator): Collection
    {
        $companies = Company::query()
            ->with(['owner:id,name,email,plan,access_until,trial_ends_at,is_admin,created_by_user_id'])
            ->withCount([
                'documents as platform_invoices_count' => fn ($q) => $q
                    ->where('type', 'invoice')
                    ->where('status', 'issued'),
            ])
            ->orderByDesc('platform_invoices_count')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        if ($companies->isEmpty()) {
            return $companies;
        }

        $normalizedCuis = $companies
            ->map(fn (Company $c) => preg_replace('/\D+/', '', (string) $c->cui) ?: '')
            ->filter()
            ->unique()
            ->values();

        $crmByCui = collect();
        $billingByClientId = collect();
        $billingByCui = collect();

        if ($operator && $normalizedCuis->isNotEmpty()) {
            $crmClients = Client::query()
                ->where('company_id', $operator->id)
                ->where(function ($q) use ($normalizedCuis) {
                    foreach ($normalizedCuis as $cui) {
                        $q->orWhere('cui', $cui)
                            ->orWhere('cui', 'RO'.$cui)
                            ->orWhere('cui', 'ro'.$cui);
                    }
                })
                ->orderBy('id')
                ->get();

            $crmByCui = $crmClients->keyBy(
                fn (Client $c) => preg_replace('/\D+/', '', (string) $c->cui) ?: ''
            );

            $billingByClientId = Document::query()
                ->where('company_id', $operator->id)
                ->whereIn('client_id', $crmClients->pluck('id')->all() ?: [0])
                ->where('type', 'invoice')
                ->where('status', 'issued')
                ->selectRaw('client_id, COUNT(*) as aggregate')
                ->groupBy('client_id')
                ->pluck('aggregate', 'client_id');

            $billingDocs = Document::query()
                ->where('company_id', $operator->id)
                ->where('type', 'invoice')
                ->where('status', 'issued')
                ->where(function ($q) use ($normalizedCuis) {
                    foreach ($normalizedCuis as $cui) {
                        $q->orWhere('client_cui', $cui)
                            ->orWhere('client_cui', 'RO'.$cui)
                            ->orWhere('client_cui', 'ro'.$cui);
                    }
                })
                ->get(['id', 'client_cui']);

            $billingByCui = $billingDocs
                ->groupBy(fn (Document $d) => preg_replace('/\D+/', '', (string) $d->client_cui) ?: '')
                ->map(fn (Collection $rows) => $rows->pluck('id')->unique()->count());
        }

        foreach ($companies as $company) {
            $cui = preg_replace('/\D+/', '', (string) $company->cui) ?: '';
            $crmClient = $cui !== '' ? $crmByCui->get($cui) : null;
            $owner = $company->owner;
            $promoUntil = $owner ? $this->accessGate->effectiveAccessUntil($owner) : null;
            $isPayingUnlimited = $owner
                && ! $owner->is_admin
                && $owner->plan === 'paid'
                && ! $owner->access_until;

            $billingCount = 0;
            if ($crmClient) {
                $billingCount = max(
                    (int) ($billingByClientId[$crmClient->id] ?? 0),
                    $cui !== '' ? (int) ($billingByCui[$cui] ?? 0) : 0,
                );
            } elseif ($cui !== '') {
                $billingCount = (int) ($billingByCui[$cui] ?? 0);
            }

            $company->setAttribute('fly_david_client', $crmClient);
            $company->setAttribute('billing_invoices_count', $billingCount);
            $company->setAttribute('platform_invoices_count', (int) $company->platform_invoices_count);
            $company->setAttribute('promo_ends_at', $promoUntil);
            $company->setAttribute('promo_ends_label', $owner?->is_admin
                ? 'nelimitat'
                : ($isPayingUnlimited ? 'plătitor' : null));
            $company->setAttribute('is_operator', $operator && (int) $company->id === (int) $operator->id);
        }

        return $companies;
    }

    /**
     * Top N utilizatori după ultima activitate (sesiune / societăți proprii / documente).
     *
     * @return Collection<int, User>
     */
    private function topActiveUsers(int $limit, CarbonInterface $activeSince): Collection
    {
        $userIds = User::query()->pluck('id');
        if ($userIds->isEmpty()) {
            return collect();
        }

        $sessionAt = VisitorSession::query()
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, MAX(last_seen_at) as last_at')
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        $companyAt = Company::query()
            ->whereIn('owner_id', $userIds)
            ->selectRaw('owner_id, MAX(updated_at) as last_at')
            ->groupBy('owner_id')
            ->pluck('last_at', 'owner_id');

        $documentAt = Document::query()
            ->whereIn('created_by', $userIds)
            ->selectRaw('created_by, MAX(updated_at) as last_at')
            ->groupBy('created_by')
            ->pluck('last_at', 'created_by');

        $rankedIds = $userIds
            ->map(function ($id) use ($sessionAt, $companyAt, $documentAt) {
                $at = $this->latestTimestamp([
                    $sessionAt->get($id),
                    $companyAt->get($id),
                    $documentAt->get($id),
                ]);

                return [
                    'id' => (int) $id,
                    'ts' => $at?->getTimestamp() ?? 0,
                ];
            })
            ->sortByDesc('ts')
            ->take($limit)
            ->pluck('id')
            ->values()
            ->all();

        if ($rankedIds === []) {
            return collect();
        }

        $users = User::query()
            ->withCount(['companies', 'ownedCompanies'])
            ->whereIn('id', $rankedIds)
            ->get(['id', 'name', 'email', 'created_at', 'plan', 'is_admin', 'access_until', 'trial_ends_at'])
            ->keyBy('id');

        $ordered = collect($rankedIds)
            ->map(fn (int $id) => $users->get($id))
            ->filter()
            ->values();

        $this->attachUserActivity($ordered, $activeSince);

        return $ordered;
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

    private function visitorsQuery(): Builder
    {
        return VisitorSession::query();
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

    private function attachAgentLabels(Collection $visitors): void
    {
        foreach ($visitors as $visitor) {
            $parsed = $this->userAgents->parse($visitor->user_agent);
            $visitor->setAttribute('browser_name', $parsed['browser']);
            $visitor->setAttribute('os_name', $parsed['platform']);
            $visitor->setAttribute('browser_label', $parsed['browser'].' · '.$parsed['platform']);
        }
    }

    /**
     * @return Collection<int, object{label: string, visitors: int}>
     */
    private function agentBreakdown(Collection $visitors, string $field): Collection
    {
        return $visitors
            ->map(fn (VisitorSession $v) => $this->userAgents->parse($v->user_agent)[$field])
            ->countBy()
            ->map(fn ($count, $label) => (object) [
                'label' => $label,
                'visitors' => (int) $count,
            ])
            ->sortByDesc('visitors')
            ->values()
            ->take(8);
    }

    /**
     * @return array{unique: int, total: int}
     */
    private function visitorPeriodStats($since): array
    {
        $base = fn () => $this->visitorsQuery()
            ->when($since, fn ($q) => $q->where('last_seen_at', '>=', $since));

        return [
            'unique' => (int) $base()->count(),
            'total' => (int) $base()->sum('page_views'),
        ];
    }
}
