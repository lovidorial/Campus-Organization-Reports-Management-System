<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'user')->withCount('activities');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('org_name', 'like', "%{$term}%")
                  ->orWhere('org_type', 'like', "%{$term}%")
                  ->orWhere('college', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('org_type', $request->type);
        }

        $organizations = $query->latest()->paginate(10)->withQueryString();

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('admin.organizations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|max:100',
            'college'     => 'nullable|string|max:100',
            'sc_president'=> 'nullable|string|max:255',
            'term'        => 'nullable|string|max:50',
            'school_year' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        Organization::create($validated);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization)
    {
        $members = $organization->members()->paginate(10);

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

        return view('admin.organizations.show', compact('organization', 'members', 'activities', 'stats'));
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
            'is_active'   => 'boolean',
        ]);

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
