<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $captcha = $this->generateCaptcha();
        request()->session()->put('login_captcha', $captcha);

        return response()
            ->view('auth.login', compact('captcha'))
            ->header('Cache-Control', 'no-store');
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
        $request->ensureIsNotRateLimited();

        $captchaValidator = Validator::make($request->only('captcha'), [
            'captcha' => 'required|string',
        ]);
        $expectedCaptcha = $request->session()->pull('login_captcha');
        $providedCaptcha = trim((string) $request->input('captcha'));
        $captchaIsValid = ! $captchaValidator->fails()
            && is_string($expectedCaptcha)
            && hash_equals(strtoupper($expectedCaptcha), strtoupper($providedCaptcha));

        if (! $captchaIsValid) {
            RateLimiter::hit($request->throttleKey(), 300);
            $request->session()->put('login_captcha', $this->generateCaptcha());

            return back()
                ->withErrors(['captcha' => 'The captcha code is incorrect.'])
                ->withInput($request->only('email'));
        }

        $request->authenticate();

        $user = Auth::user();

        $request->session()->regenerate();
        $intendedUrl = $request->session()->pull('url.intended');
        $isAdmin = $user->isAdmin() || $user->role === 'admin';
        $intendedIsAdmin = is_string($intendedUrl) && str_contains($intendedUrl, '/admin');

        if ($isAdmin) {
            return redirect()->to($intendedUrl && $intendedIsAdmin
                ? $intendedUrl
                : route('admin.dashboard', absolute: false));
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

            return redirect()->to($intendedUrl && ! $intendedIsAdmin
                ? $intendedUrl
                : route('dashboard', absolute: false));
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
