<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return $next($request);
        }

        if ($user?->terms_accepted_at === null) {
            return redirect()
                ->route('terms.accept')
                ->with('error', 'You must accept the Terms and Conditions to continue.');
        }

        return $next($request);
    }
}
