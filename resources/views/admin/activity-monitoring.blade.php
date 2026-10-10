<x-app-layout>
    @php
        $tabQuery = request()->query();
        unset($tabQuery['status'], $tabQuery['page']);
        $recentUrl = route('admin.activities', array_merge($tabQuery, ['tab' => 'recent']));
        $todoUrl = route('admin.activities', array_merge($tabQuery, ['tab' => 'todo']));
        $backToMonitorUrl = route('admin.activities', request()->query());
        $statusFilter = request('status', '');
        $sort = request('sort', 'latest');
    @endphp

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8" x-data="{ activityDetailsOpen: false, activityDetails: {} }" @show-activity-details="activityDetails = $event.detail; activityDetailsOpen = true" @keydown.escape.window="activityDetailsOpen = false">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Activity Monitoring</h1>
                <p class="mt-1 text-sm text-slate-600">Review organizational submissions and compliance.</p>
            </div>
            <a href="{{ route('admin.activities.export', array_merge(['format' => 'excel'], request()->query())) }}" class="rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">Export CSV</a>
        </header>

        <nav class="flex border-b border-slate-200" aria-label="Activity lists">
            <a href="{{ $recentUrl }}" @class(['border-b-2 px-4 py-2 text-sm font-semibold', 'border-sky-700 text-sky-800' => $tab === 'recent', 'border-transparent text-slate-600 hover:text-slate-900' => $tab !== 'recent'])>Recent activity <span class="ml-1 text-xs text-slate-500">{{ $statusCounts['All'] }}</span></a>
            <a href="{{ $todoUrl }}" @class(['border-b-2 px-4 py-2 text-sm font-semibold', 'border-sky-700 text-sky-800' => $tab === 'todo', 'border-transparent text-slate-600 hover:text-slate-900' => $tab !== 'todo'])>Not yet started</a>
        </nav>

        <form method="GET" action="{{ route('admin.activities') }}" class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-300 bg-white p-2.5">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search activity or organization" class="min-w-[220px] flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none">
            @if($tab === 'recent')
                @foreach($statusCounts as $label => $count)
                    <button type="submit" name="status" value="{{ $label === 'All' ? '' : $label }}" @class(['rounded-full border px-3 py-1.5 text-xs font-semibold', 'border-sky-700 bg-sky-700 text-white' => ($label === 'All' && $statusFilter === '') || $statusFilter === $label, 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => !(($label === 'All' && $statusFilter === '') || $statusFilter === $label)])>{{ $label }} <span class="opacity-75">{{ $count }}</span></button>
                @endforeach
            @endif

            <div class="flex items-center gap-2 rounded-md border border-slate-300 bg-white">
                <label for="activitySort" class="sr-only">Sort activity list</label>
                <select id="activitySort" name="sort" onchange="this.form.submit()" class="rounded-md border-0 bg-transparent px-3 py-2 text-sm text-slate-700 focus:outline-none">
                    <option value="latest" @selected($sort !== 'activity_date')>Latest submission</option>
                    <option value="activity_date" @selected($sort === 'activity_date')>Activity date</option>
                </select>
            </div>

            <details class="relative">
                <summary class="cursor-pointer list-none rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</summary>
                <div class="absolute right-0 z-30 mt-2 w-[min(90vw,360px)] rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                    <div class="grid gap-3">
                        <select name="organization" aria-label="Organization" class="rounded-md border-slate-300 text-sm"><option value="">All organizations</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected((string) request('organization') === (string) $organization->id)>{{ $organization->org_name ?: $organization->name }}</option>@endforeach</select>
                        <select name="college" aria-label="College" class="rounded-md border-slate-300 text-sm"><option value="">All colleges</option>@foreach($colleges as $college)<option value="{{ $college }}" @selected(request('college') === $college)>{{ $college }}</option>@endforeach</select>
                        <select name="category" aria-label="Category" class="rounded-md border-slate-300 text-sm"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select>
                        <select name="term" aria-label="Term" class="rounded-md border-slate-300 text-sm"><option value="">All terms</option>@foreach($terms as $term)<option value="{{ $term }}" @selected(request('term') === $term)>{{ $term }}</option>@endforeach</select>
                        <select name="school_year" aria-label="School year" class="rounded-md border-slate-300 text-sm"><option value="">All school years</option>@foreach($schoolYears as $schoolYear)<option value="{{ $schoolYear }}" @selected(request('school_year') === $schoolYear)>{{ $schoolYear }}</option>@endforeach</select>
                    </div>
                </div>
            </details>

            <button type="submit" class="rounded-md bg-sky-700 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-800">Apply</button>
            <a href="{{ route('admin.activities', ['tab' => $tab]) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
        </form>

        <details class="rounded-lg border border-slate-200 bg-white">
            <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-slate-800">
                <span>Organization progress</span>
                <span class="inline-flex h-6 w-6 items-center justify-center rounded border border-slate-200 bg-slate-50 text-slate-500">⌃</span>
            </summary>
            <div class="divide-y divide-slate-100 px-4">
                @forelse($organizationProgress as $progress)
                    <div class="flex items-center gap-3 py-3">
                        <span class="w-48 shrink-0 truncate text-sm font-medium text-slate-800">{{ $progress['organization'] }}</span>
                        <progress max="100" value="{{ $progress['percent'] }}" class="h-2 min-w-0 flex-1 accent-emerald-600"></progress>
                        <span class="w-10 text-right text-xs font-semibold text-slate-600">{{ $progress['percent'] }}%</span>
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">No organization progress to show.</p>
                @endforelse
            </div>
        </details>

        @if($activities->isEmpty())
            <div class="rounded-lg border border-dashed border-slate-300 bg-white px-5 py-12 text-center text-sm text-slate-600">
                {{ $tab === 'todo' ? 'No planned activities are waiting for their first submission.' : 'No recent submissions match these filters.' }}
            </div>
        @else
            <div class="hidden overflow-hidden rounded-lg border border-slate-200 bg-white md:block">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Activity</th><th class="px-4 py-3">Activity date</th><th class="px-4 py-3">Documents</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">View</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($activities as $activity)
                            @php
                                $activityRequest = $activity->activityRequest;
                                $letterPresent = filled($activityRequest?->communication_letter);
                                $reportPresent = $activityRequest?->report && (filled($activityRequest->report->narrative_report) || filled($activityRequest->report->narrative_content));
                                $reportFilePresent = filled($activityRequest?->report?->narrative_report);
                                $viewUrl = $activityRequest
                                    ? route('admin.activity-requests.show', [$activityRequest, 'back' => $backToMonitorUrl])
                                    : route('admin.gpoa.show', $activity->gpoa_id);
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $activity->title }}</div>
                                    <div class="text-xs text-slate-500">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">Submitted by {{ $activity->gpoa?->user?->name ?? '—' }}</div>
                                    @if($activity->last_submitted_at)<time class="text-xs text-slate-500" datetime="{{ $activity->last_submitted_at->toIso8601String() }}" title="{{ $activity->last_submitted_at->format('M j, Y g:i A') }}">{{ $activity->last_submitted_at->diffForHumans() }}</time>@endif
                                    <details class="mt-1 text-xs text-slate-600"><summary class="cursor-pointer text-sky-700">Activity details and remarks</summary><div class="mt-2 space-y-1"><p>{{ $activity->category ?: 'Category not set' }} · {{ $activity->venue ?: 'Venue not set' }}</p><p>{{ $activity->gpoa?->term }} / {{ $activity->gpoa?->school_year }}</p>@if($activity->monitoringResult)<p>{{ $activity->monitoringResult->compliance_notes }}</p>@endif
                                        @if($activity->activityRequest && $activity->activityRequest->programFlows->isNotEmpty())
                                            <div class="pt-2">
                                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Program flow</p>
                                                <ul class="mt-1 list-disc pl-4 text-xs text-slate-600">
                                                    @foreach($activity->activityRequest->programFlows as $flow)
                                                        <li>{{ $flow->time ?: 'TBA' }} · {{ $flow->flow }}@if($flow->person_in_charge) ({{ $flow->person_in_charge }})@endif</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        <form method="POST" action="{{ route('admin.monitoring.record', $activity->id) }}" class="mt-3 grid gap-2 sm:grid-cols-[180px_1fr_auto]">@csrf
                                            <select name="compliance_status" required class="rounded border-slate-300 text-xs"><option value="">Assessment</option>@foreach(['aligned' => 'Aligned', 'partial' => 'Partially Aligned', 'not_aligned' => 'Not Aligned'] as $value => $label)<option value="{{ $value }}" @selected($activity->monitoringResult?->compliance_status === $value)>{{ $label }}</option>@endforeach</select>
                                            <input name="compliance_notes" maxlength="1000" value="{{ $activity->monitoringResult?->compliance_notes }}" placeholder="Optional remark" class="rounded border-slate-300 text-xs">
                                            <button type="submit" class="rounded bg-slate-700 px-2 py-1 text-xs font-semibold text-white">Save assessment</button>
                                        </form>
                                    </div></details>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $activity->date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-4 py-3"><div class="flex gap-2">
                                    <a @if($letterPresent) href="{{ route('admin.file.view', [$activityRequest->id, 'communication']) }}" data-file-viewer data-title="Communication Letter – {{ $activity->title }}" @else aria-disabled="true" @endif title="Communication letter{{ $letterPresent ? ' uploaded' : ' not uploaded' }}" class="inline-flex h-8 w-8 items-center justify-center rounded border {{ $letterPresent ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400' }}">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4.25 4.25v12.25H7A2.25 2.25 0 0 1 4.75 18V6A2.25 2.25 0 0 1 7 3.75ZM14 4v4h4M8.5 13h7m-7 3.5h7"/></svg><span class="sr-only">Communication letter</span>
                                    </a>
                                    <a @if($reportFilePresent) href="{{ route('admin.file.view', [$activityRequest->id, 'narrative']) }}" data-file-viewer data-title="Narrative Report – {{ $activity->title }}" @else aria-disabled="true" @endif title="Narrative report: {{ $activityRequest?->report?->reviewStatusLabel() ?? 'not submitted' }}" class="relative inline-flex h-8 w-8 items-center justify-center rounded border {{ $reportFilePresent ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400' }}">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4.25 4.25v12.25H7A2.25 2.25 0 0 1 4.75 18V6A2.25 2.25 0 0 1 7 3.75ZM14 4v4h4M8.5 13h7m-7 3.5h7"/></svg>
                                        @if($reportPresent)<span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-white" aria-label="Pending review"></span>@endif
                                        <span class="sr-only">Narrative report</span>
                                    </a>
                                </div></td>
                                <td class="px-4 py-3"><x-status-pill :status="$activity->monitoring_status" /> @if($activity->monitoring_late)<span class="ml-1"><x-status-pill status="Late" /></span>@endif</td>
                                <td class="px-4 py-3 text-right"><div class="flex justify-end gap-2"><a href="{{ $viewUrl }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">View</a>@if($activityRequest)<a href="{{ route('activity-requests.pdf', $activityRequest) }}" data-download-loading class="rounded border border-rose-200 px-2 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" title="Download activity PDF">PDF</a>@endif</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 md:hidden">
                @foreach($activities as $activity)
                    @php
                        $activityRequest = $activity->activityRequest;
                        $letterPresent = filled($activityRequest?->communication_letter);
                        $reportPresent = $activityRequest?->report && (filled($activityRequest->report->narrative_report) || filled($activityRequest->report->narrative_content));
                        $reportFilePresent = filled($activityRequest?->report?->narrative_report);
                        $viewUrl = $activityRequest
                            ? route('admin.activity-requests.show', [$activityRequest, 'back' => $backToMonitorUrl])
                            : route('admin.gpoa.show', $activity->gpoa_id);
                    @endphp
                    <article class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">{{ $activity->title }}</h2><p class="text-xs text-slate-500">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</p><p class="text-xs text-slate-500">Submitted by {{ $activity->gpoa?->user?->name ?? '—' }}</p>@if($activity->last_submitted_at)<time class="text-xs text-slate-500" title="{{ $activity->last_submitted_at->format('M j, Y g:i A') }}">{{ $activity->last_submitted_at->diffForHumans() }}</time>@endif</div><x-status-pill :status="$activity->monitoring_status" /></div>
                        <p class="text-sm text-slate-700">{{ $activity->date?->format('M j, Y') ?? '—' }}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex gap-2">
                                @if($letterPresent)<a href="{{ route('admin.file.view', [$activityRequest->id, 'communication']) }}" data-file-viewer data-title="Communication Letter – {{ $activity->title }}" class="text-xs font-semibold text-emerald-700 underline">Letter</a>@else<span class="text-xs text-slate-400">Letter missing</span>@endif
                                @if($reportFilePresent)<a href="{{ route('admin.file.view', [$activityRequest->id, 'narrative']) }}" data-file-viewer data-title="Narrative Report – {{ $activity->title }}" class="relative text-xs font-semibold text-sky-700 underline">Report @if($reportPresent)<span class="text-amber-600" aria-label="Pending review">●</span>@endif</a>@elseif($reportPresent)<span class="text-xs text-slate-600">Report <span class="text-amber-600" aria-label="Pending review">●</span></span>@else<span class="text-xs text-slate-400">Report missing</span>@endif
                            </div>
                            <div class="flex gap-2"><a href="{{ $viewUrl }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">View</a>@if($activityRequest)<a href="{{ route('activity-requests.pdf', $activityRequest) }}" data-download-loading class="rounded border border-rose-200 px-2 py-1.5 text-xs font-semibold text-rose-700" title="Download activity PDF">PDF</a>@endif</div>
                        </div>
                        <details class="text-xs text-slate-600"><summary class="cursor-pointer text-sky-700">More details and quick remark</summary><p class="mt-2">{{ $activity->category ?: 'Category not set' }} · {{ $activity->venue ?: 'Venue not set' }} · {{ $activity->gpoa?->term }} / {{ $activity->gpoa?->school_year }}</p>@if($activity->monitoringResult)<p class="mt-1">{{ $activity->monitoringResult->compliance_notes }}</p>@endif
                            <form method="POST" action="{{ route('admin.monitoring.record', $activity->id) }}" class="mt-3 grid gap-2">@csrf<select name="compliance_status" required class="rounded border-slate-300 text-xs"><option value="">Assessment</option>@foreach(['aligned' => 'Aligned', 'partial' => 'Partially Aligned', 'not_aligned' => 'Not Aligned'] as $value => $label)<option value="{{ $value }}" @selected($activity->monitoringResult?->compliance_status === $value)>{{ $label }}</option>@endforeach</select><input name="compliance_notes" maxlength="1000" value="{{ $activity->monitoringResult?->compliance_notes }}" placeholder="Optional remark" class="rounded border-slate-300 text-xs"><button type="submit" class="w-fit rounded bg-slate-700 px-2 py-1 text-xs font-semibold text-white">Save assessment</button></form>
                        </details>
                    </article>
                @endforeach
            </div>
        @endif
        <div>{{ $activities->links() }}</div>

        <div x-cloak x-show="activityDetailsOpen" x-transition.opacity class="fixed inset-0 z-[90] overflow-y-auto bg-slate-950/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="adminActivityDetailsTitle" @click.self="activityDetailsOpen = false">
            <section class="mx-auto my-4 max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white shadow-2xl sm:my-8" @click.stop>
                <header class="sticky top-0 z-10 flex items-start gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                    <div class="min-w-0 flex-1"><h2 id="adminActivityDetailsTitle" class="text-lg font-bold text-slate-900" x-text="activityDetails.title"></h2><p class="mt-0.5 text-sm text-slate-500" x-text="activityDetails.organization"></p></div>
                    <button type="button" @click="activityDetailsOpen = false" class="rounded border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700">Close</button>
                </header>
                <div class="space-y-5 p-4 sm:p-6">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">College</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.college"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Category</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.category"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Venue</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.venue"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Date</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.date"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Status</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.status"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Term / School year</dt><dd class="mt-1 text-slate-900"><span x-text="activityDetails.term"></span> / <span x-text="activityDetails.schoolYear"></span></dd></div>
                    </dl>

                    <section>
                        <h3 class="text-sm font-semibold text-slate-900">Documents</h3>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                            <template x-if="activityDetails.letterUrl"><a :href="activityDetails.letterUrl" data-file-viewer :data-title="'Communication Letter – ' + activityDetails.title" class="font-semibold text-sky-700 underline">Communication Letter</a></template>
                            <span x-show="!activityDetails.letterUrl" class="text-slate-500">Communication Letter missing</span>
                            <template x-if="activityDetails.reportUrl"><a :href="activityDetails.reportUrl" data-file-viewer :data-title="'Narrative Report – ' + activityDetails.title" class="font-semibold text-sky-700 underline">Narrative Report</a></template>
                            <span x-show="!activityDetails.reportUrl" class="text-slate-500">Narrative Report missing</span>
                            <template x-if="activityDetails.attendanceUrl"><a :href="activityDetails.attendanceUrl" data-file-viewer :data-title="'Attendance Sheet – ' + activityDetails.title" class="font-semibold text-sky-700 underline">Attendance Sheet</a></template>
                            <template x-for="(photo, index) in activityDetails.photos || []" :key="photo.url"><a :href="photo.url" data-file-viewer :data-title="photo.title + ' – ' + activityDetails.title" class="font-semibold text-sky-700 underline" x-text="photo.title + ' ' + (index + 1)"></a></template>
                        </div>
                        <p class="mt-2 text-xs text-slate-600">Communication letter: <span class="font-medium" x-text="activityDetails.letterStatus"></span> · Narrative report: <span class="font-medium" x-text="activityDetails.reportStatus"></span></p>
                    </section>

                    <section>
                        <h3 class="text-sm font-semibold text-slate-900">Program flow</h3>
                        <ol class="mt-2 space-y-1 text-sm text-slate-700"><template x-for="(flow, index) in activityDetails.programFlows || []" :key="index"><li><span class="font-medium" x-text="flow.time"></span> · <span x-text="flow.flow"></span><template x-if="flow.person"><span> (<span x-text="flow.person"></span>)</span></template></li></template></ol>
                        <p x-show="!activityDetails.programFlows || activityDetails.programFlows.length === 0" class="mt-2 text-sm text-slate-500">No program flow added.</p>
                    </section>

                    <section><h3 class="text-sm font-semibold text-slate-900">Monitoring remark</h3><p class="mt-1 whitespace-pre-wrap text-sm text-slate-700" x-text="activityDetails.remark"></p></section>

                    <div class="grid gap-4 border-t border-slate-200 pt-4 sm:grid-cols-2">
                        <form method="POST" :action="activityDetails.monitoringUrl" class="space-y-2">@csrf
                            <h3 class="text-sm font-semibold text-slate-900">Assessment / remark</h3>
                            <select name="compliance_status" x-model="activityDetails.complianceStatus" required class="w-full rounded-md border-slate-300 text-sm"><option value="">Assessment</option><option value="aligned">Aligned</option><option value="partial">Partially Aligned</option><option value="not_aligned">Not Aligned</option></select>
                            <textarea name="compliance_notes" maxlength="1000" x-model="activityDetails.remarkInput" placeholder="Optional remark" class="w-full rounded-md border-slate-300 text-sm"></textarea>
                            <button type="submit" class="rounded-md bg-slate-800 px-3 py-2 text-sm font-semibold text-white">Save assessment</button>
                        </form>
                        <div class="space-y-3">
                            <div class="flex flex-wrap gap-2">
                                <form x-show="activityDetails.archived" method="POST" :action="activityDetails.restoreUrl">@csrf<button type="submit" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Restore</button></form>
                                <form x-show="!activityDetails.archived && activityDetails.canArchive" method="POST" :action="activityDetails.archiveUrl" data-confirm data-confirm-title="Archive this activity?" data-confirm-message="Archived activities are excluded from the totals. You can restore it later." data-confirm-label="Archive" data-confirm-variant="warning">@csrf<button type="submit" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Archive</button></form>
                                <a :href="activityDetails.fullPageUrl" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Open full page</a>
                            </div>
                            <template x-if="activityDetails.reportReview === 'For Review'">
                                <div x-data="{ revisionOpen: {{ $errors->has('feedback') ? 'true' : 'false' }}, feedbackLength: {{ strlen(old('feedback', '')) }} }" class="space-y-3 rounded-md border border-slate-200 p-3">
                                    <div class="flex flex-wrap items-center gap-2"><p class="text-sm font-semibold text-slate-900">Narrative report awaiting review</p><span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">For Review</span></div>
                                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                        <form method="POST" :action="activityDetails.approveUrl" data-confirm data-confirm-title="Approve narrative report?" data-confirm-message="The activity will be marked Completed." data-confirm-label="Approve" data-confirm-variant="primary">@csrf<button type="submit" class="min-h-10 w-full rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white sm:w-auto">Approve</button></form>
                                        <button type="button" @click="revisionOpen = !revisionOpen" :aria-expanded="revisionOpen.toString()" aria-controls="activity-details-revision-panel" class="min-h-10 w-full rounded-md border border-amber-600 bg-white px-3 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50 sm:w-auto">Return for revision</button>
                                    </div>
                                    <p class="text-xs text-slate-500">Returning a report sends your feedback to the organization so they can fix and resubmit.</p>
                                    <div id="activity-details-revision-panel" x-show="revisionOpen" x-cloak x-transition class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                        <form method="POST" :action="activityDetails.revisionUrl" class="space-y-3" @submit="const button = $el.querySelector('[data-revision-submit]'); button.disabled = true; button.textContent = 'Sending…'">@csrf
                                            <label class="block text-sm font-semibold text-slate-800">Reason for revision (sent to the organization)</label>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" @click="const separator = $refs.activityFeedback.value.trim() ? '\n' : ''; $refs.activityFeedback.value += separator + 'Incomplete content'; feedbackLength = $refs.activityFeedback.value.length; $refs.activityFeedback.dispatchEvent(new Event('input', { bubbles: true }));" class="min-h-10 rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-100">Incomplete content</button>
                                                <button type="button" @click="const separator = $refs.activityFeedback.value.trim() ? '\n' : ''; $refs.activityFeedback.value += separator + 'Needs correction'; feedbackLength = $refs.activityFeedback.value.length; $refs.activityFeedback.dispatchEvent(new Event('input', { bubbles: true }));" class="min-h-10 rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-100">Needs correction</button>
                                                <button type="button" @click="const separator = $refs.activityFeedback.value.trim() ? '\n' : ''; $refs.activityFeedback.value += separator + 'Missing photos or attachments'; feedbackLength = $refs.activityFeedback.value.length; $refs.activityFeedback.dispatchEvent(new Event('input', { bubbles: true }));" class="min-h-10 rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-100">Missing photos or attachments</button>
                                                <button type="button" @click="const separator = $refs.activityFeedback.value.trim() ? '\n' : ''; $refs.activityFeedback.value += separator + 'Wrong format'; feedbackLength = $refs.activityFeedback.value.length; $refs.activityFeedback.dispatchEvent(new Event('input', { bubbles: true }));" class="min-h-10 rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-100">Wrong format</button>
                                            </div>
                                            <textarea x-ref="activityFeedback" name="feedback" required minlength="10" maxlength="1000" rows="4" @input="feedbackLength = $event.target.value.length" placeholder="Explain what needs to be revised" class="w-full rounded-md border-slate-300 text-sm">{{ old('feedback') }}</textarea>
                                            @error('feedback')<p class="text-xs text-rose-700" role="alert">{{ $message }}</p>@enderror
                                            @error('feedback')<p class="text-xs text-rose-700" role="alert">{{ $message }}</p>@enderror
                                            <div class="flex justify-end"><span class="text-xs tabular-nums text-slate-500"><span x-text="feedbackLength">0</span> / 1000</span></div>
                                            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                                <button type="button" @click="revisionOpen = false" class="min-h-10 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                                                <button type="submit" data-revision-submit class="min-h-10 rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Return to organization</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</x-app-layout>