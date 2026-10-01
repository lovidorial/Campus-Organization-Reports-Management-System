<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\User;
use App\Models\UserNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivityReportController extends Controller
{
    public function create(ActivityRequest $activityRequest)
    {
        $this->authorize('update', $activityRequest);
        $this->ensureActivityHasPassed($activityRequest);
        $existingReport = $activityRequest->report;
        $existingReport?->loadMissing('photos');

        return view('users.submit-report', compact('activityRequest', 'existingReport'));
    }

    public function store(Request $request, ActivityRequest $activityRequest)
    {
        $this->authorize('update', $activityRequest);
        $this->ensureActivityHasPassed($activityRequest);

        $existingReport = $activityRequest->report;
        $existingPhotoCount = $existingReport?->photos()->count() ?? 0;
        $hasExistingPhotos = $existingPhotoCount > 0;
        $photoRules = $hasExistingPhotos
            ? 'nullable|array|max:' . max(0, 10 - $existingPhotoCount)
            : 'required|array|min:1|max:10';

        $validated = $request->validate([
            'narrative_source' => 'required|in:uploaded,generated',
            'narrative_report' => 'nullable|file|mimes:pdf|max:20480',
            'narrative_content' => 'nullable|string|max:30000',
            'description' => 'nullable|string|max:1000',
            'signed_by_secretary' => 'required|accepted',
            'signed_by_governor' => 'required|accepted',
            'signed_by_advisor' => 'required|accepted',
            'signed_by_dean_president' => 'required|accepted',
            'photos' => $photoRules,
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'attendance_sheet' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,gif|max:10240',
        ]);

        if ($validated['narrative_source'] === 'uploaded'
            && ! $request->hasFile('narrative_report')
            && ! ($existingReport?->narrative_source === 'uploaded' && filled($existingReport?->narrative_report))) {
            throw ValidationException::withMessages([
                'narrative_report' => 'Choose a PDF narrative report to upload.',
            ]);
        }

        if ($validated['narrative_source'] === 'generated' && ! filled($validated['narrative_content'] ?? null)) {
            throw ValidationException::withMessages([
                'narrative_content' => 'Enter the narrative report text.',
            ]);
        }

        if ($validated['narrative_source'] === 'uploaded') {
            $path = $request->hasFile('narrative_report')
                ? $request->file('narrative_report')->store('activity-documents/narrative-reports', 'private')
                : $existingReport->narrative_report;
            $narrativeContent = null;
        } else {
            $narrativeContent = ['body' => trim($validated['narrative_content'])];
            $path = 'activity-documents/narrative-reports/' . $activityRequest->id . '/' . Str::uuid() . '.pdf';
            $pdf = Pdf::loadView('reports.narrative-report', [
                'activityRequest' => $activityRequest,
                'body' => $narrativeContent['body'],
            ]);
            Storage::disk('private')->put($path, $pdf->output());
        }

        $previousAttendancePath = $existingReport?->attendance_sheet_path;
        $attendancePath = $request->hasFile('attendance_sheet')
            ? $request->file('attendance_sheet')->store('activity-documents/attendance-sheets', 'private')
            : $previousAttendancePath;

        if ($existingReport) {
            $previousPath = $existingReport->narrative_report;
            $existingReport->update([
                'narrative_report' => $path,
                'narrative_source' => $validated['narrative_source'],
                'narrative_content' => $narrativeContent,
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
                'attendance_sheet_path' => $attendancePath,
            ]);
            $report = $existingReport;

            if ($previousPath && $previousPath !== $path && Storage::disk('private')->exists($previousPath)) {
                Storage::disk('private')->delete($previousPath);
            }
            if ($previousPath && $previousPath !== $path && Storage::disk('public')->exists($previousPath)) {
                Storage::disk('public')->delete($previousPath);
            }
        } else {
            $report = ActivityReport::create([
                'activity_request_id' => $activityRequest->id,
                'narrative_report'    => $path,
                'narrative_source' => $validated['narrative_source'],
                'narrative_content' => $narrativeContent,
                'submitted_at'        => now(),
                'description'         => $validated['description'] ?? null,
                'signed_by_secretary' => $validated['signed_by_secretary'],
                'signed_by_governor' => $validated['signed_by_governor'],
                'signed_by_advisor' => $validated['signed_by_advisor'],
                'signed_by_dean_president' => $validated['signed_by_dean_president'],
                'attendance_sheet_path' => $attendancePath,
            ]);
        }

        if ($request->hasFile('photos')) {
            $sortOrder = ($report->photos()->max('sort_order') ?? -1) + 1;
            foreach ($request->file('photos') as $photoFile) {
                $photoPath = $photoFile->store('activity-documents/activity-photos', 'private');
                $report->photos()->create([
                    'path' => $photoPath,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        if ($previousAttendancePath && $previousAttendancePath !== $attendancePath && Storage::disk('private')->exists($previousAttendancePath)) {
            Storage::disk('private')->delete($previousAttendancePath);
        }

        User::where('role', 'admin')->each(function (User $admin) use ($activityRequest) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => 'activity_report_submitted',
                'title' => 'New Activity Report',
                'message' => "A final report was submitted for {$activityRequest->title} by " . auth()->user()->org_name . '.',
            ]);
        });

        return redirect()->route('activity-requests.show', $activityRequest)
            ->with('success', 'Narrative report saved.');
    }

    private function ensureActivityHasPassed(ActivityRequest $activityRequest): void
    {
        $activityDate = $activityRequest->end_date
            ?? $activityRequest->gpoaActivity?->end_date
            ?? $activityRequest->date
            ?? $activityRequest->gpoaActivity?->date;

        if ($activityDate && $activityDate->gt(today())) {
            throw ValidationException::withMessages([
                'activity_date' => 'You can submit an activity report only after the activity end date has passed.',
            ]);
        }
    }

}
