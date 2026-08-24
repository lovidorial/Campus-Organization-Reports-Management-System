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

<div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
    <div class="p-4 border-b">
        <div class="flex items-center gap-2">
            <h3 class="font-semibold">Legacy Planned Activities</h3>
            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                {{ $gpoa->activities->count() }}
            </span>
        </div>
        <p class="text-sm text-gray-500 mt-1">List of legacy planned activities from previous years.</p>
    </div>

    <div class="p-4">
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
            <div data-legacy-activity class="bg-white rounded-xl border shadow-sm mb-4 last:mb-0">
                <div class="p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold tracking-wide">
                                LEGACY
                            </span>
                            <h4 class="mt-3 text-xl font-bold text-gray-900">{{ $activity->title }}</h4>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ $activity->category ?? '—' }}
                                <span class="mx-1">•</span>
                                {{ $activity->date ? $activity->date->format('M d, Y') : '—' }}
                                <span class="mx-1">•</span>
                                {{ $activity->venue ?? '—' }}
                            </p>
                            <p class="mt-2 text-sm text-gray-700">{{ $activity->target_participants ?? '—' }}</p>
                        </div>

                        <div class="flex items-start justify-between gap-5 lg:justify-end">
                            <div class="text-left lg:text-right">
                                <p class="text-2xl font-bold text-gray-900">₱ {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</p>
                                <p class="text-xs text-gray-400">{{ $activity->source_of_funds ?? 'Organization Funds' }}</p>
                            </div>
                            <button type="button" onclick="const details = this.closest('[data-legacy-activity]').querySelector('.legacy-activity-details'); details.classList.toggle('hidden'); this.querySelector('.toggle-label').textContent = details.classList.contains('hidden') ? 'Show Details' : 'Hide Details'; this.querySelector('svg').classList.toggle('rotate-180');" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200">
                                <span class="toggle-label">Show Details</span>
                                <svg class="w-4 h-4 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="legacy-activity-details hidden border-t bg-gray-50 p-5">
                    @php
                        $sdgs = $activity->sdgs ?? [];
                        if (!is_array($sdgs)) {
                            $sdgs = json_decode($sdgs, true) ?? [];
                        }
                        $sdgText = collect($sdgs)
                            ->map(fn($id) => 'SDG ' . $id . ': ' . ($sdgLabels[$id] ?? 'Unknown'))
                            ->join(', ');
                    @endphp

                    <div class="mb-6">
                        <h4 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-3">Activity Overview</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Classification</p><p class="text-sm font-bold text-gray-800">{{ $activity->category ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Activity Level</p><p class="text-sm font-bold text-gray-800">{{ $activity->activity_level ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Date</p><p class="text-sm font-bold text-gray-800">{{ $activity->date ? $activity->date->format('M d, Y') : '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Venue</p><p class="text-sm font-bold text-gray-800">{{ $activity->venue ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Budget</p><p class="text-sm font-bold text-gray-800">₱ {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-2">SDGs Addressed</p>
                                @if($sdgs)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($sdgs as $id)
                                            <span title="{{ $sdgLabels[$id] ?? 'Unknown' }}" class="inline-flex items-center justify-center min-w-[2rem] px-2 py-1 rounded-md bg-blue-100 text-blue-700 text-sm font-semibold">
                                                {{ $id }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm font-bold text-gray-800">—</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-3">Planning</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Objectives</p><p class="text-sm font-bold text-gray-800 whitespace-pre-wrap">{{ $activity->objectives ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Expected Outcome</p><p class="text-sm font-bold text-gray-800 whitespace-pre-wrap">{{ $activity->expected_outcome ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Plan / Key Strategy</p><p class="text-sm font-bold text-gray-800 whitespace-pre-wrap">{{ $activity->plan_key_strategy ?? '—' }}</p></div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-3">Logistics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Target Participants</p><p class="text-sm font-bold text-gray-800">{{ $activity->target_participants ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Persons Involved</p><p class="text-sm font-bold text-gray-800">{{ $activity->person_in_charge ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Preceding Activity</p><p class="text-sm font-bold text-gray-800">{{ $activity->preceding_activity ?? 'None — first activity' }}</p></div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-3">Resources</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Facilities / Materials</p><p class="text-sm font-bold text-gray-800">{{ $activity->facilities_materials ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Source of Funds</p><p class="text-sm font-bold text-gray-800">{{ $activity->source_of_funds ?? '—' }}</p></div>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white"><p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Remarks</p><p class="text-sm font-bold text-gray-800">{{ $activity->remarks ?? '—' }}</p></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
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
