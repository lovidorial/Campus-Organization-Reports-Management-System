<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isTermEnded()) {
            return $next($request);
        }

        activity('auth')
            ->performedOn($user)
            ->withProperties(['user_id' => $user->id])
            ->log('login blocked: term ended');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => User::TERM_ENDED_MESSAGE,
        ]);
    }
}