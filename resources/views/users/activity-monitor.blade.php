<x-app-layout>
    <div class="max-w-6xl mx-auto px-0 py-6 sm:px-4 sm:py-8">
        <div class="flex flex-col items-start justify-between gap-3 mb-6 sm:flex-row sm:items-center sm:gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Activity Monitor</h1>
                <p class="text-sm text-slate-500 mt-1">Approved planned activities and their current request status.</p>
            </div>
            <a href="{{ route('activity-requests.create') }}" class="px-4 py-2 rounded-lg bg-sky-600 text-white text-sm font-semibold">Request Activity</a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Planned activity</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Venue</th>
                        <th class="px-4 py-3">GPOA</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activities as $activity)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $activity->title }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $activity->venue ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $activity->monitor_gpoa->term }} / {{ $activity->monitor_gpoa->school_year }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{{ $activity->monitor_status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No approved planned activities found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
