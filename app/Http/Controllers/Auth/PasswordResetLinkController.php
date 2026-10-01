<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $archivedOfficer = User::query()
            ->where('email', $request->input('email'))
            ->where('officer_status', 'archived')
            ->exists();

        if (! $archivedOfficer) {
            try {
                Password::sendResetLink($request->only('email'));
            } catch (\Throwable $exception) {
                Log::error('Password reset email could not be sent.', [
                    'exception' => $exception::class,
                ]);
            }
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
