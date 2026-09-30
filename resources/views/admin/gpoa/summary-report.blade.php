<x-app-layout>
<div class="space-y-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Activity Overview Report</h1>
            <p class="mt-1 text-sm text-slate-600">Planned GPOA activities and their current monitoring status.</p>
        </div>
        <div class="flex gap-2">
            <a id="generateExcelReport" href="{{ route('admin.summary-report.download', array_merge(request()->query(), ['include_category_summary' => 1])) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Export Excel</a>
            <a id="generatePdfReport" href="{{ route('admin.summary-report.pdf', array_merge(request()->query(), ['include_category_summary' => 1])) }}" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">Export PDF</a>
        </div>
    </header>

    <form method="GET" action="{{ route('admin.summary-report') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="organization" class="mb-1 block text-xs font-semibold text-slate-600">Organization</label>
                <select id="organization" name="organization" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
                    <option value="">All organizations</option>
                    @foreach($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected((string) ($filters['organization'] ?? '') === (string) $organization->id)>{{ $organization->org_name ?: $organization->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category" class="mb-1 block text-xs font-semibold text-slate-600">Category</label>
                <select id="category" name="category" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="college" class="mb-1 block text-xs font-semibold text-slate-600">College</label>
                <select id="college" name="college" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
                    <option value="">All colleges</option>
                    @foreach($colleges as $college)
                        <option value="{{ $college }}" @selected(($filters['college'] ?? '') === $college)>{{ $college }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="term" class="mb-1 block text-xs font-semibold text-slate-600">Term</label>
                <select id="term" name="term" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
                    <option value="">All terms</option>
                    @foreach($terms as $term)
                        <option value="{{ $term }}" @selected(($filters['term'] ?? '') === $term)>{{ $term }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="school_year" class="mb-1 block text-xs font-semibold text-slate-600">School year</label>
                <select id="school_year" name="school_year" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
                    <option value="">All school years</option>
                    @foreach($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear }}" @selected(($filters['school_year'] ?? '') === $schoolYear)>{{ $schoolYear }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-semibold text-slate-600">Status</label>
                <select id="status" name="status" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach(['Pending', 'Ongoing', 'Completed', 'Late'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date_from" class="mb-1 block text-xs font-semibold text-slate-600">Date from</label>
                <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="date_to" class="mb-1 block text-xs font-semibold text-slate-600">Date to</label>
                <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="rounded bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Apply Filters</button>
            <a href="{{ route('admin.summary-report') }}" class="inline-flex items-center rounded border border-slate-300 px-3 text-sm text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Activity status counts">
        @foreach([
            ['Pending', $pendingCount],
            ['Ongoing', $ongoingCount],
            ['Completed', $completedCount],
            ['Late', $lateCount],
        ] as [$label, $count])
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <x-status-pill :status="$label" />
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ $count }}</p>
            </div>
        @endforeach
    </section>

    <div class="flex items-center gap-2">
        <input id="includeSummaryTables" type="checkbox" checked class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
        <label for="includeSummaryTables" class="text-sm font-semibold text-slate-700">Include summary tables in exports</label>
    </div>

    @if($activityCount > 0)
    <div class="report-summary-block overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Organization Summary</h2>
            <p class="text-xs text-slate-500">Activity totals and completion progress by organization.</p>
        </div>
        <table class="w-full min-w-[760px] text-sm">
            <thead class="border-b bg-slate-50"><tr><th class="p-3 text-left">Organization</th><th class="p-3 text-right">Total Activities</th><th class="p-3 text-right">Completed</th><th class="p-3 text-right">Ongoing</th><th class="p-3 text-right">Pending</th><th class="p-3 text-right">Progress</th></tr></thead>
            <tbody>
                @forelse($organizationSummary as $summary)
                    <tr class="border-b"><td class="p-3">{{ $summary['organization'] }}</td><td class="p-3 text-right">{{ $summary['activity_count'] }}</td><td class="p-3 text-right">{{ $summary['completed'] }}</td><td class="p-3 text-right">{{ $summary['ongoing'] }}</td><td class="p-3 text-right">{{ $summary['pending'] }}</td><td class="p-3 text-right">{{ $summary['progress'] }}%</td></tr>
                @empty
                    <tr><td colspan="6" class="p-5 text-center text-slate-500">No organization data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="report-summary-block overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3"><h2 class="font-semibold text-slate-900">Category Summary</h2></div>
        <table class="w-full min-w-[420px] text-sm">
            <thead class="border-b bg-slate-50"><tr><th class="p-3 text-left">Category</th><th class="p-3 text-right">Activity Count</th><th class="p-3 text-right">Completed</th></tr></thead>
            <tbody>
                @forelse($categorySummary as $summary)
                    <tr class="border-b"><td class="p-3">{{ $summary['category'] }}</td><td class="p-3 text-right">{{ $summary['activity_count'] }}</td><td class="p-3 text-right">{{ $summary['completed'] }}</td></tr>
                @empty
                    <tr><td colspan="3" class="p-5 text-center text-slate-500">No category data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="report-summary-block overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3"><h2 class="font-semibold text-slate-900">Monitoring Status Summary</h2></div>
        <table class="w-full min-w-[420px] text-sm">
            <thead class="border-b bg-slate-50"><tr><th class="p-3 text-left">Status</th><th class="p-3 text-right">Activity Count</th></tr></thead>
            <tbody>
                @foreach($statusSummary as $summary)
                    <tr class="border-b"><td class="p-3"><x-status-pill :status="$summary['status']" /></td><td class="p-3 text-right">{{ $summary['activity_count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[1500px] text-sm">
            <thead class="border-b bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="p-3">Organization</th><th class="p-3">Term / School Year</th><th class="p-3">Activity # / Title</th><th class="p-3">Category</th><th class="p-3">Date</th><th class="p-3">Venue</th><th class="p-3">Status</th><th class="p-3">Communication Letter</th><th class="p-3">Narrative Report</th><th class="p-3 text-right">Estimated Budget</th></tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    @php
                        $activityRequest = $activity->activityRequest;
                        $report = $activityRequest?->report;
                        $letterSubmitted = filled($activityRequest?->communication_letter);
                        $narrativeStatus = $report?->status === 'needs_revision'
                            ? 'Needs Revision'
                            : ((filled($report?->narrative_report) || filled($report?->narrative_content)) ? 'Submitted' : 'Pending');
                    @endphp
                    <tr class="border-b align-top">
                        <td class="p-3">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</td>
                        <td class="p-3">{{ $activity->gpoa?->term ?? '—' }} / {{ $activity->gpoa?->school_year ?? '—' }}</td>
                        <td class="p-3"><span class="font-semibold">Activity #{{ $activity->activity_number ?? '—' }}</span><br>{{ $activity->title }}</td>
                        <td class="p-3">{{ $activity->category ?: '—' }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                        <td class="p-3">{{ $activity->venue ?: '—' }}</td>
                        <td class="p-3 whitespace-nowrap">
                            <x-status-pill :status="$activity->monitoring_status" />
                            @if($activity->monitoring_late)<x-status-pill status="Late" />@endif
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            <x-status-pill :status="$letterSubmitted ? 'Uploaded' : 'Pending'" />
                            @if($letterSubmitted)<a href="{{ route('admin.file.view', [$activityRequest->id, 'communication']) }}" target="_blank" class="ml-1 text-xs font-semibold text-sky-700 underline">View</a>@endif
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            <x-status-pill :status="$narrativeStatus" />
                            @if(filled($report?->narrative_report))<a href="{{ route('admin.file.view', [$activityRequest->id, 'narrative']) }}" target="_blank" class="ml-1 text-xs font-semibold text-sky-700 underline">View</a>@endif
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">PHP {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="p-6 text-center text-slate-500">No planned activities match the selected filters.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr class="border-t bg-slate-50 font-semibold"><td class="p-3" colspan="9">Total Estimated Budget</td><td class="p-3 text-right">PHP {{ number_format((float) $totalBudget, 2) }}</td></tr></tfoot>
        </table>
    </div>
    @else
        <section class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 text-sky-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l4 4v14H4V3h4Zm0 0v5h8V3M8 13h8m-8 4h5" /></svg></span>
            <h2 class="mt-4 text-base font-semibold text-slate-900">No activities match these filters</h2>
            <p class="mt-1 text-sm text-slate-500">Clear the filters to see the available monitoring report.</p>
            <a href="{{ route('admin.summary-report') }}" class="mt-5 inline-flex rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Reset filters</a>
        </section>
    @endif
</div>

@push('scripts')
<script>
const includeSummaryTables = document.getElementById('includeSummaryTables');
const summaryBlocks = document.querySelectorAll('.report-summary-block');
const summaryExportLinks = [document.getElementById('generateExcelReport'), document.getElementById('generatePdfReport')];

function updateSummaryExportLinks() {
    const includeSummaries = includeSummaryTables.checked;
    summaryExportLinks.forEach((link) => {
        const url = new URL(link.href);
        url.searchParams.set('include_category_summary', includeSummaries ? '1' : '0');
        link.href = url.toString();
    });
    summaryBlocks.forEach((block) => block.classList.toggle('hidden', !includeSummaries));
}

includeSummaryTables.addEventListener('change', updateSummaryExportLinks);
updateSummaryExportLinks();
</script>
@endpush
</x-app-layout>