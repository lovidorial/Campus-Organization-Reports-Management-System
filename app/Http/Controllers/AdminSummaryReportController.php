<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
        $activityRequests = $this->activityRequests($filters)->get();
        $organizations = User::query()
            ->where('role', '!=', 'admin')
            ->whereNotNull('org_name')
            ->where('org_name', '!=', '')
            ->distinct()
            ->orderBy('org_name')
            ->pluck('org_name');

        return view('admin.gpoa.summary-report', [
            'activityRequests' => $activityRequests,
            'totalRequestCount' => $this->activityRequests($filters, false)->count(),
            'totalBudget' => $activityRequests->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            'categorySummary' => $this->categorySummary($activityRequests),
            'filters' => $filters,
            'organizations' => $organizations,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function download(Request $request)
    {
        $filters = $this->filters($request);
        $activityRequests = $this->activityRequests($filters)->get();

        return Pdf::loadView('admin.gpoa.summary-report-pdf', [
            'activityRequests' => $activityRequests,
            'totalBudget' => $activityRequests->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            'categorySummary' => $this->categorySummary($activityRequests),
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
        return ActivityRequest::with(['user', 'gpoaActivity.gpoa', 'gpoa', 'report'])
            ->when($completedOnly, fn ($query) => $query->whereIn('status', [
                'approved', 'in_progress', 'awaiting_report', 'report_submitted', 'closed',
            ]))
            ->when($filters['term'], function ($query, $term) {
                $query->whereHas('gpoaActivity.gpoa', fn ($gpoaQuery) => $gpoaQuery->where('term', $term));
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
}