<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\MonitoringResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\AdminActivityMonitoringService;

class AdminController extends Controller
{
    public function monitoringDashboard(AdminActivityMonitoringService $monitoringService)
    {
        $activities = $monitoringService->all();
        $stats = $monitoringService->counts($activities);
        $visibleActivities = $activities->reject(fn (GpoaActivity $activity) => $activity->archived_at)->values();
        $today = now()->startOfDay();
        $upcomingThrough = $today->copy()->addDays(7)->endOfDay();
        $alerts = [
            'awaitingReview' => $visibleActivities->filter(fn (GpoaActivity $activity) => $activity->narrativeStatusLabel() === 'For Review')->count(),
            'upcomingInSevenDays' => $visibleActivities->filter(function (GpoaActivity $activity) use ($today, $upcomingThrough): bool {
                return $activity->date
                    && $activity->date->greaterThanOrEqualTo($today)
                    && $activity->date->lessThanOrEqualTo($upcomingThrough)
                    && ! filled($activity->activityRequest?->communication_letter);
            })->count(),
            'late' => $stats['Late'],
        ];
        $dashboardData = $monitoringService->dashboardData($visibleActivities);
        $organizationProgress = $monitoringService->organizationProgress($visibleActivities);
        $recentActivities = $visibleActivities
            ->sortByDesc(fn ($activity) => $activity->updated_at?->timestamp ?? 0)
            ->take(8)
            ->values();

        return view('admin.monitoring-dashboard', compact('stats', 'alerts', 'recentActivities', 'dashboardData', 'organizationProgress'));
    }

    public function monitor(Request $request, AdminActivityMonitoringService $monitoringService)
    {
        $allActivities = $monitoringService->all();
        $tab = $request->query('tab', 'recent') === 'todo' ? 'todo' : 'recent';
        $countRequest = $request->duplicate();
        $countRequest->query->remove('status');
        $filterBase = $monitoringService->filtered($countRequest, $allActivities);
        $recentForCounts = $filterBase->filter(fn (GpoaActivity $activity) => $activity->last_submitted_at !== null)->values();
        $archivedCountRequest = $request->duplicate();
        $archivedCountRequest->query->set('status', 'Archived');
        $archivedCount = $monitoringService->filtered($archivedCountRequest, $allActivities)
            ->filter(fn (GpoaActivity $activity) => $activity->archived_at)
            ->count();
        $statusCounts = [
            'All' => $recentForCounts->count(),
            'Pending' => $recentForCounts->where('monitoring_status', 'Pending')->count(),
            'Ongoing' => $recentForCounts->where('monitoring_status', 'Ongoing')->count(),
            'Completed' => $recentForCounts->where('monitoring_status', 'Completed')->count(),
            'Late' => $recentForCounts->where('monitoring_late', true)->count(),
            'Archived' => $archivedCount,
        ];
        $filteredActivities = $monitoringService->filtered($request, $allActivities);
        $selectedTabActivities = $tab === 'todo'
            ? $filteredActivities->filter(fn (GpoaActivity $activity) => $activity->last_submitted_at === null && ! $activity->archived_at)->sortBy(fn (GpoaActivity $activity) => $activity->date?->timestamp ?? PHP_INT_MAX)->values()
            : $filteredActivities->filter(fn (GpoaActivity $activity) => $request->query('status') === 'Archived'
                ? (bool) $activity->archived_at
                : $activity->last_submitted_at !== null)->values();
        $activities = $monitoringService->paginate($selectedTabActivities, $request);
        $stats = $monitoringService->counts($allActivities);
        $organizations = $allActivities->pluck('gpoa.user')->filter()->unique('id')->sortBy('org_name')->values();
        $categories = $allActivities->pluck('category')->filter()->unique()->sort()->values();
        $colleges = $allActivities->pluck('gpoa.college')->filter()->unique()->sort()->values();
        $terms = $allActivities->pluck('gpoa.term')->filter()->unique()->sort()->values();
        $schoolYears = $allActivities->pluck('gpoa.school_year')->filter()->unique()->sort()->values();
        $progressActivities = $monitoringService->filtered($request, $allActivities, false);
        $organizationProgress = $monitoringService->organizationProgress($progressActivities);

        return view('admin.activity-monitoring', compact(
            'activities', 'stats', 'organizations', 'categories', 'colleges', 'terms', 'schoolYears', 'organizationProgress', 'tab', 'statusCounts'
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

    public function showRequest(Request $request, ActivityRequest $activityRequest)
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Unauthorized access.');

        $activityRequest->load([
            'user',
            'gpoa',
            'gpoaActivity.monitoringResult.admin',
            'report.photos',
            'report.reviewer',
            'monitoringResult.admin',
            'programFlows',
        ]);

        $monitoringResult = $activityRequest->monitoringResult ?? $activityRequest->gpoaActivity?->monitoringResult;
        $requestedBack = (string) $request->query('back', '');
        $backPath = parse_url($requestedBack, PHP_URL_PATH);
        $activitiesPath = parse_url(route('admin.activities'), PHP_URL_PATH);
        $backQuery = [];
        if ($backPath === $activitiesPath) {
            parse_str((string) parse_url($requestedBack, PHP_URL_QUERY), $backQuery);
        }
        $backUrl = route('admin.activities', $backQuery);

        return view('admin.activity-request-show', compact('activityRequest', 'monitoringResult', 'backUrl'));
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

        return redirect()->back()
            ->with('success', 'Monitoring remark saved.');
    }

    public function archiveActivity(GpoaActivity $gpoaActivity)
    {
        $this->authorize('archive', $gpoaActivity);

        abort_unless(! $gpoaActivity->archived_at && $gpoaActivity->monitoringStatus()['status'] === 'Completed', 422, 'Only completed activities can be archived.');

        $gpoaActivity->update(['archived_at' => now()]);

        activity('monitoring')
            ->performedOn($gpoaActivity)
            ->causedBy(auth()->user())
            ->log('monitoring.activity_archived');

        return back()->with('success', 'Activity archived.');
    }

    public function restoreActivity(GpoaActivity $gpoaActivity)
    {
        $this->authorize('restore', $gpoaActivity);
        abort_unless($gpoaActivity->archived_at, 422, 'This activity is not archived.');

        $gpoaActivity->update(['archived_at' => null]);

        activity('monitoring')
            ->performedOn($gpoaActivity)
            ->causedBy(auth()->user())
            ->log('monitoring.activity_restored');

        return back()->with('success', 'Activity restored.');
    }

    public function approveReport(ActivityReport $activityReport)
    {
        $this->authorize('review', $activityReport);

        $activityReport->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'feedback' => null,
        ]);

        activity('activity_reports')
            ->performedOn($activityReport)
            ->causedBy(auth()->user())
            ->withProperties(['activity_request_id' => $activityReport->activity_request_id])
            ->log('activity_report.approved');

        $this->notifyReportOrganization($activityReport, 'activity_report_approved', 'Activity Report Approved', 'Your activity report has been approved.');

        return redirect()->back()->with('success', 'Activity report approved.');
    }

    public function requestReportRevision(Request $request, ActivityReport $activityReport)
    {
        $this->authorize('review', $activityReport);
        $validated = $request->validate([
            'feedback' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'feedback.min' => 'Please explain what needs to be revised (at least 10 characters).',
        ]);

        $activityReport->update([
            'status' => 'needs_revision',
            'feedback' => $validated['feedback'],
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        activity('activity_reports')
            ->performedOn($activityReport)
            ->causedBy(auth()->user())
            ->withProperties([
                'activity_request_id' => $activityReport->activity_request_id,
                'feedback' => $validated['feedback'],
            ])
            ->log('activity_report.revision_requested');

        $this->notifyReportOrganization($activityReport, 'activity_report_revision_requested', 'Activity Report Needs Revision', $validated['feedback']);

        return redirect()->back()->with('success', 'Revision requested for the activity report.');
    }

    public function viewReportEvidence(ActivityReport $activityReport, string $evidence)
    {
        $this->authorize('review', $activityReport);

        $filePath = match ($evidence) {
            'narrative' => $activityReport->narrative_report,
            'attendance' => $activityReport->attendance_sheet_path,
            default => null,
        };
        $disks = ['private', 'public'];

        if (preg_match('/^photo-(\d+)$/', $evidence, $matches)) {
            $filePath = $activityReport->photos()->whereKey((int) $matches[1])->value('path');
            $disks = ['public', 'private'];
        }

        abort_unless($filePath, 404, 'Evidence not found.');

        foreach ($disks as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                return $disk->response($filePath, basename($filePath), [
                    'Content-Type' => mime_content_type($disk->path($filePath)) ?: 'application/octet-stream',
                ], 'inline');
            }
        }

        abort(404, 'Evidence not found.');
    }

    private function notifyReportOrganization(ActivityReport $activityReport, string $type, string $title, string $message): void
    {
        $userId = $activityReport->activityRequest?->user_id;
        if ($userId) {
            \App\Models\UserNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
            ]);
        }
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
            'attendance'   => $activity->report?->attendance_sheet_path,
            default         => null,
        };
        abort_unless($filePath, 404, 'File not found');

        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                $fileName = basename($filePath);
                $mimeType = mime_content_type($disk->path($filePath)) ?: 'application/octet-stream';

                return $disk->response($filePath, $fileName, [
                    'Content-Type' => $mimeType,
                    'X-Frame-Options' => 'SAMEORIGIN',
                ], 'inline');
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
            'attendance'   => $activity->report?->attendance_sheet_path,
            default         => null,
        };

        abort_unless($filePath, 404, 'File not found');

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fileNamePrefix = match ($fileType) {
            'communication' => 'Communication-Letter-',
            'attendance' => 'Attendance-Sheet-',
            default => 'Narrative-Report-',
        };
        $fileName = $fileNamePrefix . $activity->id . ($extension !== '' ? '.' . $extension : '');

        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                return response()->download($disk->path($filePath), $fileName, [
                    'Content-Type' => mime_content_type($disk->path($filePath)) ?: 'application/octet-stream',
                ]);
            }
        }

        abort(404, 'File not found');
    }

    public function viewGpoaDocument(Gpoa $gpoa)
    {
        abort_unless($gpoa->document_path, 404, 'GPOA document not found');

        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($gpoa->document_path)) {
                return $disk->response($gpoa->document_path, basename($gpoa->document_path), [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline',
                ]);
            }
        }

        abort(404, 'GPOA document not found');
    }
}
