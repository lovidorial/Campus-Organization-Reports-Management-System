<x-app-layout>
<div class="mb-6">
    <a href="{{ route('gpoa.index') }}" class="text-sky-600 text-sm hover:underline">← Back to My GPOA</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">GPOA Details</h2>
    <p class="text-sm text-gray-500">{{ $gpoa->term }} / SY {{ $gpoa->school_year }}</p>
</div>

<!-- GPOA Status and Summary -->
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
        <p class="text-xs text-gray-500 uppercase">Activities</p>
        <p class="text-lg font-bold mt-1">{{ $gpoa->activities()->count() }}</p>
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

<!-- GPOA Header Information Table -->
<div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-8">
    <div class="px-6 py-4 bg-gray-50 border-b">
        <h3 class="font-bold text-gray-800">GPOA Information</h3>
    </div>
    <table class="w-full text-sm">
        <tbody class="divide-y">
            <tr>
                <td class="px-6 py-3 font-semibold text-gray-700 bg-gray-50 w-1/3">Organization</td>
                <td class="px-6 py-3">{{ auth()->user()->org_name ?? auth()->user()->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="px-6 py-3 font-semibold text-gray-700 bg-gray-50 w-1/3">College</td>
                <td class="px-6 py-3">{{ $gpoa->college ?? '—' }}</td>
            </tr>
            <tr>
                <td class="px-6 py-3 font-semibold text-gray-700 bg-gray-50 w-1/3">Term</td>
                <td class="px-6 py-3">{{ $gpoa->term ?? '—' }}</td>
            </tr>
            <tr>
                <td class="px-6 py-3 font-semibold text-gray-700 bg-gray-50 w-1/3">School Year</td>
                <td class="px-6 py-3">{{ $gpoa->school_year ?? '—' }}</td>
            </tr>
            <tr>
                <td class="px-6 py-3 font-semibold text-gray-700 bg-gray-50 w-1/3">Prepared By</td>
                <td class="px-6 py-3">{{ $gpoa->prepared_by ?? '—' }}</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- GPOA Activities Table -->
<div class="bg-white rounded-xl shadow-sm border overflow-x-auto mb-8">
    <div class="px-6 py-4 bg-gray-50 border-b">
        <h3 class="font-bold text-gray-800">Activities</h3>
    </div>
    <table class="w-full text-sm min-w-[1400px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3">PROGRAM/ACTIVITIES/PROJECT</th>
                <th class="text-left px-4 py-3">SDGs ADDRESSED</th>
                <th class="text-left px-4 py-3">OBJECTIVES</th>
                <th class="text-left px-4 py-3">EXPECTED OUTCOME</th>
                <th class="text-left px-4 py-3">TARGET PARTICIPANTS</th>
                <th class="text-left px-4 py-3">TIME FRAME</th>
                <th class="text-left px-4 py-3">DELIVERY STRATEGY</th>
                <th class="text-left px-4 py-3">PERSONS INVOLVED</th>
                <th class="text-left px-4 py-3">FACILITIES/MATERIALS</th>
                <th class="text-left px-4 py-3">BUDGET ALLOCATION</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($gpoa->activities as $activity)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $activity->title ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if($activity->sdgs)
                        <div class="flex flex-wrap gap-1">
                            @foreach($activity->sdgs as $sdg)
                                <span class="inline-block bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">{{ $sdg }}</span>
                            @endforeach
                        </div>
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3 text-sm">{{ Str::limit($activity->objectives ?? '—', 50) }}</td>
                <td class="px-4 py-3 text-sm">{{ Str::limit($activity->expected_outcome ?? '—', 50) }}</td>
                <td class="px-4 py-3">{{ $activity->target_participants ?? '—' }}</td>
                <td class="px-4 py-3">{{ $activity->date ? $activity->date->format('M d, Y') : '—' }}</td>
                <td class="px-4 py-3 text-sm">{{ Str::limit($activity->plan_key_strategy ?? '—', 50) }}</td>
                <td class="px-4 py-3">{{ $activity->person_in_charge ?? '—' }}</td>
                <td class="px-4 py-3 text-sm">{{ Str::limit($activity->facilities_materials ?? '—', 50) }}</td>
                <td class="px-4 py-3">{{ $activity->estimated_budget ? '₱' . number_format($activity->estimated_budget, 2) : '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-6 text-center text-gray-500">No activities yet. Activities will appear here as they are approved.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
</x-app-layout>
