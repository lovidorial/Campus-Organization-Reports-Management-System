<x-app-layout>
<div class="mb-6">
    <a href="{{ route('admin.gpoa.index') }}" class="text-sky-600 text-sm hover:underline">← Back to GPOA Review</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">Review GPOA</h2>
    <p class="text-sm text-gray-500">{{ $gpoa->user->org_name ?? $gpoa->user->name }} — {{ $gpoa->term }} / SY {{ $gpoa->school_year }}</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">Status</p>
        <p class="text-lg font-bold">{{ ucfirst($gpoa->status) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">College</p>
        <p class="text-lg font-bold">{{ $gpoa->college ?? '—' }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">Submitted</p>
        <p class="text-lg font-bold">{{ $gpoa->created_at->format('M d, Y') }}</p>
    </div>
</div>

@if($gpoa->document_path)
<div class="mb-6">
    <a href="{{ route('admin.gpoa.document', $gpoa) }}" target="_blank"
       class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold">View GPOA Document</a>
</div>
@endif

@if($gpoa->status === 'pending')
<div class="mb-6 flex gap-3">
    <form action="{{ route('admin.gpoa.approve', $gpoa) }}" method="POST" onsubmit="return confirm('Verify, approve, and store this GPOA? The organization can then submit activity requests.');">
        @csrf
        <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">Approve & Store GPOA</button>
    </form>
    <button onclick="document.getElementById('rejectModal').classList.remove('hidden')"
            class="px-5 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700">Reject</button>
</div>
@endif

@if($gpoa->approved_at)
<div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
    Approved by {{ $gpoa->approver->name ?? 'Admin' }} on {{ $gpoa->approved_at->format('M d, Y g:i A') }}
    @if($gpoa->stored_at) — Stored {{ $gpoa->stored_at->format('M d, Y') }}@endif
</div>
@endif

@if($gpoa->reject_reason)
<div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
    <strong>Rejection reason:</strong> {{ $gpoa->reject_reason }}
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border overflow-x-auto mb-6">
    <div class="p-4 border-b font-semibold">Planned Activities ({{ $gpoa->activities->count() }})</div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left">Title</th>
                <th class="p-3 text-left">Date</th>
                <th class="p-3 text-left">Venue</th>
                <th class="p-3 text-left">Category</th>
                <th class="p-3 text-left">Participants</th>
                <th class="p-3 text-left">Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gpoa->activities as $activity)
            <tr class="border-b">
                <td class="p-3 font-medium">{{ $activity->title }}</td>
                <td class="p-3">{{ $activity->date->format('M d, Y') }}</td>
                <td class="p-3">{{ $activity->venue }}</td>
                <td class="p-3">{{ $activity->category }}</td>
                <td class="p-3">{{ $activity->participants_count }}</td>
                <td class="p-3 text-xs max-w-[200px]">{{ Str::limit($activity->description, 80) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-3">Reject GPOA</h3>
        <form action="{{ route('admin.gpoa.reject', $gpoa) }}" method="POST">
            @csrf
            <textarea name="reject_reason" rows="4" placeholder="Reason for rejection..."
                      class="w-full border rounded-lg px-3 py-2 text-sm mb-4"></textarea>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
