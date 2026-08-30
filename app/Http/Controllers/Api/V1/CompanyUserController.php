<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CompanyPermission;
use App\Services\SubuserSeatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CompanyUserController extends Controller
{
    use ResolvesApiCompany;

    public function __construct(
        private CompanyPermission $permissions,
        private SubuserSeatService $seats,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $owner = $request->user();
        abort_unless($owner->canManageCompanyUsers(), 403);

        $users = $this->seats->collaborators($owner);
        $ownedIds = $owner->ownedCompanies()->pluck('id');

        $data = $users->map(function (User $user) use ($ownedIds, $owner) {
            $memberships = $user->companies()
                ->whereIn('companies.id', $ownedIds)
                ->get()
                ->map(function ($company) {
                    $role = $company->pivot->role ?? 'operator';
                    $perms = $this->permissions->normalizePermissions($company->pivot->permissions ?? null, $role);

                    return [
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'role' => $role,
                        'permissions' => $perms,
                    ];
                })->values();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_subuser' => $user->isCreatedBy($owner),
                'is_invited' => $user->isInvitedBy($owner),
                'memberships' => $memberships,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'seat_summary' => $this->seats->summary($owner),
            'abilities' => $this->permissions->abilities(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $owner = $request->user();
        abort_unless($owner->canManageCompanyUsers(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'company_ids' => ['required', 'array', 'min:1'],
            'company_ids.*' => ['integer'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $ownedIds = $owner->ownedCompanies()->pluck('id')->all();
        $companyIds = array_values(array_intersect($data['company_ids'], $ownedIds));
        abort_if($companyIds === [], 422, 'Selectează cel puțin o societate proprie.');

        if (! $this->seats->canCreateSubuser($owner)) {
            return response()->json(['message' => 'Nu mai ai locuri disponibile pentru subuseri.'], 422);
        }

        $perms = $this->permissions->filterChecked($data['permissions'] ?? $this->permissions->actionKeys());

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'created_by_user_id' => $owner->id,
        ]);

        foreach ($companyIds as $companyId) {
            $user->companies()->attach($companyId, [
                'role' => 'operator',
                'permissions' => json_encode($perms),
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function updatePermissions(Request $request, User $user): JsonResponse
    {
        $owner = $request->user();
        abort_unless($owner->canManageCompanyUsers(), 403);
        abort_unless($user->isCreatedBy($owner) || $user->isInvitedBy($owner), 403);

        $data = $request->validate([
            'company_id' => ['required', 'integer'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $company = $owner->ownedCompanies()->whereKey($data['company_id'])->firstOrFail();
        $perms = $this->permissions->filterChecked($data['permissions'] ?? []);

        $user->companies()->updateExistingPivot($company->id, [
            'permissions' => json_encode($perms),
            'role' => 'operator',
        ]);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'permissions' => $perms,
            ],
        ]);
    }
}
