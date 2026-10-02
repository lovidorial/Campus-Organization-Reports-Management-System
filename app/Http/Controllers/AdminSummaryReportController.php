<?php

namespace App\Http\Controllers;

use App\Exports\SummaryReportExport;
use App\Services\AdminActivityMonitoringService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdminSummaryReportController extends Controller
{
    public function index(Request $request, AdminActivityMonitoringService $monitoringService)
    {
        $data = $this->reportData($request, $monitoringService);

        return view('admin.gpoa.summary-report', $data);
    }

    public function download(Request $request, AdminActivityMonitoringService $monitoringService)
    {
        $data = $this->reportData($request, $monitoringService);

        return Excel::download(
            new SummaryReportExport(
                $data['activities'],
                $data['organizationSummary'],
                $data['categorySummary'],
                $data['statusSummary'],
                $data['filters'],
                $request->boolean('include_category_summary', true),
            ),
            'summary-report.xlsx'
        );
    }

    public function downloadPdf(Request $request, AdminActivityMonitoringService $monitoringService)
    {
        $data = $this->reportData($request, $monitoringService);

        return Pdf::loadView('admin.gpoa.summary-report-pdf', array_merge($data, [
            'includeSummaries' => $request->boolean('include_category_summary', true),
        ]))->setPaper('a4', 'landscape')->download('summary-report.pdf');
    }

    private function reportData(Request $request, AdminActivityMonitoringService $monitoringService): array
    {
        $filters = $request->validate([
            'organization' => ['nullable', 'integer'],
            'category' => ['nullable', 'string'],
            'college' => ['nullable', 'string'],
            'term' => ['nullable', 'string'],
            'school_year' => ['nullable', 'string'],
            'status' => ['nullable', 'in:Pending,Ongoing,Completed,Archived,Late'],
            'assessment' => ['nullable', 'in:aligned,partial,not_aligned'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $filters = array_merge([
            'organization' => '',
            'category' => '',
            'college' => '',
            'term' => '',
            'school_year' => '',
            'status' => '',
            'assessment' => '',
            'date_from' => '',
            'date_to' => '',
        ], $filters);

        $allActivities = $monitoringService->reportActivities();
        $this->assignActivityNumbers($allActivities);

        $defaultTerm = $this->defaultTerm($allActivities);
        $defaultSchoolYear = $this->defaultSchoolYear($allActivities);
        if ($request->missing('term') && $defaultTerm !== '') {
            $filters['term'] = $defaultTerm;
        }
        if ($request->missing('school_year') && $defaultSchoolYear !== '') {
            $filters['school_year'] = $defaultSchoolYear;
        }
        if ($request->missing('term') && $request->missing('school_year') && $defaultTerm === '' && $defaultSchoolYear === '') {
            $filters['term'] = '';
            $filters['school_year'] = '';
        }

        $activities = $monitoringService->filtered($request->merge($filters), $allActivities, true, true)
            ->filter(function ($activity) use ($filters): bool {
                $date = $activity->date?->toDateString();

                return (! $filters['date_from'] || ($date && $date >= $filters['date_from']))
                    && (! $filters['date_to'] || ($date && $date <= $filters['date_to']));
            })
            ->values();

        $counts = $monitoringService->counts($activities);

        return [
            'activities' => $activities,
            'activityCount' => $activities->count(),
            'totalBudget' => $activities->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            'organizationSummary' => $this->organizationSummary($activities),
            'categorySummary' => $this->categorySummary($activities),
            'statusSummary' => collect(['Pending', 'Ongoing', 'Completed', 'Archived'])->map(fn (string $status) => [
                'status' => $status,
                'activity_count' => $counts[$status],
            ]),
            'lateSummary' => ['status' => 'Late', 'activity_count' => $counts['Late']],
            'filters' => $filters,
            'organizationLabel' => $allActivities->first(fn ($activity) => (string) $activity->gpoa?->user_id === (string) $filters['organization'])?->gpoa?->user?->org_name
                ?? ($filters['organization'] ? 'Selected Organization' : 'All Organizations'),
            'organizations' => $allActivities->pluck('gpoa.user')->filter()->unique('id')->sortBy('org_name')->values(),
            'categories' => $allActivities->pluck('category')->filter()->unique()->sort()->values(),
            'colleges' => $allActivities->pluck('gpoa.college')->filter()->unique()->sort()->values(),
            'terms' => $allActivities->pluck('gpoa.term')->filter()->unique()->sort()->values(),
            'schoolYears' => $allActivities->pluck('gpoa.school_year')->filter()->unique()->sort()->values(),
            'pendingCount' => $counts['Pending'],
            'ongoingCount' => $counts['Ongoing'],
            'completedCount' => $counts['Completed'],
            'archivedCount' => $counts['Archived'],
            'lateCount' => $counts['Late'],
        ];
    }

    private function defaultTerm($allActivities): string
    {
        $gpoa = $allActivities->pluck('gpoa')->filter()->unique('id')->sortByDesc(function ($gpoa): int {
            $year = (int) preg_replace('/\D+/', '', (string) ($gpoa->school_year ?? '0'));
            $term = match (true) {
                str_contains(strtolower((string) ($gpoa->term ?? '')), '1st') => 1,
                str_contains(strtolower((string) ($gpoa->term ?? '')), '2nd') => 2,
                str_contains(strtolower((string) ($gpoa->term ?? '')), '3rd') => 3,
                str_contains(strtolower((string) ($gpoa->term ?? '')), '4th') => 4,
                str_contains(strtolower((string) ($gpoa->term ?? '')), 'summer') => 5,
                default => 0,
            };

            return ($year * 10) + $term;
        })->first();

        return $gpoa?->term ?? '';
    }

    private function defaultSchoolYear($allActivities): string
    {
        $gpoa = $allActivities->pluck('gpoa')->filter()->unique('id')->sortByDesc(function ($gpoa): int {
            return (int) preg_replace('/\D+/', '', (string) ($gpoa->school_year ?? '0'));
        })->first();

        return $gpoa?->school_year ?? '';
    }

    private function assignActivityNumbers($activities): void
    {
        $activities->groupBy('gpoa_id')->each(function ($gpoaActivities): void {
            $gpoaActivities->sortBy([['date', 'asc'], ['id', 'asc']])->values()
                ->each(fn ($activity, $index) => $activity->setAttribute('activity_number', $index + 1));
        });
    }

    private function categorySummary($activities)
    {
        return $activities
            ->groupBy(fn ($activity) => $activity->category ?: 'Uncategorized')
            ->map(function ($categoryActivities, $category): array {
                $finishedCount = $categoryActivities->filter(fn ($activity) => in_array($activity->monitoring_status, ['Completed', 'Archived'], true))->count();

                return [
                    'category' => $category,
                    'activity_count' => $categoryActivities->count(),
                    'completed' => $finishedCount,
                    'archived' => $categoryActivities->where('monitoring_status', 'Archived')->count(),
                    'late' => $categoryActivities->filter(fn ($activity) => (bool) $activity->monitoring_late)->count(),
                ];
            })
            ->sortKeys()
            ->values();
    }

    private function organizationSummary($activities)
    {
        return $activities
            ->groupBy(fn ($activity) => ($activity->gpoa?->user_id ?? 'unknown') . '|' . ($activity->gpoa?->term ?? '—') . '|' . ($activity->gpoa?->school_year ?? '—'))
            ->map(function ($group): array {
                $first = $group->first();
                $total = $group->count();
                $completed = $group->where('monitoring_status', 'Completed')->count();
                $archived = $group->where('monitoring_status', 'Archived')->count();
                $finished = $completed + $archived;
                $ongoing = $group->where('monitoring_status', 'Ongoing')->count();
                $pending = $group->where('monitoring_status', 'Pending')->count();
                $late = $group->filter(fn ($activity) => (bool) $activity->monitoring_late)->count();
                $unfinished = $total - $finished;

                return [
                    'organization' => $first->gpoa?->user?->org_name ?? $first->gpoa?->user?->name ?? '—',
                    'term' => $first->gpoa?->term ?? '—',
                    'school_year' => $first->gpoa?->school_year ?? '—',
                    'term_sy' => trim(($first->gpoa?->term ?? '—') . ' / ' . ($first->gpoa?->school_year ?? '—'), ' / '),
                    'activity_count' => $total,
                    'completed' => $finished,
                    'archived' => $archived,
                    'late' => $late,
                    'ongoing' => $ongoing,
                    'pending' => $pending,
                    'unfinished_count' => $unfinished,
                    'gpoa_finished' => $unfinished === 0 ? 'Yes' : 'No (' . $unfinished . ' left)',
                    'progress' => $total === 0 ? 0 : (int) round(($finished / $total) * 100),
                ];
            })
            ->sortBy('organization')
            ->values();
    }
}