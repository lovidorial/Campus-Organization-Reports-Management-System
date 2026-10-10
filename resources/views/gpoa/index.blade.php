<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">My GPOA</h2>
        <p class="text-sm text-gray-500">General Plan of Action</p>
    </div>
    @if($canSubmitGpoa)
        <a href="{{ route('gpoa.create') }}"
           class="px-4 py-2 text-white rounded-lg text-sm font-semibold bg-amber-500 transition hover:bg-amber-600">+ Submit GPOA</a>
    @endif
</div>

@if(!$canSubmitGpoa && $submissionBlockMessage)
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">{{ $submissionBlockMessage }}</div>
@endif

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-center justify-between gap-4">
        <div>
            <h3 class="font-bold text-gray-800">Activity Monitoring</h3>
            <p class="text-sm text-gray-500">Progress across your planned activities</p>
        </div>
        <span class="text-sm font-semibold text-gray-700">{{ $completionPercent }}% complete</span>
    </div>
    <div class="mb-4 h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
        <div
            class="h-full rounded-full transition-all duration-500 {{ $completionPercent == 0 ? 'bg-gray-300' : ($completionPercent >= 1 && $completionPercent < 100 ? 'bg-amber-500' : 'bg-emerald-500') }}"
            style="width: {{ $completionPercent }}%"
        ></div>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach($monitoringCounts as $status => $count)
            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                <span class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <span class="h-2.5 w-2.5 rounded-full {{ match($status) { 'pending' => 'bg-amber-400', 'ongoing' => 'bg-blue-500', 'completed' => 'bg-emerald-500', 'archived' => 'bg-gray-400', default => 'bg-gray-400' } }}"></span>
                    {{ $status }}
                </span>
                <span class="text-base font-bold text-gray-900">{{ $count }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Term / SY</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">College</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Activities</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Submitted</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($gpoas as $gpoa)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4">{{ $gpoa->term }}<br><span class="text-xs text-gray-500">{{ $gpoa->school_year }}</span></td>
                <td class="px-4 py-4">{{ $gpoa->college ?? '—' }}</td>
                <td class="px-4 py-4">{{ $gpoa->activities_count }} activities</td>
                <td class="px-4 py-4">
                    <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">Approved</span>
                    @if($gpoa->document_path)
                        <a href="{{ route('gpoa.document', $gpoa) }}" data-file-viewer data-title="Approved GPOA Document" class="ml-2 text-xs font-semibold text-emerald-700 hover:underline">View approved document</a>
                    @endif
                </td>
                <td class="px-4 py-4 text-xs text-gray-500">{{ $gpoa->created_at->format('M d, Y') }}</td>
                <td class="px-4 py-4 text-center">
                    <div class="flex justify-center gap-3">
                        <a href="{{ route('gpoa.show', $gpoa) }}" class="text-sky-600 hover:underline text-xs font-semibold">View Details</a>
                        <span class="text-xs text-slate-500">Locked after submission</span>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-10">
                    <div class="flex flex-col items-center justify-center text-center text-gray-400">
                        <svg class="mb-3 h-12 w-12 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M7 3.75A2.75 2.75 0 0 0 4.25 6.5v11A2.75 2.75 0 0 0 7 20.25h10A2.75 2.75 0 0 0 19.75 17.5V8.25L15 3.75H7Z" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14.5 3.75V8.5H19.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8.5 12.5h7M8.5 15.5h7" stroke-linecap="round"/>
                        </svg>
                        <p class="text-lg font-medium text-gray-500">No GPOA submitted yet.</p>
                        <p class="mt-1 text-sm text-gray-400">Your planned activities will appear here once you submit a GPOA.</p>
                        @if($canSubmitGpoa)
                            <a href="{{ route('gpoa.create') }}" class="mt-4 inline-flex items-center justify-center rounded-md bg-amber-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">Submit your GPOA →</a>
                        @endif
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $gpoas->links() }}</div>
</x-app-layout>
