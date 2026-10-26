<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IdentityVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): View
    {
        $user = User::find($id);
        $identityMatches = $user && hash_equals(sha1($user->getEmailForVerification()), $hash);
        if ($identityMatches
            && session()->has('verification-status')
            && (int) session('verification_user_id') === $id) {
            return view('auth.identity-verification-result', [
                'verified' => false,
                'userId' => $user->id,
                'message' => 'A new confirmation link has been sent. It expires in 60 minutes.',
            ]);
        }

        $valid = $identityMatches && URL::hasValidSignature($request);

        if (! $valid) {
            return view('auth.identity-verification-result', [
                'verified' => false,
                'userId' => $user?->id,
                'message' => 'This confirmation link is invalid or has expired. Request a new link to continue.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return view('auth.identity-verification-result', [
            'verified' => true,
            'userId' => $user->id,
            'message' => 'Your identity is confirmed. You can now log in.',
        ]);
    }

    public function resend(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);
        $user = User::find($id);

        if ($user
            && hash_equals(Str::lower($user->email), Str::lower($validated['email']))
            && ! $user->isAdmin()
            && ! $user->hasVerifiedEmail()) {
            $key = 'identity-verification-resend:'.$user->getKey();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return back()
                    ->with('verification_user_id', $user->id)
                    ->with('verification_email', $user->email)
                    ->with('verification-error', 'You have requested too many links. Please try again later.');
            }

            if (! $user->sendEmailVerificationNotification()) {
                return back()
                    ->with('verification_user_id', $user->id)
                    ->with('verification-error', 'You have requested too many links. Please try again later.');
            }
        }

        return back()
            ->with('verification_user_id', $user?->id)
            ->with('verification_email', $user?->email)
            ->with('verification-status', 'If this account can be verified, a confirmation link has been sent.');
    }
}
