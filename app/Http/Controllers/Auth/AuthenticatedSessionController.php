<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if ($user && $user->officer_status === 'archived') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account\'s term has ended and can no longer log in. If you are the outgoing officer, your organization\'s new Secretary should have received new login credentials from OSDW. If you believe this was done in error, please contact OSDW at osdwcsuaparri@gmail.com or via the CSUAparri-OSDW Facebook page for assistance.',
            ]);
        }

        $request->session()->regenerate();

        if ($user->isAdmin() || $user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        if ($user->role === 'user') {
            if ($user->organization_id && ! $user->organization()->exists()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'This organization account has been deleted and can no longer log in.',
                ]);
            }

            return redirect()->intended(route('dashboard', absolute: false));
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors(['role' => 'Please sign in with a student organization account.']);
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
