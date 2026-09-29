<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Activity Monitor</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $term }} / SY {{ $schoolYear }} progress for your organization.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('gpoa.create') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add GPOA</a>
                <a href="{{ route('activity-requests.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Request Activity</a>
            </div>
        </div>

        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                <span>Overall GPOA progress</span>
                <span class="font-semibold text-slate-800">{{ $progressPercent }}%</span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $progressPercent }}%"></div>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $completedCount }} Completed · {{ $ongoingCount }} Ongoing · {{ $pendingCount }} Pending</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Activity</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Venue</th>
                            <th class="px-4 py-3">Communication Letter</th>
                            <th class="px-4 py-3">Narrative Report</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($activities as $activity)
                            @php($monitoring = $activity->monitoringStatus())
                            @php($statusClass = match ($monitoring['status']) {
                                'Completed' => 'bg-emerald-100 text-emerald-700',
                                'Ongoing' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-700',
                            })
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">Activity #{{ $activity->activity_number }}: {{ $activity->title }}</div>
                                    @if($activity->monitor_late)
                                        <div class="mt-1 text-[11px] font-medium text-rose-600">Late</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->venue ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->letterStatusLabel() }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->narrativeStatusLabel() }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ $monitoring['status'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($activity->activityRequest)
                                        <a href="{{ route('activity-requests.show', $activity->activityRequest) }}" class="rounded bg-sky-100 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-200">Open</a>
                                    @else
                                        <a href="{{ route('activity-requests.create', ['gpoa' => $activity->gpoa_id]) }}" class="rounded bg-sky-100 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-200">Add</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                    No planned activities found for your current GPOA.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
