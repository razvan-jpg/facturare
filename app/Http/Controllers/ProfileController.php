<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Self-delete is disabled: users cannot remove themselves.
     * Owners may close only subusers they created (Setări → Utilizatori).
     */
    public function destroy(Request $request): RedirectResponse
    {
        return Redirect::route('profile.edit')
            ->withErrors([
                'userDeletion' => 'Nu poți șterge acest cont din Contul meu. Conturile nu se auto-șterg; doar subuserii creați de tine pot fi închiși din Setări → Utilizatori.',
            ]);
    }
}
