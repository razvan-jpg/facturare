<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccessGate;
use App\Services\CompanyContext;
use App\Services\CompanyPermission;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, AccessGate $accessGate, CompanyContext $context, CompanyPermission $permissions): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', strtolower(trim($data['email'])))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Date de autentificare invalide.'],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'ios')->plainTextToken;

        return response()->json($this->authPayload($user, $token, $accessGate, $context, $permissions));
    }

    public function register(Request $request, AccessGate $accessGate, CompanyContext $context, CompanyPermission $permissions): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified_at' => now(),
        ]);

        $accessGate->applyOnRegister($user);
        event(new Registered($user));

        $token = $user->createToken($data['device_name'] ?? 'ios')->plainTextToken;

        return response()->json($this->authPayload($user->fresh(), $token, $accessGate, $context, $permissions), 201);
    }

    public function me(Request $request, AccessGate $accessGate, CompanyContext $context, CompanyPermission $permissions): JsonResponse
    {
        return response()->json($this->userPayload($request->user(), $accessGate, $context, $permissions));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Deconectat.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function authPayload(
        User $user,
        string $token,
        AccessGate $accessGate,
        CompanyContext $context,
        CompanyPermission $permissions,
    ): array {
        return array_merge(
            ['token' => $token, 'token_type' => 'Bearer'],
            $this->userPayload($user, $accessGate, $context, $permissions)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(
        User $user,
        AccessGate $accessGate,
        CompanyContext $context,
        CompanyPermission $permissions,
    ): array {
        $companies = $user->is_admin
            ? $user->companies()->orderBy('companies.name')->get()
            : $user->companies()->orderBy('companies.name')->get();

        $current = $context->current($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan,
                'ui_locale' => $user->uiLocale(),
                'is_admin' => (bool) $user->is_admin,
                'has_access' => $accessGate->hasAccess($user),
                'access_label' => $accessGate->accessLabel($user),
                'current_company_id' => $current?->id ?? $user->current_company_id,
                'can_manage_company_users' => $user->canManageCompanyUsers(),
            ],
            'app_version' => (string) config('dateconta.version'),
            'companies' => $companies->map(function ($company) use ($user, $permissions) {
                $role = $company->pivot->role ?? ((int) $company->owner_id === (int) $user->id ? 'owner' : 'operator');
                $perms = $permissions->normalizePermissions($company->pivot->permissions ?? null, $role);

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'cui' => $company->cui,
                    'promo_code' => $company->promo_code,
                    'role' => $role,
                    'permissions' => $role === 'owner' || (int) $company->owner_id === (int) $user->id
                        ? $permissions->actionKeys()
                        : $perms,
                    'vat_payer' => (bool) $company->vat_payer,
                    'default_vat_rate' => (float) $company->default_vat_rate,
                    'efactura_send_mode' => $company->efactura_send_mode,
                    'anaf_authorized' => filled($company->anaf_access_token),
                    'updated_at' => optional($company->updated_at)?->toIso8601String(),
                ];
            })->values(),
        ];
    }
}
