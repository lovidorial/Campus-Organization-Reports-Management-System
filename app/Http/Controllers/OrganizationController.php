<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::with('members');

        if ($request->filled('search')) {
            $term = $request->search;

            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('college', 'like', "%{$term}%")
                  ->orWhere('type', 'like', "%{$term}%")
                  ->orWhereHas('members', function ($query) use ($term) {
                      $query->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('student_number', 'like', "%{$term}%");
                  });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'pending') {
                $query->whereDoesntHave('members');
            }
        }

        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, ['name', 'college', 'status'])) {
            if ($sort === 'status') {
                $query->orderBy('is_active', $direction);
            } else {
                $query->orderBy($sort, $direction);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $organizations = $query->paginate(12)->withQueryString();

        $summary = [
            'total' => Organization::count(),
            'active' => Organization::where('is_active', true)->count(),
            'inactive' => Organization::where('is_active', false)->count(),
            'pending' => Organization::whereDoesntHave('members')->count(),
        ];

        return view('admin.organizations.index', compact('organizations', 'summary'));
    }

    public function create()
    {
        return view('admin.organizations.create');
    }

    public function store(Request $request)
    {
        // Build validation rules dynamically to avoid querying non-existing DB columns (e.g. username)
        $rules = [
            'name'                    => 'required|string|max:255',
            'type'                    => 'required|string|max:100',
            'college'                 => 'nullable|string|max:100',
            'sc_president'            => 'nullable|string|max:255',
            'term'                    => 'nullable|string|max:50',
            'school_year'             => 'nullable|string|max:20',
            'description'             => 'nullable|string',
            'logo'                    => 'nullable|image|max:2048',
            'is_active'               => 'boolean',
            'secretary_name'          => 'required|string|max:255',
            'secretary_email'         => 'required|email|max:255|unique:users,email',
            'secretary_username'      => 'nullable|string|max:100',
            'secretary_student_number'=> 'nullable|string|max:100',
            'secretary_password'      => 'required|string|min:8|confirmed',
        ];

        // Only enforce unique username if the users table actually has the column
        try {
            if (Schema::hasColumn('users', 'username')) {
                $rules['secretary_username'] = 'nullable|string|max:100|unique:users,username';
            }
        } catch (\Exception $e) {
            // If there is any DB/schema issue, skip the unique check to avoid throwing SQL errors here.
        }

        $validated = $request->validate($rules);

        $organizationData = [
            'name'         => $validated['name'],
            'type'         => $validated['type'],
            'college'      => $validated['college'],
            'sc_president' => $validated['sc_president'],
            'term'         => $validated['term'],
            'school_year'  => $validated['school_year'],
            'description'  => $validated['description'],
            'is_active'    => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('logo')) {
            $organizationData['logo_path'] = $request->file('logo')->store('organization-logos', 'public');
        }

        $organization = Organization::create($organizationData);

        $userData = [
            'name'            => $validated['secretary_name'],
            'email'           => $validated['secretary_email'],
            'password'        => Hash::make($validated['secretary_password']),
            'role'            => 'user',
            'organization_id' => $organization->id,
            'org_name'        => $organization->name,
            'org_type'        => $organization->type,
            'college'         => $organization->college,
            'position'        => 'Secretary',
        ];

        if (! empty($validated['secretary_username'])) {
            $userData['username'] = $validated['secretary_username'];
        }

        if (! empty($validated['secretary_student_number'])) {
            $userData['student_number'] = $validated['secretary_student_number'];
        }

        User::create($userData);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization account created successfully.');
    }

    public function show(Organization $organization)
    {
        $members = $organization->members()->paginate(10);
        $secretary = $organization->members()->first();

        // Activities submitted by members of this org
        $activities = Activity::whereIn('user_id', $organization->members->pluck('id'))
            ->orWhere('organization', $organization->name)
            ->latest()
            ->paginate(10);

        $stats = [
            'total'    => $activities->total(),
            'approved' => Activity::where(function($q) use ($organization) {
                $q->whereIn('user_id', $organization->members->pluck('id'))
                  ->orWhere('organization', $organization->name);
            })->where('status', 'approved')->count(),
            'pending'  => Activity::where(function($q) use ($organization) {
                $q->whereIn('user_id', $organization->members->pluck('id'))
                  ->orWhere('organization', $organization->name);
            })->where('status', 'pending')->count(),
            'rejected' => Activity::where(function($q) use ($organization) {
                $q->whereIn('user_id', $organization->members->pluck('id'))
                  ->orWhere('organization', $organization->name);
            })->where('status', 'rejected')->count(),
        ];

        return view('admin.organizations.show', compact('organization', 'members', 'activities', 'stats', 'secretary'));
    }

    public function deactivate(Organization $organization)
    {
        $organization->update(['is_active' => false]);

        return back()->with('success', 'Organization account deactivated.');
    }

    public function resetPassword(Organization $organization)
    {
        $secretary = $organization->members()->first();

        if (! $secretary) {
            return back()->with('error', 'No secretary account is linked to this organization.');
        }

        $status = Password::broker()->sendResetLink([
            'email' => $secretary->email,
        ]);

        return back()->with('success', $status === Password::RESET_LINK_SENT
            ? 'Password reset link sent to ' . $secretary->email
            : 'Unable to send password reset link.');
    }

    public function edit(Organization $organization)
    {
        $allUsers = User::where('role', 'user')->get();
        return view('admin.organizations.edit', compact('organization', 'allUsers'));
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|max:100',
            'college'     => 'nullable|string|max:100',
            'sc_president'=> 'nullable|string|max:255',
            'term'        => 'nullable|string|max:50',
            'school_year' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'logo'        => 'nullable|image|max:2048',
            'is_active'   => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('organization-logos', 'public');
        }

        $organization->update($validated);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization updated.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();
        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization deleted.');
    }
}
