<?php

namespace App\Http\Controllers;

use App\Models\OrganizationWorkflow;
use App\Models\WorkflowSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminWorkflowController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.dashboard');
    }

    public function show(OrganizationWorkflow $workflow)
    {
        return redirect()->route('admin.dashboard');
    }

    public function viewDocument(WorkflowSubmission $submission)
    {
        if (!$submission->file_path || !Storage::disk('public')->exists($submission->file_path)) {
            if ($submission->gpoa && $submission->gpoa->document_path) {
                $path = storage_path('app/public/' . $submission->gpoa->document_path);
                if (file_exists($path)) {
                    return response()->file($path);
                }
            }
            abort(404, 'Document not found.');
        }

        return response()->file(storage_path('app/public/' . $submission->file_path));
    }

    public function export(Request $request)
    {
        $workflows = OrganizationWorkflow::with('user')->get();

        $filename = 'organization_workflows_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($workflows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Organization', 'Term', 'School Year', 'Current Stage',
                'Completion %', 'Status', 'Last Updated',
            ]);

            foreach ($workflows as $w) {
                fputcsv($handle, [
                    $w->user->org_name ?? $w->user->name,
                    $w->term,
                    $w->school_year,
                    str_replace('_', ' ', ucfirst($w->current_stage)),
                    $w->completion_percentage . '%',
                    $w->is_completed ? 'Completed' : 'In Progress',
                    $w->updated_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
