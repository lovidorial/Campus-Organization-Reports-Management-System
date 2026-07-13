<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use Illuminate\Http\Request;

class ActivityReportController extends Controller
{
    public function create(ActivityRequest $activityRequest)
    {
        if ($activityRequest->user_id !== auth()->id()) {
            abort(403);
        }

        $activityRequest->refreshLifecycleStatus();

        if (!in_array($activityRequest->status, [
            ActivityRequest::STATUS_APPROVED,
            ActivityRequest::STATUS_IN_PROGRESS,
            ActivityRequest::STATUS_AWAITING_REPORT,
        ])) {
            return redirect()->route('activity-requests.index')
                ->with('error', 'Final report can only be submitted after the activity is approved and conducted.');
        }

        if ($activityRequest->report) {
            return redirect()->route('activity-requests.index')
                ->with('error', 'A final report has already been submitted for this activity.');
        }

        return view('users.submit-report', compact('activityRequest'));
    }

    public function store(Request $request, ActivityRequest $activityRequest)
    {
        if ($activityRequest->user_id !== auth()->id()) {
            abort(403);
        }

        $activityRequest->refreshLifecycleStatus();

        if (!in_array($activityRequest->status, [
            ActivityRequest::STATUS_APPROVED,
            ActivityRequest::STATUS_IN_PROGRESS,
            ActivityRequest::STATUS_AWAITING_REPORT,
        ])) {
            abort(403, 'Final report cannot be submitted at this stage.');
        }

        if ($activityRequest->report) {
            return redirect()->route('activity-requests.index')
                ->with('error', 'A final report has already been submitted.');
        }

        $validated = $request->validate([
            'narrative_report' => 'required|file|mimes:pdf|max:20480',
        ]);

        $path = $request->file('narrative_report')->store('uploads/narratives', 'public');

        ActivityReport::create([
            'activity_request_id' => $activityRequest->id,
            'narrative_report'    => $path,
            'submitted_at'        => now(),
        ]);

        $activityRequest->update(['status' => ActivityRequest::STATUS_REPORT_SUBMITTED]);

        return redirect()->route('activity-requests.index')
            ->with('success', 'Final report submitted. Awaiting admin monitoring review.');
    }
}
