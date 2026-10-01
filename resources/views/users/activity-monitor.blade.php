<x-app-layout>
    @php
        $tabQuery = request()->query();
        unset($tabQuery['status'], $tabQuery['page']);
        $submittedUrl = route('activity-monitor.index', array_merge($tabQuery, ['tab' => 'submitted']));
        $todoUrl = route('activity-monitor.index', array_merge($tabQuery, ['tab' => 'todo']));
    @endphp

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if($errors->has('activity_date'))
            <div role="alert" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first('activity_date') }}</div>
        @endif
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Activity Monitor</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $term }} / SY {{ $schoolYear }} · {{ auth()->user()->org_name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('gpoa.create') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">GPOA</a>
                <a href="{{ route('activity-requests.create') }}" class="rounded-md bg-sky-700 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-800">Request activity</a>
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
                    <p class="mt-1 text-sm text-slate-500">Start one of these planned activities to begin monitoring.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($pendingActivities as $activity)
                            <a href="{{ route('activity-requests.create', ['gpoa' => $activity->gpoa_id, 'activity' => $activity->id]) }}" class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100">{{ $activity->title }}</a>
                        @endforeach
                    </div>
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
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Activity</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Documents</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Next action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($activities as $activity)
                            @php
                                $activityRequest = $activity->activityRequest;
                                $letterPresent = filled($activityRequest?->communication_letter);
                                $reportPresent = $activityRequest?->report && (filled($activityRequest->report->narrative_report) || filled($activityRequest->report->narrative_content));
                                $viewUrl = $activityRequest ? route('activity-requests.show', $activityRequest) : route('activity-requests.create', ['gpoa' => $activity->gpoa_id, 'activity' => $activity->id]);
                                $reportDate = $activity->end_date ?? $activity->date;
                            @endphp
                            <tr>
                                <td class="px-4 py-3"><div class="font-semibold text-slate-900">{{ $activity->title }}</div>@if($activity->last_submitted_at)<time class="text-xs text-slate-500" datetime="{{ $activity->last_submitted_at->toIso8601String() }}" title="{{ $activity->last_submitted_at->format('M j, Y g:i A') }}">{{ $activity->last_submitted_at->diffForHumans() }}</time>@endif</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $activity->date?->format('M j, Y') ?? '—' }}@if($activity->end_date && $activity->end_date->ne($activity->date))<span class="block text-xs text-slate-500">to {{ $activity->end_date->format('M j, Y') }}</span>@endif</td>
                                <td class="px-4 py-3"><div class="flex gap-2"><span title="Communication letter {{ $letterPresent ? 'uploaded' : 'not uploaded' }}" class="text-xs {{ $letterPresent ? 'text-emerald-700' : 'text-slate-400' }}">{{ $letterPresent ? '●' : '○' }} Letter</span><span title="Narrative report {{ $reportPresent ? 'submitted' : 'not submitted' }}" class="text-xs {{ $reportPresent ? 'text-emerald-700' : 'text-slate-400' }}">{{ $reportPresent ? '●' : '○' }} Report</span></div></td>
                                <td class="px-4 py-3"><x-status-pill :status="$activity->monitor_status" /> @if($activity->monitor_late)<span class="ml-1"><x-status-pill status="Late" /></span>@endif</td>
                                <td class="px-4 py-3 text-right">
                                    @if($activity->archived_at)
                                        <form method="POST" action="{{ route('activities.restore', $activity) }}">@csrf<button class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Restore</button></form>
                                    @elseif($activity->monitor_status === 'Completed')
                                        <form method="POST" action="{{ route('activities.archive', $activity) }}">@csrf<button class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Archive</button></form>
                                    @elseif(!$activityRequest)
                                        <a href="{{ $viewUrl }}" class="rounded bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white">Request activity</a>
                                    @elseif(!$letterPresent)
                                        <a href="{{ $viewUrl }}" class="rounded bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white">Upload letter</a>
                                    @elseif($activityRequest->report?->status === 'needs_revision')
                                        <a href="{{ route('activity-reports.create', $activityRequest) }}" class="rounded bg-amber-700 px-3 py-1.5 text-xs font-semibold text-white">Fix &amp; resubmit</a>
                                    @elseif(!$reportPresent && $reportDate && $reportDate->gt(today()))
                                        <div class="flex flex-col items-end gap-1">
                                            <button type="button" disabled class="cursor-not-allowed rounded bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-400">Report unavailable</button>
                                            <span class="text-[10px] text-slate-500">Available after the activity end date</span>
                                        </div>
                                    @elseif(!$reportPresent && $reportDate && $reportDate->lte(today()))
                                        <a href="{{ route('activity-reports.create', $activityRequest) }}" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Submit report</a>
                                    @else
                                        <a href="{{ $viewUrl }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">View</a>
                                    @endif
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
                    @endphp
                    <article class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">{{ $activity->title }}</h2><p class="text-sm text-slate-700">{{ $activity->date?->format('M j, Y') ?? '—' }}</p>@if($activity->last_submitted_at)<time class="text-xs text-slate-500" title="{{ $activity->last_submitted_at->format('M j, Y g:i A') }}">{{ $activity->last_submitted_at->diffForHumans() }}</time>@endif</div><x-status-pill :status="$activity->monitor_status" /></div>
                        <div class="flex items-center justify-between"><div class="flex gap-2"><span class="text-xs {{ $letterPresent ? 'text-emerald-700' : 'text-slate-400' }}">{{ $letterPresent ? '●' : '○' }} Letter</span><span class="text-xs {{ $reportPresent ? 'text-emerald-700' : 'text-slate-400' }}">{{ $reportPresent ? '●' : '○' }} Report</span></div>
                            @if($activity->archived_at)<form method="POST" action="{{ route('activities.restore', $activity) }}">@csrf<button class="rounded border px-3 py-1.5 text-xs font-semibold">Restore</button></form>
                            @elseif($activity->monitor_status === 'Completed')<form method="POST" action="{{ route('activities.archive', $activity) }}">@csrf<button class="rounded border px-3 py-1.5 text-xs font-semibold">Archive</button></form>
                            @elseif(!$activityRequest)<a href="{{ route('activity-requests.create', ['gpoa' => $activity->gpoa_id, 'activity' => $activity->id]) }}" class="rounded bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white">Request activity</a>
                            @elseif(!$letterPresent)<a href="{{ route('activity-requests.show', $activityRequest) }}" class="rounded bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white">Upload letter</a>
                            @elseif($activityRequest->report?->status === 'needs_revision')<a href="{{ route('activity-reports.create', $activityRequest) }}" class="rounded bg-amber-700 px-3 py-1.5 text-xs font-semibold text-white">Fix &amp; resubmit</a>
                            @elseif(!$reportPresent && $reportDate && $reportDate->gt(today()))<div class="flex flex-col items-end gap-1"><button type="button" disabled class="cursor-not-allowed rounded bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-400">Report unavailable</button><span class="text-[10px] text-slate-500">Available after the activity end date</span></div>
                            @elseif(!$reportPresent && $reportDate && $reportDate->lte(today()))<a href="{{ route('activity-reports.create', $activityRequest) }}" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Submit report</a>
                            @else<a href="{{ route('activity-requests.show', $activityRequest) }}" class="rounded border px-3 py-1.5 text-xs font-semibold">View</a>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
        <div>{{ $activities->links() }}</div>
    </main>
</x-app-layout>