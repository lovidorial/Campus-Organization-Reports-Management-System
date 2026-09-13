<?php

namespace App\Http\Controllers;

use App\Models\OrganizationMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrganizationMemberController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', OrganizationMember::class);

        $user = Auth::user();

        $members = OrganizationMember::query()
            ->where('organization_id', $user->organization_id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('admin.officers.index', [
            'officers' => collect(),
            'history' => collect(),
            'members' => $members,
            'organizations' => collect(),
            'selectedOrganization' => null,
            'selectedTerm' => null,
            'selectedSchoolYear' => null,
            'tab' => 'members',
            'isAdminView' => false,
            'filterRoute' => route('organization.members.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', OrganizationMember::class);

        $user = Auth::user();

        $blockedFields = ['email', 'password', 'role', 'username', 'student_number', 'officer_status', 'archived_at', 'archived_reason', 'organization_id'];
        foreach ($blockedFields as $field) {
            if ($request->filled($field)) {
                abort(422, 'This endpoint only accepts org chart roster data and cannot create login accounts.');
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('uploads/members', 'public');
        }

        OrganizationMember::create([
            'organization_id' => $user->organization_id,
            'name' => $validated['name'],
            'position' => $validated['position'],
            'year_level' => $validated['year_level'] ?? null,
            'facebook_url' => $validated['facebook_url'] ?? null,
            'photo_path' => $photoPath,
            'display_order' => 0,
        ]);

        return redirect()->route('organization.members.index')->with('success', 'Member added to the org chart.');
    }

    public function update(Request $request, OrganizationMember $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('uploads/members', 'public');
        }

        $member->update([
            'name' => $validated['name'],
            'position' => $validated['position'],
            'year_level' => $validated['year_level'] ?? null,
            'facebook_url' => $validated['facebook_url'] ?? null,
            'photo_path' => $validated['photo_path'] ?? $member->photo_path,
        ]);

        return redirect()->route('organization.members.index')->with('success', 'Member updated successfully.');
    }

    public function destroy(OrganizationMember $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $user = Auth::user();

        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }

        $member->delete();

        return redirect()->route('organization.members.index')->with('success', 'Member removed from the org chart.');
    }
}
