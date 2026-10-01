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
    $monitoringCards = [
        ['label' => 'Pending', 'count' => $activityCounts['Pending'] ?? 0, 'classes' => 'bg-slate-100 text-slate-700'],
        ['label' => 'Ongoing', 'count' => $activityCounts['Ongoing'] ?? 0, 'classes' => 'bg-amber-100 text-amber-700'],
        ['label' => 'Completed', 'count' => $activityCounts['Completed'] ?? 0, 'classes' => 'bg-emerald-100 text-emerald-700'],
        ['label' => 'Archived', 'count' => $activityCounts['Archived'] ?? 0, 'classes' => 'bg-slate-200 text-slate-700'],
        ['label' => 'Late', 'count' => $activityCounts['Late'] ?? 0, 'classes' => 'bg-rose-100 text-rose-700'],
    ];
    $dashboardCards = [
        ['Total', $activities->count(), 'text-slate-700'],
        ['Completed', $activityCounts['Completed'] ?? 0, 'text-emerald-700'],
        ['Ongoing', $activityCounts['Ongoing'] ?? 0, 'text-sky-700'],
        ['Pending', $activityCounts['Pending'] ?? 0, 'text-amber-700'],
        ['Archived', $activityCounts['Archived'] ?? 0, 'text-slate-600'],
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

<section class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Activity summary">
    @foreach($dashboardCards as [$label, $count, $color])
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold {{ $color }}">{{ $count }}</p>
        </article>
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

    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($monitoringCards as $card)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</span>
                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $card['classes'] }}">{{ $card['count'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>

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
            <a href="{{ route('gpoa.create') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-sky-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-700">Submit GPOA</a>
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
                            <td class="px-3 py-3 text-slate-600">{{ $activity->letterStatusLabel() }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $activity->narrativeStatusLabel() }}</td>
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
