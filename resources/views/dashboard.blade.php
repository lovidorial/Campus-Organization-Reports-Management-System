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
            'label' => 'Total', 'count' => $totalCount, 'accent' => 'border-t-slate-400', 'color' => 'text-slate-700', 'icon' => 'total',
            'subtitle' => 'your planned activities', 'helper' => '', 'title' => 'All non-archived planned activities: Pending + Ongoing + Completed.',
            'link' => route('activity-monitor.index', ['tab' => 'submitted']),
        ],
        [
            'label' => 'Pending', 'count' => $gpoa ? ($activityCounts['Pending'] ?? 0) : 0, 'accent' => 'border-t-amber-500', 'color' => 'text-amber-700', 'icon' => 'pending',
            'subtitle' => ($gpoa ? ($activityCounts['Pending'] ?? 0) : 0) . ' of ' . $totalCount, 'helper' => 'Letter not uploaded yet', 'title' => 'Planned activities with no communication letter uploaded.',
            'link' => route('activity-monitor.index', ['tab' => 'todo']),
        ],
        [
            'label' => 'Ongoing', 'count' => $gpoa ? ($activityCounts['Ongoing'] ?? 0) : 0, 'accent' => 'border-t-sky-500', 'color' => 'text-sky-700', 'icon' => 'ongoing',
            'subtitle' => ($gpoa ? ($activityCounts['Ongoing'] ?? 0) : 0) . ' of ' . $totalCount, 'helper' => 'Waiting for report or review', 'title' => 'Activities with a letter uploaded that are not yet completed.',
            'link' => route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Ongoing']),
        ],
        [
            'label' => 'Completed', 'count' => $gpoa ? ($activityCounts['Completed'] ?? 0) : 0, 'accent' => 'border-t-emerald-500', 'color' => 'text-emerald-700', 'icon' => 'completed',
            'subtitle' => ($gpoa ? ($activityCounts['Completed'] ?? 0) : 0) . ' of ' . $totalCount, 'helper' => '', 'title' => 'Activities whose narrative reports have been approved.',
            'link' => route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Completed']),
        ],
        [
            'label' => 'Late', 'count' => $gpoa ? ($activityCounts['Late'] ?? 0) : 0, 'accent' => 'border-t-rose-500', 'color' => 'text-rose-700', 'icon' => 'late',
            'subtitle' => 'flag, not a separate status', 'helper' => 'Past due and not completed', 'title' => 'Activities past their due date that are not completed. Late is a flag and may overlap another status.',
            'link' => route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Late']),
        ],
        [
            'label' => 'Archived', 'count' => $gpoa ? ($activityCounts['Archived'] ?? 0) : 0, 'accent' => 'border-t-slate-500', 'color' => 'text-slate-600', 'icon' => 'archived',
            'subtitle' => 'not counted in Total', 'helper' => '', 'title' => 'Completed activities archived from the active monitoring list; excluded from Total.',
            'link' => route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Archived']),
        ],
    ];
    $dashboardStatusChart = ['labels' => ['Pending', 'Ongoing', 'Completed', 'Archived'], 'values' => [$activityCounts['Pending'] ?? 0, $activityCounts['Ongoing'] ?? 0, $activityCounts['Completed'] ?? 0, $activityCounts['Archived'] ?? 0]];
@endphp

<div class="mb-6 rounded-2xl p-5 md:p-6 text-white shadow-lg transition-shadow duration-300 hover:shadow-xl" style="background: linear-gradient(135deg, {{ $themeColorLight }} 0%, {{ $themeColor }} 100%);">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4 sm:items-center">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }} profile photo" class="h-16 w-16 rounded-2xl border-2 border-white/40 object-cover shadow-md md:h-[4.5rem] md:w-[4.5rem]" />
            <div>
                <p class="text-sm font-medium text-white/80">Welcome,</p>
                <h1 class="text-xl font-bold tracking-tight md:text-2xl">{{ $user->name }}</h1>
                @if($user->position)
                    <p class="mt-0.5 text-sm text-white/75">{{ $user->position }}</p>
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

    <div class="mt-5 grid grid-cols-1 gap-4 border-t border-white/20 pt-5 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-white/70">Organization</p>
            <p class="text-base font-semibold">{{ $orgName }}</p>
        </div>
        <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-white/70">Current Semester</p>
            <p class="text-base font-semibold">{{ $semester }}</p>
        </div>
        <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-white/70">Academic Year</p>
            <p class="text-base font-semibold">{{ $academicYear }}</p>
        </div>
        <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-white/70">Current Status</p>
            <div class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/15 px-3 py-1.5 backdrop-blur-sm">
                <span class="h-2.5 w-2.5 rounded-full {{ $gpoa ? 'bg-emerald-400' : 'bg-slate-300' }} shrink-0"></span>
                <span class="text-sm font-semibold">{{ $statusLabel }}</span>
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

<section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6" aria-label="Activity status summary">
    @foreach($statusCards as $card)
        @if($gpoa)
            <a href="{{ $card['link'] }}" title="{{ $card['title'] }}" aria-label="{{ $card['label'] }}: {{ $card['count'] }}. {{ $card['title'] }}" class="group flex h-full min-h-36 flex-col rounded-xl border border-t-2 border-slate-200 {{ $card['accent'] }} bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2 {{ $card['label'] === 'Pending' ? 'sm:min-h-40' : '' }}">
        @else
            <article title="{{ $card['title'] }}" aria-label="{{ $card['label'] }}: {{ $card['count'] }}. {{ $card['title'] }}" class="flex h-full min-h-36 flex-col rounded-xl border border-t-2 border-slate-200 {{ $card['accent'] }} bg-white p-4 shadow-sm {{ $card['label'] === 'Pending' ? 'sm:min-h-40' : '' }}">
        @endif
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $card['label'] }}</p>
                <span class="{{ $card['color'] }}" aria-hidden="true">
                    @if($card['icon'] === 'total')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 0 0-2 2v2h16V5a2 2 0 0 0-2-2H4ZM18 9H2v2h16V9ZM2 13v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2H2Z"/></svg>
                    @elseif($card['icon'] === 'pending')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-12a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l2.5 2.5a1 1 0 0 0 1.414-1.414L11 9.586V6Z" clip-rule="evenodd"/></svg>
                    @elseif($card['icon'] === 'ongoing')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-12a.75.75 0 0 0-1.5 0v4.25c0 .414.336.75.75.75h3a.75.75 0 0 0 0-1.5h-2.25V6Z" clip-rule="evenodd"/></svg>
                    @elseif($card['icon'] === 'completed')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-10.72a.75.75 0 0 0-1.06-1.06L8.5 10.44 7.28 9.22a.75.75 0 1 0-1.06 1.06l1.75 1.75a.75.75 0 0 0 1.06 0l4.75-4.75Z" clip-rule="evenodd"/></svg>
                    @elseif($card['icon'] === 'late')<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.486 0l6.6 11.735c.75 1.334-.214 2.991-1.743 2.991H3.4c-1.53 0-2.493-1.657-1.743-2.99l6.6-11.736ZM11 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-1-7a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 1.5 0v-3A.75.75 0 0 0 10 7Z" clip-rule="evenodd"/></svg>
                    @else<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 0 0-2 2v2h16V5a2 2 0 0 0-2-2H4ZM2 9v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9H2Z"/></svg>@endif
                </span>
            </div>
            <p class="mt-2 {{ $card['label'] === 'Pending' ? 'text-4xl' : 'text-3xl' }} font-bold {{ $card['color'] }}">{{ $card['count'] }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ $card['subtitle'] }}</p>
            @if($card['helper'])<p class="mt-0.5 text-xs text-slate-500">{{ $card['helper'] }}</p>@endif
            @if($gpoa)<span class="mt-auto self-end pt-2 text-sm text-slate-400 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">→</span>@endif
        @if($gpoa)
            </a>
        @else
            </article>
        @endif
    @endforeach
</section>

<div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Monitoring Progress</h2>
            <p class="text-sm text-slate-500">Derived from your GPOA activities.</p>
        </div>
        @if($gpoa)
            <x-status-pill status="Submitted" />
        @else
            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $statusLabel }}</span>
        @endif
    </div>

    <div class="grid items-center gap-4 md:grid-cols-[minmax(0,1fr)_240px]">
        <div>
            <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                <span>Overall completion</span>
                <span class="font-semibold text-slate-800">{{ $overallPercent }}%</span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $overallPercent }}%"></div>
            </div>
        </div>
        <div class="relative h-56">
            <canvas data-chart-type="doughnut" data-center-text="{{ $overallPercent }}%" data-chart-data='@json($dashboardStatusChart)' role="img" aria-label="Activity status doughnut chart"></canvas>
        </div>
    </div>

</div>

@if(!$canSubmitGpoa && $submissionBlockMessage)
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900" role="status">{{ $submissionBlockMessage }}</div>
@endif

<div class="mb-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">GPOA</h3>
            @if($gpoa)
                <x-status-pill status="Submitted" />
            @else
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Not submitted</span>
            @endif
        </div>
        <p class="text-sm text-gray-500">General Plan of Activities</p>
        @if($gpoa)
            <a href="{{ route('gpoa.index') }}" class="mt-4 inline-flex items-center justify-center rounded-xl border border-gray-200 px-3.5 py-2 text-xs font-semibold text-gray-700 transition hover:border-orange-300 hover:text-orange-700">View GPOA</a>
        @else
            @if($canSubmitGpoa)
                <a href="{{ route('gpoa.create') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-sky-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-700">Submit GPOA</a>
            @else
                <p class="mt-3 text-sm font-medium text-amber-800">{{ $submissionBlockMessage }}</p>
                <span class="mt-2 inline-flex cursor-not-allowed rounded-xl bg-slate-200 px-3.5 py-2 text-xs font-semibold text-slate-500">Submit GPOA unavailable</span>
            @endif
        @endif
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Activity Monitor</h3>
            <span class="inline-flex items-center rounded-full border bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 border-blue-200">{{ $activities->count() }} activities</span>
        </div>
        <p class="text-sm text-gray-500">Track each activity by status and progress.</p>
        <a href="{{ route('activity-monitor.index') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-sky-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-700">Open Activity Monitor</a>
    </div>

</div>

@if($activities->isNotEmpty())
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Current Activities</h3>
                <p class="text-xs text-gray-500">Latest status from your GPOA entries.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Activity</th>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3">Venue</th>
                        <th class="px-3 py-3">Communication Letter</th>
                        <th class="px-3 py-3">Narrative Report</th>
                        <th class="px-3 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($activities as $activity)
                        @php($monitoring = $activity->monitoringStatus())
                        <tr>
                            <td class="px-3 py-3 font-semibold text-slate-900">{{ $activity->title }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $activity->venue ?: '—' }}</td>
                            <td class="px-3 py-3"><x-status-pill :status="$activity->letterStatusLabel()" /></td>
                            <td class="px-3 py-3"><x-status-pill :status="$activity->narrativeStatusLabel()" /></td>
                            <td class="px-3 py-3">
                                <x-status-pill :status="$monitoring['status']" />
                                @if($monitoring['late']) <x-status-pill status="Late" /> @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

</x-app-layout>
