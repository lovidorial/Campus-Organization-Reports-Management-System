<x-app-layout>
<div class="mb-6">
    <a href="{{ route('activity-requests.index') }}" class="text-sky-600 text-sm hover:underline">← Back to Activity Requests</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">Submit Final Report</h2>
    <p class="text-sm text-gray-500">{{ $activityRequest->title }} — {{ $activityRequest->date->format('M d, Y') }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-8 max-w-xl">
    <form action="{{ route('activity-reports.store', $activityRequest) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="bg-gray-50 rounded-lg p-4 text-sm">
            <p><strong>Activity:</strong> {{ $activityRequest->title }}</p>
            <p><strong>Date:</strong> {{ $activityRequest->date->format('M d, Y') }}</p>
            <p><strong>Venue:</strong> {{ $activityRequest->venue }}</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Narrative Report (PDF) *</label>
            <input type="file" name="narrative_report" accept=".pdf" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Upload your final narrative report after conducting the activity.</p>
            @error('narrative_report')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg">
            Submit Final Report
        </button>
    </form>
</div>
</x-app-layout>
