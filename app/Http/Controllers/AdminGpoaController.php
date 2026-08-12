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
        $query = Gpoa::with(['user', 'activities'])
            ->withCount('activities')
            ->when($request->search, function ($query) use ($request) {
                $search = trim($request->search);

                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('org_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })->orWhere('term', 'like', "%{$search}%")
                    ->orWhere('school_year', 'like', "%{$search}%")
                    ->orWhere('college', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            });

        $gpoas = $query->latest()->paginate(20)->appends($request->query());

        $stats = [
            'total' => Gpoa::count(),
            'pending' => Gpoa::where('status', 'pending')->count(),
            'approved' => Gpoa::whereIn('status', ['approved', 'stored'])->count(),
            'rejected' => Gpoa::where('status', 'rejected')->count(),
        ];

        return view('admin.gpoa.index', compact('gpoas', 'stats'));
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
