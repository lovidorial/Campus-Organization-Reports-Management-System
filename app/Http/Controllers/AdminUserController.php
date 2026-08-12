<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organization;
use App\Services\OrganizationClassifierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::where('role', '!=', 'admin')
            ->withCount('activities')
            ->with('organization')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|max:255|unique:users',
            'password'        => 'required|string|min:8|confirmed',
            'position'        => 'nullable|string|max:100',
            'org_name'        => 'nullable|string|max:255',
            'org_type'        => 'nullable|string|max:100',
            'college'         => 'nullable|string|max:100',
            'term'            => 'nullable|string|max:50',
            'school_year'     => 'nullable|string|max:20',
        ]);

        $validated = $this->applyOrganizationClassification($validated, app(OrganizationClassifierService::class));

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'user';

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|max:255|unique:users,email,' . $user->id,
            'role'            => 'required|in:user,admin',
            'position'        => 'nullable|string|max:100',
            'org_name'        => 'nullable|string|max:255',
            'org_type'        => 'nullable|string|max:100',
            'college'         => 'nullable|string|max:100',
            'sc_president'    => 'nullable|string|max:255',
            'term'            => 'nullable|string|max:50',
            'school_year'     => 'nullable|string|max:20',
        ]);

        $validated = $this->applyOrganizationClassification($validated, app(OrganizationClassifierService::class));

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot delete an admin user.');
        }
        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    private function applyOrganizationClassification(array $validated, OrganizationClassifierService $classifier): array
    {
        if (empty($validated['org_name'])) {
            return $validated;
        }

        $detected = $classifier->classify($validated['org_name']);
        if (! $detected) {
            return $validated;
        }

        if (empty($validated['org_type'])) {
            $validated['org_type'] = $this->typeForClassification($detected['classification']);
        }

        if (empty($validated['college'])) {
            $validated['college'] = $detected['college_area'];
        }

        return $validated;
    }

    private function typeForClassification(string $classification): string
    {
        return match ($classification) {
            'Major' => 'Major Student Organization',
            'Minor' => 'Minor Student Organization',
            'Specialized' => 'Specialized Student Organization',
            default => $classification,
        };
    }
}
