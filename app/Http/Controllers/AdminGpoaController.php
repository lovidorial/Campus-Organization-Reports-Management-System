<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use Illuminate\Http\Request;

class AdminGpoaController extends Controller
{
    public function index(Request $request)
    {
        $query = Gpoa::with(['user', 'activities'])
            ->withCount('activities')
            ->excludeAdmins()
            ->when($request->term, fn ($query) => $query->where('term', $request->term))
            ->when($request->school_year, fn ($query) => $query->where('school_year', $request->school_year))
            ->when($request->college, fn ($query) => $query->where('college', $request->college))
            ->when($request->organization, fn ($query) => $query->whereHas('user.organization', fn ($organizationQuery) => $organizationQuery->whereKey($request->organization)))
            ->when($request->classification, fn ($query) => $query->whereHas('user.organization', fn ($organizationQuery) => $organizationQuery->where('type', $request->classification)))
            ->when($request->search, function ($query) use ($request) {
                $search = trim($request->search);

                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('org_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })->orWhere('term', 'like', "%{$search}%")
                        ->orWhere('school_year', 'like', "%{$search}%")
                        ->orWhere('college', 'like', "%{$search}%");
                });
            });

        $gpoas = $query->latest()->paginate(20)->appends($request->query());

        $stats = ['total' => $gpoas->total()];
        $terms = Gpoa::excludeAdmins()->select('term')->distinct()->orderBy('term')->pluck('term');
        $schoolYears = Gpoa::excludeAdmins()->select('school_year')->distinct()->orderByDesc('school_year')->pluck('school_year');
        $colleges = Gpoa::excludeAdmins()->select('college')->whereNotNull('college')->distinct()->orderBy('college')->pluck('college');
        $organizations = \App\Models\Organization::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $classifications = \App\Models\Organization::query()->where('is_active', true)->whereNotNull('type')->distinct()->orderBy('type')->pluck('type');

        return view('admin.gpoa.index', compact('gpoas', 'stats', 'terms', 'schoolYears', 'colleges', 'organizations', 'classifications'));
    }

    public function show(Gpoa $gpoa)
    {
        $gpoa->load(['user', 'activities.activityRequest.report']);
        $gpoa->loadCount('activityRequests');

        return view('admin.gpoa.show', compact('gpoa'));
    }
}