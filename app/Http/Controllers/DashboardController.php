<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\OrganizationWorkflow;
use App\Models\UserNotification;
use App\Services\OrganizationWorkflowService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private OrganizationWorkflowService $workflowService
    ) {}

    public function index()
    {
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $user = auth()->user();
        $term = $user->term ?? '1st Term';
        $schoolYear = $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        $workflow = $this->workflowService->getOrCreateForUser($user, $term, $schoolYear);
        $workflow->load(['submissions.reviewer', 'events']);

        $progressStages = $workflow->progressStages();
        $submissionHistory = $workflow->submissions()->with('reviewer')->orderByDesc('created_at')->get();
        $recentEvents = $workflow->events()->with('user')->take(10)->get();
        $notifications = $user->notifications()->latest()->take(5)->get();
        $unreadCount = $user->unreadNotificationsCount();

        $gpoa = \App\Models\Gpoa::where('user_id', auth()->id())
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->with('activities')
            ->latest()
            ->first();

        $activities = ActivityRequest::where('user_id', auth()->id())
            ->with(['gpoaActivity', 'report'])
            ->latest()
            ->paginate(5);

        $stats = [
            'total'    => ActivityRequest::where('user_id', auth()->id())->count(),
            'pending'  => ActivityRequest::where('user_id', auth()->id())->where('status', 'pending')->count(),
            'approved' => ActivityRequest::where('user_id', auth()->id())
                ->whereIn('status', ['approved', 'in_progress', 'awaiting_report', 'report_submitted', 'closed'])->count(),
            'rejected' => ActivityRequest::where('user_id', auth()->id())->where('status', 'rejected')->count(),
        ];

        $hasApprovedGpoa = $workflow->isGpoaApproved();

        return view('dashboard', compact(
            'activities', 'stats', 'gpoa', 'hasApprovedGpoa', 'term', 'schoolYear',
            'workflow', 'progressStages', 'submissionHistory', 'recentEvents',
            'notifications', 'unreadCount'
        ));
    }
}
