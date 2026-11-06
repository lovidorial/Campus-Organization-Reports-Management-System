<x-app-layout>
    <div class="admin-monitoring-dashboard space-y-6">
        @php
            $periodQuery = array_filter($filters, fn ($value) => filled($value));
            $selectedOrganizationRow = collect($organizationProgressRows)->firstWhere('id', (int) ($filters['organization'] ?? 0));
            $activityFilters = array_filter([
                'term' => $filters['term'],
                'school_year' => $filters['school_year'],
                'college' => $filters['college'],
                'organization' => $selectedOrganizationRow['user_id'] ?? null,
            ], fn ($value) => filled($value));
            $gpoaFilters = array_filter([
                'term' => $filters['term'],
                'school_year' => $filters['school_year'],
                'college' => $filters['college'],
                'classification' => $filters['classification'],
                'organization' => $filters['organization'],
            ], fn ($value) => filled($value));
            $reportFilters = array_filter([
                'term' => $filters['term'],
                'school_year' => $filters['school_year'],
                'college' => $filters['college'],
                'organization' => $selectedOrganizationRow['user_id'] ?? null,
            ], fn ($value) => filled($value));
            $statCards = [
                ['Total Organizations', $stats['Organizations'], route('admin.organizations.index', array_filter(['college' => $filters['college'], 'type' => $filters['classification'], 'search' => $selectedOrganizationRow['name'] ?? null])), 'Active Organizations in the selected period and filters', 'orange'],
                ['Submitted GPOAs', $stats['Submitted GPOAs'], route('admin.gpoa.index', $gpoaFilters), 'GPOA records on file; stored as approved', 'gold'],
                ['Total Activities', $stats['Total'], route('admin.activities', array_merge($activityFilters, ['tab' => 'recent'])), 'Planned activities in the selected period', 'navy'],
                ['Completed Activities', $stats['Completed'], route('admin.activities', array_merge($activityFilters, ['tab' => 'recent', 'status' => 'Completed'])), 'Narrative report assessed as complete', 'green'],
                ['Ongoing Activities', $stats['Ongoing'], route('admin.activities', array_merge($activityFilters, ['tab' => 'recent', 'status' => 'Ongoing'])), 'Activities currently being monitored', 'blue'],
                ['Overdue Activities', $stats['Late'], route('admin.activities', array_merge($activityFilters, ['tab' => 'recent', 'status' => 'Late'])), 'Late is tracked separately from activity status', 'red'],
                ['Outstanding Narrative Reports', $outstandingReportCount, route('admin.narrative-reports.outstanding', $reportFilters), 'Missing Reports, overdue, or needing revision', 'red'],
            ];
            $complianceLabels = ['Aligned' => 'aligned', 'Partially Aligned' => 'partial', 'Not Aligned' => 'not_aligned'];
            $complianceTotal = array_sum($monitoringAssessments);
            $complianceChart = [
                'labels' => array_keys($complianceLabels),
                'values' => [$monitoringAssessments['aligned'], $monitoringAssessments['partial'], $monitoringAssessments['not_aligned']],
            ];
        @endphp

        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-700">Office of Student Development and Welfare</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Monitoring Dashboard</h1>
                <p class="mt-1 text-sm text-slate-600">Organization activity, documentation, and compliance monitoring.</p>
            </div>
            <a href="{{ route('admin.activities', array_merge($activityFilters, ['tab' => 'recent'])) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-orange-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Open Activity Monitor</a>
        </header>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="admin-dashboard-filters grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
            <div>
                <label for="dashboard-school-year" class="mb-1 block text-xs font-semibold text-slate-600">Academic year</label>
                <select id="dashboard-school-year" name="school_year" class="min-h-11 w-full rounded-lg border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">All academic years</option>
                    @foreach($schoolYearOptions as $schoolYear)<option value="{{ $schoolYear }}" @selected($filters['school_year'] === $schoolYear)>{{ $schoolYear }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-term" class="mb-1 block text-xs font-semibold text-slate-600">Semester</label>
                <select id="dashboard-term" name="term" class="min-h-11 w-full rounded-lg border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">All semesters</option>
                    @foreach($termOptions as $term)<option value="{{ $term }}" @selected($filters['term'] === $term)>{{ $term }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-college" class="mb-1 block text-xs font-semibold text-slate-600">College</label>
                <select id="dashboard-college" name="college" class="min-h-11 w-full rounded-lg border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">All colleges</option>
                    @foreach($collegeOptions as $college)<option value="{{ $college }}" @selected($filters['college'] === $college)>{{ $college }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-classification" class="mb-1 block text-xs font-semibold text-slate-600">Organization classification</label>
                <select id="dashboard-classification" name="classification" class="min-h-11 w-full rounded-lg border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">All classifications</option>
                    @foreach($classificationOptions as $classification)<option value="{{ $classification }}" @selected($filters['classification'] === $classification)>{{ $classification }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-organization" class="mb-1 block text-xs font-semibold text-slate-600">Organization</label>
                <select id="dashboard-organization" name="organization" class="min-h-11 w-full rounded-lg border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">All organizations</option>
                    @foreach($organizationOptions as $organization)<option value="{{ $organization->id }}" @selected((string) $filters['organization'] === (string) $organization->id)>{{ $organization->name }}</option>@endforeach
                </select>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5 lg:justify-end">
                <button type="submit" class="min-h-11 rounded-lg bg-orange-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Apply filters</button>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Reset</a>
            </div>
        </form>

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

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Activity monitoring totals">
            @foreach($statCards as [$label, $count, $url, $helper, $accent])
                <a href="{{ $url }}" class="admin-stat-card admin-stat-card-{{ $accent }} flex h-full min-h-32 flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $label }}</p>
                    <p @class(['mt-2 text-3xl font-semibold tabular-nums', 'text-slate-400' => $count === 0, 'text-slate-900' => $count > 0])>{{ $count }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-600">{{ $helper }}</p>
                </a>
            @endforeach
        </section>

        <section class="admin-panel overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-label="Monitoring alerts">
            <div class="flex flex-wrap items-end justify-between gap-2 border-b border-slate-200 px-4 py-3 sm:px-5">
                <div><h2 class="font-semibold text-slate-900">Monitoring alerts</h2><p class="mt-0.5 text-xs text-slate-600">Items that may need OSDW follow-up</p></div>
                <a href="{{ route('admin.narrative-reports.outstanding', $reportFilters) }}" class="inline-flex min-h-11 items-center rounded-md px-2 text-sm font-semibold text-orange-900 underline decoration-orange-300 underline-offset-4 hover:text-orange-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Open report tracking</a>
            </div>
            <div class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 lg:grid-cols-4 sm:p-4">
                @foreach([
                    ['Upcoming document deadlines', $deadlines->count(), route('admin.document-deadlines.index'), 'amber'],
                    ['Overdue narrative reports', $alerts['overdueReports'], route('admin.narrative-reports.outstanding', array_merge($reportFilters, ['status' => 'Overdue'])), 'rose'],
                    ['Missing communication letters', $alerts['missingLetters'], route('admin.activities', array_merge($activityFilters, ['tab' => 'todo'])), 'orange'],
                    ['Reports needing revision', $alerts['needsRevision'], route('admin.narrative-reports.outstanding', array_merge($reportFilters, ['status' => 'Needs revision'])), 'amber'],
                    ['Incomplete GPOA activities', $alerts['incompleteGpoaActivities'], route('admin.activities', array_merge($activityFilters, ['tab' => 'recent', 'status' => 'Pending'])), 'blue'],
                    ['Awaiting your review', $alerts['awaitingReview'], route('admin.activities', array_merge($activityFilters, ['tab' => 'recent', 'status' => 'Ongoing'])), 'amber'],
                    ['Activities upcoming in 7 days', $alerts['upcomingInSevenDays'], route('admin.activities', array_merge($activityFilters, ['tab' => 'todo'])), 'blue'],
                    ['Late activity flag', $alerts['late'], route('admin.activities', array_merge($activityFilters, ['tab' => 'recent', 'status' => 'Late'])), 'rose'],
                ] as [$label, $count, $url, $tone])
                    <a href="{{ $url }}" class="admin-alert-tile admin-alert-{{ $tone }} flex min-h-20 items-center gap-3 rounded-lg border border-slate-200 bg-white p-3 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">
                        <div class="min-w-0 flex-1"><p class="text-xs font-semibold leading-5 text-slate-800">{{ $label }}</p><p class="mt-1 text-[11px] text-slate-600">{{ $count === 0 ? 'No items currently recorded' : 'Open monitoring records' }}</p></div>
                        <span @class(['text-xl font-semibold tabular-nums', 'text-slate-400' => $count === 0, 'text-slate-900' => $count > 0])>{{ $count }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="admin-panel rounded-xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-5" aria-label="Upcoming configured deadlines">
            <div class="border-l-2 border-orange-500 pl-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming configured deadlines · next 30 days</h3>
                @forelse($deadlines->take(4) as $deadline)
                    <a href="{{ route('admin.document-deadlines.index') }}" class="mt-2 flex min-h-10 flex-wrap items-center justify-between gap-2 rounded-md px-2 text-sm text-slate-700 transition hover:bg-white hover:text-orange-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">
                        <span>{{ str($deadline->document_type)->replace('_', ' ')->title() }} · {{ $deadline->term }} / {{ $deadline->school_year }}</span>
                        <time class="font-semibold" datetime="{{ $deadline->deadline_date->toDateString() }}">{{ $deadline->deadline_date->format('M j, Y') }}</time>
                    </a>
                @empty
                    <p class="mt-2 text-sm text-slate-600">No configured deadlines are due in the next 30 days.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-panel overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-label="Organization monitoring overview">
            <div class="flex flex-wrap items-end justify-between gap-2 border-b border-slate-200 px-4 py-3">
                <div><h2 class="font-semibold text-slate-900">Organization monitoring overview</h2><p class="mt-0.5 text-xs text-slate-500">Filtered to {{ $filters['term'] ?: 'all semesters' }} · {{ $filters['school_year'] ?: 'all academic years' }}</p></div>
                <a href="{{ route('admin.organizations.index') }}" class="text-sm font-semibold text-orange-800 hover:underline">Manage organizations</a>
            </div>
            <div class="max-w-full overflow-x-auto overscroll-x-contain">
                <table class="min-w-[1080px] w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-left text-[11px] uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Organization</th><th class="px-3 py-3">College</th><th class="px-3 py-3">Classification</th><th class="px-3 py-3">GPOA on file</th><th class="px-3 py-3 text-right">Activities</th><th class="px-3 py-3 text-right">Complete</th><th class="px-3 py-3 text-right">Ongoing</th><th class="px-3 py-3 text-right">Pending</th><th class="px-3 py-3 text-right">Late</th><th class="px-3 py-3">Progress</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($organizationProgressRows as $row)
                            @php $organizationUrl = route('admin.organizations.show', $row['id']); @endphp
                            <tr class="transition-colors hover:bg-orange-50/40">
                                <td class="max-w-56 whitespace-normal break-words px-4 py-3 font-semibold"><a href="{{ $organizationUrl }}" class="text-orange-900 underline decoration-orange-200 underline-offset-2 hover:text-orange-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">{{ $row['name'] }}</a></td>
                                <td class="px-3 py-3 text-slate-700">{{ $row['college'] ?: '—' }}</td>
                                <td class="max-w-48 whitespace-normal px-3 py-3 text-slate-700">{{ $row['classification'] ?: '—' }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ $row['gpoa_count'] ? 'On file · ' . $row['gpoa_count'] : 'No GPOA' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-slate-700">{{ $row['total'] }}</td>
                                <td class="px-3 py-3 text-right tabular-nums font-semibold text-emerald-800">{{ $row['completed'] }}</td>
                                <td class="px-3 py-3 text-right tabular-nums font-semibold text-sky-800">{{ $row['ongoing'] }}</td>
                                <td class="px-3 py-3 text-right tabular-nums font-semibold text-orange-800">{{ $row['pending'] }}</td>
                                <td class="px-3 py-3 text-right tabular-nums font-semibold text-rose-800">{{ $row['late'] }}</td>
                                <td class="min-w-44 px-3 py-3"><div class="flex items-center gap-2"><div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-emerald-700" style="width: {{ $row['percent'] }}%"></div></div><span class="min-w-12 text-right text-xs font-semibold tabular-nums text-slate-700">{{ number_format($row['percent'], 1) }}%</span></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-8 text-center text-slate-500">No active organizations match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Compliance analytics">
            <div class="mb-3"><h2 class="font-semibold text-slate-900">Compliance analytics</h2><p class="mt-0.5 text-xs text-slate-500">Recorded OSDW assessments only. Unassessed activities are not included.</p></div>
            <div class="grid items-center gap-4 md:grid-cols-[minmax(220px,280px)_1fr]">
                <div @class(['relative mx-auto w-full max-w-[280px]', 'h-36' => $complianceTotal === 0, 'h-56' => $complianceTotal > 0])>
                    <canvas data-chart-type="doughnut" data-chart-data='@json($complianceChart)' role="img" aria-label="Recorded compliance assessment counts"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"><span class="text-3xl font-bold text-slate-900">{{ $complianceTotal }}</span><span class="text-xs text-slate-500">assessed</span></div>
                </div>
                <div class="grid items-stretch gap-3 sm:grid-cols-3">
                    @foreach($complianceLabels as $label => $assessment)
                        @php $assessmentCount = $monitoringAssessments[$assessment]; $assessmentPercent = $complianceTotal > 0 ? round(($assessmentCount / $complianceTotal) * 100, 1) : 0; @endphp
                        <a href="{{ route('admin.activities', array_merge($activityFilters, ['assessment' => $assessment, 'tab' => 'recent'])) }}" class="flex min-h-28 flex-col rounded-lg border border-slate-200 bg-white p-4 transition hover:border-orange-300 hover:bg-orange-50/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">
                            <p class="text-xs font-semibold leading-5 text-slate-700">{{ $label }}</p><p @class(['mt-2 text-2xl font-semibold tabular-nums', 'text-slate-400' => $assessmentCount === 0, 'text-slate-900' => $assessmentCount > 0])>{{ $assessmentCount }}</p><p class="mt-auto pt-2 text-xs text-slate-600">{{ number_format($assessmentPercent, 1) }}% · inspect activities</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid items-stretch gap-4 xl:grid-cols-2" aria-label="Monitoring charts">
            <article class="admin-panel flex h-full min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
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
            <article class="admin-panel flex h-full min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" x-data="{
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
                        <button type="button" @click="sortMode = 'name'" :aria-pressed="sortMode === 'name'" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 px-3 py-2 text-xs font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700" :class="sortMode === 'name' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'">A–Z</button>
                        <button type="button" @click="sortMode = 'completion'" :aria-pressed="sortMode === 'completion'" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 px-3 py-2 text-xs font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700" :class="sortMode === 'completion' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'">Highest completion</button>
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

        <details open class="admin-panel group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 [&::-webkit-details-marker]:hidden focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-orange-700">
                <h2 class="font-semibold text-slate-900">Recent Document Submissions</h2>
                <svg class="h-4 w-4 shrink-0 text-slate-500 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="max-w-full overflow-x-auto overscroll-x-contain">
                <table class="min-w-[840px] w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs uppercase text-slate-600"><tr><th class="px-4 py-3">Document</th><th class="px-4 py-3">Activity</th><th class="px-4 py-3">Organization</th><th class="px-4 py-3">Submitted</th><th class="px-4 py-3">Current status</th><th class="px-4 py-3">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($dashboardData['recentSubmissions'] as $submission)
                            <tr class="transition-colors hover:bg-orange-50/40"><td class="max-w-52 whitespace-normal break-words px-4 py-3 font-medium text-slate-900">{{ $submission['document'] }}</td><td class="max-w-64 whitespace-normal break-words px-4 py-3 text-slate-700">{{ $submission['activity'] }}</td><td class="max-w-56 whitespace-normal break-words px-4 py-3 text-slate-700">{{ $submission['organization'] }}</td><td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700">{{ $submission['submitted_at']?->format('M d, Y') ?? '—' }}</td><td class="px-4 py-3"><x-status-pill :status="$submission['status']" /></td><td class="px-4 py-3"><a href="{{ $submission['url'] }}" data-file-viewer data-title="{{ $submission['document'] }} – {{ $submission['activity'] }}" class="font-semibold text-orange-900 underline decoration-orange-200 underline-offset-2 hover:text-orange-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">View</a></td></tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No communication letters or narrative reports have been submitted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>

        <section class="admin-panel overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                <div>
                    <h2 class="font-semibold text-slate-900">Recently Updated Activities</h2>
                    <p class="text-xs text-slate-500">{{ $stats['Organizations'] }} organizations represented</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.activities.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Export CSV (data)</a>
                    <a href="{{ route('admin.activities.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-orange-800 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-orange-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Export Excel (report)</a>
                </div>
            </div>
            <div class="max-w-full overflow-x-auto overscroll-x-contain">
                <table class="min-w-[1120px] w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Activity</th>
                            <th class="px-4 py-3">Organization</th>
                            <th class="px-4 py-3">College</th>
                            <th class="px-4 py-3">Schedule</th>
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
                                $organizationCollege = $activity->gpoa?->college ?? $activity->gpoa?->user?->organization?->college ?? '—';
                                $activityRequest = $activity->activityRequest;
                                $letterStatus = $activity->letterStatusLabel();
                                $narrativeStatus = $activity->narrativeStatusLabel();
                            @endphp
                            <tr class="transition-colors hover:bg-orange-50/40">
                                <td class="max-w-64 whitespace-normal break-words px-4 py-3 font-medium text-slate-900">{{ $activity->title }}</td>
                                <td class="max-w-56 whitespace-normal break-words px-4 py-3 text-slate-700">@if($organizationId)<a href="{{ route('admin.activities', ['organization' => $organizationId]) }}" class="font-medium text-orange-900 underline decoration-orange-200 underline-offset-2 hover:text-orange-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">{{ $organizationName }}</a>@else{{ $organizationName }}@endif</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $organizationCollege }}</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700">{{ $activity->date?->format('M d, Y') ?? '—' }}@if($activity->end_date && $activity->end_date->ne($activity->date))<span class="block text-xs text-slate-600">through {{ $activity->end_date->format('M d, Y') }}</span>@endif</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700">{{ $activity->updated_at?->format('M d, Y') ?? '—' }}<span class="block text-xs text-slate-600">{{ $activity->updated_at?->diffForHumans() ?? '' }}</span></td>
                                <td class="px-4 py-3">
                                    <x-status-pill :status="$activity->monitoring_status" />
                                    @if($activity->monitoring_late)<div class="mt-1"><x-status-pill status="Late" /></div>@endif
                                </td>
                                <td class="px-4 py-3"><div class="flex flex-wrap items-center gap-2"><span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-1 text-[11px] {{ $letterStatus === 'Uploaded' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-600' }}">{{ $letterStatus === 'Uploaded' ? '✓' : '–' }} Letter</span><span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-1 text-[11px] {{ $narrativeStatus === 'Needs Revision' ? 'border-amber-200 bg-amber-50 text-amber-800' : (in_array($narrativeStatus, ['Approved', 'For Review', 'Rejected'], true) ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-600') }}">{{ $narrativeStatus === 'Needs Revision' ? '!' : (in_array($narrativeStatus, ['Approved', 'For Review', 'Rejected'], true) ? '✓' : '–') }} Report</span></div></td>
                                <td class="whitespace-nowrap px-4 py-3">@if($activityRequest)<a href="{{ route('admin.activity-requests.show', $activityRequest) }}" class="font-semibold text-orange-900 underline decoration-orange-200 underline-offset-2 hover:text-orange-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">View</a>@else<span class="text-slate-500">—</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">No planned activities have been submitted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
