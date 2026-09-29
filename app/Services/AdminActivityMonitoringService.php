<?php

namespace App\Services;

use App\Models\GpoaActivity;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

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
                $activity->setAttribute('monitoring_status', $status['status']);
                $activity->setAttribute('monitoring_late', $status['late']);

                return $activity;
            });
    }

    public function filtered(Request $request, ?Collection $activities = null, bool $applySearchAndStatus = true): Collection
    {
        $activities ??= $this->all();
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');
        $organizationFilter = (string) $request->query('organization', '');
        $categoryFilter = (string) $request->query('category', '');
        $collegeFilter = (string) $request->query('college', '');
        $termFilter = (string) $request->query('term', '');
        $schoolYearFilter = (string) $request->query('school_year', '');

        return $activities->filter(function (GpoaActivity $activity) use ($search, $statusFilter, $organizationFilter, $categoryFilter, $collegeFilter, $termFilter, $schoolYearFilter, $applySearchAndStatus): bool {
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
        })->sortByDesc(fn (GpoaActivity $activity) => $activity->date?->timestamp ?? 0)->values();
    }

    public function counts(?Collection $activities = null): array
    {
        $activities ??= $this->all();
        $counts = ['Not Started' => 0, 'Ongoing' => 0, 'Completed' => 0, 'Late' => 0];

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
