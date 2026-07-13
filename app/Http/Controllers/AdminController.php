<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityRequest;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Organization;
use App\Models\Gpoa;
use App\Services\GpoaMatchValidator;
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
            'gpoa_pending'  => Gpoa::where('status', 'pending')->count(),
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

        $byCategory = Activity::selectRaw('category, count(*) as count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->get();

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
        $query = ActivityRequest::with(['user', 'gpoaActivity.gpoa', 'report', 'monitoringResult']);

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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('organization')) {
            $query->where('user_id', $request->organization);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $activities = $query->latest()->paginate(10)->withQueryString();

        foreach ($activities as $activity) {
            $activity->refreshLifecycleStatus();
        }

        $stats = [
            'total'         => ActivityRequest::count(),
            'approved'      => ActivityRequest::whereIn('status', ['approved', 'in_progress', 'awaiting_report', 'report_submitted', 'closed'])->count(),
            'pending'       => ActivityRequest::where('status', 'pending')->count(),
            'rejected'      => ActivityRequest::where('status', 'rejected')->count(),
            'organizations' => User::where('role', 'user')->count(),
        ];

        $organizations = User::whereHas('activityRequests')->select('id', 'org_name', 'name')->get();
        $categories    = ActivityRequest::distinct()->pluck('category')->filter()->sort()->values();

        return view('admin.monitoring', compact('activities', 'stats', 'organizations', 'categories'));
    }

    public function approve($id)
    {
        $activity = ActivityRequest::with('gpoaActivity')->findOrFail($id);

        if ($activity->status !== ActivityRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending activity requests can be approved.');
        }

        $matchError = GpoaMatchValidator::validate($activity->gpoaActivity, [
            'title' => $activity->title,
            'date'  => $activity->date->toDateString(),
            'venue' => $activity->venue,
        ]);

        if ($matchError) {
            return back()->with('error', 'Cannot approve: ' . $matchError);
        }

        $conflict = ActivityRequest::where('date', $activity->date)
            ->where('venue', $activity->venue)
            ->whereIn('status', [ActivityRequest::STATUS_APPROVED, ActivityRequest::STATUS_IN_PROGRESS])
            ->where('id', '!=', $id)
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Cannot approve. Conflict detected at same venue/date.');
        }

        $activity->update(['status' => ActivityRequest::STATUS_APPROVED, 'reject_reason' => null]);
        return back()->with('success', 'Activity request approved. Organization may now conduct the activity.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => 'nullable|string|max:500',
        ]);

        ActivityRequest::findOrFail($id)->update([
            'status'        => ActivityRequest::STATUS_REJECTED,
            'reject_reason' => $request->reject_reason,
        ]);

        return back()->with('success', 'Activity request rejected.');
    }

    public function recordMonitoring(Request $request, $id)
    {
        $activity = ActivityRequest::with(['gpoaActivity', 'report'])->findOrFail($id);

        if ($activity->status !== ActivityRequest::STATUS_REPORT_SUBMITTED) {
            return back()->with('error', 'Monitoring can only be recorded after the organization submits a final report.');
        }

        $validated = $request->validate([
            'compliance_status' => 'required|in:aligned,partial,not_aligned',
            'compliance_notes'  => 'nullable|string|max:1000',
        ]);

        MonitoringResult::updateOrCreate(
            ['activity_request_id' => $activity->id],
            [
                'gpoa_activity_id'  => $activity->gpoa_activity_id,
                'admin_id'          => auth()->id(),
                'compliance_status' => $validated['compliance_status'],
                'compliance_notes'  => $validated['compliance_notes'],
                'recorded_at'       => now(),
            ]
        );

        $activity->update(['status' => ActivityRequest::STATUS_CLOSED]);

        return back()->with('success', 'Monitoring results recorded against GPOA. Activity closed.');
    }

    public function exportActivities(Request $request, $format)
    {
        $query = ActivityRequest::with('user');

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

        if ($format !== 'excel') {
            abort(400, 'Unsupported export format');
        }

        $headers = ['ID', 'Title', 'Organization', 'Venue', 'Date', 'Status', 'GPOA Match'];
        $csv = implode(',', $headers) . "\n";

        foreach ($activities as $activity) {
            $csv .= implode(',', [
                $activity->id,
                str_replace(',', ' ', $activity->title),
                str_replace(',', ' ', $activity->user->org_name ?? $activity->user->name ?? 'N/A'),
                str_replace(',', ' ', $activity->venue),
                $activity->date?->toDateString() ?? '',
                $activity->status,
                $activity->matchesGpoaLineItem() ? 'Match' : 'Mismatch',
            ]) . "\n";
        }

        $fileName = 'activities_export_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function downloadFile($activityId, $fileType)
    {
        $activity = ActivityRequest::with('report')->findOrFail($activityId);

        $filePath = match ($fileType) {
            'communication' => $activity->communication_letter,
            'narrative'     => $activity->report?->narrative_report,
            default         => null,
        };

        if (!$filePath || !file_exists(storage_path('app/public/' . $filePath))) {
            abort(404, 'File not found');
        }

        $fileName = $fileType === 'communication'
            ? 'Communication-Letter-' . $activity->id . '.pdf'
            : 'Narrative-Report-' . $activity->id . '.pdf';

        return response()->download(storage_path('app/public/' . $filePath), $fileName);
    }

    public function viewGpoaDocument(Gpoa $gpoa)
    {
        if (!$gpoa->document_path || !file_exists(storage_path('app/public/' . $gpoa->document_path))) {
            abort(404, 'GPOA document not found');
        }

        return response()->file(storage_path('app/public/' . $gpoa->document_path));
    }
}
