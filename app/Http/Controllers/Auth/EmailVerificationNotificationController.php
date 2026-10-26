<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $key = 'identity-verification-resend:'.$request->user()->getKey();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'You have requested too many links. Please try again later.']);
        }

        RateLimiter::hit($key, 3600);
        if (! $request->user()->sendEmailVerificationNotification()) {
            return back()->withErrors(['email' => 'You have requested too many links. Please try again later.']);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
