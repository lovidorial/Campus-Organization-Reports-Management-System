<?php

namespace App\Http\Middleware;

use App\Models\Gpoa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedGpoa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $hasApprovedGpoa = Gpoa::where('user_id', $user->id)
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->whereIn('status', ['approved', 'stored'])
            ->exists();

        if (!$hasApprovedGpoa) {
            return redirect()
                ->route('gpoa.index')
                ->with('error', 'You must have an approved GPOA for the current term and school year before submitting activity requests.');
        }

        return $next($request);
    }
}
