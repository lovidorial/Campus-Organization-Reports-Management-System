<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $captcha = $this->generateCaptcha();
        request()->session()->put('login_captcha', $captcha);

        return view('auth.login', compact('captcha'));
    }

    public function refreshCaptcha(Request $request)
    {
        $captcha = $this->generateCaptcha();
        $request->session()->put('login_captcha', $captcha);

        return response()->json(['captcha' => $captcha]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $captchaValidator = Validator::make($request->only('captcha'), [
            'captcha' => 'required|string',
        ]);
        $expectedCaptcha = $request->session()->pull('login_captcha');
        $providedCaptcha = trim((string) $request->input('captcha'));
        $captchaIsValid = ! $captchaValidator->fails()
            && is_string($expectedCaptcha)
            && hash_equals(strtoupper($expectedCaptcha), strtoupper($providedCaptcha));

        if (! $captchaIsValid) {
            $request->session()->put('login_captcha', $this->generateCaptcha());

            return back()
                ->withErrors(['captcha' => 'The captcha code is incorrect.'])
                ->withInput($request->only('email'));
        }

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

    private function generateCaptcha(): string
    {
        $characters = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $captcha = '';

        for ($position = 0; $position < 5; $position++) {
            $captcha .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $captcha;
    }
}
