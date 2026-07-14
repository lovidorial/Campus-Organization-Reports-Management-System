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
        $user = auth()->user();
        $workflow = $this->workflowService->getOrCreateForUser($user);

        if (!$workflow->canSubmitCommunicationLetter()) {
            return redirect()->route('dashboard')
                ->with('error', 'Communication Letter submission is locked until your GPOA is approved.');
        }

        $submission = $workflow->currentSubmission(OrganizationWorkflow::DOC_COMMUNICATION);

        return view('workflow.communication-letter', compact('workflow', 'submission'));
    }

    public function storeCommunicationLetter(Request $request)
    {
        $user = auth()->user();
        $workflow = $this->workflowService->getOrCreateForUser($user);

        if (!$workflow->canSubmitCommunicationLetter()) {
            return back()->with('error', 'Communication Letter submission is not available at this stage.');
        }

        $validated = $request->validate([
            'communication_letter' => 'required|file|mimes:pdf|max:20480',
            'verify' => 'required|accepted',
        ]);

        $path = $request->file('communication_letter')->store('uploads/communication-letters', 'public');

        $this->workflowService->recordDocumentSubmission(
            $workflow,
            OrganizationWorkflow::DOC_COMMUNICATION,
            $path
        );

        return redirect()->route('dashboard')
            ->with('success', 'Communication Letter submitted successfully. Awaiting OSDW review.');
    }

    public function summaryReport()
    {
        $user = auth()->user();
        $workflow = $this->workflowService->getOrCreateForUser($user);

        if (!$workflow->canSubmitSummaryReport()) {
            return redirect()->route('dashboard')
                ->with('error', 'Summary Report submission is locked until your Communication Letter is approved.');
        }

        $submission = $workflow->currentSubmission(OrganizationWorkflow::DOC_SUMMARY);

        return view('workflow.summary-report', compact('workflow', 'submission'));
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
