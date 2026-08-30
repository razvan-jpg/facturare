<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccessGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request, AccessGate $accessGate): JsonResponse
    {
        $user = $request->user();

        // Abonament: doar proprietarul — preferă firma curentă dacă e a lui.
        $billingCompany = $user->ownedCompanies()
            ->when(
                $user->current_company_id,
                fn ($q) => $q->orderByRaw('id = ? desc', [(int) $user->current_company_id])
            )
            ->orderBy('name')
            ->first();

        $locales = [];
        foreach (\App\Support\UiLocales::all() as $code => $meta) {
            $locales[] = [
                'code' => $code,
                'label' => $meta['label'],
                'flag' => $meta['flag'],
                'short' => $meta['short'] ?? strtoupper($code),
            ];
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan,
                'ui_locale' => $user->uiLocale(),
                'has_access' => $accessGate->hasAccess($user),
                'access_label' => $accessGate->accessLabel($user),
                'access_until' => optional($user->access_until)?->toIso8601String(),
                'trial_ends_at' => optional($user->trial_ends_at)?->toIso8601String(),
            ],
            'ui_locales' => $locales,
            'billing_url' => $billingCompany
                ? route('billing.order', $billingCompany)
                : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'ui_locale' => ['nullable', 'string', 'max:10'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (array_key_exists('ui_locale', $data)) {
            $data['ui_locale'] = \App\Support\UiLocales::normalize($data['ui_locale'] ?? 'ro');
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ui_locale' => $user->uiLocale(),
            ],
        ]);
    }

    /**
     * Ștergere cont (App Store 5.1.1): soft-delete + invalidare sesiuni API.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['required', 'accepted'],
        ]);

        if (! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Parola este incorrectă.',
                'code' => 'invalid_password',
            ], 422);
        }

        if ($user->is_admin) {
            return response()->json([
                'message' => 'Contul de administrator nu poate fi șters din aplicație.',
                'code' => 'admin_protected',
            ], 403);
        }

        $user->tokens()->delete();
        $user->closeAccount();

        return response()->json([
            'deleted' => true,
            'message' => 'Contul a fost șters.',
        ]);
    }
}
