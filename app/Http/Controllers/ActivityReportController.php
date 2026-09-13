<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class ActivityReportController extends Controller
{
    public function create(ActivityRequest $activityRequest)
    {
        $this->authorize('view', $activityRequest);

        $activityRequest->refreshLifecycleStatus();
        $existingReport = $activityRequest->report;

        $isReturnedForCorrection = $existingReport && $existingReport->status === 'needs_revision';

        if ($existingReport && !$isReturnedForCorrection) {
            return redirect()->route('activity-requests.index')
                ->with('error', 'A final report has already been submitted for this activity.');
        }

        if (!$isReturnedForCorrection && !in_array($activityRequest->status, [
            ActivityRequest::STATUS_APPROVED,
            ActivityRequest::STATUS_IN_PROGRESS,
            ActivityRequest::STATUS_AWAITING_REPORT,
        ])) {
            return redirect()->route('activity-requests.index')
                ->with('error', 'Final report can only be submitted after the activity is approved and conducted.');
        }

        return view('users.submit-report', compact('activityRequest', 'existingReport'));
    }

    public function store(Request $request, ActivityRequest $activityRequest)
    {
        $this->authorize('update', $activityRequest);

        $activityRequest->refreshLifecycleStatus();
        $existingReport = $activityRequest->report;
        $isReturnedForCorrection = $existingReport && $existingReport->status === 'needs_revision';

        if (!$isReturnedForCorrection && !in_array($activityRequest->status, [
            ActivityRequest::STATUS_APPROVED,
            ActivityRequest::STATUS_IN_PROGRESS,
            ActivityRequest::STATUS_AWAITING_REPORT,
        ])) {
            abort(403, 'Final report cannot be submitted at this stage.');
        }

        if ($existingReport && !$isReturnedForCorrection) {
            return redirect()->route('activity-requests.index')
                ->with('error', 'A final report has already been submitted.');
        }

        $validated = $request->validate([
            'narrative_report' => 'required|file|mimes:pdf|max:20480',
            'description' => 'nullable|string|max:1000',
            'signed_by_secretary' => 'required|accepted',
            'signed_by_governor' => 'required|accepted',
            'signed_by_advisor' => 'required|accepted',
            'signed_by_dean_president' => 'required|accepted',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $path = $request->file('narrative_report')->store('uploads/narratives', 'public');

        if ($existingReport && $isReturnedForCorrection) {
            $existingReport->update([
                'narrative_report' => $path,
                'submitted_at' => now(),
                'description' => $validated['description'] ?? null,
                'signed_by_secretary' => $validated['signed_by_secretary'],
                'signed_by_governor' => $validated['signed_by_governor'],
                'signed_by_advisor' => $validated['signed_by_advisor'],
                'signed_by_dean_president' => $validated['signed_by_dean_president'],
                'status' => 'pending',
                'feedback' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]);
            $report = $existingReport;
        } else {
            $report = ActivityReport::create([
                'activity_request_id' => $activityRequest->id,
                'narrative_report'    => $path,
                'submitted_at'        => now(),
                'description'         => $validated['description'] ?? null,
                'signed_by_secretary' => $validated['signed_by_secretary'],
                'signed_by_governor' => $validated['signed_by_governor'],
                'signed_by_advisor' => $validated['signed_by_advisor'],
                'signed_by_dean_president' => $validated['signed_by_dean_president'],
            ]);
        }

        if ($request->hasFile('photos')) {
            $sortOrder = ($report->photos()->max('sort_order') ?? -1) + 1;
            foreach ($request->file('photos') as $photoFile) {
                $photoPath = $photoFile->store('uploads/activity-photos', 'public');
                $report->photos()->create([
                    'path' => $photoPath,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $activityRequest->update(['status' => ActivityRequest::STATUS_REPORT_SUBMITTED]);

        User::where('role', 'admin')->each(function (User $admin) use ($activityRequest) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => 'activity_report_submitted',
                'title' => 'New Activity Report',
                'message' => "A final report was submitted for {$activityRequest->title} by " . auth()->user()->org_name . '.',
            ]);
        });

        return redirect()->route('activity-requests.index')
            ->with('success', 'Final report submitted. Awaiting admin monitoring review.');
    }

    public function approve(ActivityReport $report)
    {
        $report->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'feedback' => null,
        ]);

        return back()->with('success', 'Narrative report approved.');
    }

    public function reject(Request $request, ActivityReport $report)
    {
        $validated = $request->validate([
            'feedback' => 'required|string|max:500',
        ]);

        $report->update([
            'status' => 'rejected',
            'feedback' => $validated['feedback'],
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Narrative report rejected.');
    }

    public function returnForCorrection(Request $request, ActivityReport $report)
    {
        $validated = $request->validate([
            'feedback' => 'required|string|max:500',
        ]);

        $report->update([
            'status' => 'needs_revision',
            'feedback' => $validated['feedback'],
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Narrative report returned for correction.');
    }
}
