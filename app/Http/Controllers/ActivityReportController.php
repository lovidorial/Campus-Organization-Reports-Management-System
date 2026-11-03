<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Rules\ValidateDocumentContent;
use App\Services\DocumentValidationService;
use App\Services\StoredFileMetadata;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivityReportController extends Controller
{
    public function create(ActivityRequest $activityRequest)
    {
        $this->authorize('update', $activityRequest);
        $existingReport = $activityRequest->report;
        $existingReport?->loadMissing('photos');

        return view('users.submit-report', compact('activityRequest', 'existingReport'));
    }

    public function store(Request $request, ActivityRequest $activityRequest, DocumentValidationService $documentValidation)
    {
        $this->authorize('update', $activityRequest);

        $existingReport = $activityRequest->report;

        $request->merge([
            'signed_by_organization_head' => $request->input('signed_by_organization_head', $request->input('signed_by_governor')),
            'signed_by_adviser' => $request->input('signed_by_adviser', $request->input('signed_by_advisor')),
        ]);

        $validated = Validator::make($request->all(), [
            'narrative_source' => 'required|in:uploaded,generated',
            'narrative_report' => ['nullable', 'file', 'mimes:pdf', 'max:20480', new ValidateDocumentContent(['pdf'])],
            'narrative_content' => 'nullable|string|max:30000',
            'description' => 'nullable|string|max:1000',
            'signed_by_secretary' => 'required|accepted',
            'signed_by_organization_head' => 'required|accepted',
            'signed_by_adviser' => 'required|accepted',
            'signed_by_dean_president' => 'required|accepted',
            'attendance_sheet' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,gif', 'max:10240', new ValidateDocumentContent(['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'])],
        ], [], [
            'signed_by_organization_head' => 'Organization Head signature confirmation',
            'signed_by_adviser' => 'Adviser signature confirmation',
            'signed_by_dean_president' => 'Dean/President signature confirmation',
        ])->validate();

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
            $narrativeUpload = $request->file('narrative_report');
            $narrativeValidation = $narrativeUpload
                ? $documentValidation->validateUpload($narrativeUpload, 'narrative_report', [
                    'organization_name' => $activityRequest->user?->organization?->name ?? $activityRequest->user?->org_name,
                    'activity_title' => $activityRequest->title,
                    'activity_date' => $activityRequest->date?->toDateString(),
                ])
                : [
                    'status' => $existingReport?->validation_status ?? 'needs_review',
                    'notes' => $existingReport?->validation_notes ? explode("\n", $existingReport->validation_notes) : ['Existing narrative report was not revalidated. Administrator review required.'],
                ];
            $path = $narrativeUpload
                ? $narrativeUpload->store('activity-documents/narrative-reports', 'private')
                : $existingReport->narrative_report;
            $narrativeMetadata = $narrativeUpload
                ? StoredFileMetadata::fromUpload($narrativeUpload)
                : [
                    'size' => $existingReport?->narrative_report_size,
                    'mime_type' => $existingReport?->narrative_report_mime_type,
                    'sha256' => $existingReport?->narrative_report_sha256,
                ];
            $narrativeContent = null;
        } else {
            $narrativeContent = ['body' => trim($validated['narrative_content'])];
            $path = 'activity-documents/narrative-reports/' . $activityRequest->id . '/' . Str::uuid() . '.pdf';
            $pdf = Pdf::loadView('reports.narrative-report', [
                'activityRequest' => $activityRequest,
                'body' => $narrativeContent['body'],
            ]);
            $pdfContents = $pdf->output();
            Storage::disk('private')->put($path, $pdfContents);
            $narrativeMetadata = StoredFileMetadata::fromContents($pdfContents, 'application/pdf');
            $narrativeValidation = $documentValidation->validateUpload($path, 'narrative_report', [
                'organization_name' => $activityRequest->user?->organization?->name ?? $activityRequest->user?->org_name,
                'activity_title' => $activityRequest->title,
                'activity_date' => $activityRequest->date?->toDateString(),
            ]);
        }

        $previousAttendancePath = $existingReport?->attendance_sheet_path;
        $attendanceUpload = $request->file('attendance_sheet');
        $attendancePath = $attendanceUpload
            ? $attendanceUpload->store('activity-documents/attendance-sheets', 'private')
            : $previousAttendancePath;
        $attendanceMetadata = $attendanceUpload
            ? StoredFileMetadata::fromUpload($attendanceUpload)
            : [
                'size' => $existingReport?->attendance_sheet_size,
                'mime_type' => $existingReport?->attendance_sheet_mime_type,
                'sha256' => $existingReport?->attendance_sheet_sha256,
            ];

        if ($existingReport) {
            $previousPath = $existingReport->narrative_report;
            $existingReport->update([
                'narrative_report' => $path,
                'narrative_report_size' => $narrativeMetadata['size'],
                'narrative_report_mime_type' => $narrativeMetadata['mime_type'],
                'narrative_report_sha256' => $narrativeMetadata['sha256'],
                'validation_status' => $narrativeValidation['status'],
                'validation_notes' => implode("\n", $narrativeValidation['notes']),
                'validated_at' => now(),
                'narrative_source' => $validated['narrative_source'],
                'narrative_content' => $narrativeContent,
                'submitted_at' => now(),
                'description' => $validated['description'] ?? null,
                'signed_by_secretary' => $validated['signed_by_secretary'],
                'signed_by_governor' => $validated['signed_by_organization_head'],
                'signed_by_organization_head' => $validated['signed_by_organization_head'],
                'signed_by_advisor' => $validated['signed_by_adviser'],
                'signed_by_dean_president' => $validated['signed_by_dean_president'],
                'status' => 'pending',
                'feedback' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'attendance_sheet_path' => $attendancePath,
                'attendance_sheet_size' => $attendanceMetadata['size'],
                'attendance_sheet_mime_type' => $attendanceMetadata['mime_type'],
                'attendance_sheet_sha256' => $attendanceMetadata['sha256'],
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
                'narrative_report_size' => $narrativeMetadata['size'],
                'narrative_report_mime_type' => $narrativeMetadata['mime_type'],
                'narrative_report_sha256' => $narrativeMetadata['sha256'],
                'validation_status' => $narrativeValidation['status'],
                'validation_notes' => implode("\n", $narrativeValidation['notes']),
                'validated_at' => now(),
                'narrative_source' => $validated['narrative_source'],
                'narrative_content' => $narrativeContent,
                'submitted_at'        => now(),
                'description'         => $validated['description'] ?? null,
                'signed_by_secretary' => $validated['signed_by_secretary'],
                'signed_by_governor' => $validated['signed_by_organization_head'],
                'signed_by_organization_head' => $validated['signed_by_organization_head'],
                'signed_by_advisor' => $validated['signed_by_adviser'],
                'signed_by_dean_president' => $validated['signed_by_dean_president'],
                'attendance_sheet_path' => $attendancePath,
                'attendance_sheet_size' => $attendanceMetadata['size'],
                'attendance_sheet_mime_type' => $attendanceMetadata['mime_type'],
                'attendance_sheet_sha256' => $attendanceMetadata['sha256'],
            ]);
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

}
