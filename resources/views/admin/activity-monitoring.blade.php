<x-app-layout>
    <div class="space-y-5">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Activity Monitoring</h1>
                <p class="mt-1 text-sm text-slate-600">Monitor each activity from its planned GPOA entry and submitted documents.</p>
            </div>
            <a href="{{ route('admin.activities.export', array_merge(['format' => 'excel'], request()->query())) }}" class="inline-flex items-center rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Export CSV</a>
        </header>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-5" aria-label="Monitoring totals">
            @foreach([
                ['Total', $stats['Total'], 'text-slate-900'],
                ['Pending', $stats['Pending'], 'text-slate-700'],
                ['Ongoing', $stats['Ongoing'], 'text-amber-700'],
                ['Completed', $stats['Completed'], 'text-emerald-700'],
                ['Late', $stats['Late'], 'text-rose-700'],
            ] as [$label, $count, $color])
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-xl font-semibold {{ $color }}">{{ $count }}</p>
                </div>
            @endforeach
        </section>

        <form method="GET" action="{{ route('admin.activities') }}" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-4">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search activity, organization, venue, date" class="min-w-0 rounded-md border-slate-300 text-sm">
            <select name="status" class="min-w-0 rounded-md border-slate-300 text-sm">
                <option value="">All monitoring statuses</option>
                @foreach(['Pending', 'Ongoing', 'Completed', 'Late'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="organization" class="min-w-0 rounded-md border-slate-300 text-sm">
                <option value="">All organizations</option>
                @foreach($organizations as $organization)
                    <option value="{{ $organization->id }}" @selected((string) request('organization') === (string) $organization->id)>{{ $organization->org_name ?: $organization->name }}</option>
                @endforeach
            </select>
            <select name="category" class="min-w-0 rounded-md border-slate-300 text-sm">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <select name="college" class="min-w-0 rounded-md border-slate-300 text-sm">
                <option value="">All colleges</option>
                @foreach($colleges as $college)
                    <option value="{{ $college }}" @selected(request('college') === $college)>{{ $college }}</option>
                @endforeach
            </select>
            <select name="term" class="min-w-0 rounded-md border-slate-300 text-sm">
                <option value="">All terms</option>
                @foreach($terms as $term)
                    <option value="{{ $term }}" @selected(request('term') === $term)>{{ $term }}</option>
                @endforeach
            </select>
            <select name="school_year" class="min-w-0 rounded-md border-slate-300 text-sm">
                <option value="">All school years</option>
                @foreach($schoolYears as $schoolYear)
                    <option value="{{ $schoolYear }}" @selected(request('school_year') === $schoolYear)>{{ $schoolYear }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Apply Filters</button>
                <a href="{{ route('admin.activities') }}" class="inline-flex items-center rounded-md border border-slate-300 px-3 text-sm text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="font-semibold text-slate-900">Organization GPOA Progress</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-2">Organization</th><th class="px-4 py-2">College</th><th class="px-4 py-2">Completed</th><th class="px-4 py-2">Activities</th><th class="px-4 py-2">Progress</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($organizationProgress as $progress)
                            <tr>
                                <td class="px-4 py-2 font-medium text-slate-900">{{ $progress['organization'] }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ $progress['college'] ?: '—' }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ $progress['completed'] }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ $progress['total'] }}</td>
                                <td class="px-4 py-2"><div class="flex items-center gap-2"><progress max="100" value="{{ $progress['percent'] }}" class="h-2 w-28 overflow-hidden rounded-full accent-emerald-500"></progress><span class="text-xs font-semibold text-slate-700">{{ $progress['percent'] }}%</span></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No organizations match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Activity</th>
                            <th class="px-3 py-3">Organization</th>
                            <th class="px-3 py-3">Term / Year</th>
                            <th class="px-3 py-3">Category</th>
                            <th class="px-3 py-3">Date</th>
                            <th class="px-3 py-3">Venue</th>
                            <th class="px-3 py-3">Communication Letter</th>
                            <th class="px-3 py-3">Narrative Report</th>
                            <th class="px-3 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($activities as $activity)
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-slate-900">{{ $activity->title }}</div>
                                    @if($activity->activityRequest?->programFlows->isNotEmpty())
                                        <details class="mt-1 text-xs text-slate-600">
                                            <summary class="cursor-pointer font-semibold text-sky-700">Program flow ({{ $activity->activityRequest->programFlows->count() }})</summary>
                                            <div class="mt-2 space-y-1">
                                                @foreach($activity->activityRequest->programFlows as $flow)
                                                    <p>{{ $flow->time }} · {{ $flow->flow }} · Person in Charge: {{ $flow->person_in_charge }}</p>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-slate-600">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-xs text-slate-600">{{ $activity->gpoa?->term ?? '—' }}<br>{{ $activity->gpoa?->school_year ?? '' }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $activity->category ?: '—' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-slate-600">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $activity->venue ?: '—' }}</td>
                                <td class="px-3 py-3 text-xs">
                                    @if($activity->activityRequest?->communication_letter)
                                        @php($letterRoute = route('admin.file.view', [$activity->activityRequest->id, 'communication']))
                                        <a href="{{ $letterRoute }}" target="_blank" class="font-semibold text-sky-700 underline">View</a>
                                        <a href="{{ route('admin.file.download', [$activity->activityRequest->id, 'communication']) }}" class="ml-2 font-semibold text-sky-700 underline">Download</a>
                                    @else
                                        <span class="text-slate-500">Pending</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-xs">
                                    @if($activity->activityRequest?->report)
                                        <a href="{{ route('admin.file.view', [$activity->activityRequest->id, 'narrative']) }}" target="_blank" class="font-semibold text-sky-700 underline">View</a>
                                        <a href="{{ route('admin.file.download', [$activity->activityRequest->id, 'narrative']) }}" class="ml-2 font-semibold text-sky-700 underline">Download</a>
                                        @if($activity->activityRequest->report->narrative_source === 'generated')<span class="block text-slate-500">Created in system</span>@endif
                                    @else
                                        <span class="text-slate-500">Pending</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $activity->monitoring_status === 'Completed' ? 'bg-emerald-100 text-emerald-800' : ($activity->monitoring_status === 'Ongoing' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ $activity->monitoring_status }}</span>
                                    @if($activity->monitoring_late)<span class="mt-1 block text-xs font-semibold text-rose-700">Late</span>@endif
                                    @if($activity->monitoringResult)
                                        <p class="mt-1 max-w-48 whitespace-pre-wrap text-xs text-slate-600" title="{{ $activity->monitoringResult->compliance_notes }}">{{ $activity->monitoringResult->compliance_notes ?: ucfirst(str_replace('_', ' ', $activity->monitoringResult->compliance_status)) }}</p>
                                    @endif
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-sky-700">{{ $activity->monitoringResult ? 'Edit remark' : 'Add remark' }}</summary>
                                        <form method="POST" action="{{ route('admin.monitoring.record', $activity->id) }}" class="mt-2 space-y-2">
                                            @csrf
                                            <select name="compliance_status" required class="w-full rounded border-slate-300 text-xs">
                                                <option value="">Select monitoring assessment</option>
                                                @foreach(['aligned' => 'Aligned', 'partial' => 'Partially Aligned', 'not_aligned' => 'Not Aligned'] as $value => $label)
                                                    <option value="{{ $value }}" @selected($activity->monitoringResult?->compliance_status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <textarea name="compliance_notes" rows="2" maxlength="1000" placeholder="Optional monitoring remark" class="w-full rounded border-slate-300 text-xs">{{ $activity->monitoringResult?->compliance_notes }}</textarea>
                                            <button type="submit" class="rounded bg-sky-700 px-2 py-1 text-xs font-semibold text-white hover:bg-sky-800">Save</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-12 text-center text-slate-500">No planned activities match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $activities->links() }}</div>
        </section>
    </div>
</x-app-layout>
