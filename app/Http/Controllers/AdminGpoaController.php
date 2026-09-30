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

        $stats = ['total' => Gpoa::excludeAdmins()->count()];

        return view('admin.gpoa.index', compact('gpoas', 'stats'));
    }

    public function show(Gpoa $gpoa)
    {
        $gpoa->load(['user', 'activities.activityRequest.report']);
        $gpoa->loadCount('activityRequests');

        return view('admin.gpoa.show', compact('gpoa'));
    }
}