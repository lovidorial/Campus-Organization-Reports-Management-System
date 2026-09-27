<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\User;
use App\Services\OrganizationWorkflowService;
use Illuminate\Support\Facades\Auth;

class WorkflowSubmissionHistoryController extends Controller
{
    public function __construct(
        private OrganizationWorkflowService $workflowService
    ) {}

    public function index()
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $workflow = $this->workflowService->getOrCreateForUser($user, $term, $schoolYear);
        $submissionHistory = $workflow->submissions()
            ->with('reviewer')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => ActivityRequest::where('user_id', $user->id)->count(),
            'pending' => ActivityRequest::where('user_id', $user->id)
                ->where('status', ActivityRequest::STATUS_PENDING)->count(),
            'approved' => ActivityRequest::where('user_id', $user->id)
                ->whereIn('status', [
                    ActivityRequest::STATUS_APPROVED,
                    ActivityRequest::STATUS_IN_PROGRESS,
                    ActivityRequest::STATUS_AWAITING_REPORT,
                    ActivityRequest::STATUS_REPORT_SUBMITTED,
                    ActivityRequest::STATUS_CLOSED,
                ])->count(),
            'rejected' => ActivityRequest::where('user_id', $user->id)
                ->where('status', ActivityRequest::STATUS_REJECTED)->count(),
        ];

        return view('workflow.submission-history', compact('submissionHistory', 'stats'));
    }
}