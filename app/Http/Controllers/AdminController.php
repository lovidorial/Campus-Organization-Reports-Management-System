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
        $dashboardData = $monitoringService->dashboardData($visibleActivities);
        $organizationProgress = $monitoringService->organizationProgress($visibleActivities);
        $recentActivities = $visibleActivities
            ->sortByDesc(fn ($activity) => $activity->updated_at?->timestamp ?? 0)
            ->take(8)
            ->values();

        return view('admin.monitoring-dashboard', compact('stats', 'recentActivities', 'dashboardData', 'organizationProgress'));
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

        return redirect()->route('admin.activities')->with('success', 'Activity report approved.');
    }

    public function requestReportRevision(Request $request, ActivityReport $activityReport)
    {
        $this->authorize('review', $activityReport);
        $validated = $request->validate([
            'feedback' => ['required', 'string', 'max:1000'],
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

        return redirect()->route('admin.activities')->with('success', 'Revision requested for the activity report.');
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
                return $disk->response($filePath, basename($filePath), ['Content-Disposition' => 'inline']);
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
