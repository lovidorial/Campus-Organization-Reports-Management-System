<x-app-layout>
    @php
        $tabQuery = request()->query();
        unset($tabQuery['status'], $tabQuery['page']);
        $recentUrl = route('admin.activities', array_merge($tabQuery, ['tab' => 'recent']));
        $todoUrl = route('admin.activities', array_merge($tabQuery, ['tab' => 'todo']));
        $statusFilter = request('status', '');
        $sort = request('sort', 'latest');
    @endphp

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
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
                                $viewUrl = $activityRequest ? route('activity-requests.show', $activityRequest) : route('admin.gpoa.show', $activity->gpoa_id);
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $activity->title }}</div>
                                    <div class="text-xs text-slate-500">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</div>
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
                                        @if($activity->monitoring_status === 'Completed')<form method="POST" action="{{ route('activities.archive', $activity) }}" class="mt-2">@csrf<button type="submit" class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Archive</button></form>@elseif($activity->monitoring_status === 'Archived')<form method="POST" action="{{ route('activities.restore', $activity) }}" class="mt-2">@csrf<button type="submit" class="rounded border border-sky-300 px-2 py-1 text-xs font-semibold text-sky-800">Restore</button></form>@endif
                                        @if($activity->monitoring_status !== 'Archived')<form method="POST" action="{{ route('admin.monitoring.record', $activity->id) }}" class="mt-2 grid gap-2 sm:grid-cols-[180px_1fr_auto]">@csrf<select name="compliance_status" required class="rounded border-slate-300 text-xs"><option value="">Assessment</option>@foreach(['aligned' => 'Aligned', 'partial' => 'Partially Aligned', 'not_aligned' => 'Not Aligned'] as $value => $label)<option value="{{ $value }}" @selected($activity->monitoringResult?->compliance_status === $value)>{{ $label }}</option>@endforeach</select><input name="compliance_notes" maxlength="1000" value="{{ $activity->monitoringResult?->compliance_notes }}" placeholder="Optional remark" class="rounded border-slate-300 text-xs"><button class="rounded bg-slate-700 px-2 py-1 text-xs font-semibold text-white">Save</button></form>@endif
                                    </div></details>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $activity->date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-4 py-3"><div class="flex gap-2">
                                    <a @if($letterPresent) href="{{ route('admin.file.view', [$activityRequest->id, 'communication']) }}" @else aria-disabled="true" @endif title="Communication letter{{ $letterPresent ? ' uploaded' : ' not uploaded' }}" class="inline-flex h-8 w-8 items-center justify-center rounded border {{ $letterPresent ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400' }}">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4.25 4.25v12.25H7A2.25 2.25 0 0 1 4.75 18V6A2.25 2.25 0 0 1 7 3.75ZM14 4v4h4M8.5 13h7m-7 3.5h7"/></svg><span class="sr-only">Communication letter</span>
                                    </a>
                                    <a @if($reportPresent) href="{{ route('admin.file.view', [$activityRequest->id, 'narrative']) }}" @else aria-disabled="true" @endif title="Narrative report{{ $reportPresent ? ' submitted' : ' not submitted' }}" class="inline-flex h-8 w-8 items-center justify-center rounded border {{ $reportPresent ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400' }}">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4.25 4.25v12.25H7A2.25 2.25 0 0 1 4.75 18V6A2.25 2.25 0 0 1 7 3.75ZM14 4v4h4M8.5 13h7m-7 3.5h7"/></svg><span class="sr-only">Narrative report</span>
                                    </a>
                                </div></td>
                                <td class="px-4 py-3"><x-status-pill :status="$activity->monitoring_status" /> @if($activity->monitoring_late)<span class="ml-1"><x-status-pill status="Late" /></span>@endif</td>
                                <td class="px-4 py-3 text-right"><a href="{{ $viewUrl }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">View</a></td>
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
                        $viewUrl = $activityRequest ? route('activity-requests.show', $activityRequest) : route('admin.gpoa.show', $activity->gpoa_id);
                    @endphp
                    <article class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">{{ $activity->title }}</h2><p class="text-xs text-slate-500">{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</p>@if($activity->last_submitted_at)<time class="text-xs text-slate-500" title="{{ $activity->last_submitted_at->format('M j, Y g:i A') }}">{{ $activity->last_submitted_at->diffForHumans() }}</time>@endif</div><x-status-pill :status="$activity->monitoring_status" /></div>
                        <p class="text-sm text-slate-700">{{ $activity->date?->format('M j, Y') ?? '—' }}</p>
                        <div class="flex items-center justify-between"><div class="flex gap-2"><span title="Communication letter" class="text-xs {{ $letterPresent ? 'text-emerald-700' : 'text-slate-400' }}">{{ $letterPresent ? '●' : '○' }} Letter</span><span title="Narrative report" class="text-xs {{ $reportPresent ? 'text-emerald-700' : 'text-slate-400' }}">{{ $reportPresent ? '●' : '○' }} Report</span></div><a href="{{ $viewUrl }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">View</a></div>
                        <details class="text-xs text-slate-600"><summary class="cursor-pointer text-sky-700">Details / remark</summary><p class="mt-2">{{ $activity->category ?: 'Category not set' }} · {{ $activity->venue ?: 'Venue not set' }} · {{ $activity->gpoa?->term }} / {{ $activity->gpoa?->school_year }}</p>@if($activity->monitoringResult)<p class="mt-1">{{ $activity->monitoringResult->compliance_notes }}</p>@endif</details>
                    </article>
                @endforeach
            </div>
        @endif
        <div>{{ $activities->links() }}</div>
    </main>
</x-app-layout>