<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermsController extends Controller
{
    public function show(): View
    {
        return view('public.terms');
    }

    public function accept(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->terms_accepted_at = now();
        $user->save();

        $intendedUrl = $request->session()->pull('url.intended');
        $isAdmin = $user->isAdmin() || $user->role === 'admin';
        $intendedIsAdmin = is_string($intendedUrl) && str_contains($intendedUrl, '/admin');

        return redirect()->to($intendedUrl && $intendedIsAdmin === $isAdmin
            ? $intendedUrl
            : route($isAdmin ? 'admin.dashboard' : 'dashboard', absolute: false));
    }
}
