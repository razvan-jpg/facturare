<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\AdminPromoMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use App\Models\VisitorSession;
use App\Services\AccessGate;
use App\Services\ReliableMail;
use App\Services\SubscriptionInvoiceService;
use App\Services\UserAgentParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminController extends Controller
{
    public function __construct(
        private UserAgentParser $userAgents,
        private AccessGate $accessGate,
        private SubscriptionInvoiceService $subscriptionInvoices,
    ) {}

    public function stats(Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->is_admin, 403, 'Acces rezervat administratorilor.');

        $now = now();
        $weekAgo = $now->copy()->subDays(7);
        $monthAgo = $now->copy()->subDays(30);
        $activeSince = $now->copy()->subMinutes(5);

        $operatorCompany = $this->subscriptionInvoices->issuerCompany();

        $agentRows = $this->publicVisitorsQuery()->get(['user_agent']);

        $registeredUsers = User::query()
            ->withCount(['companies', 'ownedCompanies'])
            ->orderByDesc('is_admin')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'name', 'email', 'created_at', 'plan', 'is_admin', 'access_until', 'trial_ends_at']);

        $userIds = $registeredUsers->pluck('id');
        $sessionsByUser = VisitorSession::query()
            ->whereIn('user_id', $userIds)
            ->orderByDesc('last_seen_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $usersPayload = $registeredUsers->map(function (User $user) use ($sessionsByUser, $activeSince) {
            $session = $sessionsByUser->get($user->id);
            $until = $this->accessGate->effectiveAccessUntil($user);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan,
                'is_admin' => (bool) $user->is_admin,
                'created_at' => optional($user->created_at)?->toIso8601String(),
                'companies_count' => (int) $user->companies_count,
                'owned_companies_count' => (int) $user->owned_companies_count,
                'is_logged_now' => $session && $session->last_seen_at && $session->last_seen_at->gte($activeSince),
                'access_label' => $this->accessGate->accessLabel($user) ?: ($user->is_admin ? 'Admin' : null),
                'access_until' => $until?->toDateString(),
                'last_seen_at' => optional($session?->last_seen_at)?->toIso8601String(),
            ];
        })->values();

        $activeVisitors = $this->publicVisitorsQuery()
            ->with('user:id,name,email')
            ->where('last_seen_at', '>=', $activeSince)
            ->orderByDesc('last_seen_at')
            ->limit(20)
            ->get()
            ->map(function (VisitorSession $visitor) {
                $parsed = $this->userAgents->parse($visitor->user_agent);

                return [
                    'last_seen_at' => optional($visitor->last_seen_at)?->toIso8601String(),
                    'country' => $visitor->country,
                    'country_code' => $visitor->country_code,
                    'browser' => $parsed['browser'],
                    'os' => $parsed['platform'],
                    'user_email' => $visitor->user?->email,
                    'path' => $visitor->last_path,
                    'ip' => $visitor->ip,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'visitors' => [
                    'all' => $this->visitorPeriodStats(null),
                    'month' => $this->visitorPeriodStats($monthAgo),
                    'week' => $this->visitorPeriodStats($weekAgo),
                    'active' => $this->visitorPeriodStats($activeSince),
                ],
                'totals' => [
                    'users' => User::query()->count(),
                    'users_week' => User::query()->where('created_at', '>=', $weekAgo)->count(),
                    'users_logged' => (int) VisitorSession::query()
                        ->whereNotNull('user_id')
                        ->where('last_seen_at', '>=', $activeSince)
                        ->distinct()
                        ->count('user_id'),
                    'companies' => Company::query()->count(),
                    'clients' => $operatorCompany
                        ? Client::query()->where('company_id', $operatorCompany->id)->count()
                        : 0,
                    'invoices_issued' => Document::query()->where('type', 'invoice')->where('status', 'issued')->count(),
                    'invoices_month' => Document::query()
                        ->where('type', 'invoice')
                        ->where('status', 'issued')
                        ->where('issue_date', '>=', $monthAgo->toDateString())
                        ->count(),
                    'payments_month' => (float) Payment::query()
                        ->where('paid_at', '>=', $monthAgo->toDateString())
                        ->sum('amount'),
                    'invoice_total_month' => (float) Document::query()
                        ->where('type', 'invoice')
                        ->where('status', 'issued')
                        ->where('issue_date', '>=', $monthAgo->toDateString())
                        ->sum('total'),
                ],
                'top_countries' => $this->publicVisitorsQuery()
                    ->select('country_code', 'country', DB::raw('COUNT(*) as visitors'))
                    ->whereNotNull('country_code')
                    ->groupBy('country_code', 'country')
                    ->orderByDesc('visitors')
                    ->limit(8)
                    ->get()
                    ->map(fn ($row) => [
                        'code' => $row->country_code,
                        'name' => $row->country,
                        'visitors' => (int) $row->visitors,
                    ])
                    ->values(),
                'top_browsers' => $this->agentBreakdown($agentRows, 'browser'),
                'top_operating_systems' => $this->agentBreakdown($agentRows, 'platform'),
                'active_visitors' => $activeVisitors,
                'users' => $usersPayload,
            ],
        ]);
    }

    public function sendPromoMail(Request $request, ReliableMail $mail): JsonResponse
    {
        abort_unless((bool) $request->user()?->is_admin, 403, 'Acces rezervat administratorilor.');

        $data = $request->validate([
            'emails' => ['required', 'string', 'max:4000'],
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

        if ($recipients->count() > 20) {
            throw ValidationException::withMessages([
                'emails' => 'Poți trimite către maximum 20 de adrese odată.',
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
                $recipientUser = $usersByEmail->get($email);
                $mail->send(new AdminPromoMail($sender, $recipientUser), $email);
            }
        } catch (Throwable $e) {
            Log::error('API admin promo mail failed', [
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
                ? 'Mailul de reclamă a fost trimis către '.$recipients->first().'.'
                : 'Mailul de reclamă a fost trimis către '.$count.' adrese.',
            'sent' => $count,
        ]);
    }

    public function companies(Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->is_admin, 403, 'Acces rezervat administratorilor.');

        $q = trim((string) $request->query('q', ''));

        $companies = Company::query()
            ->with(['owner:id,name,email,plan,access_until,trial_ends_at,is_admin'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('cui', 'like', '%'.$q.'%')
                        ->orWhere('promo_code', 'like', '%'.$q.'%')
                        ->orWhereHas('owner', function ($owner) use ($q) {
                            $owner->where('name', 'like', '%'.$q.'%')
                                ->orWhere('email', 'like', '%'.$q.'%');
                        });
                });
            })
            ->orderBy('name')
            ->limit(100)
            ->get();

        $payload = $companies->map(function (Company $company) {
            $owner = $company->owner;
            $until = $owner ? $this->accessGate->effectiveAccessUntil($owner) : null;

            return [
                'id' => $company->id,
                'name' => $company->name,
                'cui' => $company->cui,
                'promo_code' => $company->promo_code,
                'owner_name' => $owner?->name,
                'owner_email' => $owner?->email,
                'owner_is_admin' => (bool) ($owner?->is_admin),
                'access_label' => $owner
                    ? ($this->accessGate->accessLabel($owner) ?: ($owner->is_admin ? 'Admin' : '—'))
                    : '—',
                'access_until' => $until?->toDateString(),
            ];
        })->values();

        return response()->json(['data' => $payload]);
    }

    private function publicVisitorsQuery(): Builder
    {
        $adminIds = User::query()->where('is_admin', true)->pluck('id');

        return VisitorSession::query()
            ->when($adminIds->isNotEmpty(), fn ($q) => $q->where(function ($inner) use ($adminIds) {
                $inner->whereNull('user_id')->orWhereNotIn('user_id', $adminIds);
            }))
            ->where(function ($q) {
                $q->whereNull('landing_path')
                    ->orWhere(function ($inner) {
                        $inner->where('landing_path', 'not like', 'admin%')
                            ->where('landing_path', 'not like', '/admin%');
                    });
            })
            ->where(function ($q) {
                $q->whereNull('last_path')
                    ->orWhere(function ($inner) {
                        $inner->where('last_path', 'not like', 'admin%')
                            ->where('last_path', 'not like', '/admin%');
                    });
            });
    }

    /**
     * @return Collection<int, array{label: string, visitors: int}>
     */
    private function agentBreakdown(Collection $visitors, string $field): Collection
    {
        return $visitors
            ->map(fn (VisitorSession $v) => $this->userAgents->parse($v->user_agent)[$field])
            ->countBy()
            ->map(fn ($count, $label) => [
                'label' => (string) $label,
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
        $base = fn () => $this->publicVisitorsQuery()
            ->when($since, fn ($q) => $q->where('last_seen_at', '>=', $since));

        return [
            'unique' => (int) $base()->count(),
            'total' => (int) $base()->sum('page_views'),
        ];
    }
}
