<?php

namespace App\Http\Controllers;

use App\Exports\SummaryReportExport;
use App\Models\ActivityRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdminSummaryReportController extends Controller
{
    private const CATEGORIES = [
        'Symposium',
        'Convocation',
        'Religious Activity',
        'Socio-Cultural and Sports',
        'Makakalikasan (Clean and Green)',
        'Extension Services Conducted',
    ];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $allActivityRequests = $this->activityRequests($filters, false)->get();
        $activityRequests = $allActivityRequests->whereIn('status', [
            'approved', 'in_progress', 'awaiting_report', 'report_submitted', 'closed',
        ])->values();
        $organizations = User::query()
            ->where('role', '!=', 'admin')
            ->whereNotNull('org_name')
            ->where('org_name', '!=', '')
            ->distinct()
            ->orderBy('org_name')
            ->pluck('org_name');

        return view('admin.gpoa.summary-report', [
            'activityRequests' => $activityRequests,
            'totalRequestCount' => $allActivityRequests->count(),
            'totalBudget' => $activityRequests->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            'categorySummary' => $this->categorySummary($allActivityRequests),
            'organizationSummary' => $this->organizationSummary($allActivityRequests),
            'statusSummary' => $this->statusSummary($allActivityRequests),
            'filters' => $filters,
            'organizations' => $organizations,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function download(Request $request)
    {
        $filters = $this->filters($request);
        $activityRequests = $this->activityRequests($filters)->get();
        $allActivityRequests = $this->activityRequests($filters, false)->get();

        return Excel::download(
            new SummaryReportExport(
                $activityRequests,
                $this->organizationSummary($allActivityRequests),
                $this->categorySummary($allActivityRequests),
                $this->statusSummary($allActivityRequests),
                $request->boolean('include_category_summary', true),
            ),
            'summary-report.xlsx'
        );
    }

    public function downloadPdf(Request $request)
    {
        $filters = $this->filters($request);
        $activityRequests = $this->activityRequests($filters)->get();
        $allActivityRequests = $this->activityRequests($filters, false)->get();

        return Pdf::loadView('admin.gpoa.summary-report-pdf', [
            'activityRequests' => $activityRequests,
            'totalBudget' => $activityRequests->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            'organizationSummary' => $this->organizationSummary($allActivityRequests),
            'categorySummary' => $this->categorySummary($allActivityRequests),
            'statusSummary' => $this->statusSummary($allActivityRequests),
            'includeSummaries' => $request->boolean('include_category_summary', true),
            'term' => $filters['term'],
            'organization' => $filters['organization'],
            'category' => $filters['category'],
            'dateFrom' => $filters['date_from'],
            'dateTo' => $filters['date_to'],
        ])->download('summary-report.pdf');
    }

    private function filters(Request $request): array
    {
        return [
            'term' => $request->input('term'),
            'organization' => $request->input('organization'),
            'category' => $request->input('category'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
    }

    private function activityRequests(array $filters, bool $completedOnly = true)
    {
        return ActivityRequest::with([
            'user',
            'gpoaActivity.gpoa',
            'gpoa',
            'report',
            'venueRecord' => fn ($query) => $query->withCount(['scheduledRequests', 'futureReservationRequests']),
        ])
            ->when($completedOnly, fn ($query) => $query->whereIn('status', [
                'approved', 'in_progress', 'awaiting_report', 'report_submitted', 'closed',
            ]))
            ->when($filters['term'], function ($query, $term) {
                $query->where(function ($gpoaQuery) use ($term) {
                    $gpoaQuery->whereHas('gpoa', fn ($directGpoa) => $directGpoa->where('term', $term))
                        ->orWhereHas('gpoaActivity.gpoa', fn ($activityGpoa) => $activityGpoa->where('term', $term));
                });
            })
            ->when($filters['organization'], function ($query, $organization) {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('org_name', $organization));
            })
            ->when($filters['category'], fn ($query, $category) => $query->where('category', $category))
            ->when($filters['date_from'], fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'], fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->orderBy('date');
    }

    private function categorySummary($activityRequests)
    {
        return $activityRequests
            ->groupBy(fn ($activity) => $activity->category ?: 'Uncategorized')
            ->map(fn ($activities, $category) => [
                'category' => $category,
                'activity_count' => $activities->count(),
                'participants' => $activities->sum(fn ($activity) => (int) ($activity->participants_count ?? 0)),
                'budget' => $activities->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            ])
            ->values();
    }

    private function organizationSummary($activityRequests)
    {
        return $activityRequests
            ->groupBy(fn ($activity) => $activity->user->org_name ?? $activity->user->name ?? 'Unknown Organization')
            ->map(fn ($activities, $organization) => [
                'organization' => $organization,
                'activity_count' => $activities->count(),
                'participants' => $activities->sum(fn ($activity) => (int) ($activity->participants_count ?? 0)),
                'budget' => $activities->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            ])
            ->sortBy('organization')
            ->values();
    }

    private function statusSummary($activityRequests)
    {
        return $activityRequests
            ->groupBy('status')
            ->map(fn ($activities, $status) => [
                'status' => str_replace('_', ' ', ucfirst($status)),
                'activity_count' => $activities->count(),
                'participants' => $activities->sum(fn ($activity) => (int) ($activity->participants_count ?? 0)),
                'budget' => $activities->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            ])
            ->sortBy('status')
            ->values();
    }
}