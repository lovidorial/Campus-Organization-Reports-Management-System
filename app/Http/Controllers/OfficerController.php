<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OfficerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->active()
            ->where('position', 'Secretary')
            ->with('organization');

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

        $activeSecretaryUsers = $query->orderBy('organization_id')->orderByDesc('updated_at')->get();

        $activeOfficers = $activeSecretaryUsers->groupBy('organization_id')->map(function ($group) {
            $officer = $group->sortByDesc('updated_at')->first();

            if ($officer) {
                $officer->term = $officer->term ?? $officer->organization?->term;
                $officer->school_year = $officer->school_year ?? $officer->organization?->school_year;
            }

            return $officer;
        })->values();

        $organizations = User::query()->select('organization_id', 'org_name')->whereNotNull('organization_id')->distinct()->orderBy('org_name')->get();

        return view('admin.officers.index', [
            'officers' => $activeOfficers,
            'history' => collect(),
            'members' => collect(),
            'organizations' => $organizations,
            'selectedOrganization' => $request->organization_id,
            'selectedTerm' => $request->term,
            'selectedSchoolYear' => $request->school_year,
            'tab' => 'current',
            'isAdminView' => true,
            'filterRoute' => route('admin.officers.index'),
        ]);
    }

    public function create(Request $request, ?User $officer = null): View
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $organizations = Organization::query()
            ->select('id', 'name', 'type', 'college')
            ->orderBy('name')
            ->get();

        $prefill = [
            'name' => '',
            'email' => '',
            'position' => $officer?->position ?? $request->input('position', ''),
            'term' => $officer?->term ?? $request->input('term', ''),
            'school_year' => $officer?->school_year ?? $request->input('school_year', ''),
            'organization_id' => $officer?->organization_id ?? $request->input('organization_id', ''),
            'org_name' => $officer?->org_name ?? $request->input('org_name', ''),
            'org_type' => $officer?->org_type ?? $request->input('org_type', ''),
            'college' => $officer?->college ?? $request->input('college', ''),
        ];

        return view('admin.officers.replacement-create', [
            'officer' => $officer,
            'organizations' => $organizations,
            'prefill' => $prefill,
        ]);
    }

    public function storeReplacement(Request $request)
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'position' => ['required', 'string', 'max:255'],
            'term' => ['nullable', 'string', 'max:50'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'organization_id' => ['required', 'exists:organizations,id'],
            'org_name' => ['nullable', 'string', 'max:255'],
            'org_type' => ['nullable', 'string', 'max:100'],
            'college' => ['nullable', 'string', 'max:100'],
        ]);

        $temporaryPassword = Str::random(10);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => 'user',
            'position' => $validated['position'],
            'term' => $validated['term'] ?? null,
            'school_year' => $validated['school_year'] ?? null,
            'organization_id' => $validated['organization_id'],
            'org_name' => $validated['org_name'] ?? null,
            'org_type' => $validated['org_type'] ?? null,
            'college' => $validated['college'] ?? null,
            'officer_status' => 'active',
        ]);

        return redirect()->route('admin.officers.replacement.success')
            ->with('replacement_name', $user->name)
            ->with('replacement_password', $temporaryPassword);
    }

    public function replacementSuccess(): View
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        return view('admin.officers.replacement-success');
    }

    public function archive(Request $request, User $user)
    {
        $validated = $request->validate([
            'archived_reason' => 'nullable|string|max:255',
        ]);

        if (empty($user->term) || empty($user->school_year)) {
            $fallbackTerm = $user->term ?? $user->organization?->term;
            $fallbackSchoolYear = $user->school_year ?? $user->organization?->school_year;

            $user->fill([
                'term' => $fallbackTerm,
                'school_year' => $fallbackSchoolYear,
            ]);
        }

        $user->update([
            'officer_status' => 'archived',
            'archived_at' => now(),
            'archived_reason' => $validated['archived_reason'] ?? null,
            'term' => $user->term,
            'school_year' => $user->school_year,
        ]);

        if (Auth::id() === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account\'s term has ended and can no longer log in. If you are the outgoing officer, your organization\'s new Secretary should have received new login credentials from OSDW. If you believe this was done in error, please contact OSDW at osdwcsuaparri@gmail.com or via the CSUAparri-OSDW Facebook page for assistance.',
            ]);
        }

        $redirect = back()->with('success', $user->name . ' was archived successfully.');

        if (Auth::user()?->isAdmin()) {
            $redirect->with('replacement_url', route('admin.officers.replacement.create', [
                'officer' => $user->id,
                'position' => $user->position,
                'term' => $user->term,
                'school_year' => $user->school_year,
                'organization_id' => $user->organization_id,
                'org_name' => $user->org_name,
                'org_type' => $user->org_type,
                'college' => $user->college,
            ]));
        }

        return $redirect;
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
            ->where('position', 'Secretary')
            ->with('organization');

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $officers = $query->orderByDesc('updated_at')->get()->take(1)->map(function ($officer) {
            $officer->term = $officer->term ?? $officer->organization?->term;
            $officer->school_year = $officer->school_year ?? $officer->organization?->school_year;

            return $officer;
        });

        return view('admin.officers.index', [
            'officers' => $officers,
            'history' => collect(),
            'members' => collect(),
            'organizations' => collect(),
            'selectedOrganization' => null,
            'selectedTerm' => $request->term,
            'selectedSchoolYear' => $request->school_year,
            'tab' => 'current',
            'isAdminView' => false,
            'filterRoute' => route('organization.officers.index'),
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

        $history = $query->orderBy('school_year')->orderBy('term')->orderBy('org_name')->get()->map(function ($user) {
            $user->term = $user->term ?? $user->organization?->term;
            $user->school_year = $user->school_year ?? $user->organization?->school_year;

            return $user;
        })->groupBy(function ($user) {
            return ($user->term ?? 'Unknown Term') . ' / ' . ($user->school_year ?? 'Unknown SY');
        });

        return view('admin.officers.index', [
            'officers' => collect(),
            'history' => $history,
            'members' => collect(),
            'organizations' => collect(),
            'selectedOrganization' => null,
            'selectedTerm' => $request->term,
            'selectedSchoolYear' => $request->school_year,
            'tab' => 'previous',
            'isAdminView' => false,
            'filterRoute' => route('organization.officers.history'),
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

        $history = $query->orderBy('school_year')->orderBy('term')->orderBy('org_name')->get()->map(function ($user) {
            $user->term = $user->term ?? $user->organization?->term;
            $user->school_year = $user->school_year ?? $user->organization?->school_year;

            return $user;
        })->groupBy(function ($user) {
            return ($user->term ?? 'Unknown Term') . ' / ' . ($user->school_year ?? 'Unknown SY');
        });

        $organizations = User::query()->select('organization_id', 'org_name')->whereNotNull('organization_id')->distinct()->orderBy('org_name')->get();

        return view('admin.officers.index', [
            'officers' => collect(),
            'history' => $history,
            'members' => collect(),
            'organizations' => $organizations,
            'selectedOrganization' => $request->organization_id,
            'selectedTerm' => $request->term,
            'selectedSchoolYear' => $request->school_year,
            'tab' => 'previous',
            'isAdminView' => true,
            'filterRoute' => route('admin.officers.history'),
        ]);
    }
}
