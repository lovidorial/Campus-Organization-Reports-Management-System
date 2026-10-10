<x-app-layout>
    <div class="space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Monitoring Dashboard</h1>
                <p class="mt-1 text-sm text-slate-600">Current progress across planned GPOA activities.</p>
            </div>
            <a href="{{ route('admin.activities') }}" class="inline-flex items-center rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Open Activity Monitoring</a>
        </header>

        @if($backupWarning)
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-4 text-sm text-amber-950" role="alert">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-base font-bold">Your backup is due.</p>
                        @if($nextDueAt)
                            <p class="mt-1 text-xs">Next backup due: <strong>{{ $nextDueAt->timezone(config('app.timezone'))->format('F j, Y g:i A') }}</strong></p>
                        @endif
                    </div>
                    <form action="{{ route('admin.backups.store') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">Back up now</button>
                    </form>
                </div>
            </div>
        @endif

        @php
            $statusChart = ['labels' => ['Pending', 'Ongoing', 'Completed', 'Archived'], 'values' => [$stats['Pending'], $stats['Ongoing'], $stats['Completed'], $stats['Archived']]];
            $statusTotal = array_sum($statusChart['values']);
            $statusColorClasses = ['Pending' => 'bg-amber-500', 'Ongoing' => 'bg-sky-500', 'Completed' => 'bg-emerald-500', 'Archived' => 'bg-slate-300'];
        @endphp

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7" aria-label="Activity monitoring totals">
            @foreach([
                ['Total', $stats['Total'], 'text-slate-900', route('admin.activities'), null],
                ['Pending', $stats['Pending'], 'text-slate-700', route('admin.activities', ['tab' => 'todo', 'status' => 'Pending']), 'Not yet submitted'],
                ['Ongoing', $stats['Ongoing'], 'text-sky-700', route('admin.activities', ['tab' => 'recent', 'status' => 'Ongoing']), null],
                ['Completed', $stats['Completed'], 'text-emerald-700', route('admin.activities', ['tab' => 'recent', 'status' => 'Completed']), null],
                ['Late', $stats['Late'], 'text-rose-700', route('admin.activities', ['tab' => 'recent', 'status' => 'Late']), 'Narrative report missing after the deadline.'],
                ['Archived', $stats['Archived'], 'text-slate-600', route('admin.activities', ['tab' => 'recent', 'status' => 'Archived']), 'Excluded from totals'],
                ['Active Organizations', $dashboardData['activeOrganizations'], 'text-sky-800', route('admin.organizations.index'), null],
            ] as [$label, $count, $color, $url, $helper])
                <a href="{{ $url }}" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold {{ $color }}">{{ $count }}</p>
                    @if($helper)<p class="mt-0.5 text-[11px] text-slate-400">{{ $helper }}</p>@endif
                </a>
            @endforeach
        </section>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Monitoring alerts">
            <a href="{{ route('admin.activities', ['tab' => 'recent']) }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-amber-200 hover:bg-amber-50/30">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-amber-500"></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-800">Awaiting your review</p>
                    @if($alerts['awaitingReview'] === 0)<p class="text-[11px] text-slate-400">Nothing to review</p>@endif
                </div>
                <span class="text-2xl font-semibold {{ $alerts['awaitingReview'] === 0 ? 'text-slate-300' : 'text-slate-900' }}">{{ $alerts['awaitingReview'] }}</span>
            </a>
            <a href="{{ route('admin.activities', ['tab' => 'todo']) }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-sky-200 hover:bg-sky-50/30">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-sky-500"></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-800">Upcoming in 7 days</p>
                    @if($alerts['upcomingInSevenDays'] === 0)<p class="text-[11px] text-slate-400">Nothing to review</p>@endif
                </div>
                <span class="text-2xl font-semibold {{ $alerts['upcomingInSevenDays'] === 0 ? 'text-slate-300' : 'text-slate-900' }}">{{ $alerts['upcomingInSevenDays'] }}</span>
            </a>
            <a href="{{ route('admin.activities', ['tab' => 'recent', 'status' => 'Late']) }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-rose-200 hover:bg-rose-50/30">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-800">Late</p>
                    @if($alerts['late'] === 0)<p class="text-[11px] text-slate-400">Nothing to review</p>@endif
                </div>
                <span class="text-2xl font-semibold {{ $alerts['late'] === 0 ? 'text-slate-300' : 'text-slate-900' }}">{{ $alerts['late'] }}</span>
            </a>
        </section>

        <section class="grid items-stretch gap-5 lg:grid-cols-2" aria-label="Monitoring charts">
            <article class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Activities by status</h2>
                <div class="relative mx-auto mt-3 h-44 w-full max-w-xs">
                    <canvas data-chart-type="doughnut" data-chart-data='@json($statusChart)' role="img" aria-label="Activity status chart"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold text-slate-900">{{ $statusTotal }}</span>
                        <span class="text-xs text-slate-500">Activities</span>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2">
                    @foreach($statusChart['labels'] as $index => $label)
                        @php
                            $count = (int) $statusChart['values'][$index];
                            $percent = $statusTotal > 0 ? round(($count / $statusTotal) * 100, 1) : 0;
                        @endphp
                        <div class="flex min-w-0 items-center gap-2 text-xs text-slate-700">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $statusColorClasses[$label] }}"></span>
                            <span class="truncate">{{ $label }}</span>
                            <span class="ml-auto whitespace-nowrap tabular-nums text-slate-600">{{ $count }} · {{ number_format($percent, 1) }}%</span>
                        </div>
                    @endforeach
                </div>
            </article>
            <article class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm" x-data="{
                organizations: @js($organizationProgress->values()),
                sortMode: 'name',
                get sortedOrganizations() {
                    return [...this.organizations].sort((left, right) => this.sortMode === 'completion'
                        ? this.percentage(right) - this.percentage(left) || left.organization.localeCompare(right.organization)
                        : left.organization.localeCompare(right.organization));
                },
                percentage(organization) {
                    return organization.total > 0 ? Math.round((organization.completed / organization.total) * 1000) / 10 : 0;
                }
            }">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Completion by organization</h2>
                        <p class="mt-1 text-xs text-slate-500">Completed activities out of each organization's total</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1" aria-label="Sort organizations">
                        <button type="button" @click="sortMode = 'name'" :aria-pressed="sortMode === 'name'" class="rounded-md px-2 py-1 text-xs font-medium" :class="sortMode === 'name' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50'">A–Z</button>
                        <button type="button" @click="sortMode = 'completion'" :aria-pressed="sortMode === 'completion'" class="rounded-md px-2 py-1 text-xs font-medium" :class="sortMode === 'completion' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50'">Highest completion</button>
                    </div>
                </div>
                <div class="mt-3 max-h-72 flex-1 space-y-3 overflow-y-auto" role="list" aria-label="Completion progress by organization">
                    <template x-for="organization in sortedOrganizations" :key="organization.user_id">
                        <div role="listitem">
                            <div class="flex items-center justify-between gap-3">
                                <span class="min-w-0 truncate text-sm font-medium text-slate-800" x-text="organization.organization"></span>
                                <span class="shrink-0 text-xs text-slate-600 tabular-nums"><span x-text="percentage(organization).toFixed(1)"></span>% · <span x-text="organization.completed"></span>/<span x-text="organization.total"></span></span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-slate-200" role="progressbar" :aria-valuenow="percentage(organization)" aria-valuemin="0" aria-valuemax="100" :aria-label="organization.organization + ' completion'">
                                <div class="h-2 rounded-full bg-emerald-500" :style="'width: ' + percentage(organization) + '%'" :class="percentage(organization) === 0 ? 'hidden' : ''"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </article>
        </section>

        <details open class="group overflow-hidden rounded-lg border border-slate-200 bg-white">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 [&::-webkit-details-marker]:hidden">
                <h2 class="font-semibold text-slate-900">Activities by Category</h2>
                <svg class="h-4 w-4 shrink-0 text-slate-500 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="divide-y divide-slate-100">
                @forelse($dashboardData['categoryCounts'] as $category)
                    <div class="flex items-center justify-between gap-4 px-4 py-2 text-sm">
                        @if($category['category'] === 'Uncategorized')
                            <span class="font-medium text-slate-800">{{ $category['category'] }}</span>
                        @else
                            <a href="{{ route('admin.activities', ['category' => $category['category']]) }}" class="font-medium text-sky-700 hover:underline">{{ $category['category'] }}</a>
                        @endif
                        <span class="text-slate-600">{{ $category['count'] }}</span>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-slate-500">No categorized activities yet.</p>
                @endforelse
            </div>
        </details>

        <details open class="group overflow-hidden rounded-lg border border-slate-200 bg-white">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 [&::-webkit-details-marker]:hidden">
                <h2 class="font-semibold text-slate-900">Recent Document Submissions</h2>
                <svg class="h-4 w-4 shrink-0 text-slate-500 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="overflow-x-auto">
                <table class="min-w-[840px] w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-2">Document</th><th class="px-4 py-2">Activity</th><th class="px-4 py-2">Organization</th><th class="px-4 py-2">Submitted</th><th class="px-4 py-2">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($dashboardData['recentSubmissions'] as $submission)
                            <tr><td class="px-4 py-2 font-medium text-slate-800">{{ $submission['document'] }}</td><td class="px-4 py-2 text-slate-700">{{ $submission['activity'] }}</td><td class="px-4 py-2 text-slate-600">{{ $submission['organization'] }}</td><td class="px-4 py-2 text-slate-600">{{ $submission['submitted_at']?->format('M d, Y') ?? '—' }}</td><td class="px-4 py-2"><a href="{{ $submission['url'] }}" target="_blank" rel="noopener" class="font-semibold text-sky-700 hover:underline">View</a></td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No communication letters or narrative reports have been submitted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Recently Updated Activities</h2>
                    <p class="text-xs text-slate-500">{{ $stats['Organizations'] }} organizations represented</p>
                </div>
                <a href="{{ route('admin.activities.export', ['format' => 'excel']) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Export CSV</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[1040px] w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Activity</th>
                            <th class="px-4 py-3">Organization</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Last updated</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Documents</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentActivities as $activity)
                            @php
                                $organizationName = $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—';
                                $organizationId = $activity->gpoa?->user_id;
                                $activityRequest = $activity->activityRequest;
                                $letterStatus = $activity->letterStatusLabel();
                                $narrativeStatus = $activity->narrativeStatusLabel();
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $activity->title }}</td>
                                <td class="px-4 py-3 text-slate-600">@if($organizationId)<a href="{{ route('admin.activities', ['organization' => $organizationId]) }}" class="text-sky-700 hover:underline">{{ $organizationName }}</a>@else{{ $organizationName }}@endif</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->updated_at?->format('M d, Y') ?? '—' }}<span class="block text-xs text-slate-400">{{ $activity->updated_at?->diffForHumans() ?? '' }}</span></td>
                                <td class="px-4 py-3">
                                    <x-status-pill :status="$activity->monitoring_status" />
                                    @if($activity->monitoring_late)<div class="mt-1"><x-status-pill status="Late" /></div>@endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3"><div class="flex items-center gap-2"><span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] {{ $letterStatus === 'Uploaded' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">{{ $letterStatus === 'Uploaded' ? '✓' : '–' }} Letter</span><span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] {{ $narrativeStatus === 'Needs Revision' ? 'border-amber-200 bg-amber-50 text-amber-700' : (in_array($narrativeStatus, ['Approved', 'For Review', 'Rejected'], true) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500') }}">{{ $narrativeStatus === 'Needs Revision' ? '!' : (in_array($narrativeStatus, ['Approved', 'For Review', 'Rejected'], true) ? '✓' : '–') }} Report</span></div></td>
                                <td class="px-4 py-3">@if($activityRequest)<a href="{{ route('admin.activity-requests.show', $activityRequest) }}" class="font-semibold text-sky-700 hover:underline">View</a>@else<span class="text-slate-400">—</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No planned activities have been submitted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
