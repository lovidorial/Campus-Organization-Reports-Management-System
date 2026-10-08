<x-app-layout>

@php
    $user = auth()->user();
    $hasThemeColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $user->theme_color) === 1;
    $themeColor = $hasThemeColor ? $user->theme_color : '#e89600';
    $themeColorLight = $hasThemeColor
        ? "color-mix(in srgb, {$themeColor} 78%, white)"
        : '#f5a623';
    $orgName = $user->organization->name ?? $user->org_name ?? '—';
    $semester = str_replace('Term', 'Semester', $term);
    $academicYear = str_replace('-', '–', $schoolYear);

    $statusLabel = $gpoa ? 'GPOA submitted' : 'GPOA not submitted';
    $showActionRequired = ! $gpoa;
    $activityCounts = $counts ?? ['Pending' => 0, 'Ongoing' => 0, 'Completed' => 0, 'Late' => 0];
    $totalCount = $gpoa ? ($totalCount ?? 0) : 0;
    $statusCards = [
        [
            'label' => 'Total', 'count' => $totalCount, 'accent' => 'border-t-slate-400', 'color' => 'text-slate-700', 'icon' => 'total', 'iconBg' => 'bg-slate-100',
            'subtitle' => 'activities', 'title' => 'All non-archived planned activities: Pending + Ongoing + Completed.',
            'link' => route('activity-monitor.index', ['tab' => 'submitted']),
        ],
        [
            'label' => 'Pending', 'count' => $gpoa ? ($activityCounts['Pending'] ?? 0) : 0, 'accent' => 'border-t-amber-500', 'color' => 'text-amber-600', 'icon' => 'pending', 'iconBg' => 'bg-amber-50',
            'subtitle' => ($gpoa ? ($activityCounts['Pending'] ?? 0) : 0) . ' of ' . $totalCount, 'title' => 'Planned activities with no communication letter uploaded.',
            'link' => route('activity-monitor.index', ['tab' => 'todo']),
        ],
        [
            'label' => 'Ongoing', 'count' => $gpoa ? ($activityCounts['Ongoing'] ?? 0) : 0, 'accent' => 'border-t-sky-500', 'color' => 'text-sky-600', 'icon' => 'ongoing', 'iconBg' => 'bg-sky-50',
            'subtitle' => ($gpoa ? ($activityCounts['Ongoing'] ?? 0) : 0) . ' of ' . $totalCount, 'title' => 'Activities with a letter uploaded that are not yet completed.',
            'link' => route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Ongoing']),
        ],
        [
            'label' => 'Completed', 'count' => $gpoa ? ($activityCounts['Completed'] ?? 0) : 0, 'accent' => 'border-t-emerald-500', 'color' => 'text-emerald-600', 'icon' => 'completed', 'iconBg' => 'bg-emerald-50',
            'subtitle' => ($gpoa ? ($activityCounts['Completed'] ?? 0) : 0) . ' of ' . $totalCount, 'title' => 'Activities whose narrative reports have been approved.',
            'link' => route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Completed']),
        ],
    ];
    $dashboardStatusChart = ['labels' => ['Pending', 'Ongoing', 'Completed', 'Archived'], 'values' => [$activityCounts['Pending'] ?? 0, $activityCounts['Ongoing'] ?? 0, $activityCounts['Completed'] ?? 0, $activityCounts['Archived'] ?? 0]];
@endphp

<div class="mb-4 rounded-2xl py-3 px-4 text-white shadow-lg transition-shadow duration-300 hover:shadow-xl md:px-5" style="background: linear-gradient(135deg, {{ $themeColorLight }} 0%, {{ $themeColor }} 100%);">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }} profile photo" class="h-10 w-10 rounded-xl border border-white/40 object-cover shadow-sm md:h-10 md:w-10" />
            <div>
                <p class="text-xs font-medium text-white/80">Welcome,</p>
                <h1 class="text-lg font-bold tracking-tight md:text-xl">{{ $user->name }}</h1>
                @if($user->position)
                    <p class="text-xs text-white/75">{{ $user->position }}</p>
                @endif
            </div>
        </div>

        @if($unreadCount > 0)
            <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-2 self-start rounded-xl border border-white/20 bg-white/15 px-4 py-2 text-sm font-semibold backdrop-blur-sm transition hover:bg-white/25 lg:self-center">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                </span>
                {{ $unreadCount }} new notification{{ $unreadCount > 1 ? 's' : '' }}
            </a>
        @endif
    </div>

    <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-2 md:flex md:flex-wrap md:items-center md:gap-x-6 md:gap-y-2">
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/70">Organization</p>
            <p class="mt-0.5 text-sm font-semibold leading-tight">{{ $orgName }}</p>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/70">Current Semester</p>
            <p class="mt-0.5 text-sm font-semibold leading-tight">{{ $semester }}</p>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/70">Academic Year</p>
            <p class="mt-0.5 text-sm font-semibold leading-tight">{{ $academicYear }}</p>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/70">Current Status</p>
            <div class="mt-1 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">
                <span class="h-2 w-2 rounded-full {{ $gpoa ? 'bg-emerald-400' : 'bg-slate-300' }} shrink-0"></span>
                <span>{{ $statusLabel }}</span>
            </div>
        </div>
    </div>
</div>

@if($showActionRequired)
    <div class="mb-6 rounded-2xl border border-l-4 border-l-amber-500 bg-gradient-to-r from-amber-50/80 to-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.93 19h12.14a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L4.2 16a2 2 0 001.73 3z"/></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Action Required</h2>
                <p class="text-gray-800 font-medium">Submit your GPOA to start monitoring your activities.</p>
            </div>
        </div>
    </div>
@endif

<section class="mb-4" aria-label="Activity status summary">
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
        @foreach($statusCards as $card)
            @if($gpoa)
                <a href="{{ $card['link'] }}" title="{{ $card['title'] }}" aria-label="{{ $card['label'] }}: {{ $card['count'] }}. {{ $card['title'] }}" class="group flex h-full min-h-24 flex-col rounded-xl border border-t-2 border-slate-200 {{ $card['accent'] }} bg-white p-3 shadow-sm transition hover:border-slate-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2">
            @else
                <article title="{{ $card['title'] }}" aria-label="{{ $card['label'] }}: {{ $card['count'] }}. {{ $card['title'] }}" class="flex h-full min-h-24 flex-col rounded-xl border border-t-2 border-slate-200 {{ $card['accent'] }} bg-white p-3 shadow-sm">
            @endif
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    <span class="flex h-6 w-6 items-center justify-center rounded-md {{ $card['iconBg'] }} {{ $card['color'] }}" aria-hidden="true">
                        @if($card['icon'] === 'total')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 0 0-2 2v2h16V5a2 2 0 0 0-2-2H4ZM18 9H2v2h16V9ZM2 13v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2H2Z"/></svg>
                        @elseif($card['icon'] === 'pending')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-12a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l2.5 2.5a1 1 0 0 0 1.414-1.414L11 9.586V6Z" clip-rule="evenodd"/></svg>
                        @elseif($card['icon'] === 'ongoing')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-12a.75.75 0 0 0-1.5 0v4.25c0 .414.336.75.75.75h3a.75.75 0 0 0 0-1.5h-2.25V6Z" clip-rule="evenodd"/></svg>
                        @elseif($card['icon'] === 'completed')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-10.72a.75.75 0 0 0-1.06-1.06L8.5 10.44 7.28 9.22a.75.75 0 1 0-1.06 1.06l1.75 1.75a.75.75 0 0 0 1.06 0l4.75-4.75Z" clip-rule="evenodd"/></svg>
                        @else<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 0 0-2 2v2h16V5a2 2 0 0 0-2-2H4ZM2 9v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9H2Z"/></svg>@endif
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold {{ $card['color'] }}">{{ $card['count'] }}</p>
                <p class="mt-1 text-[11px] text-slate-500">{{ $card['subtitle'] }}</p>
                @if($gpoa)<span class="mt-auto self-end pt-2 text-sm text-slate-400 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">→</span>@endif
            @if($gpoa)
                </a>
            @else
                </article>
            @endif
        @endforeach
    </div>

    <div class="mt-2.5 flex flex-wrap items-center gap-2">
        @if($gpoa)
        <a href="{{ route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Late']) }}" title="Narrative report missing after the deadline." class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-100 text-[10px] font-bold">!</span>
            Late {{ $activityCounts['Late'] ?? 0 }}
        </a>
        @else
        <span title="Narrative report missing after the deadline." class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-100 text-[10px] font-bold">!</span>
            Late {{ $activityCounts['Late'] ?? 0 }}
        </span>
        @endif
        @if($gpoa)
        <a href="{{ route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Archived']) }}" title="Completed activities archived from the active monitoring list; excluded from Total." class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold">•</span>
            Archived {{ $activityCounts['Archived'] ?? 0 }}
        </a>
        @else
        <span title="Completed activities archived from the active monitoring list; excluded from Total." class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold">•</span>
            Archived {{ $activityCounts['Archived'] ?? 0 }}
        </span>
        @endif
    </div>
</section>

<div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Monitoring Progress</h2>
            <p class="mt-0.5 text-sm text-slate-500">Derived from your GPOA activities.</p>
        </div>
        @if($gpoa)
            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Submitted</span>
        @else
            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $statusLabel }}</span>
        @endif
    </div>

    <div class="mt-2">
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold text-slate-900">{{ $overallPercent }}%</span>
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Overall completion</span>
        </div>

        <div class="mt-2 flex h-2 w-full overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-valuenow="{{ $overallPercent }}" aria-valuemin="0" aria-valuemax="100">
            <span class="block min-w-0 h-full bg-emerald-500 transition-all duration-500 w-[var(--overall-percent)]" @style(['--overall-percent' => $overallPercent . '%'])></span>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1">
        <div class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
            <span class="text-xs text-slate-500">Pending</span>
            <span class="text-sm font-semibold tabular-nums text-slate-800">{{ $activityCounts['Pending'] ?? 0 }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full bg-sky-500"></span>
            <span class="text-xs text-slate-500">Ongoing</span>
            <span class="text-sm font-semibold tabular-nums text-slate-800">{{ $activityCounts['Ongoing'] ?? 0 }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class="text-xs text-slate-500">Completed</span>
            <span class="text-sm font-semibold tabular-nums text-slate-800">{{ $activityCounts['Completed'] ?? 0 }}</span>
        </div>
        <div class="flex items-center gap-1.5 text-slate-500">
            <span class="h-2 w-2 rounded-full bg-slate-300"></span>
            <span class="text-xs">Archived</span>
            <span class="text-sm font-semibold tabular-nums text-slate-800">{{ $activityCounts['Archived'] ?? 0 }}</span>
        </div>
    </div>
</div>

@if(!$canSubmitGpoa && $submissionBlockMessage)
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900" role="status">{{ $submissionBlockMessage }}</div>
@endif

<div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h3 class="text-base font-bold text-gray-900">GPOA</h3>
                @if($gpoa)
                    <x-status-pill status="Submitted" />
                @else
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700">Not submitted</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-500">General Plan of Action</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($gpoa)
                <a href="{{ route('gpoa.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900">View GPOA</a>
            @else
                @if($canSubmitGpoa)
                    <a href="{{ route('gpoa.create') }}" class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-sky-700">Submit GPOA</a>
                @else
                    <span class="inline-flex cursor-not-allowed items-center justify-center rounded-xl bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-500">Submit GPOA unavailable</span>
                    <p class="text-xs text-amber-800">{{ $submissionBlockMessage }}</p>
                @endif
            @endif

            <a href="{{ route('activity-monitor.index') }}" class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-sky-700">Open Activity Monitor</a>
        </div>
    </div>
</div>

@php
    $dashboardActivities = $activities
        ->sortBy(fn ($activity) => $activity->date ? $activity->date->format('Y-m-d') : '9999-12-31')
        ->values();

    $upcomingDashboardActivities = $dashboardActivities->filter(function ($activity) {
        return $activity->date && ($activity->date->isToday() || $activity->date->isFuture());
    })->values();

    $dashboardActivities = $upcomingDashboardActivities->isNotEmpty()
        ? $upcomingDashboardActivities->take(8)
        : $dashboardActivities->take(8);
@endphp

@if($activities->isNotEmpty())
    <div class="mb-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-lg font-bold text-gray-900">Current Activities</h3>
                <p class="text-xs text-gray-500">Latest status from your GPOA entries.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                <label class="relative block">
                    <span class="sr-only">Search activity</span>
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Zm0 1.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM13.707 15.293l4 4 1.414-1.414-4-4-1.414 1.414Z" clip-rule="evenodd"/></svg>
                    <input id="dashboard-activity-search" type="search" placeholder="Search activity..." class="w-56 rounded-xl border border-slate-200 bg-white py-1.5 pl-8 pr-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-sky-300 focus:outline-none focus:ring-1 focus:ring-sky-200 md:w-72" />
                </label>

                <select id="dashboard-activity-filter" class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-700 focus:border-sky-300 focus:outline-none focus:ring-1 focus:ring-sky-200">
                    <option value="All">All</option>
                    <option value="Not Started">Not Started</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Completed">Completed</option>
                    <option value="Late">Late</option>
                </select>

                <a href="{{ route('activity-monitor.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-sky-700 hover:text-sky-800">
                    View all activities <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-[42%] min-w-[260px] px-3 py-2 align-middle" aria-sort="none">
                            <button type="button" class="sort-btn inline-flex items-center gap-1 font-medium text-slate-500" data-key="title" data-sort-dir="none">
                                Activity <span class="sort-arrow text-[10px]">↕</span>
                            </button>
                        </th>
                        <th class="px-3 py-2 align-middle" aria-sort="ascending">
                            <button type="button" class="sort-btn inline-flex items-center gap-1 font-medium text-slate-500" data-key="date" data-sort-dir="asc">
                                Date <span class="sort-arrow text-[10px]">↑</span>
                            </button>
                        </th>
                        <th class="max-w-[110px] px-3 py-2 align-middle">Venue</th>
                        <th class="px-3 py-2 align-middle">Letter</th>
                        <th class="px-3 py-2 align-middle">Report</th>
                        <th class="px-3 py-2 align-middle" title="Overall activity progress">Status</th>
                    </tr>
                </thead>
                <tbody id="dashboard-activity-body" class="divide-y divide-slate-100">
                    @foreach($dashboardActivities as $activity)
                        @php($monitoring = $activity->monitoringStatus())
                        @php($mainStatus = $monitoring['late'] ? 'Late' : (($monitoring['status'] ?? 'Not Started') === 'Pending' ? 'Not Started' : ($monitoring['status'] ?? 'Not Started')))
                        @php($letterStatus = $activity->letterStatusLabel())
                        @php($reportStatus = $activity->narrativeStatusLabel())
                        <tr data-title="{{ strtolower($activity->title) }}" data-date="{{ $activity->date ? $activity->date->format('Y-m-d') : '9999-12-31' }}" data-status="{{ $mainStatus }}" class="align-middle transition-colors hover:bg-slate-50">
                            <td class="px-3 py-2 align-middle text-slate-900">
                                <div class="max-w-[28rem]">
                                    <span class="block leading-snug text-sm font-medium text-slate-800 line-clamp-2" title="{{ $activity->title }}">{{ $activity->title }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-middle text-xs text-slate-600">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                            <td class="max-w-[110px] truncate px-3 py-2 align-middle text-xs text-slate-600" title="{{ $activity->venue ?: '—' }}">{{ $activity->venue ?: '—' }}</td>
                            <td class="px-3 py-2 align-middle">
                                @if(! empty($letterStatus))
                                    <x-status-pill :status="$letterStatus" />
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-middle">
                                @if(! empty($reportStatus))
                                    <x-status-pill :status="$reportStatus" />
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-middle">
                                <div class="flex flex-wrap items-center gap-1">
                                    @if(! empty($mainStatus))
                                        <x-status-pill :status="$mainStatus" />
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                    @if($monitoring['late'])
                                        <x-status-pill status="Late" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="dashboard-activity-empty" class="hidden">
                        <td colspan="6" class="px-3 py-4 text-center text-sm text-slate-500">No matching activities</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex items-center justify-between gap-2 text-xs text-slate-500">
            <p>Showing {{ $dashboardActivities->count() }} of {{ $activities->count() }} activities</p>
            <a href="{{ route('activity-monitor.index') }}" class="inline-flex items-center gap-1 font-medium text-sky-700 hover:text-sky-800">
                View all activities <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>

    <script>
        (function () {
            const body = document.getElementById('dashboard-activity-body');
            const searchInput = document.getElementById('dashboard-activity-search');
            const filterSelect = document.getElementById('dashboard-activity-filter');
            const emptyRow = document.getElementById('dashboard-activity-empty');
            const sortButtons = Array.from(document.querySelectorAll('.sort-btn'));
            const rows = body ? Array.from(body.querySelectorAll('tr[data-title]')) : [];
            let sortState = { key: 'date', direction: 'asc' };

            function updateSortIndicators() {
                sortButtons.forEach((button) => {
                    const header = button.closest('th');
                    const isActive = button.dataset.key === sortState.key;
                    const text = isActive ? (sortState.direction === 'asc' ? '↑' : '↓') : '↕';
                    const icon = button.querySelector('.sort-arrow');

                    if (icon) {
                        icon.textContent = text;
                    }

                    if (header) {
                        header.setAttribute('aria-sort', isActive ? (sortState.direction === 'asc' ? 'ascending' : 'descending') : 'none');
                    }
                });
            }

            function sortRows() {
                if (!body) return;

                const sortedRows = [...rows].sort((a, b) => {
                    let comparison = 0;

                    if (sortState.key === 'date') {
                        const aValue = a.dataset.date ? new Date(a.dataset.date + 'T00:00:00').getTime() : Number.MAX_SAFE_INTEGER;
                        const bValue = b.dataset.date ? new Date(b.dataset.date + 'T00:00:00').getTime() : Number.MAX_SAFE_INTEGER;
                        comparison = aValue - bValue;
                    } else {
                        comparison = (a.dataset.title || '').localeCompare(b.dataset.title || '');
                    }

                    return sortState.direction === 'asc' ? comparison : -comparison;
                });

                sortedRows.forEach((row) => body.appendChild(row));
            }

            function applyDashboardFilters() {
                const searchTerm = (searchInput?.value || '').trim().toLowerCase();
                const selectedStatus = filterSelect?.value || 'All';

                let visibleCount = 0;

                rows.forEach((row) => {
                    const title = (row.dataset.title || '').toLowerCase();
                    const status = row.dataset.status || 'Not Started';
                    const matchesSearch = !searchTerm || title.includes(searchTerm);
                    const matchesStatus = selectedStatus === 'All' || status === selectedStatus;
                    const shouldShow = matchesSearch && matchesStatus;

                    row.classList.toggle('hidden', !shouldShow);
                    if (shouldShow) visibleCount++;
                });

                if (emptyRow) {
                    emptyRow.classList.toggle('hidden', visibleCount > 0);
                }

                sortRows();
            }

            sortButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const key = button.dataset.key;
                    if (sortState.key === key) {
                        sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortState.key = key;
                        sortState.direction = 'asc';
                    }

                    updateSortIndicators();
                    applyDashboardFilters();
                });
            });

            searchInput?.addEventListener('input', applyDashboardFilters);
            filterSelect?.addEventListener('change', applyDashboardFilters);

            updateSortIndicators();
            applyDashboardFilters();
        })();
    </script>
@endif

</x-app-layout>
