<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityRequest;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Organization;
use App\Models\Gpoa;
use App\Models\OrganizationWorkflow;
use App\Models\WorkflowEvent;
use App\Models\WorkflowSubmission;
use App\Services\GpoaMatchValidator;
use App\Services\OrganizationWorkflowService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard(Request $request, OrganizationWorkflowService $workflowService)
    {
        $admin = auth()->user();

        $currentTerm = OrganizationWorkflow::latest()->value('term')
            ?? Activity::latest()->value('term')
            ?? '1st Term';
        $currentSY = OrganizationWorkflow::latest()->value('school_year')
            ?? (date('Y') . '-' . (date('Y') + 1));

        $totalSubmissions = WorkflowSubmission::where('is_current', true)->count();
        $approvedSubmissions = WorkflowSubmission::where('is_current', true)
            ->where('status', WorkflowSubmission::STATUS_APPROVED)->count();
        $pendingReviews = WorkflowSubmission::where('is_current', true)
            ->where('status', WorkflowSubmission::STATUS_UNDER_REVIEW)->count();
        $rejectedSubmissions = WorkflowSubmission::where('is_current', true)
            ->where('status', WorkflowSubmission::STATUS_REJECTED)->count();

        $lastMonthTotal = WorkflowSubmission::where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->where('created_at', '<', now()->startOfMonth())->count();
        $thisMonthTotal = WorkflowSubmission::where('created_at', '>=', now()->startOfMonth())->count();
        $growthPercent = $lastMonthTotal > 0
            ? (int) round((($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100)
            : ($thisMonthTotal > 0 ? 100 : 0);

        $stats = [
            'total'          => $totalSubmissions,
            'approved'       => $approvedSubmissions,
            'pending'        => $pendingReviews,
            'rejected'       => $rejectedSubmissions,
            'organizations'  => Organization::count() ?: User::where('role', 'user')->count(),
            'growth_percent' => $growthPercent,
            'users'          => User::where('role', '!=', 'admin')->count(),
            'gpoa_pending'   => WorkflowSubmission::where('is_current', true)
                ->where('document_type', OrganizationWorkflow::DOC_GPOA)
                ->where('status', WorkflowSubmission::STATUS_UNDER_REVIEW)->count(),
        ];

        $pendingByDoc = [
            'gpoa' => WorkflowSubmission::where('is_current', true)
                ->where('document_type', OrganizationWorkflow::DOC_GPOA)
                ->where('status', WorkflowSubmission::STATUS_UNDER_REVIEW)->count(),
            'communication_letter' => WorkflowSubmission::where('is_current', true)
                ->where('document_type', OrganizationWorkflow::DOC_COMMUNICATION)
                ->where('status', WorkflowSubmission::STATUS_UNDER_REVIEW)->count(),
            'summary_report' => WorkflowSubmission::where('is_current', true)
                ->where('document_type', OrganizationWorkflow::DOC_SUMMARY)
                ->where('status', WorkflowSubmission::STATUS_UNDER_REVIEW)->count(),
        ];

        $highPriority = WorkflowSubmission::where('is_current', true)
            ->where('status', WorkflowSubmission::STATUS_UNDER_REVIEW)
            ->where('submitted_at', '<', now()->subDays(7))
            ->count();

        $overdueCount = OrganizationWorkflow::where('is_completed', false)
            ->where('updated_at', '<', now()->subDays(30))->count();
        $commApprovedToday = WorkflowSubmission::where('document_type', OrganizationWorkflow::DOC_COMMUNICATION)
            ->where('status', WorkflowSubmission::STATUS_APPROVED)
            ->whereDate('approved_at', today())->count();

        $alerts = [];
        if ($pendingByDoc['gpoa'] > 0) {
            $alerts[] = ['type' => 'orange', 'message' => "{$pendingByDoc['gpoa']} GPOA" . ($pendingByDoc['gpoa'] > 1 ? 's' : '') . ' awaiting review'];
        }
        if ($overdueCount > 0) {
            $alerts[] = ['type' => 'red', 'message' => "{$overdueCount} submission" . ($overdueCount > 1 ? 's' : '') . ' overdue'];
        }
        if ($commApprovedToday > 0) {
            $alerts[] = ['type' => 'green', 'message' => "{$commApprovedToday} Communication Letter" . ($commApprovedToday > 1 ? 's' : '') . ' approved today'];
        }
        $alerts[] = ['type' => 'amber', 'message' => 'Semester deadline is approaching'];

        $recentActivity = WorkflowEvent::with(['workflow.user', 'user'])
            ->latest('created_at')
            ->take(12)
            ->get();

        $topOrgs = User::withCount('activityRequests')
            ->where('role', 'user')
            ->orderByDesc('activity_requests_count')
            ->take(5)
            ->get();

        $monthlyTrend = WorkflowSubmission::selectRaw('DATE_FORMAT(submitted_at, "%b %Y") as month, count(*) as count')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderByRaw('MIN(submitted_at)')
            ->get();

        $submissionsQuery = WorkflowSubmission::with(['workflow.user', 'reviewer'])
            ->where('is_current', true)
            ->whereNotNull('submitted_at');

        if ($request->filled('search')) {
            $term = $request->search;
            $submissionsQuery->whereHas('workflow.user', function ($q) use ($term) {
                $q->where('org_name', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            });
        }
        if ($request->filled('status')) {
            $submissionsQuery->where('status', $request->status);
        }
        if ($request->filled('document_type')) {
            $submissionsQuery->where('document_type', $request->document_type);
        }
        if ($request->filled('organization')) {
            $submissionsQuery->whereHas('workflow', fn ($q) => $q->where('user_id', $request->organization));
        }
        if ($request->filled('semester')) {
            $submissionsQuery->whereHas('workflow', fn ($q) => $q->where('term', $request->semester));
        }
        if ($request->filled('academic_year')) {
            $submissionsQuery->whereHas('workflow', fn ($q) => $q->where('school_year', $request->academic_year));
        }
        if ($request->filled('date')) {
            try {
                $submissionsQuery->whereDate('submitted_at', Carbon::parse($request->date));
            } catch (\Exception $e) {
            }
        }

        $recentSubmissions = $submissionsQuery->latest('submitted_at')->take(15)->get();

        $approvalsToday = WorkflowSubmission::where('status', WorkflowSubmission::STATUS_APPROVED)
            ->whereDate('approved_at', today())->count();
        $submissionsToday = WorkflowSubmission::whereDate('submitted_at', today())->count();
        $unreadCount = $admin->unreadNotificationsCount();

        $upcomingDeadlines = [
            ['date' => now()->addDays(6)->format('M j'), 'label' => 'Communication Letter', 'month' => now()->addDays(6)->format('F')],
            ['date' => now()->addDays(14)->format('M j'), 'label' => 'Summary Report', 'month' => now()->addDays(14)->format('F')],
            ['date' => now()->addDays(19)->format('M j'), 'label' => 'Final Approval', 'month' => now()->addDays(19)->format('F')],
        ];

        $byCategory = ActivityRequest::selectRaw('category, count(*) as count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->get();

        $organizations = User::where('role', 'user')->select('id', 'org_name', 'name')->orderBy('org_name')->get();
        $academicYears = OrganizationWorkflow::distinct()->pluck('school_year')->filter()->sort()->values();
        $semesters = OrganizationWorkflow::distinct()->pluck('term')->filter()->sort()->values();

        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

        return view('admin.dashboard', compact(
            'stats', 'topOrgs', 'currentTerm', 'currentSY',
            'byCategory', 'monthlyTrend', 'pendingByDoc', 'highPriority',
            'alerts', 'recentActivity', 'recentSubmissions', 'approvalsToday',
            'submissionsToday', 'unreadCount', 'upcomingDeadlines',
            'organizations', 'academicYears', 'semesters', 'greeting', 'admin'
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
