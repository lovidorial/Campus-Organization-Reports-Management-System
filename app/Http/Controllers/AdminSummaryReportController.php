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
            'status' => ['nullable', 'in:Pending,Ongoing,Completed,Late'],
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
            'date_from' => '',
            'date_to' => '',
        ], $filters);

        $allActivities = $monitoringService->all();
        $this->assignActivityNumbers($allActivities);
        $activities = $monitoringService->filtered($request, $allActivities)
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
            'statusSummary' => collect(['Pending', 'Ongoing', 'Completed', 'Late'])->map(fn (string $status) => [
                'status' => $status,
                'activity_count' => $counts[$status],
            ]),
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
            'lateCount' => $counts['Late'],
        ];
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
            ->map(fn ($categoryActivities, $category) => [
                'category' => $category,
                'activity_count' => $categoryActivities->count(),
                'completed' => $categoryActivities->where('monitoring_status', 'Completed')->count(),
            ])
            ->sortKeys()
            ->values();
    }

    private function organizationSummary($activities)
    {
        return $activities
            ->groupBy(fn ($activity) => $activity->gpoa?->user_id)
            ->map(function ($organizationActivities): array {
                $first = $organizationActivities->first();
                $total = $organizationActivities->count();

                return [
                    'organization' => $first->gpoa?->user?->org_name ?? $first->gpoa?->user?->name ?? '—',
                    'activity_count' => $total,
                    'completed' => $organizationActivities->where('monitoring_status', 'Completed')->count(),
                    'ongoing' => $organizationActivities->where('monitoring_status', 'Ongoing')->count(),
                    'pending' => $organizationActivities->where('monitoring_status', 'Pending')->count(),
                    'progress' => $total === 0 ? 0 : (int) round(($organizationActivities->where('monitoring_status', 'Completed')->count() / $total) * 100),
                ];
            })
            ->sortBy('organization')
            ->values();
    }
}