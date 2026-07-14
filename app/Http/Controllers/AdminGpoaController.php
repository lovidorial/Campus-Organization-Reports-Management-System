<?php

namespace App\Http\Controllers;

use App\Models\Gpoa;
use App\Models\OrganizationWorkflow;
use App\Models\WorkflowSubmission;
use App\Services\OrganizationWorkflowService;
use Illuminate\Http\Request;

class AdminGpoaController extends Controller
{
    public function __construct(
        private OrganizationWorkflowService $workflowService
    ) {}

    public function index(Request $request)
    {
        return redirect()->route('admin.workflows.index', $request->query());
    }

    public function show(Gpoa $gpoa)
    {
        $gpoa->load(['user', 'activities', 'approver']);

        $workflow = OrganizationWorkflow::where('user_id', $gpoa->user_id)
            ->where('term', $gpoa->term)
            ->where('school_year', $gpoa->school_year)
            ->first();

        return view('admin.gpoa.show', compact('gpoa', 'workflow'));
    }

    public function approve(Gpoa $gpoa)
    {
        $submission = WorkflowSubmission::where('gpoa_id', $gpoa->id)
            ->where('is_current', true)
            ->where('document_type', OrganizationWorkflow::DOC_GPOA)
            ->first();

        if (!$submission || $submission->status !== WorkflowSubmission::STATUS_UNDER_REVIEW) {
            return back()->with('error', 'No GPOA submission under review found.');
        }

        $this->workflowService->approveSubmission($submission, auth()->user());

        return back()->with('success', 'GPOA verified, approved, and stored successfully.');
    }

    public function reject(Request $request, Gpoa $gpoa)
    {
        $request->validate(['reject_reason' => 'required|string|max:500']);

        $submission = WorkflowSubmission::where('gpoa_id', $gpoa->id)
            ->where('is_current', true)
            ->where('document_type', OrganizationWorkflow::DOC_GPOA)
            ->first();

        if (!$submission || $submission->status !== WorkflowSubmission::STATUS_UNDER_REVIEW) {
            return back()->with('error', 'No GPOA submission under review found.');
        }

        $this->workflowService->rejectSubmission($submission, auth()->user(), $request->reject_reason);

        return back()->with('success', 'GPOA rejected.');
    }
}
