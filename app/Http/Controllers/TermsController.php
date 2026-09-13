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

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
