<x-app-layout>
<div class="mb-6">
    <a href="{{ route('activity-requests.show', $activityRequest) }}" class="text-sky-600 text-sm hover:underline">← Back to Activity</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">Narrative Report</h2>
    <p class="text-sm text-gray-500">{{ $activityRequest->title }} — {{ $activityRequest->date->format('M d, Y') }}</p>
    @error('activity_date')<p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
</div>

@php
    $selectedSource = old('narrative_source', $existingReport?->narrative_source ?? ($existingReport?->narrative_report ? 'uploaded' : 'generated'));
    $hasExistingUpload = $existingReport?->narrative_source === 'uploaded' && filled($existingReport?->narrative_report);
    $existingPhotoCount = $existingReport?->photos->count() ?? 0;
    $remainingPhotoCount = max(0, 10 - $existingPhotoCount);
@endphp

<div class="bg-white rounded-xl shadow-sm border p-4 sm:p-8 max-w-xl">
    <form action="{{ route('activity-reports.store', $activityRequest) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="bg-gray-50 rounded-lg p-4 text-sm">
            <p><strong>Activity:</strong> {{ $activityRequest->title }}</p>
            <p><strong>Date:</strong> {{ $activityRequest->date->format('M d, Y') }}</p>
            <p><strong>Venue:</strong> {{ $activityRequest->venue }} <x-venue-status-badge :venue="$activityRequest->venueRecord" /></p>
        </div>

        <fieldset>
            <legend class="mb-2 text-sm font-semibold text-gray-700">Narrative source</legend>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" name="narrative_source" value="uploaded" {{ $selectedSource === 'uploaded' ? 'checked' : '' }}>
                    Upload PDF
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" name="narrative_source" value="generated" {{ $selectedSource === 'generated' ? 'checked' : '' }}>
                    Create in System
                </label>
            </div>
            @error('narrative_source')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </fieldset>

        <div id="narrativeUploadSection">
            <label class="mb-2 block text-sm font-semibold text-gray-700">Narrative Report PDF {{ $hasExistingUpload ? '(optional replacement)' : '*' }}</label>
            @if($hasExistingUpload)
                <p class="mb-2 text-xs text-gray-500">Current file: <a class="text-sky-700 underline" data-file-viewer data-title="Narrative Report" href="{{ route('activity-requests.documents.show', [$activityRequest, 'narrative-report']) }}">View narrative report</a></p>
            @endif
            <input id="narrativeReportFile" type="file" name="narrative_report" accept=".pdf" {{ $hasExistingUpload ? '' : 'required' }} class="w-full rounded-lg border border-gray-300 px-3 py-2">
            <p class="mt-1 text-xs text-gray-500">PDF, up to 20 MB.</p>
            @error('narrative_report')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div id="narrativeEditorSection">
            <label for="narrative_content" class="mb-2 block text-sm font-semibold text-gray-700">Narrative text *</label>
            <textarea id="narrative_content" name="narrative_content" rows="12" maxlength="30000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Write the activity narrative">{{ old('narrative_content', data_get($existingReport?->narrative_content, 'body')) }}</textarea>
            @error('narrative_content')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Report Description</label>
            <textarea id="description" name="description" maxlength="1000" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                      placeholder="Briefly summarize what this report covers (optional)">{{ old('description') }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Optional metadata summary, up to 1000 characters.</p>
            @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="border border-gray-200 rounded-lg p-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">Signatory Confirmation</p>
            <p class="text-xs text-gray-500 mb-3">Confirm that the uploaded PDF has been physically signed by each signatory.</p>
            <div class="space-y-2 text-sm text-gray-700">
                @foreach([
                    'signed_by_secretary' => 'Secretary',
                    'signed_by_governor' => 'Governor',
                    'signed_by_advisor' => 'Advisor',
                    'signed_by_dean_president' => 'Dean/President',
                ] as $field => $label)
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="{{ $field }}" value="1" required {{ old($field) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                    <span>{{ $label }}</span>
                </label>
                @error($field)<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Photos of the finished activity {{ $existingPhotoCount > 0 ? '(optional, ' . $remainingPhotoCount . ' more allowed)' : '(at least 1 required, up to 10)' }}</label>
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Previously submitted photos are kept when you resubmit.</p>
            @error('photos')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            @error('photos.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            <div id="photoPreview" class="grid grid-cols-3 gap-3 mt-3"></div>
        </div>

        <div>
            <label for="attendance_sheet" class="mb-2 block text-sm font-semibold text-gray-700">Attendance sheet (optional)</label>
            @if(filled($existingReport?->attendance_sheet_path))
                <p class="mb-2 text-xs text-gray-500">An attendance sheet is already on file. Uploading another replaces it.</p>
            @endif
            <input id="attendance_sheet" type="file" name="attendance_sheet" accept=".pdf,image/jpeg,image/png,image/webp,image/gif" class="w-full rounded-lg border border-gray-300 px-3 py-2">
            <p class="mt-1 text-xs text-gray-500">PDF or image, up to 10 MB.</p>
            @error('attendance_sheet')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg">
            Save Narrative Report
        </button>
    </form>
</div>

<script>
    const narrativeSourceInputs = document.querySelectorAll('input[name="narrative_source"]');
    const narrativeUploadSection = document.getElementById('narrativeUploadSection');
    const narrativeEditorSection = document.getElementById('narrativeEditorSection');
    const narrativeReportFile = document.getElementById('narrativeReportFile');
    const narrativeContent = document.getElementById('narrative_content');
    const hasExistingUpload = @json($hasExistingUpload);

    function updateNarrativeSourceFields() {
        const source = document.querySelector('input[name="narrative_source"]:checked')?.value;
        narrativeUploadSection.hidden = source !== 'uploaded';
        narrativeEditorSection.hidden = source !== 'generated';
        narrativeReportFile.required = source === 'uploaded' && !hasExistingUpload;
        narrativeContent.required = source === 'generated';
    }

    narrativeSourceInputs.forEach(input => input.addEventListener('change', updateNarrativeSourceFields));
    updateNarrativeSourceFields();

    document.querySelector('input[name="photos[]"]').addEventListener('change', function(event) {
        const preview = document.getElementById('photoPreview');
        preview.innerHTML = '';

        Array.from(event.target.files).slice(0, 10).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full h-24 object-cover rounded-lg border border-gray-200';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
</script>
</x-app-layout>
