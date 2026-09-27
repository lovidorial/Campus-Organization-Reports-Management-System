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
            'terms',
            'schoolYears'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'term' => 'required|string|max:50',
            'school_year' => 'required|string|max:20',
            'deadlines' => 'nullable|array',
            'deadlines.*' => 'nullable|date',
        ]);

        foreach (DocumentDeadline::TYPES as $type) {
            $date = $validated['deadlines'][$type] ?? null;

            if ($date) {
                DocumentDeadline::updateOrCreate(
                    [
                        'document_type' => $type,
                        'term' => $validated['term'],
                        'school_year' => $validated['school_year'],
                    ],
                    ['deadline_date' => $date]
                );
            } else {
                DocumentDeadline::where('document_type', $type)
                    ->where('term', $validated['term'])
                    ->where('school_year', $validated['school_year'])
                    ->delete();
            }
        }

        return redirect()
            ->route('admin.document-deadlines.index', [
                'term' => $validated['term'],
                'school_year' => $validated['school_year'],
            ])
            ->with('success', 'Document deadlines updated.');
    }
}
