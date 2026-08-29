<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OfficerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->active()->with('organization');

        if (! Auth::user()?->isAdmin()) {
            $query->where('organization_id', Auth::id() ? (Auth::user()->organization_id ?? 0) : 0);
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $activeOfficers = $query->orderBy('org_name')->orderBy('position')->get();
        $organizations = User::query()->select('organization_id', 'org_name')->whereNotNull('organization_id')->distinct()->orderBy('org_name')->get();

        return view('admin.officers.index', [
            'officers' => $activeOfficers,
            'organizations' => $organizations,
            'selectedOrganization' => $request->organization_id,
            'selectedTerm' => $request->term,
            'selectedSchoolYear' => $request->school_year,
        ]);
    }

    public function archive(Request $request, User $user)
    {
        $validated = $request->validate([
            'archived_reason' => 'nullable|string|max:255',
        ]);

        $user->update([
            'officer_status' => 'archived',
            'archived_at' => now(),
            'archived_reason' => $validated['archived_reason'] ?? null,
        ]);

        if (Auth::id() === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been archived and can no longer log in. Contact OSDW if you believe this is a mistake.',
            ]);
        }

        return back()->with('success', $user->name . ' was archived successfully.');
    }

    public function userIndex(Request $request): View
    {
        $user = Auth::user();

        if (! $user || $user->isAdmin()) {
            abort(403);
        }

        $query = User::query()
            ->where('organization_id', $user->organization_id)
            ->where('officer_status', 'active')
            ->with('organization');

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $officers = $query->orderBy('org_name')->orderBy('position')->get();

        return view('admin.officers.index', [
            'officers' => $officers,
            'organizations' => collect(),
            'selectedOrganization' => null,
            'selectedTerm' => $request->term,
            'selectedSchoolYear' => $request->school_year,
        ]);
    }

    public function userHistory(Request $request): View
    {
        $user = Auth::user();

        if (! $user || $user->isAdmin()) {
            abort(403);
        }

        $query = User::query()
            ->where('organization_id', $user->organization_id)
            ->where('officer_status', 'archived')
            ->with('organization');

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $history = $query->orderBy('school_year')->orderBy('term')->orderBy('org_name')->get()->groupBy(function ($user) {
            return ($user->term ?? 'Unknown Term') . ' / ' . ($user->school_year ?? 'Unknown SY');
        });

        return view('admin.officers.history', [
            'history' => $history,
            'organizations' => collect(),
            'selectedOrganization' => null,
            'selectedTerm' => $request->term,
            'selectedSchoolYear' => $request->school_year,
        ]);
    }

    public function restore(User $user)
    {
        $user->update([
            'officer_status' => 'active',
            'archived_at' => null,
            'archived_reason' => null,
        ]);

        return back()->with('success', $user->name . ' was restored to active status.');
    }

    public function history(Request $request): View
    {
        $query = User::query()->archived()->with('organization');

        if (! Auth::user()?->isAdmin()) {
            $query->where('organization_id', Auth::user()->organization_id ?? 0);
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $history = $query->orderBy('school_year')->orderBy('term')->orderBy('org_name')->get()->groupBy(function ($user) {
            return ($user->term ?? 'Unknown Term') . ' / ' . ($user->school_year ?? 'Unknown SY');
        });

        $organizations = User::query()->select('organization_id', 'org_name')->whereNotNull('organization_id')->distinct()->orderBy('org_name')->get();

        return view('admin.officers.history', [
            'history' => $history,
            'organizations' => $organizations,
            'selectedOrganization' => $request->organization_id,
            'selectedTerm' => $request->term,
            'selectedSchoolYear' => $request->school_year,
        ]);
    }
}
