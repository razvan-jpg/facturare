<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CompanyContext;
use App\Services\VisitorActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, VisitorActivityService $activity): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Asigură o societate activă imediat după login (prima, dacă nu a ales încă).
        app(CompanyContext::class)->current($request->user());

        // Marchează activitatea acum — TrackVisitor poate rata POST-ul de login.
        $activity->touchAuthenticated($request);

        // Fereastră info cont (nume, email, societăți + cod promo / expirare) — 1 minut.
        // put (nu flash): supraviețuiește redirect-urilor din middleware (ex. billing.expired).
        $request->session()->put('show_login_account_modal', true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
