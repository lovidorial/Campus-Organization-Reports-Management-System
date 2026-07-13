<x-app-layout>
<div class="mb-6">
    <a href="{{ route('gpoa.index') }}" class="text-sky-600 text-sm hover:underline">← Back to My GPOA</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">GPOA Details</h2>
    <p class="text-sm text-gray-500">{{ $gpoa->term }} / SY {{ $gpoa->school_year }}</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">Status</p>
        <p class="text-lg font-bold mt-1">{{ ucfirst($gpoa->status) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">College</p>
        <p class="text-lg font-bold mt-1">{{ $gpoa->college ?? '—' }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">Planned Activities</p>
        <p class="text-lg font-bold mt-1">{{ $gpoa->activities->count() }}</p>
    </div>
</div>

@if($gpoa->document_path)
<div class="mb-6">
    <a href="{{ asset('storage/'.$gpoa->document_path) }}" target="_blank"
       class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold hover:bg-blue-200">View GPOA Document</a>
</div>
@endif

@if($gpoa->reject_reason)
<div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
    <strong>Rejection reason:</strong> {{ $gpoa->reject_reason }}
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3">Title</th>
                <th class="text-left px-4 py-3">Date</th>
                <th class="text-left px-4 py-3">Venue</th>
                <th class="text-left px-4 py-3">Category</th>
                <th class="text-left px-4 py-3">Participants</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($gpoa->activities as $activity)
            <tr>
                <td class="px-4 py-3 font-medium">{{ $activity->title }}</td>
                <td class="px-4 py-3">{{ $activity->date->format('M d, Y') }}</td>
                <td class="px-4 py-3">{{ $activity->venue }}</td>
                <td class="px-4 py-3">{{ $activity->category }}</td>
                <td class="px-4 py-3">{{ $activity->participants_count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
</x-app-layout>
