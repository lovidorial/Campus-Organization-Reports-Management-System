<?php

namespace App\Services;

use App\Models\GpoaActivity;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminActivityMonitoringService
{
    public function all(): Collection
    {
        return GpoaActivity::query()
            ->whereHas('gpoa.user', fn ($query) => $query->where('role', '!=', 'admin'))
            ->with([
                'gpoa.user',
                'activityRequest.report.photos',
                'activityRequest.programFlows',
                'activityRequest.venueRecord' => fn ($query) => $query->withCount(['scheduledRequests', 'futureReservationRequests']),
                'activityRequest.monitoringResult',
                'monitoringResult',
            ])
            ->get()
            ->map(function (GpoaActivity $activity): GpoaActivity {
                $status = $activity->monitoringStatus();
                $activityRequest = $activity->activityRequest;
                $letterSubmittedAt = filled($activityRequest?->communication_letter)
                    ? ($activityRequest->communication_letter_signed_at ?? $activityRequest->updated_at)
                    : null;
                $reportSubmittedAt = $activityRequest?->report?->submitted_at;
                $requestUpdatedAt = $activityRequest?->updated_at;
                $lastSubmittedAt = collect([$letterSubmittedAt, $reportSubmittedAt, $requestUpdatedAt])
                    ->filter()
                    ->sortByDesc(fn ($submittedAt) => $submittedAt->timestamp)
                    ->first();
                $activity->setAttribute('monitoring_status', $status['status']);
                $activity->setAttribute('monitoring_late', $status['late']);
                $activity->setAttribute('last_submitted_at', $lastSubmittedAt);

                return $activity;
            });
    }

    public function filtered(Request $request, ?Collection $activities = null, bool $applySearchAndStatus = true, bool $forceIncludeArchived = false): Collection
    {
        $activities ??= $this->all();
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');
        $organizationFilter = (string) $request->query('organization', '');
        $categoryFilter = (string) $request->query('category', '');
        $collegeFilter = (string) $request->query('college', '');
        $termFilter = (string) $request->query('term', '');
        $schoolYearFilter = (string) $request->query('school_year', '');
        $assessmentFilter = (string) $request->query('assessment', '');

        $sort = (string) $request->query('sort', 'latest');
        $includeArchived = $forceIncludeArchived || $request->boolean('include_archived') || $statusFilter === 'Archived';

        $filtered = $activities->filter(function (GpoaActivity $activity) use ($search, $statusFilter, $organizationFilter, $categoryFilter, $collegeFilter, $termFilter, $schoolYearFilter, $assessmentFilter, $applySearchAndStatus, $includeArchived): bool {
            if (! $includeArchived && $activity->archived_at) {
                return false;
            }

            if ($organizationFilter !== '' && (string) $activity->gpoa?->user_id !== $organizationFilter) {
                return false;
            }

            if ($categoryFilter !== '' && $activity->category !== $categoryFilter) {
                return false;
            }

            if ($collegeFilter !== '' && $activity->gpoa?->college !== $collegeFilter) {
                return false;
            }
            if ($termFilter !== '' && $activity->gpoa?->term !== $termFilter) {
                return false;
            }
            if ($schoolYearFilter !== '' && $activity->gpoa?->school_year !== $schoolYearFilter) {
                return false;
            }
            if ($assessmentFilter !== '' && ($activity->monitoringResult?->compliance_status ?? '') !== $assessmentFilter) {
                return false;
            }

            if ($applySearchAndStatus) {
                if ($statusFilter === 'Late' && ! $activity->monitoring_late) {
                    return false;
                }
                if ($statusFilter !== '' && $statusFilter !== 'Late' && $activity->monitoring_status !== $statusFilter) {
                    return false;
                }
            }

            if ($applySearchAndStatus && $search !== '') {
                $searchable = implode(' ', array_filter([
                    $activity->title,
                    $activity->venue,
                    $activity->date?->toDateString(),
                    $activity->gpoa?->user?->org_name,
                    $activity->gpoa?->user?->name,
                ]));
                if (! str_contains(mb_strtolower($searchable), mb_strtolower($search))) {
                    return false;
                }
            }

            return true;
        });

        return ($sort === 'activity_date'
            ? $filtered->sortByDesc(fn (GpoaActivity $activity) => $activity->date?->timestamp ?? 0)
            : $filtered->sortByDesc(fn (GpoaActivity $activity) => $activity->last_submitted_at?->timestamp ?? 0))
            ->values();
    }

    public function reportActivities(): Collection
    {
        return GpoaActivity::query()
            ->whereHas('gpoa.user', fn ($query) => $query->where('role', '!=', 'admin'))
            ->with([
                'gpoa.user',
                'activityRequest.report',
                'monitoringResult',
            ])
            ->get()
            ->map(function (GpoaActivity $activity): GpoaActivity {
                $status = $activity->monitoringStatus();
                $activityRequest = $activity->activityRequest;
                $letterSubmittedAt = filled($activityRequest?->communication_letter)
                    ? ($activityRequest->communication_letter_signed_at ?? $activityRequest->updated_at)
                    : null;
                $reportSubmittedAt = $activityRequest?->report?->submitted_at;
                $requestUpdatedAt = $activityRequest?->updated_at;
                $lastSubmittedAt = collect([$letterSubmittedAt, $reportSubmittedAt, $requestUpdatedAt])
                    ->filter()
                    ->sortByDesc(fn ($submittedAt) => $submittedAt->timestamp)
                    ->first();
                $activity->setAttribute('monitoring_status', $status['status']);
                $activity->setAttribute('monitoring_late', $status['late']);
                $activity->setAttribute('last_submitted_at', $lastSubmittedAt);

                return $activity;
            });
    }

    public function paginate(Collection $activities, Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $activities->forPage($page, $perPage)->values(),
            $activities->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    public function counts(?Collection $activities = null): array
    {
        $activities ??= $this->all();
        $counts = ['Pending' => 0, 'Ongoing' => 0, 'Completed' => 0, 'Archived' => 0, 'Late' => 0];

        foreach ($activities as $activity) {
            $counts[$activity->monitoring_status]++;
            if ($activity->monitoring_late) {
                $counts['Late']++;
            }
        }

        $counts['Total'] = $activities->count();
        $counts['Organizations'] = $activities->pluck('gpoa.user_id')->filter()->unique()->count();

        return $counts;
    }

    public function organizationProgress(?Collection $activities = null): Collection
    {
        $activities ??= $this->all();

        return $activities->groupBy(fn (GpoaActivity $activity) => $activity->gpoa?->user_id)
            ->filter(fn ($organizationActivities, $userId) => $userId !== null)
            ->map(function (Collection $organizationActivities): array {
                $first = $organizationActivities->first();
                $total = $organizationActivities->count();
                $completed = $organizationActivities->where('monitoring_status', 'Completed')->count();

                return [
                    'user_id' => $first->gpoa->user_id,
                    'organization' => $first->gpoa->user?->org_name ?? $first->gpoa->user?->name ?? '—',
                    'college' => $first->gpoa->college,
                    'total' => $total,
                    'completed' => $completed,
                    'percent' => $total === 0 ? 0 : (int) round(($completed / $total) * 100),
                ];
            })->values();
    }

    public function dashboardData(?Collection $activities = null): array
    {
        $activities ??= $this->all();
        $topOrganizations = $activities->groupBy(fn (GpoaActivity $activity) => $activity->gpoa?->user_id)
            ->filter(fn ($organizationActivities, $userId) => $userId !== null)
            ->map(function (Collection $organizationActivities): array {
                $first = $organizationActivities->first();

                return [
                    'organization' => $first->gpoa->user?->org_name ?? $first->gpoa->user?->name ?? '—',
                    'completed' => $organizationActivities->where('monitoring_status', 'Completed')->count(),
                    'total' => $organizationActivities->count(),
                ];
            })->sortByDesc('completed')->take(5)->values();

        $categoryCounts = $activities->groupBy(fn (GpoaActivity $activity) => $activity->category ?: 'Uncategorized')
            ->map(fn (Collection $categoryActivities, string $category) => [
                'category' => $category,
                'count' => $categoryActivities->count(),
            ])->sortByDesc('count')->values();

        $recentSubmissions = collect();
        foreach ($activities as $activity) {
            $request = $activity->activityRequest;
            if ($request?->communication_letter) {
                $recentSubmissions->push([
                    'activity' => $activity->title,
                    'organization' => $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—',
                    'document' => 'Communication Letter',
                    'submitted_at' => $request->communication_letter_signed_at ?? $request->updated_at,
                ]);
            }
            if ($request?->report && ($request->report->narrative_report || $request->report->narrative_content)) {
                $recentSubmissions->push([
                    'activity' => $activity->title,
                    'organization' => $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—',
                    'document' => 'Narrative Report',
                    'submitted_at' => $request->report->submitted_at,
                ]);
            }
        }

        return [
            'activeOrganizations' => Organization::query()->where('is_active', true)->count(),
            'topOrganizations' => $topOrganizations,
            'categoryCounts' => $categoryCounts,
            'recentSubmissions' => $recentSubmissions->sortByDesc(fn (array $row) => $row['submitted_at']?->timestamp ?? 0)->take(10)->values(),
        ];
    }
}
