<?php

namespace App\Http\Controllers;

use App\Models\DocumentDeadline;
use App\Models\OrganizationWorkflow;
use Illuminate\Http\Request;

class AdminDocumentDeadlineController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->input(
            'term',
            OrganizationWorkflow::latest()->value('term') ?? '1st Term'
        );

        $schoolYear = $request->input(
            'school_year',
            OrganizationWorkflow::latest()->value('school_year')
                ?? (date('Y') . '-' . (date('Y') + 1))
        );

        $deadlines = DocumentDeadline::where('term', $term)
            ->where('school_year', $schoolYear)
            ->get()
            ->keyBy('document_type');
        $reportDeadline = $deadlines->get(DocumentDeadline::TYPE_ACTIVITY_REPORT);
        $graceDays = $reportDeadline?->grace_days;
        $deadlineDate = $reportDeadline?->deadline_date;

        $terms = OrganizationWorkflow::distinct()
            ->whereNotNull('term')
            ->pluck('term')
            ->sort()
            ->values();

        $schoolYears = OrganizationWorkflow::distinct()
            ->whereNotNull('school_year')
            ->pluck('school_year')
            ->sort()
            ->values();

        return view('admin.document-deadlines.index', compact(
            'term',
            'schoolYear',
            'deadlines',
            'graceDays',
            'deadlineDate',
            'terms',
            'schoolYears'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'term' => 'required|string|max:50',
            'school_year' => 'required|string|max:20',
            'grace_days' => 'nullable|integer|min:0|max:60',
            'deadlines.activity_report' => 'nullable|date',
        ]);

        $type = DocumentDeadline::TYPE_ACTIVITY_REPORT;
        $graceDays = $validated['grace_days'] ?? null;
        $deadlineDate = $validated['deadlines']['activity_report'] ?? null;
        $period = [
            'document_type' => $type,
            'term' => $validated['term'],
            'school_year' => $validated['school_year'],
        ];
        $existingDeadline = DocumentDeadline::where($period)->first();
        $oldValues = [
            'grace_days' => $existingDeadline?->grace_days,
            'deadline_date' => $existingDeadline?->deadline_date?->toDateString(),
        ];

        if ($graceDays === null && $deadlineDate === null) {
            DocumentDeadline::where($period)->delete();
        } else {
            DocumentDeadline::updateOrCreate($period, [
                'grace_days' => $graceDays,
                'deadline_date' => $deadlineDate,
            ]);
        }

        activity('document_deadlines')
            ->causedBy(auth()->user())
            ->withProperties([
                'term' => $validated['term'],
                'school_year' => $validated['school_year'],
                'old' => $oldValues,
                'new' => [
                    'grace_days' => $graceDays,
                    'deadline_date' => $deadlineDate,
                ],
            ])
            ->log('document_deadline.updated');

        return redirect()
            ->route('admin.document-deadlines.index', [
                'term' => $validated['term'],
                'school_year' => $validated['school_year'],
            ])
            ->with('success', 'Document deadlines updated.');
    }
}
