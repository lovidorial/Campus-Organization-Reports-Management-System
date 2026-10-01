<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @if($errors->has('activity_date'))
            <div role="alert" class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first('activity_date') }}</div>
        @endif
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">My Activities</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $term }} / SY {{ $schoolYear }} progress for your organization.</p>
            </div>
            <form method="GET" action="{{ route('activity-monitor.index') }}" class="flex items-center gap-2">
                <label for="monitorStatus" class="sr-only">Filter activities by status</label>
                <select id="monitorStatus" name="status" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
                    <option value="" @selected($statusFilter === '')>Active statuses</option>
                    @foreach(['Pending', 'Ongoing', 'Completed', 'Archived'] as $status)
                        <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </form>
            <div class="flex items-center gap-3">
                <a href="{{ route('gpoa.create') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add GPOA</a>
                <a href="{{ route('activity-requests.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Request Activity</a>
            </div>
        </div>

        @php
            $summaryCards = [
                ['Total', $activities->count() + ($statusFilter === 'Archived' ? 0 : $archivedCount), 'text-slate-700'],
                ['Completed', $completedCount, 'text-emerald-700'],
                ['Ongoing', $ongoingCount, 'text-sky-700'],
                ['Pending', $pendingCount, 'text-amber-700'],
                ['Archived', $archivedCount, 'text-slate-600'],
            ];
            $statusChart = ['labels' => ['Pending', 'Ongoing', 'Completed', 'Archived'], 'values' => [$pendingCount, $ongoingCount, $completedCount, $archivedCount]];
        @endphp

        <section class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Activity summary">
            @foreach($summaryCards as [$label, $count, $color])
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $color }}">{{ $count }}</p>
                </article>
            @endforeach
        </section>

        <div class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.8fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                    <span>Overall GPOA progress</span>
                    <span class="font-semibold text-slate-800">{{ $progressPercent }}%</span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $progressPercent }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ $completedCount }} Completed · {{ $ongoingCount }} Ongoing · {{ $pendingCount }} Pending</p>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Activity status chart">
                <div class="relative h-56">
                    <canvas data-chart-type="doughnut" data-center-text="{{ $progressPercent }}%" data-chart-data='@json($statusChart)' role="img" aria-label="Activity status doughnut chart"></canvas>
                </div>
            </section>
        </div>

        @if($activities->isEmpty())
            <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l4 4v14H4V3h4Zm0 0v5h8V3M8 13h8m-8 4h5" /></svg>
                </span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">No activities to monitor yet</h2>
                <p class="mt-1 text-sm text-slate-500">Add your approved plan’s activities to start tracking progress.</p>
                <a href="{{ route('gpoa.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Add GPOA</a>
            </section>
        @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[760px] w-full text-sm">
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
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">Activity #{{ $activity->activity_number }}: {{ $activity->title }}</div>
                                    @if($activity->monitor_late)
                                        <div class="mt-1"><x-status-pill status="Late" /></div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->venue ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->letterStatusLabel() }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->narrativeStatusLabel() }}</td>
                                <td class="px-4 py-3">
                                    <x-status-pill :status="$monitoring['status']" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($activity->archived_at)
                                        <form method="POST" action="{{ route('activities.restore', $activity) }}">
                                            @csrf
                                            <button type="submit" class="rounded bg-sky-100 px-3 py-1.5 text-xs font-semibold text-sky-800 hover:bg-sky-200">Restore</button>
                                        </form>
                                    @elseif($activity->monitor_status === 'Completed')
                                        <form method="POST" action="{{ route('activities.archive', $activity) }}">
                                            @csrf
                                            <button type="submit" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Archive</button>
                                        </form>
                                    @elseif($activity->activityRequest)
                                        @php($reportDate = $activity->end_date ?? $activity->date)
                                        @php($reportAllowed = $reportDate && $reportDate->lte(today()))
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('activity-requests.show', $activity->activityRequest) }}" class="rounded bg-sky-100 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-200">Open</a>
                                            @if($reportAllowed)
                                                <a href="{{ route('activity-reports.create', $activity->activityRequest) }}" class="rounded bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-200">{{ $activity->activityRequest->report ? 'Update report' : 'Submit report' }}</a>
                                            @else
                                                <button type="button" disabled title="Available after the activity end date" class="cursor-not-allowed rounded bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-400">Report unavailable</button>
                                            @endif
                                        </div>
                                    @else
                                        <a href="{{ route('activity-requests.create', ['gpoa' => $activity->gpoa_id, 'activity' => $activity->id]) }}" class="rounded bg-sky-100 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-200">Add</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
