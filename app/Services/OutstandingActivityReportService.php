<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\DocumentDeadline;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OutstandingActivityReportService
{
    public function forUser(User $user): Collection
    {
        if ($user->isAdmin()) {
            return collect();
        }

        return ActivityRequest::query()
            ->where('user_id', $user->id)
            ->with(['gpoaActivity', 'report'])
            ->get()
            ->filter(function (ActivityRequest $activityRequest): bool {
                $plannedActivity = $activityRequest->gpoaActivity;
                if ($plannedActivity?->archived_at) {
                    return false;
                }

                $report = $activityRequest->report;
                if ($report?->status !== 'needs_revision'
                    && (filled($report?->narrative_report) || filled($report?->narrative_content))) {
                    return false;
                }

                $dueDate = $activityRequest->end_date
                    ?? $activityRequest->date
                    ?? $plannedActivity?->end_date
                    ?? $plannedActivity?->date;

                return $dueDate !== null && $dueDate->lte(today());
            })
            ->sortBy(fn (ActivityRequest $activityRequest) => (
                $activityRequest->end_date
                ?? $activityRequest->date
                ?? $activityRequest->gpoaActivity?->end_date
                ?? $activityRequest->gpoaActivity?->date
            )?->timestamp ?? 0)
            ->values();
    }

    public function forAdmin(array $filters = []): Collection
    {
        $today = today();
        $deadlines = DocumentDeadline::query()
            ->where('document_type', DocumentDeadline::TYPE_ACTIVITY_REPORT)
            ->get()
            ->keyBy(fn (DocumentDeadline $deadline) => $deadline->term . '|' . $deadline->school_year);

        $plannedActivities = GpoaActivity::query()
            ->with(['gpoa.user.organization', 'activityRequest.report'])
            ->whereNull('archived_at')
            ->get();

        $rows = $plannedActivities->map(function (GpoaActivity $activity) use ($deadlines, $today): ?array {
            $request = $activity->activityRequest;
            if (in_array($request?->status, [ActivityRequest::STATUS_CANCELLED, 'deleted'], true)) {
                return null;
            }

            $report = $request?->report;
            $endDate = $request?->end_date ?? $request?->date ?? $activity->end_date ?? $activity->date;
            $gpoa = $activity->gpoa;
            $deadline = $gpoa
                ? $deadlines->get($gpoa->term . '|' . $gpoa->school_year)
                : null;
            $dueDate = $deadline?->deadline_date?->copy()->addDays((int) ($deadline->grace_days ?? 0))
                ?? $endDate?->copy()->addDays((int) ($deadline?->grace_days ?? 0));
            $displayDeadline = $deadline?->deadline_date ?? $endDate;
            $reportStatus = $this->reportStatus($report, $dueDate, $today);

            if (! $endDate || ($endDate->gt($today) && $reportStatus !== 'Needs revision')) {
                return null;
            }

            return $this->adminRow($activity, $request, $report, $gpoa?->user, $gpoa?->college ?? $gpoa?->user?->organization?->college ?? $gpoa?->user?->college, $gpoa?->term, $gpoa?->school_year, $endDate, $displayDeadline, $dueDate, $reportStatus, $today);
        });

        $directRequests = ActivityRequest::query()
            ->whereNull('gpoa_activity_id')
            ->whereNotIn('status', [ActivityRequest::STATUS_CANCELLED, 'deleted'])
            ->with(['user.organization', 'gpoa', 'report'])
            ->get()
            ->map(function (ActivityRequest $request) use ($deadlines, $today): ?array {
                $report = $request->report;
                $endDate = $request->end_date ?? $request->date;
                $gpoa = $request->gpoa;
                $deadline = $gpoa
                    ? $deadlines->get($gpoa->term . '|' . $gpoa->school_year)
                    : null;
                $dueDate = $deadline?->deadline_date?->copy()->addDays((int) ($deadline->grace_days ?? 0))
                    ?? $endDate?->copy()->addDays((int) ($deadline?->grace_days ?? 0));
                $displayDeadline = $deadline?->deadline_date ?? $endDate;
                $reportStatus = $this->reportStatus($report, $dueDate, $today);

                if (! $endDate || ($endDate->gt($today) && $reportStatus !== 'Needs revision')) {
                    return null;
                }

                return $this->adminRow($request, $request, $report, $request->user, $gpoa?->college ?? $request->user?->organization?->college ?? $request->user?->college, $gpoa?->term, $gpoa?->school_year, $endDate, $displayDeadline, $dueDate, $reportStatus, $today);
            });

        return $rows->merge($directRequests)
            ->filter()
            ->filter(fn (array $row) => (! filled($filters['organization'] ?? null) || (string) $row['organization_id'] === (string) $filters['organization'])
                && (! filled($filters['college'] ?? null) || mb_strtolower((string) $row['college']) === mb_strtolower((string) $filters['college']))
                && (! filled($filters['status'] ?? null) || $row['report_status'] === $filters['status'])
                && (! filled($filters['school_year'] ?? null) || $row['school_year'] === $filters['school_year'])
                && (! filled($filters['term'] ?? null) || $row['term'] === $filters['term']))
            ->sortBy(fn (array $row) => [$row['end_date']?->timestamp ?? 0, $row['organization']])
            ->values();
    }

    private function reportStatus(?\App\Models\ActivityReport $report, ?Carbon $dueDate, Carbon $today): string
    {
        if ($report?->status === 'needs_revision') {
            return 'Needs revision';
        }

        if (filled($report?->narrative_report) || filled($report?->narrative_content)) {
            return 'Submitted';
        }

        return $dueDate && $today->gt($dueDate) ? 'Overdue' : 'Missing';
    }

    private function adminRow(
        GpoaActivity|ActivityRequest $activity,
        ?ActivityRequest $request,
        ?\App\Models\ActivityReport $report,
        ?User $user,
        ?string $college,
        ?string $term,
        ?string $schoolYear,
        Carbon $endDate,
        ?Carbon $displayDeadline,
        ?Carbon $dueDate,
        string $reportStatus,
        Carbon $today,
    ): array {
        return [
            'activity' => $activity,
            'activity_request_id' => $request?->id,
            'organization' => $user?->org_name ?: $user?->name ?: '—',
            'organization_id' => $user?->id,
            'college' => $college ?: '—',
            'term' => $term,
            'school_year' => $schoolYear,
            'end_date' => $endDate,
            'deadline' => $displayDeadline,
            'days_overdue' => $dueDate && $today->gt($dueDate) ? $dueDate->diffInDays($today) : 0,
            'report_status' => $reportStatus,
            'submitted_at' => $report?->submitted_at,
            'signed_by_organization_head' => (bool) $report?->signed_by_organization_head,
        ];
    }
}
