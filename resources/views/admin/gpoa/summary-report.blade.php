<x-app-layout>
    <div class="space-y-6">
        @php
            $exportQuery = request()->query();
            unset($exportQuery['page']);
            $moreFilterCount = collect(['category', 'college', 'assessment', 'date_from', 'date_to'])
                ->filter(fn ($filter) => filled($filters[$filter] ?? null))
                ->count();
        @endphp
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Activity Overview Report</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $filters['term'] ?: 'All terms' }} · SY {{ $filters['school_year'] ?: 'All school years' }} · {{ $organizationLabel }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a id="generateExcelReport" href="{{ route('admin.summary-report.download', array_merge($exportQuery, ['include_category_summary' => 1])) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Export Excel</a>
                <a id="generatePdfReport" href="{{ route('admin.summary-report.pdf', array_merge($exportQuery, ['include_category_summary' => 1])) }}" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">Export PDF</a>
            </div>
        </header>

        <form method="GET" action="{{ route('admin.summary-report') }}" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            @if($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            @if($sort)<input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
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

                <div class="flex items-center justify-end gap-2 sm:col-span-2 lg:col-span-1">
                    <button type="submit" class="rounded bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Apply Filters</button>
                    <a href="{{ route('admin.summary-report') }}" class="inline-flex items-center rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </div>

            <details class="mt-3 border-t border-slate-100 pt-3" @if($moreFilterCount > 0) open @endif>
                <summary class="cursor-pointer text-xs font-semibold text-slate-600">More filters @if($moreFilterCount > 0)<span class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">{{ $moreFilterCount }}</span>@endif</summary>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
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
                        <label for="assessment" class="mb-1 block text-xs font-semibold text-slate-600">Assessment</label>
                        <select id="assessment" name="assessment" class="w-full rounded border-slate-300 px-3 py-2 text-sm">
                            <option value="">All assessments</option>
                            @foreach(['aligned' => 'Aligned', 'partial' => 'Partially Aligned', 'not_aligned' => 'Not Aligned'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['assessment'] ?? '') === $value)>{{ $label }}</option>
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
            </details>
        </form>

        @php
            $statusCards = [
                ['Pending', $pendingCount],
                ['Ongoing', $ongoingCount],
                ['Completed', $completedCount],
                ['Archived', $archivedCount],
            ];
            $activeStatus = request('status', '');
        @endphp

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Activity status counts">
            @foreach($statusCards as [$label, $count])
                @php
                    $statusQuery = array_merge(request()->query(), ['status' => $label]);
                    $statusUrl = route('admin.summary-report', $statusQuery);
                    $isActive = $activeStatus === $label;
                @endphp
                <a href="{{ $statusUrl }}" class="block rounded-lg border p-3 transition {{ $isActive ? 'border-sky-600 bg-sky-50 shadow-sm ring-2 ring-sky-200' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                    <x-status-pill :status="$label" />
                    <p class="mt-2 text-xl font-semibold text-slate-900">{{ $count }}</p>
                </a>
            @endforeach
        </section>

        <div class="flex items-center justify-end">
            @php
                $lateQuery = array_merge(request()->query(), ['status' => 'Late']);
                $lateUrl = route('admin.summary-report', $lateQuery);
            @endphp
            <a href="{{ $lateUrl }}" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-800 {{ request('status') === 'Late' ? 'ring-2 ring-rose-200' : '' }}">
                <x-status-pill status="Late" />
                <span>Late (overlaps with Pending/Ongoing)</span>
                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs">{{ $lateCount }}</span>
            </a>
        </div>

        @if($activityCount > 0)
            <div class="report-summary-block overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="font-semibold text-slate-900">Organization Summary</h2>
                    <p class="text-xs text-slate-500">Activity totals and completion progress by organization and term / school year.</p>
                </div>

                <table class="w-full min-w-[1100px] text-sm">
                    <thead class="border-b bg-slate-50">
                        <tr>
                            <th class="p-3 text-left">Organization</th>
                            <th class="p-3 text-left">Term / SY</th>
                            <th class="p-3 text-right">Total</th>
                            <th class="p-3 text-right">Finished</th>
                            <th class="p-3 text-right">Archived</th>
                            <th class="p-3 text-right">Late</th>
                            <th class="p-3 text-right">Ongoing</th>
                            <th class="p-3 text-right">Not Started</th>
                            <th class="p-3 text-right">GPOA finished?</th>
                            <th class="p-3 text-right">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($organizationSummary as $summary)
                            <tr class="border-b align-top">
                                <td class="p-3">{{ $summary['organization'] }}</td>
                                <td class="p-3">{{ $summary['term_sy'] }}</td>
                                <td class="p-3 text-right">{{ $summary['activity_count'] }}</td>
                                <td class="p-3 text-right">{{ $summary['completed'] }}</td>
                                <td class="p-3 text-right">{{ $summary['archived'] }}</td>
                                <td class="p-3 text-right">{{ $summary['late'] }}</td>
                                <td class="p-3 text-right">{{ $summary['ongoing'] }}</td>
                                <td class="p-3 text-right">{{ $summary['pending'] }}</td>
                                <td class="p-3 text-right">{{ $summary['gpoa_finished'] }}</td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="h-2.5 w-28 overflow-hidden rounded-full bg-slate-200">
                                            <div class="flex h-full w-full">
                                                <div class="h-full bg-emerald-500" style="width: {{ $summary['progress'] }}%"></div>
                                            </div>
                                        </div>
                                        <span class="font-semibold">{{ $summary['progress'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="p-5 text-center text-slate-500">No organization data for the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="report-summary-block overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3"><h2 class="font-semibold text-slate-900">Category Summary</h2></div>
                <table class="w-full min-w-[420px] text-sm">
                    <thead class="border-b bg-slate-50"><tr><th class="p-3 text-left">Category</th><th class="p-3 text-right">Activity Count</th><th class="p-3 text-right">Finished</th><th class="p-3 text-right">Archived</th><th class="p-3 text-right">Late</th></tr></thead>
                    <tbody>
                        @forelse($categorySummary as $summary)
                            <tr class="border-b"><td class="p-3">{{ $summary['category'] }}</td><td class="p-3 text-right">{{ $summary['activity_count'] }}</td><td class="p-3 text-right">{{ $summary['completed'] }}</td><td class="p-3 text-right">{{ $summary['archived'] }}</td><td class="p-3 text-right">{{ $summary['late'] }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="p-5 text-center text-slate-500">No category data for the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1500px] text-sm">
                        @php
                            $organizationSortQuery = request()->query();
                            unset($organizationSortQuery['page']);
                            $organizationSortQuery['sort'] = 'organization';
                            $organizationSortQuery['direction'] = $sort === 'organization' && $direction === 'asc' ? 'desc' : 'asc';
                            $dateSortQuery = request()->query();
                            unset($dateSortQuery['page']);
                            $dateSortQuery['sort'] = 'date';
                            $dateSortQuery['direction'] = $sort === 'date' && $direction === 'asc' ? 'desc' : 'asc';
                            $statusSortQuery = request()->query();
                            unset($statusSortQuery['page']);
                            $statusSortQuery['sort'] = 'status';
                            $statusSortQuery['direction'] = $sort === 'status' && $direction === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <thead class="sticky top-0 z-10 border-b bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2"><a href="{{ route('admin.summary-report', $organizationSortQuery) }}" class="inline-flex items-center gap-1">Organization @if($sort === 'organization')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif</a></th>
                                <th class="p-3">Submitted by</th>
                                <th class="p-3">Term / School Year</th>
                                <th class="p-3">Activity # / Title</th>
                                <th class="p-3">Category</th>
                                <th class="px-3 py-2"><a href="{{ route('admin.summary-report', $dateSortQuery) }}" class="inline-flex items-center gap-1">Date @if($sort === 'date')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif</a></th>
                                <th class="p-3">Venue</th>
                                <th class="px-3 py-2"><a href="{{ route('admin.summary-report', $statusSortQuery) }}" class="inline-flex items-center gap-1">Status @if($sort === 'status')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif</a></th>
                                <th class="p-3">Assessment</th>
                                <th class="p-3">Communication Letter</th>
                                <th class="p-3">Narrative Report</th>
                                <th class="p-3 text-right">Estimated Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                                @php
                                    $activityRequest = $activity->activityRequest;
                                    $report = $activityRequest?->report;
                                    $letterSubmitted = filled($activityRequest?->communication_letter);
                                    $narrativeStatus = $report?->reviewStatusLabel() ?? 'Pending';
                                    $narrativePresent = filled($report?->narrative_report) || filled($report?->narrative_content);
                                    $assessmentLabel = match ($activity->monitoringResult?->compliance_status ?? '') {
                                        'aligned' => 'Aligned',
                                        'partial' => 'Partially Aligned',
                                        'not_aligned' => 'Not Aligned',
                                        default => '—',
                                    };
                                @endphp

                                <tr class="border-b align-middle text-sm hover:bg-slate-50">
                                    <td class="px-3 py-2">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $activity->gpoa?->user?->name ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $activity->gpoa?->term ?? '—' }} / {{ $activity->gpoa?->school_year ?? '—' }}</td>
                                    <td class="px-3 py-2"><span class="font-semibold">Activity #{{ $activity->activity_number ?? '—' }}</span><br>@if($activityRequest)<a href="{{ route('admin.activity-requests.show', $activityRequest) }}" title="{{ $activity->title }}" class="line-clamp-2 text-sky-700 hover:underline">{{ $activity->title }}</a>@else<span title="{{ $activity->title }}" class="line-clamp-2">{{ $activity->title }}</span>@endif</td>
                                    <td class="px-3 py-2">{{ $activity->category ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $activity->venue ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">
                                        <x-status-pill :status="$activity->monitoring_status" />
                                        @if($activity->monitoring_late)<x-status-pill status="Late" />@endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $assessmentLabel }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if($letterSubmitted)
                                            <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">Submitted</span>
                                        @else
                                            <span class="text-slate-500">Pending</span>
                                        @endif
                                        @if($letterSubmitted)
                                            <a href="{{ route('admin.file.view', [$activityRequest->id, 'communication']) }}" data-file-viewer data-title="Communication Letter" class="ml-1 text-xs font-semibold text-sky-700 underline">View</a>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if($narrativePresent)
                                            <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs {{ $narrativeStatus === 'Needs Revision' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">{{ $narrativeStatus === 'Needs Revision' ? 'Needs Revision' : 'Submitted' }}</span>
                                        @else
                                            <span class="text-slate-500">Pending</span>
                                        @endif
                                        @if($narrativePresent)
                                            <a href="{{ route('admin.file.view', [$activityRequest->id, 'narrative']) }}" data-file-viewer data-title="Narrative Report" class="ml-1 text-xs font-semibold text-sky-700 underline">View</a>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">PHP {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="p-6 text-center text-slate-500">No planned activities match the selected filters.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-slate-50 font-semibold">
                                <td class="px-3 py-2" colspan="11">Total Estimated Budget</td>
                                <td class="px-3 py-2 text-right">PHP {{ number_format((float) $totalBudget, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="space-y-3 p-3 md:hidden">
                    @forelse($activities as $activity)
                        @php
                            $activityRequest = $activity->activityRequest;
                            $report = $activityRequest?->report;
                            $letterSubmitted = filled($activityRequest?->communication_letter);
                            $narrativeStatus = $report?->reviewStatusLabel() ?? 'Pending';
                            $narrativePresent = filled($report?->narrative_report) || filled($report?->narrative_content);
                            $assessmentLabel = match ($activity->monitoringResult?->compliance_status ?? '') {
                                'aligned' => 'Aligned',
                                'partial' => 'Partially Aligned',
                                'not_aligned' => 'Not Aligned',
                                default => '—',
                            };
                        @endphp

                        <article class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? 'Organization' }}</p>
                                    <p class="text-xs text-slate-600">Submitted by {{ $activity->gpoa?->user?->name ?? '—' }}</p>
                                    <h3 class="mt-1 font-semibold text-slate-900">Activity #{{ $activity->activity_number ?? '—' }} · {{ $activity->title }}</h3>
                                </div>
                                <x-status-pill :status="$activity->monitoring_status" />
                            </div>

                            <dl class="mt-3 space-y-1 text-xs text-slate-600">
                                <div class="flex justify-between gap-3"><dt>Term / SY</dt><dd>{{ $activity->gpoa?->term ?? '—' }} / {{ $activity->gpoa?->school_year ?? '—' }}</dd></div>
                                <div class="flex justify-between gap-3"><dt>Category</dt><dd>{{ $activity->category ?: '—' }}</dd></div>
                                <div class="flex justify-between gap-3"><dt>Assessment</dt><dd>{{ $assessmentLabel }}</dd></div>
                                <div class="flex justify-between gap-3"><dt>Venue</dt><dd>{{ $activity->venue ?: '—' }}</dd></div>
                                <div class="flex justify-between gap-3"><dt>Budget</dt><dd>PHP {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</dd></div>
                            </dl>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($letterSubmitted)
                                    <a href="{{ route('admin.file.view', [$activityRequest->id, 'communication']) }}" data-file-viewer data-title="Communication Letter" class="text-xs font-semibold text-sky-700 underline">View letter</a>
                                @endif
                                @if($narrativePresent)
                                    <a href="{{ route('admin.file.view', [$activityRequest->id, 'narrative']) }}" data-file-viewer data-title="Narrative Report" class="text-xs font-semibold text-sky-700 underline">View narrative</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="p-4 text-center text-sm text-slate-500">No planned activities match the selected filters.</p>
                    @endforelse
                </div>
            </div>
            <div class="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} of {{ $activities->total() }} activities</p>
                {{ $activities->links() }}
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
                if (!includeSummaryTables) {
                    return;
                }

                const includeSummaries = includeSummaryTables.value === '1';
                summaryExportLinks.forEach((link) => {
                    if (!link) {
                        return;
                    }

                    const url = new URL(link.href);
                    url.searchParams.set('include_category_summary', includeSummaries ? '1' : '0');
                    link.href = url.toString();
                });

                summaryBlocks.forEach((block) => block.classList.toggle('hidden', !includeSummaries));
            }

            if (includeSummaryTables) {
                includeSummaryTables.addEventListener('change', updateSummaryExportLinks);
                updateSummaryExportLinks();
            }
        </script>
    @endpush
</x-app-layout>