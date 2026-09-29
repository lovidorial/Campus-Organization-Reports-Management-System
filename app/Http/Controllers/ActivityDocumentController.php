<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivityDocumentController extends Controller
{
    public function storeCommunicationLetter(Request $request, ActivityRequest $activityRequest)
    {
        $this->authorize('update', $activityRequest);

        $validated = $request->validate([
            'communication_letter' => 'required|file|mimes:pdf|max:20480',
            'signed_confirmation' => 'required|accepted',
        ]);

        $path = $validated['communication_letter']->store('activity-documents/communication-letters', 'private');
        $previousPath = $activityRequest->communication_letter;

        $activityRequest->update([
            'communication_letter' => $path,
            'communication_letter_signed_at' => now(),
        ]);

        if ($previousPath && Storage::disk('private')->exists($previousPath)) {
            Storage::disk('private')->delete($previousPath);
        }
        if ($previousPath && Storage::disk('public')->exists($previousPath)) {
            Storage::disk('public')->delete($previousPath);
        }

        return back()->with('success', 'Communication letter uploaded.');
    }

    public function show(ActivityRequest $activityRequest, string $documentType)
    {
        $this->authorize('view', $activityRequest);

        $path = match ($documentType) {
            'communication-letter' => $activityRequest->communication_letter,
            'narrative-report' => $activityRequest->report?->narrative_report,
            default => null,
        };

        abort_unless($path, 404, 'Document not found.');

        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($path)) {
                $fileName = $documentType === 'communication-letter'
                    ? 'communication-letter-' . $activityRequest->id . '.pdf'
                    : 'narrative-report-' . $activityRequest->id . '.pdf';

                return $disk->response($path, $fileName, ['Content-Type' => 'application/pdf']);
            }
        }

        abort(404, 'Document not found.');
    }
}