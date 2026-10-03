<x-app-layout>
    @php
        $tabQuery = request()->query();
        unset($tabQuery['status'], $tabQuery['page']);
        $submittedUrl = route('activity-monitor.index', array_merge($tabQuery, ['tab' => 'submitted']));
        $todoUrl = route('activity-monitor.index', array_merge($tabQuery, ['tab' => 'todo']));
    @endphp

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8" x-data="{ activityDetailsOpen: false, activityDetails: {} }" @show-activity-details="activityDetails = $event.detail; activityDetailsOpen = true" @keydown.escape.window="activityDetailsOpen = false">
        @if($errors->has('activity_date'))
            <div role="alert" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first('activity_date') }}</div>
        @endif
        @if($outstandingReports->isNotEmpty())
            <section x-data="{ visible: true }" x-show="visible" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-amber-950" role="status">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">You have {{ $outstandingReports->count() }} activity report{{ $outstandingReports->count() === 1 ? '' : 's' }} to submit before requesting a new activity</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm">
                            @foreach($outstandingReports as $outstandingReport)
                                <li><a href="{{ $outstandingReport['url'] }}" class="font-semibold underline">Submit report</a> <span>for {{ $outstandingReport['title'] }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" @click="visible = false" aria-label="Dismiss outstanding reports notice" class="shrink-0 rounded px-2 py-1 font-semibold hover:bg-amber-100">×</button>
                </div>
            </section>
        @endif
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Activity Monitor</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $term }} / SY {{ $schoolYear }} · {{ auth()->user()->org_name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('gpoa.create') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">GPOA</a>
                @if($outstandingReports->isNotEmpty())
                    <button type="button" disabled title="{{ $outstandingReports->first()['title'] }}" class="cursor-not-allowed rounded-md bg-slate-300 px-3 py-2 text-sm font-semibold text-slate-600">Submit pending report first</button>
                @else
                    <a href="{{ route('activity-requests.create') }}" class="rounded-md bg-sky-700 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-800">Request activity</a>
                @endif
            </div>
        </header>

        <nav class="flex border-b border-slate-200" aria-label="Activity list">
            <a href="{{ $submittedUrl }}" @class(['border-b-2 px-4 py-2 text-sm font-semibold', 'border-sky-700 text-sky-800' => $tab === 'submitted', 'border-transparent text-slate-600 hover:text-slate-900' => $tab !== 'submitted'])>Submitted</a>
            <a href="{{ $todoUrl }}" @class(['border-b-2 px-4 py-2 text-sm font-semibold', 'border-sky-700 text-sky-800' => $tab === 'todo', 'border-transparent text-slate-600 hover:text-slate-900' => $tab !== 'todo'])>To do</a>
        </nav>

        <form method="GET" action="{{ route('activity-monitor.index') }}" class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white p-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="search" name="search" value="{{ $search }}" placeholder="Search your activities" class="min-w-[180px] flex-1 rounded-md border-slate-300 text-sm">
            @if($tab === 'submitted')
                @foreach($statusCounts as $label => $count)
                    <button type="submit" name="status" value="{{ $label === 'All' ? '' : $label }}" @class(['rounded-full border px-3 py-1.5 text-xs font-semibold', 'border-sky-700 bg-sky-700 text-white' => ($label === 'All' && $statusFilter === '') || $statusFilter === $label, 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => !(($label === 'All' && $statusFilter === '') || $statusFilter === $label)])>{{ $label }} <span class="opacity-75">{{ $count }}</span></button>
                @endforeach
                <select name="sort" aria-label="Sort submissions" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
                    <option value="latest" @selected($sort !== 'activity_date')>Latest submission</option>
                    <option value="activity_date" @selected($sort === 'activity_date')>Activity date</option>
                </select>
            @endif
            <button type="submit" class="rounded-md bg-sky-700 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-800">Apply</button>
            <a href="{{ route('activity-monitor.index', ['tab' => $tab]) }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
        </form>

        @if($activities->isEmpty())
            @if($tab === 'submitted' && $pendingActivities->isNotEmpty())
                <section class="rounded-lg border border-dashed border-slate-300 bg-white px-5 py-8">
                    <h2 class="font-semibold text-slate-900">No submissions yet</h2>
                    <p class="mt-1 text-sm text-slate-500">Start a planned activity from your To do list to begin monitoring.</p>
                    @if($outstandingReports->isNotEmpty())
                        <div class="mt-4">
                            <button type="button" disabled title="{{ $outstandingReports->first()['title'] }}" class="cursor-not-allowed rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-500">Submit your pending report first</button>
                            <p class="mt-2 text-sm text-slate-600">{{ $outstandingReports->first()['title'] }}@if(!empty($outstandingReports->first()['url'])) <a href="{{ $outstandingReports->first()['url'] }}" class="font-semibold text-sky-700 underline">Submit report</a>@endif</p>
                        </div>
                    @else
                        <ul class="mt-4 space-y-2">
                            @foreach($pendingActivities->sortBy('date')->take(5) as $activity)
                                <li class="flex items-center justify-between gap-3 rounded-md border border-slate-200 px-3 py-2">
                                    <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
                                        <span class="truncate text-sm font-medium text-slate-800">{{ $activity->title }}</span>
                                        <span class="shrink-0 text-xs text-slate-500">{{ $activity->date?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <a href="{{ route('activity-requests.create-from-activity', $activity) }}" class="inline-flex shrink-0 items-center whitespace-nowrap rounded-md border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-100">Start request</a>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ $todoUrl }}" class="mt-3 inline-block text-sm font-semibold text-sky-700 hover:text-sky-800">View all {{ $pendingActivities->count() }} planned activities in To do &rarr;</a>
                    @endif
                </section>
            @else
                <section class="rounded-lg border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                    <h2 class="font-semibold text-slate-900">{{ $tab === 'todo' ? 'You are all caught up' : 'No submissions yet' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $tab === 'todo' ? 'No planned activities are waiting for their first document.' : 'Activities will appear here when you upload a communication letter or report.' }}</p>
                    @if($tab === 'todo')<a href="{{ route('gpoa.index') }}" class="mt-4 inline-flex rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">View GPOA</a>@endif
                </section>
            @endif
        @else
            <div class="hidden overflow-hidden rounded-lg border border-slate-200 bg-white md:block">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Activity</th><th class="px-4 py-3">Date</th><th class="whitespace-nowrap px-4 py-3">Documents</th><th class="px-4 py-3">Status</th><th class="min-w-[170px] px-4 py-3 text-right">Next action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($activities as $activity)
                            @php
                                $activityRequest = $activity->activityRequest;
                                $letterPresent = filled($activityRequest?->communication_letter);
                                $reportPresent = $activityRequest?->report && (filled($activityRequest->report->narrative_report) || filled($activityRequest->report->narrative_content));
                                $viewUrl = $activityRequest ? route('activity-requests.show', $activityRequest) : route('activity-requests.create-from-activity', $activity);
                                $createFromActivityUrl = route('activity-requests.create-from-activity', $activity);
                                $reportDate = $activity->end_date ?? $activity->date;
                            @endphp
                            <tr>
                                <td class="px-4 py-3"><div class="font-semibold text-slate-900">{{ $activities->firstItem() + $loop->index }}. {{ $activity->title }}</div>@if($activity->last_submitted_at)<time class="text-xs text-slate-500" datetime="{{ $activity->last_submitted_at->toIso8601String() }}" title="{{ $activity->last_submitted_at->format('M j, Y g:i A') }}">{{ $activity->last_submitted_at->diffForHumans() }}</time>@endif</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $activity->date?->format('M d, Y') ?? '—' }}@if($activity->end_date && $activity->end_date->ne($activity->date))<span class="block whitespace-nowrap text-sm text-slate-500">to {{ $activity->end_date->format('M d, Y') }}</span>@endif</td>
                                <td class="whitespace-nowrap px-4 py-3"><div class="flex gap-2 whitespace-nowrap"><span title="Communication letter {{ $letterPresent ? 'uploaded' : 'not uploaded' }}" class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] {{ $letterPresent ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">{{ $letterPresent ? '✓' : '–' }} Letter</span><span title="Narrative report {{ $reportPresent ? 'submitted' : 'not submitted' }}" class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] {{ $activityRequest?->report?->status === 'needs_revision' ? 'border-amber-200 bg-amber-50 text-amber-700' : ($reportPresent ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500') }}">{{ $activityRequest?->report?->status === 'needs_revision' ? '!' : ($reportPresent ? '✓' : '–') }} Report</span></div></td>
                                <td class="px-4 py-3"><x-status-pill :status="$activity->monitor_status" /> @if($activity->monitor_late)<span class="ml-1"><x-status-pill status="Late" /></span>@endif</td>
                                <td class="min-w-[170px] px-4 py-3 text-right">
                                    <div class="flex flex-nowrap items-center justify-end gap-2 whitespace-nowrap">
                                    @if($activityRequest)
                                        <a href="{{ route('activity-requests.pdf', $activityRequest) }}" class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">PDF</a>
                                    @endif
                                    @if($activity->archived_at)
                                        <form method="POST" action="{{ route('activities.restore', $activity) }}" class="inline-flex">@csrf<button class="inline-flex items-center justify-center whitespace-nowrap rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Restore</button></form>
                                    @elseif($activity->monitor_status === 'Completed')
                                        <form method="POST" action="{{ route('activities.archive', $activity) }}" class="inline-flex" data-confirm data-confirm-title="Archive this activity?" data-confirm-message="Archived activities are excluded from the totals. You can restore it later." data-confirm-label="Archive" data-confirm-variant="warning">@csrf<button class="inline-flex items-center justify-center whitespace-nowrap rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Archive</button></form>
                                    @elseif(!$activityRequest)
                                        @if($outstandingReports->isNotEmpty())<button type="button" disabled title="{{ $outstandingReports->first()['title'] }}" class="inline-flex items-center justify-center whitespace-nowrap cursor-not-allowed rounded-md bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-500">Submit pending report first</button>@else<a href="{{ $createFromActivityUrl }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white">Start request</a>@endif
                                    @elseif(!$letterPresent)
                                        <a href="{{ $createFromActivityUrl }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white">Upload letter</a>
                                    @elseif($activityRequest->report?->status === 'needs_revision')
                                        <a href="{{ route('activity-reports.create', $activityRequest) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-amber-700 px-3 py-1.5 text-xs font-semibold text-white">Fix &amp; resubmit</a>
                                    @elseif(!$reportPresent)
                                        <a href="{{ route('activity-reports.create', $activityRequest) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Submit report</a>
                                    @else
                                        <button type="button" @click="$dispatch('show-activity-details', @js([
                                            'title' => $activityRequest->title ?? $activity->title,
                                            'organization' => auth()->user()->org_name ?? auth()->user()->name,
                                            'college' => $activity->gpoa?->college ?? '—',
                                            'category' => $activityRequest->category ?? $activity->category ?? '—',
                                            'venue' => $activityRequest->venue ?? $activity->venue ?? '—',
                                            'date' => ($activityRequest->date ?? $activity->date)?->format('M j, Y') ?? '—',
                                            'endDate' => ($activityRequest->end_date ?? $activity->end_date)?->format('M j, Y'),
                                            'status' => $activity->monitor_status . ($activity->monitor_late ? ' · Late' : ''),
                                            'term' => $activity->gpoa?->term ?? '—',
                                            'schoolYear' => $activity->gpoa?->school_year ?? '—',
                                            'letterStatus' => $letterPresent ? 'Uploaded' : 'Missing',
                                            'letterUrl' => $letterPresent ? route('activity-requests.documents.show', [$activityRequest, 'communication-letter']) : null,
                                            'reportStatus' => $activityRequest->report?->reviewStatusLabel() ?? 'Not submitted',
                                            'reportUrl' => filled($activityRequest->report?->narrative_report) ? route('activity-requests.documents.show', [$activityRequest, 'narrative-report']) : null,
                                            'attendanceUrl' => filled($activityRequest->report?->attendance_sheet_path) ? route('activity-requests.documents.show', [$activityRequest, 'attendance-sheet']) : null,
                                            'photos' => $activityRequest->report?->photos->map(fn ($photo, $index) => ['url' => route('activity-requests.report-photos.show', [$activityRequest, $photo]), 'title' => 'Activity Photo ' . ($index + 1)])->values()->all() ?? [],
                                            'programFlows' => $activityRequest->programFlows->map(fn ($flow) => ['time' => $flow->time ?: 'TBA', 'flow' => $flow->flow, 'person' => $flow->person_in_charge])->values()->all(),
                                            'remark' => $activity->monitoringResult?->compliance_notes ?: 'No monitoring remark recorded.',
                                            'fullPageUrl' => $viewUrl,
                                        ]))" class="inline-flex items-center justify-center whitespace-nowrap rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">View</button>
                                    @endif
                                    </div>
                                </td>
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
                        $reportDate = $activity->end_date ?? $activity->date;
                        $createFromActivityUrl = route('activity-requests.create-from-activity', $activity);
                    @endphp
                    <article class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">{{ $activities->firstItem() + $loop->index }}. {{ $activity->title }}</h2><p class="whitespace-nowrap text-sm text-slate-700">{{ $activity->date?->format('M d, Y') ?? '—' }}@if($activity->end_date && $activity->end_date->ne($activity->date))<span class="block whitespace-nowrap text-sm text-slate-500">to {{ $activity->end_date->format('M d, Y') }}</span>@endif</p>@if($activity->last_submitted_at)<time class="text-xs text-slate-500" title="{{ $activity->last_submitted_at->format('M j, Y g:i A') }}">{{ $activity->last_submitted_at->diffForHumans() }}</time>@endif</div><x-status-pill :status="$activity->monitor_status" /></div>
                        <div class="flex items-center justify-between gap-2"><div class="flex flex-nowrap gap-2 whitespace-nowrap"><span title="Communication letter {{ $letterPresent ? 'uploaded' : 'not uploaded' }}" class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] {{ $letterPresent ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">{{ $letterPresent ? '✓' : '–' }} Letter</span><span title="Narrative report {{ $reportPresent ? 'submitted' : 'not submitted' }}" class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] {{ $activityRequest?->report?->status === 'needs_revision' ? 'border-amber-200 bg-amber-50 text-amber-700' : ($reportPresent ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500') }}">{{ $activityRequest?->report?->status === 'needs_revision' ? '!' : ($reportPresent ? '✓' : '–') }} Report</span></div><div class="flex flex-nowrap items-center justify-end gap-2 whitespace-nowrap">@if($activityRequest)<a href="{{ route('activity-requests.pdf', $activityRequest) }}" class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700">PDF</a>@endif
                            @if($activity->archived_at)<form method="POST" action="{{ route('activities.restore', $activity) }}" class="inline-flex">@csrf<button class="inline-flex items-center justify-center whitespace-nowrap rounded-md border px-3 py-1.5 text-xs font-semibold">Restore</button></form>
                            @elseif($activity->monitor_status === 'Completed')<form method="POST" action="{{ route('activities.archive', $activity) }}" class="inline-flex" data-confirm data-confirm-title="Archive this activity?" data-confirm-message="Archived activities are excluded from the totals. You can restore it later." data-confirm-label="Archive" data-confirm-variant="warning">@csrf<button class="inline-flex items-center justify-center whitespace-nowrap rounded-md border px-3 py-1.5 text-xs font-semibold">Archive</button></form>
                            @elseif(!$activityRequest)@if($outstandingReports->isNotEmpty())<button type="button" disabled title="{{ $outstandingReports->first()['title'] }}" class="inline-flex items-center justify-center whitespace-nowrap cursor-not-allowed rounded-md bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-500">Submit pending report first</button>@else<a href="{{ $createFromActivityUrl }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white">Start request</a>@endif
                            @elseif(!$letterPresent)<a href="{{ $createFromActivityUrl }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white">Upload letter</a>
                            @elseif($activityRequest->report?->status === 'needs_revision')<a href="{{ route('activity-reports.create', $activityRequest) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-amber-700 px-3 py-1.5 text-xs font-semibold text-white">Fix &amp; resubmit</a>
                            @elseif(!$reportPresent)<a href="{{ route('activity-reports.create', $activityRequest) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Submit report</a>
                            @else
                                <button type="button" @click="$dispatch('show-activity-details', @js([
                                    'title' => $activityRequest->title ?? $activity->title,
                                    'organization' => auth()->user()->org_name ?? auth()->user()->name,
                                    'college' => $activity->gpoa?->college ?? '—',
                                    'category' => $activityRequest->category ?? $activity->category ?? '—',
                                    'venue' => $activityRequest->venue ?? $activity->venue ?? '—',
                                    'date' => ($activityRequest->date ?? $activity->date)?->format('M j, Y') ?? '—',
                                    'endDate' => ($activityRequest->end_date ?? $activity->end_date)?->format('M j, Y'),
                                    'status' => $activity->monitor_status . ($activity->monitor_late ? ' · Late' : ''),
                                    'term' => $activity->gpoa?->term ?? '—',
                                    'schoolYear' => $activity->gpoa?->school_year ?? '—',
                                    'letterStatus' => $letterPresent ? 'Uploaded' : 'Missing',
                                    'letterUrl' => $letterPresent ? route('activity-requests.documents.show', [$activityRequest, 'communication-letter']) : null,
                                    'reportStatus' => $activityRequest->report?->reviewStatusLabel() ?? 'Not submitted',
                                    'reportUrl' => filled($activityRequest->report?->narrative_report) ? route('activity-requests.documents.show', [$activityRequest, 'narrative-report']) : null,
                                    'attendanceUrl' => filled($activityRequest->report?->attendance_sheet_path) ? route('activity-requests.documents.show', [$activityRequest, 'attendance-sheet']) : null,
                                    'photos' => $activityRequest->report?->photos->map(fn ($photo, $index) => ['url' => route('activity-requests.report-photos.show', [$activityRequest, $photo]), 'title' => 'Activity Photo ' . ($index + 1)])->values()->all() ?? [],
                                    'programFlows' => $activityRequest->programFlows->map(fn ($flow) => ['time' => $flow->time ?: 'TBA', 'flow' => $flow->flow, 'person' => $flow->person_in_charge])->values()->all(),
                                    'remark' => $activity->monitoringResult?->compliance_notes ?: 'No monitoring remark recorded.',
                                    'fullPageUrl' => route('activity-requests.show', $activityRequest),
                                ]))" class="inline-flex items-center justify-center whitespace-nowrap rounded-md border px-3 py-1.5 text-xs font-semibold">View</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
        <div>{{ $activities->links() }}</div>

        <div x-cloak x-show="activityDetailsOpen" x-transition.opacity class="fixed inset-0 z-[90] overflow-y-auto bg-slate-950/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="userActivityDetailsTitle" @click.self="activityDetailsOpen = false">
            <section class="mx-auto my-4 max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white shadow-2xl sm:my-8" @click.stop>
                <header class="sticky top-0 z-10 flex items-start gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                    <div class="min-w-0 flex-1"><h2 id="userActivityDetailsTitle" class="text-lg font-bold text-slate-900" x-text="activityDetails.title"></h2><p class="mt-0.5 text-sm text-slate-500" x-text="activityDetails.organization"></p></div>
                    <button type="button" @click="activityDetailsOpen = false" class="rounded border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700">Close</button>
                </header>
                <div class="space-y-5 p-4 sm:p-6">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">College</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.college"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Category</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.category"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Venue</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.venue"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Date</dt><dd class="mt-1 text-slate-900"><span x-text="activityDetails.date"></span><template x-if="activityDetails.endDate"><span> to <span x-text="activityDetails.endDate"></span></span></template></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Status</dt><dd class="mt-1 text-slate-900" x-text="activityDetails.status"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Term / School year</dt><dd class="mt-1 text-slate-900"><span x-text="activityDetails.term"></span> / <span x-text="activityDetails.schoolYear"></span></dd></div>
                    </dl>
                    <section>
                        <h3 class="text-sm font-semibold text-slate-900">Documents</h3>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                            <template x-if="activityDetails.letterUrl"><a :href="activityDetails.letterUrl" data-file-viewer :data-title="'Communication Letter – ' + activityDetails.title" class="font-semibold text-sky-700 underline">Communication Letter</a></template>
                            <span x-show="!activityDetails.letterUrl" class="text-slate-500">Communication Letter missing</span>
                            <template x-if="activityDetails.reportUrl"><a :href="activityDetails.reportUrl" data-file-viewer :data-title="'Narrative Report – ' + activityDetails.title" class="font-semibold text-sky-700 underline">Narrative Report</a></template>
                            <template x-if="activityDetails.attendanceUrl"><a :href="activityDetails.attendanceUrl" data-file-viewer :data-title="'Attendance Sheet – ' + activityDetails.title" class="font-semibold text-sky-700 underline">Attendance Sheet</a></template>
                            <template x-for="photo in activityDetails.photos || []" :key="photo.url"><a :href="photo.url" data-file-viewer :data-title="photo.title + ' – ' + activityDetails.title" class="font-semibold text-sky-700 underline" x-text="photo.title"></a></template>
                            <span x-show="!activityDetails.reportUrl && !activityDetails.attendanceUrl && (!activityDetails.photos || activityDetails.photos.length === 0)" class="text-slate-500">No report documents uploaded</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-600">Communication letter: <span class="font-medium" x-text="activityDetails.letterStatus"></span> · Narrative report: <span class="font-medium" x-text="activityDetails.reportStatus"></span></p>
                    </section>
                    <section>
                        <h3 class="text-sm font-semibold text-slate-900">Program flow</h3>
                        <ol class="mt-2 space-y-1 text-sm text-slate-700"><template x-for="(flow, index) in activityDetails.programFlows || []" :key="index"><li><span class="font-medium" x-text="flow.time"></span> · <span x-text="flow.flow"></span><template x-if="flow.person"><span> (<span x-text="flow.person"></span>)</span></template></li></template></ol>
                        <p x-show="!activityDetails.programFlows || activityDetails.programFlows.length === 0" class="mt-2 text-sm text-slate-500">No program flow added.</p>
                    </section>
                    <section><h3 class="text-sm font-semibold text-slate-900">Monitoring remark</h3><p class="mt-1 whitespace-pre-wrap text-sm text-slate-700" x-text="activityDetails.remark"></p></section>
                    <footer class="border-t border-slate-200 pt-4"><a :href="activityDetails.fullPageUrl" class="inline-flex rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Open full page</a></footer>
                </div>
            </section>
        </div>
    </main>
</x-app-layout>