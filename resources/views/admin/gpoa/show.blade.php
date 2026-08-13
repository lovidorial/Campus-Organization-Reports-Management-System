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
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
    Legacy attachment stored for this GPOA record. Current submissions are data-first and may not include a document file.
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
    <div class="p-4 border-b font-semibold">Legacy Planned Activities ({{ $gpoa->activities->count() }})</div>
    <table class="w-full text-sm min-w-[1100px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left">Title</th>
                <th class="p-3 text-left">Category</th>
                <th class="p-3 text-left">Activity Level</th>
                <th class="p-3 text-left">Date</th>
                <th class="p-3 text-left">Budget</th>
                <th class="p-3 text-center">Details</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sdgLabels = [
                    1 => 'No Poverty',
                    2 => 'Zero Hunger',
                    3 => 'Good Health and Well-being',
                    4 => 'Quality Education',
                    5 => 'Gender Equality',
                    6 => 'Clean Water and Sanitation',
                    7 => 'Affordable and Clean Energy',
                    8 => 'Decent Work and Economic Growth',
                    9 => 'Industry, Innovation and Infrastructure',
                    10 => 'Reduced Inequality',
                    11 => 'Sustainable Cities and Communities',
                    12 => 'Responsible Consumption and Production',
                    13 => 'Climate Action',
                    14 => 'Life Below Water',
                    15 => 'Life on Land',
                    16 => 'Peace, Justice and Strong Institutions',
                    17 => 'Partnerships for the Goals',
                ];
            @endphp
            @foreach($gpoa->activities as $activity)
            <tr class="border-b align-top">
                <td class="p-3 font-medium">{{ $activity->title }}</td>
                <td class="p-3">{{ $activity->category ?? '—' }}</td>
                <td class="p-3">{{ $activity->activity_level ?? '—' }}</td>
                <td class="p-3">{{ $activity->date ? $activity->date->format('M d, Y') : '—' }}</td>
                <td class="p-3">₱ {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</td>
                <td class="p-3 text-center">
                    <button type="button" onclick="this.closest('tr').nextElementSibling.classList.toggle('hidden'); this.textContent = this.textContent === 'Show Details' ? 'Hide Details' : 'Show Details';" class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200">
                        Show Details
                    </button>
                </td>
            </tr>
            <tr class="hidden border-b bg-gray-50">
                <td colspan="6" class="p-6">
                    {{-- CLASSIFICATION SECTION --}}
                    <div class="mb-6">
                        <h4 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-3">Classification</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">SDGs Addressed</p>
                                <p class="text-sm text-gray-700">
                                    @php
                                        $sdgs = $activity->sdgs ?? [];
                                        if (!is_array($sdgs)) {
                                            $sdgs = json_decode($sdgs, true) ?? [];
                                        }
                                        $sdgText = collect($sdgs)
                                            ->map(fn($id) => 'SDG ' . $id . ': ' . ($sdgLabels[$id] ?? 'Unknown'))
                                            ->join(', ');
                                    @endphp
                                    {{ $sdgText ?: '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Venue</p>
                                <p class="text-sm text-gray-700">{{ $activity->venue ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- PLANNING SECTION --}}
                    <div class="mb-6">
                        <h4 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-3">Planning</h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Objectives</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->objectives ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Expected Outcome</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->expected_outcome ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Plan / Key Strategy</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->plan_key_strategy ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- LOGISTICS SECTION --}}
                    <div class="mb-6">
                        <h4 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-3">Logistics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Target Participants</p>
                                <p class="text-sm text-gray-700">{{ $activity->target_participants ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Persons Involved</p>
                                <p class="text-sm text-gray-700">{{ $activity->person_in_charge ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Preceding Activity</p>
                                <p class="text-sm text-gray-700">{{ $activity->preceding_activity ?? 'None — first activity' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- RESOURCES SECTION --}}
                    <div>
                        <h4 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-3">Resources</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Facilities / Materials</p>
                                <p class="text-sm text-gray-700">{{ $activity->facilities_materials ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Source of Funds</p>
                                <p class="text-sm text-gray-700">{{ $activity->source_of_funds ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Remarks</p>
                                <p class="text-sm text-gray-700">{{ $activity->remarks ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </td>
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
