<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdminSummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $activityRequests = $this->activityRequests($filters)->get();

        return view('admin.gpoa.summary-report', [
            'activityRequests' => $activityRequests,
            'totalRequestCount' => $this->activityRequests($filters, false)->count(),
            'totalBudget' => $activityRequests->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            'filters' => $filters,
        ]);
    }

    public function download(Request $request)
    {
        $filters = $this->filters($request);
        $activityRequests = $this->activityRequests($filters)->get();

        return Pdf::loadView('admin.gpoa.summary-report-pdf', [
            'activityRequests' => $activityRequests,
            'totalBudget' => $activityRequests->sum(fn ($activity) => (float) ($activity->estimated_budget ?? 0)),
            'term' => $filters['term'],
            'dateFrom' => $filters['date_from'],
            'dateTo' => $filters['date_to'],
        ])->download('summary-report.pdf');
    }

    private function filters(Request $request): array
    {
        return [
            'term' => $request->input('term'),
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
            ->when($filters['date_from'], fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'], fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->orderBy('date');
    }
}