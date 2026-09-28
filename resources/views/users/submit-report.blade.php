<x-app-layout>
<div class="mb-6">
    <a href="{{ route('activity-requests.index') }}" class="text-sky-600 text-sm hover:underline">← Back to Activity Requests</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">Submit Final Report</h2>
    <p class="text-sm text-gray-500">{{ $activityRequest->title }} — {{ $activityRequest->date->format('M d, Y') }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-4 sm:p-8 max-w-xl">
    <form action="{{ route('activity-reports.store', $activityRequest) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="bg-gray-50 rounded-lg p-4 text-sm">
            <p><strong>Activity:</strong> {{ $activityRequest->title }}</p>
            <p><strong>Date:</strong> {{ $activityRequest->date->format('M d, Y') }}</p>
            <p><strong>Venue:</strong> {{ $activityRequest->venue }} <x-venue-status-badge :venue="$activityRequest->venueRecord" /></p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Narrative Report (PDF) *</label>
            <input type="file" name="narrative_report" accept=".pdf" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Upload your final narrative report after conducting the activity.</p>
            @error('narrative_report')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
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
            <label class="block text-sm font-semibold text-gray-700 mb-2">Upload photos of the finished activity (optional, up to 10)</label>
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Add supporting images for your final report.</p>
            @error('photos')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            @error('photos.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            <div id="photoPreview" class="grid grid-cols-3 gap-3 mt-3"></div>
        </div>

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg">
            Submit Final Report
        </button>
    </form>
</div>

<script>
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
