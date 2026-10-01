<?php

namespace App\Http\Controllers;

use App\Models\GpoaActivity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function publicActivities(Request $request)
    {
        $query = GpoaActivity::query()->with([
            'gpoa.user',
            'activityRequest.report',
            'activityRequest.venueRecord' => fn ($query) => $query->withCount(['scheduledRequests', 'futureReservationRequests']),
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('objectives', 'like', "%{$search}%")
                        ->orWhereHas('gpoa.user', function ($u) use ($search) {
                            $u->where('org_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('activityRequest', fn ($requestQuery) => $requestQuery->where('description', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('organization')) {
            $query->whereHas('gpoa', fn ($gpoaQuery) => $gpoaQuery->where('user_id', $request->organization));
        }

        if ($request->filled('school_year')) {
            $query->whereHas('gpoa', function ($q) use ($request) {
                $q->where('school_year', $request->school_year);
            });
        }

        $allActivities = $query->orderByDesc('date')->get();
        foreach ($allActivities as $activity) {
            $activity->setRelation('user', $activity->gpoa?->user);
            $activity->setRelation('report', $activity->activityRequest?->report);
            $activity->setRelation('venueRecord', $activity->activityRequest?->venueRecord);
            $activity->setAttribute('participants_count', $activity->activityRequest?->participants_count);
            $activity->setAttribute('description', $activity->activityRequest?->description ?? $activity->objectives);
            $activity->monitoring = $activity->monitoringStatus();
        }

        $upcoming = $allActivities->filter(fn ($activity) => $activity->monitoring['status'] === 'Pending' && $activity->date?->isFuture())->take(12);
        $ongoing = $allActivities->filter(fn ($activity) => $activity->monitoring['status'] === 'Ongoing')->take(12);
        $completed = $allActivities->filter(fn ($activity) => $activity->monitoring['status'] === 'Completed')->take(12);

        $stats = [
            'total' => $allActivities->count(),
            'organizations' => $allActivities->pluck('gpoa.user_id')->filter()->unique()->count(),
            'school_years' => $allActivities->pluck('gpoa.school_year')->filter()->unique()->count(),
        ];

        $organizations = $allActivities->pluck('gpoa.user')->filter()->unique('id')->values();
        $schoolYears = $allActivities->pluck('gpoa.school_year')->filter()->unique()->sortDesc()->values();

        return view('public.activities', compact(
            'upcoming',
            'ongoing',
            'completed',
            'stats',
            'organizations',
            'schoolYears'
        ));
    }
}