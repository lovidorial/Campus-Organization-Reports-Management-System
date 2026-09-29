<x-app-layout>
    <div class="space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Monitoring Dashboard</h1>
                <p class="mt-1 text-sm text-slate-600">Current progress across planned GPOA activities.</p>
            </div>
            <a href="{{ route('admin.activities') }}" class="inline-flex items-center rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Open Activity Monitoring</a>
        </header>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6" aria-label="Activity monitoring totals">
            @foreach([
            ['Active Organizations', $dashboardData['activeOrganizations'], 'text-sky-800'],
                ['Total Activities', $stats['Total'], 'text-slate-900'],
                ['Not Started', $stats['Not Started'], 'text-slate-700'],
                ['Ongoing', $stats['Ongoing'], 'text-amber-700'],
                ['Completed', $stats['Completed'], 'text-emerald-700'],
                ['Late', $stats['Late'], 'text-rose-700'],
            ] as [$label, $count, $color])
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold {{ $color }}">{{ $count }}</p>
                </div>
            @endforeach
        </section>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3"><h2 class="font-semibold text-slate-900">Organizations by Completed Activities</h2></div>
                <div class="divide-y divide-slate-100">
                    @forelse($dashboardData['topOrganizations'] as $organization)
                        <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                            <span class="font-medium text-slate-800">{{ $organization['organization'] }}</span>
                            <span class="text-slate-600">{{ $organization['completed'] }} / {{ $organization['total'] }} completed</span>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-slate-500">No planned activities yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3"><h2 class="font-semibold text-slate-900">Activities by Category</h2></div>
                <div class="divide-y divide-slate-100">
                    @forelse($dashboardData['categoryCounts'] as $category)
                        <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                            <span class="font-medium text-slate-800">{{ $category['category'] }}</span>
                            <span class="text-slate-600">{{ $category['count'] }}</span>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-slate-500">No categorized activities yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-4 py-3"><h2 class="font-semibold text-slate-900">Recent Document Submissions</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-2">Document</th><th class="px-4 py-2">Activity</th><th class="px-4 py-2">Organization</th><th class="px-4 py-2">Submitted</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($dashboardData['recentSubmissions'] as $submission)
                            <tr><td class="px-4 py-2 font-medium text-slate-800">{{ $submission['document'] }}</td><td class="px-4 py-2 text-slate-700">{{ $submission['activity'] }}</td><td class="px-4 py-2 text-slate-600">{{ $submission['organization'] }}</td><td class="px-4 py-2 text-slate-600">{{ $submission['submitted_at']?->format('M d, Y g:i A') ?? '—' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No communication letters or narrative reports have been submitted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Recently Updated Activities</h2>
                    <p class="text-xs text-slate-500">{{ $stats['Organizations'] }} organizations represented</p>
                </div>
                <a href="{{ route('admin.activities.export', ['format' => 'excel']) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Export CSV</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Activity</th>
                            <th class="px-4 py-3">Organization</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Documents</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentActivities as $activity)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $activity->title }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $activity->monitoring_status === 'Completed' ? 'bg-emerald-100 text-emerald-800' : ($activity->monitoring_status === 'Ongoing' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ $activity->monitoring_status }}</span>
                                    @if($activity->monitoring_late)<span class="ml-1 inline-flex rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Late</span>@endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">Letter: {{ $activity->letterStatusLabel() }} · Narrative: {{ $activity->narrativeStatusLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No planned activities have been submitted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
