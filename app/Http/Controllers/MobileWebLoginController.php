<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WebLoginToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileWebLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $plain = (string) $request->query('token', '');
        if ($plain === '') {
            return redirect()->route('login')->withErrors(['email' => 'Link de autentificare invalid.']);
        }

        WebLoginToken::ensureSchema();

        $row = WebLoginToken::query()
            ->where('token_hash', hash('sha256', $plain))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $row) {
            return redirect()->route('login')->withErrors(['email' => 'Linkul a expirat. Deschide din nou din aplicație.']);
        }

        $user = User::query()->find($row->user_id);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Cont invalid.']);
        }

        $row->forceFill(['used_at' => now()])->save();
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->to($row->redirect ?: '/dashboard');
    }
}
