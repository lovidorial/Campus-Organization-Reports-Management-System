<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total'         => Activity::count(),
            'organizations' => Organization::count() ?: User::where('role', 'user')->count(),
            'approved'      => Activity::where('status', 'approved')->count(),
            'pending'       => Activity::where('status', 'pending')->count(),
            'rejected'      => Activity::where('status', 'rejected')->count(),
        ];

        // Current term/SY from settings or latest activity
        $currentTerm = Activity::latest()->value('term') ?? '1st Term';
        $currentSY   = Activity::latest()->value('school_year') ?? date('Y') . '-' . (date('Y') + 1);

        // SC President (from organizations table or config)
        $scPresident = Organization::where('type', 'Student Council')
            ->orWhere('type', 'SC')
            ->latest()->value('sc_president') ?? 'N/A';

        // Chart Data — top orgs by submitted activity count
        $topOrgs = User::withCount('activities')
            ->where('role', 'user')
            ->orderBy('activities_count', 'desc')
            ->take(8)
            ->get();

        // Activities by category for pie chart
        $byCategory = Activity::selectRaw('category, count(*) as count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->get();

        // Monthly activity trend (last 6 months)
        $monthlyTrend = Activity::selectRaw('DATE_FORMAT(created_at, "%b %Y") as month, count(*) as count')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('created_at')
            ->get();

        $recentActivities = Activity::with('user')->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'stats', 'topOrgs', 'recentActivities',
            'currentTerm', 'currentSY', 'scPresident',
            'byCategory', 'monthlyTrend'
        ));
    }

    public function monitor(Request $request)
    {
        $query = Activity::with('user');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('venue', 'like', "%{$term}%")
                  ->orWhere('title', 'like', "%{$term}%")
                  ->orWhere('organization', 'like', "%{$term}%");
                try {
                    $date = Carbon::parse($term)->toDateString();
                    $q->orWhereDate('date', $date);
                } catch (\Exception $e) {}
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('organization')) {
            $query->where('user_id', $request->organization);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $activities = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total'         => Activity::count(),
            'approved'      => Activity::where('status', 'approved')->count(),
            'pending'       => Activity::where('status', 'pending')->count(),
            'rejected'      => Activity::where('status', 'rejected')->count(),
            'organizations' => User::where('role', 'user')->count(),
        ];

        // Filter options
        $organizations = User::whereHas('activities')->select('id', 'org_name', 'name')->get();
        $categories    = Activity::distinct()->pluck('category')->filter()->sort()->values();
        $terms         = Activity::distinct()->pluck('term')->filter()->sort()->values();
        $schoolYears   = Activity::distinct()->pluck('school_year')->filter()->sort()->values();

        return view('admin.monitoring', compact('activities', 'stats', 'organizations', 'categories', 'terms', 'schoolYears'));
    }

    public function approve($id)
    {
        $activity = Activity::findOrFail($id);

        $conflict = Activity::where('date', $activity->date)
            ->where('venue', $activity->venue)
            ->where('status', 'approved')
            ->where('id', '!=', $id)
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Cannot approve. Conflict detected at same venue/date.');
        }

        $activity->update(['status' => 'approved', 'reject_reason' => null]);
        return back()->with('success', 'Activity approved.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => 'nullable|string|max:500',
        ]);

        Activity::findOrFail($id)->update([
            'status'        => 'rejected',
            'reject_reason' => $request->reject_reason,
        ]);

        return back()->with('success', 'Activity rejected.');
    }

    public function exportActivities(Request $request, $format)
    {
        $query = Activity::with('user');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('venue', 'like', "%{$term}%")
                  ->orWhere('title', 'like', "%{$term}%");
                try {
                    $date = Carbon::parse($term)->toDateString();
                    $q->orWhereDate('date', $date);
                } catch (\Exception $e) {}
            });
        }

        $activities = $query->latest()->get();

        if ($format === 'excel') {
            $filename = 'activities_' . now()->format('Ymd_His') . '.csv';
            $headers  = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];
            $callback = function () use ($activities) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Title', 'Organization', 'Category', 'Date', 'Venue', 'Participants', 'Term', 'SY', 'User', 'Status']);
                foreach ($activities as $a) {
                    fputcsv($handle, [
                        $a->title,
                        $a->organization,
                        $a->category,
                        $a->date->toDateString(),
                        $a->venue,
                        $a->participants_count,
                        $a->term,
                        $a->school_year,
                        $a->user->name ?? '',
                        $a->status,
                    ]);
                }
                fclose($handle);
            };
            return response()->stream($callback, 200, $headers);
        }

        if ($format === 'pdf') {
            $pdf = \PDF::loadView('admin.exports.activities', compact('activities'));
            return $pdf->download('activities_' . now()->format('Ymd_His') . '.pdf');
        }

        abort(404);
    }

    public function viewFile($activityId, $fileType)
    {
        $activity = Activity::findOrFail($activityId);
        
        $filePath = $fileType === 'communication' 
            ? $activity->communication_letter 
            : $activity->narrative_report;
        
        if (!$filePath || !file_exists(storage_path('app/public/' . $filePath))) {
            abort(404, 'File not found');
        }

        return response()->file(storage_path('app/public/' . $filePath));
    }

    public function downloadFile($activityId, $fileType)
    {
        $activity = Activity::findOrFail($activityId);
        
        $filePath = $fileType === 'communication' 
            ? $activity->communication_letter 
            : $activity->narrative_report;
        
        if (!$filePath || !file_exists(storage_path('app/public/' . $filePath))) {
            abort(404, 'File not found');
        }

        $fileName = $fileType === 'communication' 
            ? 'Communication-Letter-' . $activity->id . '.pdf'
            : 'Narrative-Report-' . $activity->id . '.pdf';

        return response()->download(storage_path('app/public/' . $filePath), $fileName);
    }
}
