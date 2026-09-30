<x-app-layout>
<div class="mb-6">
    <a href="{{ route('gpoa.index') }}" class="text-sky-600 text-sm hover:underline">← Back to My GPOA</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">GPOA Details</h2>
    <p class="text-sm text-gray-500">{{ $gpoa->term }} / SY {{ $gpoa->school_year }}</p>
    <a href="{{ route('gpoa.edit', $gpoa) }}" class="mt-3 inline-flex rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Edit GPOA</a>
</div>

<!-- GPOA Status and Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">Status</p>
        <span class="mt-1 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">Approved</span>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">College</p>
        <p class="text-lg font-bold mt-1">{{ $gpoa->college ?? '—' }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <p class="text-xs text-gray-500 uppercase">Activities</p>
        <p class="text-lg font-bold mt-1">{{ $gpoa->activities->count() }}</p>
    </div>
</div>

@if($gpoa->document_path)
<div class="mb-6">
    <a href="{{ asset('storage/'.$gpoa->document_path) }}" target="_blank"
    class="px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg text-sm font-semibold hover:bg-emerald-200">View Approved GPOA Document</a>
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
    <table class="w-full text-sm min-w-[900px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3">PROGRAM/ACTIVITIES/PROJECT</th>
                <th class="text-left px-4 py-3">SDGs ADDRESSED</th>
                <th class="text-left px-4 py-3">TIME FRAME</th>
                <th class="text-left px-4 py-3">STATUS</th>
                <th class="text-left px-4 py-3">VIEW MORE</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($gpoa->activities as $activity)
            <tr class="hover:bg-gray-50" x-data="{ expanded: false }">
                <td class="px-4 py-3 font-medium">{{ $activity->title ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if($activity->sdgs)
                        <div class="flex flex-wrap gap-1">
                            @foreach($activity->sdgs as $sdg)
                                <x-sdg-badge :number="$sdg" :show-label="false" />
                            @endforeach
                        </div>
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3">{{ $activity->date ? $activity->date->format('M d, Y') : '—' }}</td>
                <td class="px-4 py-3">
                    @php
                        $monitoring = $activity->monitoringStatus();
                        $statusClass = match ($monitoring['status']) {
                            'Completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'Ongoing' => 'bg-amber-100 text-amber-700 border-amber-200',
                            default => 'bg-gray-100 text-gray-600 border-gray-200',
                        };
                    @endphp

                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                        {{ $monitoring['status'] }}
                    </span>
                    @if($monitoring['late'])
                        <span class="ml-1 inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Late</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <button type="button" @click="expanded = !expanded" class="inline-flex items-center justify-center rounded-lg border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-100 transition">
                        <span x-text="expanded ? 'Hide Details' : 'View More'"></span>
                    </button>

                    <div x-show="expanded" x-transition class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3" style="display: none;">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">Objectives</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->objectives ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">Expected Outcome</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->expected_outcome ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">Target Participants</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->target_participants ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">Delivery Strategy</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->plan_key_strategy ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">Persons Involved</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->person_in_charge ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">Facilities / Materials</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->facilities_materials ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-3 md:col-span-2">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">Budget Allocation</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $activity->estimated_budget ? '₱' . number_format($activity->estimated_budget, 2) : '—' }}</p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No activities are recorded in this GPOA yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
</x-app-layout>
