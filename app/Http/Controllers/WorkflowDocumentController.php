<?php

namespace App\Http\Controllers;

use App\Models\OrganizationWorkflow;
use App\Models\UserNotification;
use App\Services\OrganizationWorkflowService;
use Illuminate\Http\Request;

class WorkflowDocumentController extends Controller
{
    public function __construct(
        private OrganizationWorkflowService $workflowService
    ) {}

    public function communicationLetter()
    {
        // Deprecated: Communication letters are now scoped to individual activities
        // This view is deprecated and will redirect users to activity requests
        return redirect()->route('activity-requests.index')
            ->with('info', 'Communication letters are now submitted with individual activity requests. Please review and submit your activity requests.');
    }

    public function storeCommunicationLetter(Request $request)
    {
        // Deprecated: Communication letters are now scoped to individual activities
        return back()->with('error', 'Communication letter submission is no longer available at the organization level. Please submit your activity requests instead.');
    }

    public function summaryReport()
    {
        $user = auth()->user();
        $workflow = $this->workflowService->getOrCreateForUser($user);
        $canSubmit = $workflow->canSubmitSummaryReport();
        $submission = $workflow->currentSubmission(OrganizationWorkflow::DOC_SUMMARY);

        return view('workflow.summary-report', compact('workflow', 'submission', 'canSubmit'));
    }

    public function storeSummaryReport(Request $request)
    {
        $user = auth()->user();
        $workflow = $this->workflowService->getOrCreateForUser($user);

        if (!$workflow->canSubmitSummaryReport()) {
            return back()->with('error', 'Summary Report submission is not available at this stage.');
        }

        $validated = $request->validate([
            'summary_report' => 'required|file|mimes:pdf|max:20480',
            'verify' => 'required|accepted',
        ]);

        $path = $request->file('summary_report')->store('uploads/summary-reports', 'public');

        $this->workflowService->recordDocumentSubmission(
            $workflow,
            OrganizationWorkflow::DOC_SUMMARY,
            $path
        );

        return redirect()->route('dashboard')
            ->with('success', 'Summary Report submitted successfully. Awaiting OSDW review.');
    }

    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('workflow.notifications', compact('notifications'));
    }

    public function markNotificationRead(UserNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        return back();
    }

    public function markAllNotificationsRead()
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
