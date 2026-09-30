<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\MonitoringResult;
use App\Models\Organization;
use App\Models\User;
use App\Models\OrganizationWorkflow;
use App\Models\WorkflowEvent;
use App\Models\WorkflowSubmission;
use App\Services\OrganizationWorkflowService;
use App\Services\VenueAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\AdminActivityMonitoringService;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function monitoringDashboard(AdminActivityMonitoringService $monitoringService)
    {
        $activities = $monitoringService->all();
        $stats = $monitoringService->counts($activities);
        $dashboardData = $monitoringService->dashboardData($activities);
        $organizationProgress = $monitoringService->organizationProgress($activities);
        $recentActivities = $activities
            ->sortByDesc(fn ($activity) => $activity->updated_at?->timestamp ?? 0)
            ->take(8)
            ->values();

        return view('admin.monitoring-dashboard', compact('stats', 'recentActivities', 'dashboardData', 'organizationProgress'));
    }

    public function dashboard(Request $request, OrganizationWorkflowService $workflowService)
    {
        $admin = auth()->user();

        $currentTerm = OrganizationWorkflow::latest()->value('term')
            ?? Gpoa::latest()->value('term')
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

    public function monitor(Request $request, AdminActivityMonitoringService $monitoringService)
    {
        $allActivities = $monitoringService->all();
        $filteredActivities = $monitoringService->filtered($request, $allActivities);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $activities = new LengthAwarePaginator(
            $filteredActivities->forPage($page, 15)->values(),
            $filteredActivities->count(),
            15,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $stats = $monitoringService->counts($allActivities);
        $organizations = $allActivities->pluck('gpoa.user')->filter()->unique('id')->sortBy('org_name')->values();
        $categories = $allActivities->pluck('category')->filter()->unique()->sort()->values();
        $colleges = $allActivities->pluck('gpoa.college')->filter()->unique()->sort()->values();
        $terms = $allActivities->pluck('gpoa.term')->filter()->unique()->sort()->values();
        $schoolYears = $allActivities->pluck('gpoa.school_year')->filter()->unique()->sort()->values();
        $progressActivities = $monitoringService->filtered($request, $allActivities, false);
        $organizationProgress = $monitoringService->organizationProgress($progressActivities);

        return view('admin.activity-monitoring', compact(
            'activities', 'stats', 'organizations', 'categories', 'colleges', 'terms', 'schoolYears', 'organizationProgress'
        ));
    }

    public function activityStatuses(Request $request, AdminActivityMonitoringService $monitoringService)
    {
        $activities = $monitoringService->filtered($request);

        return response()->json([
            'activities' => $activities->map(fn ($activity) => [
                'id' => $activity->id,
                'status' => $activity->monitoring_status,
                'late' => $activity->monitoring_late,
            ])->values(),
        ]);
    }

    public function recordMonitoring(Request $request, $id)
    {
        $activity = GpoaActivity::with('activityRequest')->findOrFail($id);

        $validated = $request->validate([
            'compliance_status' => 'required|in:aligned,partial,not_aligned',
            'compliance_notes'  => 'nullable|string|max:1000',
        ]);

        $result = MonitoringResult::updateOrCreate(
            ['gpoa_activity_id' => $activity->id],
            [
                'activity_request_id' => $activity->activityRequest?->id,
                'admin_id'          => auth()->id(),
                'compliance_status' => $validated['compliance_status'],
                'compliance_notes'  => $validated['compliance_notes'],
                'recorded_at'       => now(),
            ]
        );

        activity('monitoring')
            ->performedOn($result)
            ->causedBy(auth()->user())
            ->withProperties(['gpoa_activity_id' => $activity->id])
            ->log('monitoring.remark_recorded');

        return redirect()->route('admin.activities')
            ->with('success', 'Monitoring remark saved.');
    }

    public function exportActivities(Request $request, $format, AdminActivityMonitoringService $monitoringService)
    {
        if ($format !== 'excel') {
            abort(400, 'Unsupported export format');
        }

        $activities = $monitoringService->filtered($request);
        $headers = ['Activity ID', 'Title', 'Organization', 'Venue', 'Date', 'Monitoring Status', 'Late', 'Communication Letter', 'Narrative Report', 'Term', 'School Year'];
        $csvStream = fopen('php://temp', 'r+');
        $sanitizeForSpreadsheet = static function ($value): string {
            $value = (string) $value;

            return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)
                ? "'" . $value
                : $value;
        };

        fputcsv($csvStream, array_map($sanitizeForSpreadsheet, $headers), ',', '"', '');

        foreach ($activities as $activity) {
            $row = [
                $activity->id,
                $activity->title,
                $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? 'N/A',
                $activity->venue ?: '—',
                $activity->date?->toDateString() ?? '',
                $activity->monitoring_status,
                $activity->monitoring_late ? 'Yes' : 'No',
                filled($activity->activityRequest?->communication_letter) ? 'Submitted' : 'Pending',
                filled($activity->activityRequest?->report?->narrative_report) || filled($activity->activityRequest?->report?->narrative_content) ? 'Submitted' : 'Pending',
                $activity->gpoa?->term ?? '',
                $activity->gpoa?->school_year ?? '',
            ];

            fputcsv($csvStream, array_map($sanitizeForSpreadsheet, $row), ',', '"', '');
        }

        rewind($csvStream);
        $csv = stream_get_contents($csvStream);
        fclose($csvStream);

        $fileName = 'activities_export_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function viewFile($activityId, $fileType)
    {
        $activity = ActivityRequest::with('report')->findOrFail($activityId);

        $filePath = match ($fileType) {
            'communication' => $activity->communication_letter,
            'narrative'     => $activity->report?->narrative_report,
            default         => null,
        };
        abort_unless($filePath, 404, 'File not found');

        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                return $disk->response($filePath, null, ['Content-Type' => 'application/pdf']);
            }
        }

        abort(404, 'File not found');
    }

    public function downloadFile($activityId, $fileType)
    {
        $activity = ActivityRequest::with('report')->findOrFail($activityId);

        $filePath = match ($fileType) {
            'communication' => $activity->communication_letter,
            'narrative'     => $activity->report?->narrative_report,
            default         => null,
        };

        abort_unless($filePath, 404, 'File not found');

        $fileName = $fileType === 'communication'
            ? 'Communication-Letter-' . $activity->id . '.pdf'
            : 'Narrative-Report-' . $activity->id . '.pdf';

        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                return $disk->download($filePath, $fileName);
            }
        }

        abort(404, 'File not found');
    }

    public function viewGpoaDocument(Gpoa $gpoa)
    {
        if (!$gpoa->document_path || !file_exists(storage_path('app/public/' . $gpoa->document_path))) {
            abort(404, 'GPOA document not found');
        }

        return response()->file(storage_path('app/public/' . $gpoa->document_path));
    }
}
