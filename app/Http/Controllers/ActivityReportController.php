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
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $path = $request->file('narrative_report')->store('uploads/narratives', 'public');

        $report = ActivityReport::create([
            'activity_request_id' => $activityRequest->id,
            'narrative_report'    => $path,
            'submitted_at'        => now(),
        ]);

        if ($request->hasFile('photos')) {
            $sortOrder = 0;
            foreach ($request->file('photos') as $photoFile) {
                $photoPath = $photoFile->store('uploads/activity-photos', 'public');
                $report->photos()->create([
                    'path' => $photoPath,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $activityRequest->update(['status' => ActivityRequest::STATUS_REPORT_SUBMITTED]);

        return redirect()->route('activity-requests.index')
            ->with('success', 'Final report submitted. Awaiting admin monitoring review.');
    }
}
