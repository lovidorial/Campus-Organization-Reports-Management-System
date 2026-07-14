<?php

namespace App\Http\Controllers;

use App\Models\OrganizationWorkflow;
use App\Models\WorkflowSubmission;
use App\Services\OrganizationWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminWorkflowController extends Controller
{
    public function __construct(
        private OrganizationWorkflowService $workflowService
    ) {}

    public function index(Request $request)
    {
        $query = OrganizationWorkflow::with(['user', 'submissions.reviewer']);

        if ($request->filled('status')) {
            if ($request->status === 'completed') {
                $query->where('is_completed', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_completed', false);
            } elseif ($request->status === 'overdue') {
                $query->where('is_completed', false)
                    ->where('updated_at', '<', now()->subDays(30));
            }
        }

        if ($request->filled('stage')) {
            $query->where('current_stage', $request->stage);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('user', function ($q) use ($term) {
                $q->where('org_name', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            });
        }

        $workflows = $query->latest()->paginate(15)->withQueryString();
        $stats = $this->workflowService->workflowStats();

        $pendingSubmissions = WorkflowSubmission::with(['workflow.user', 'reviewer'])
            ->where('is_current', true)
            ->where('status', WorkflowSubmission::STATUS_UNDER_REVIEW)
            ->latest('submitted_at')
            ->take(10)
            ->get();

        return view('admin.workflows.index', compact('workflows', 'stats', 'pendingSubmissions'));
    }

    public function show(OrganizationWorkflow $workflow)
    {
        $workflow->load([
            'user',
            'submissions.reviewer',
            'submissions.gpoa.activities',
            'events.user',
        ]);

        return view('admin.workflows.show', compact('workflow'));
    }

    public function approveSubmission(Request $request, WorkflowSubmission $submission)
    {
        $request->validate(['approval_remarks' => 'nullable|string|max:500']);

        if ($submission->status !== WorkflowSubmission::STATUS_UNDER_REVIEW) {
            return back()->with('error', 'Only submissions under review can be approved.');
        }

        $this->workflowService->approveSubmission(
            $submission,
            auth()->user(),
            $request->approval_remarks
        );

        return back()->with('success', $submission->documentLabel() . ' approved successfully.');
    }

    public function rejectSubmission(Request $request, WorkflowSubmission $submission)
    {
        $request->validate(['reject_reason' => 'required|string|max:500']);

        if ($submission->status !== WorkflowSubmission::STATUS_UNDER_REVIEW) {
            return back()->with('error', 'Only submissions under review can be rejected.');
        }

        $this->workflowService->rejectSubmission(
            $submission,
            auth()->user(),
            $request->reject_reason
        );

        return back()->with('success', $submission->documentLabel() . ' rejected.');
    }

    public function reopen(OrganizationWorkflow $workflow)
    {
        if (!$workflow->is_locked) {
            return back()->with('error', 'This workflow is not locked.');
        }

        $this->workflowService->reopenWorkflow($workflow, auth()->user());

        return back()->with('success', 'Workflow reopened. Organization may submit revisions.');
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
