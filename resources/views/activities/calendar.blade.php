<x-app-layout>
    @php
        $statusClasses = [
            'Pending' => 'border-l-orange-600 bg-orange-50 text-orange-950',
            'Ongoing' => 'border-l-sky-600 bg-sky-50 text-sky-950',
            'Completed' => 'border-l-emerald-500 bg-emerald-50 text-emerald-900',
        ];
        $selectedVenue = $selectedVenue ? (int) $selectedVenue : null;
        $calendarQuery = request()->query();
        $calendarDateLabel = fn ($start, $end) => $start
            ? $start->format('M j, Y') . ($end && ! $end->isSameDay($start) ? ' - ' . $end->format('M j, Y') : '')
            : null;
    @endphp

    <main class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:px-5 sm:py-6">
        <header class="flex flex-col gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Activity Calendar</h1>
                <p class="mt-1 text-sm text-slate-600">Planned activities and their current monitoring status.</p>
            </div>
            <form method="GET" action="{{ route('activities.calendar') }}" class="grid w-full grid-cols-1 items-end gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-2 sm:p-4 lg:grid-cols-4">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <div>
                    <label for="venue-filter" class="mb-1 block text-xs font-semibold text-slate-600">Venue</label>
                    <select id="venue-filter" name="venue" class="min-h-11 w-full rounded-lg border-slate-300 py-2 text-sm focus:border-orange-600 focus:ring-orange-600">
                        <option value="">All venues</option>
                        @foreach($venues as $venue)
                            <option value="{{ $venue->id }}" @selected($selectedVenue === $venue->id)>{{ $venue->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->isAdmin())
                    <div>
                        <label for="organization-filter" class="mb-1 block text-xs font-semibold text-slate-600">Organization</label>
                        <select id="organization-filter" name="organization" class="min-h-11 w-full rounded-lg border-slate-300 py-2 text-sm focus:border-orange-600 focus:ring-orange-600">
                            <option value="">All organizations</option>
                            @foreach($organizations as $organization)
                                <option value="{{ $organization->id }}" @selected((string) ($filters['organization'] ?? '') === (string) $organization->id)>{{ $organization->org_name ?: $organization->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label for="college-filter" class="mb-1 block text-xs font-semibold text-slate-600">College</label>
                    <select id="college-filter" name="college" class="min-h-11 w-full rounded-lg border-slate-300 py-2 text-sm focus:border-orange-600 focus:ring-orange-600">
                        <option value="">All colleges</option>
                        @foreach($colleges as $college)
                            <option value="{{ $college }}" @selected(($filters['college'] ?? '') === $college)>{{ $college }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status-filter" class="mb-1 block text-xs font-semibold text-slate-600">Activity status</label>
                    <select id="status-filter" name="status" class="min-h-11 w-full rounded-lg border-slate-300 py-2 text-sm focus:border-orange-600 focus:ring-orange-600">
                        <option value="">All statuses</option>
                        @foreach(['Pending', 'Ongoing', 'Completed'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date-from-filter" class="mb-1 block text-xs font-semibold text-slate-600">Date from</label>
                    <input id="date-from-filter" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="min-h-11 w-full rounded-lg border-slate-300 py-2 text-sm focus:border-orange-600 focus:ring-orange-600">
                </div>
                <div>
                    <label for="date-to-filter" class="mb-1 block text-xs font-semibold text-slate-600">Date to</label>
                    <input id="date-to-filter" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="min-h-11 w-full rounded-lg border-slate-300 py-2 text-sm focus:border-orange-600 focus:ring-orange-600">
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center lg:col-span-2 lg:justify-end">
                    <button type="submit" class="min-h-11 rounded-lg bg-orange-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Apply filters</button>
                    <a href="{{ route('activities.calendar', ['month' => $month->format('Y-m')]) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Reset</a>
                </div>
            </form>
        </header>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col items-center justify-between gap-3 border-b border-slate-200 px-3 py-3 sm:flex-row sm:flex-wrap sm:px-4">
                <div class="flex w-full min-w-0 items-center gap-2 sm:w-auto">
                    <a aria-label="Previous month" title="Previous month" href="{{ route('activities.calendar', array_merge($calendarQuery, ['month' => $month->copy()->subMonth()->format('Y-m')])) }}" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-xl text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">&lsaquo;</a>
                    <h2 class="min-w-0 flex-1 truncate text-center text-lg font-bold text-slate-900 sm:min-w-36 sm:flex-initial">{{ $month->format('F Y') }}</h2>
                    <a aria-label="Next month" title="Next month" href="{{ route('activities.calendar', array_merge($calendarQuery, ['month' => $month->copy()->addMonth()->format('Y-m')])) }}" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-xl text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">&rsaquo;</a>
                    <a href="{{ route('activities.calendar', array_merge($calendarQuery, ['month' => now()->format('Y-m')])) }}" class="ml-1 inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-900 transition hover:bg-orange-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700">Today</a>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px] font-medium text-slate-600">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-orange-600"></span>Pending</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-sky-600"></span>Ongoing</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Completed</span>
                </div>
            </div>

            <div class="overflow-x-auto overscroll-x-contain">
            <div class="min-w-[700px] sm:min-w-0">
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-100 text-center text-[10px] font-bold uppercase tracking-wide text-slate-700 sm:text-xs">
                @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $weekday)
                    <div class="border-r border-slate-200 px-1 py-2 last:border-r-0"><span class="hidden sm:inline">{{ $weekday }}</span><span class="sm:hidden">{{ substr($weekday, 0, 1) }}</span></div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach($calendarCells as $week)
                    @foreach($week as $day)
                        @if($day)
                            @php
                                $dayDate = $month->copy()->day($day);
                                $isToday = $dayDate->isSameDay($today);
                            @endphp
                            <div class="min-h-24 border-b border-r border-slate-200 p-1.5 sm:min-h-36 sm:p-2 {{ $isToday ? 'bg-amber-50' : 'bg-white' }}">
                                <div class="mb-1 flex justify-center sm:justify-end">
                                    <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-full px-1 text-xs {{ $isToday ? 'bg-orange-800 font-bold text-white ring-2 ring-orange-200' : 'text-slate-700' }}">{{ $day }}</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach($eventsByDay[$day] ?? [] as $activity)
                                        <span title="{{ $activity->title }}" aria-label="{{ $activity->status }} activity: {{ $activity->title }}" @class([
                                            'mx-auto block h-2 w-2 rounded-full sm:hidden',
                                            'bg-orange-600' => $activity->status === 'Pending',
                                            'bg-sky-600' => $activity->status === 'Ongoing',
                                            'bg-emerald-500' => $activity->status === 'Completed',
                                        ])></span>
                                        <a href="{{ route('activity-monitor.index') }}" title="{{ $activity->title }} · {{ $activity->organization_name }} · {{ $activity->venue ?: 'Venue not set' }} · {{ $activity->status }} · {{ $activity->start_time ? substr((string) $activity->start_time, 0, 5) : 'Time not set' }}{{ $activity->end_time ? '–' . substr((string) $activity->end_time, 0, 5) : '' }}" class="hidden overflow-hidden rounded-md border border-l-4 px-1.5 py-1 text-[9px] leading-3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-orange-700 sm:block sm:text-[10px] sm:leading-tight {{ $statusClasses[$activity->status] ?? 'border-l-slate-400 bg-slate-50 text-slate-800' }}">
                                            <span class="block truncate font-semibold">{{ $activity->title }}</span>
                                            <span class="block truncate">{{ $activity->organization_name }}</span>
                                            <span class="hidden truncate sm:block">{{ $activity->venue ?: 'Venue not set' }}</span>
                                            <span class="block truncate font-semibold">{{ $activity->status }}</span>
                                            <span class="block truncate">{{ $activity->start_time ? substr((string) $activity->start_time, 0, 5) : 'Time not set' }}{{ $activity->end_time ? '–' . substr((string) $activity->end_time, 0, 5) : '' }}</span>
                                            @if($activity->planned_date)
                                                <span class="block truncate">Planned: {{ $calendarDateLabel($activity->planned_date, $activity->planned_end_date) }}</span>
                                            @endif
                                            @if($activity->actual_date && (! $activity->planned_date || ! $activity->actual_date->isSameDay($activity->planned_date) || $activity->actual_end_date?->toDateString() !== $activity->planned_end_date?->toDateString()))
                                                <span class="block truncate">Actual: {{ $calendarDateLabel($activity->actual_date, $activity->actual_end_date) }}</span>
                                            @endif
                                        </a>
                                        @if($activity->conflicting_activity)
                                            <details class="mt-1 text-[10px] leading-tight">
                                                <summary class="min-h-8 w-fit cursor-pointer rounded-md bg-rose-100 px-2 py-1 font-bold text-rose-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700">Potential conflict</summary>
                                                <p class="mt-1 rounded border border-rose-200 bg-white p-1.5 text-rose-900">
                                                    @if($activity->conflict_details_visible)
                                                        {{ $activity->conflicting_activity->title }} · {{ $activity->conflicting_activity->user?->org_name ?: $activity->conflicting_activity->user?->name }} · {{ $activity->conflicting_activity->date_range_label }}
                                                    @else
                                                        Another organization's reservation overlaps at this venue.
                                                    @endif
                                                </p>
                                            </details>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div aria-hidden="true" class="min-h-24 border-b border-r border-slate-200 bg-slate-50/70 sm:min-h-36"></div>
                        @endif
                    @endforeach
                @endforeach
            </div>
            </div>
            </div>

            <div class="border-t border-slate-200 bg-slate-50/60 p-3 sm:hidden">
                <h2 class="mb-3 text-base font-bold text-slate-900">Activities this month</h2>
                @if(count($eventsByDay))
                    <div class="space-y-2">
                        @foreach(collect($eventsByDay)->sortKeys() as $dayNumber => $dayActivities)
                            @php($dayDate = $month->copy()->day($dayNumber))
                            @foreach($dayActivities as $activity)
                                <a href="{{ route('activity-monitor.index') }}" class="block rounded-xl border border-l-4 border-slate-200 p-3 shadow-sm {{ $statusClasses[$activity->status] ?? 'border-l-slate-400 bg-slate-50 text-slate-800' }}">
                                    <div class="mb-1 flex items-baseline gap-2 text-xs font-semibold">
                                        <span>{{ $dayDate->format('j') }}</span>
                                        <span>{{ $dayDate->format('D') }}</span>
                                    </div>
                                    <p class="break-words text-sm font-bold">{{ $activity->title }}</p>
                                    <p class="mt-1 break-words text-xs">{{ $activity->organization_name }} · {{ $activity->status }}</p>
                                    <p class="mt-1 break-words text-xs">{{ $activity->venue ?: 'Venue not set' }}</p>
                                    <p class="mt-1 text-xs">{{ $activity->start_time ? substr((string) $activity->start_time, 0, 5) . ($activity->end_time ? '–' . substr((string) $activity->end_time, 0, 5) : '') : 'Time not set' }}</p>
                                    @if($activity->planned_date)<p class="mt-1 text-xs">Planned: {{ $calendarDateLabel($activity->planned_date, $activity->planned_end_date) }}</p>@endif
                                    @if($activity->actual_date && (! $activity->planned_date || ! $activity->actual_date->isSameDay($activity->planned_date) || $activity->actual_end_date?->toDateString() !== $activity->planned_end_date?->toDateString()))<p class="mt-1 text-xs">Actual: {{ $calendarDateLabel($activity->actual_date, $activity->actual_end_date) }}</p>@endif
                                </a>
                                @if($activity->conflicting_activity)
                                    <details class="mt-1 text-xs">
                                        <summary class="w-fit cursor-pointer rounded bg-rose-100 px-2 py-1 font-bold text-rose-800">Potential conflict</summary>
                                        <p class="mt-1 rounded border border-rose-200 bg-white p-2 text-rose-900">
                                            @if($activity->conflict_details_visible)
                                                {{ $activity->conflicting_activity->title }} · {{ $activity->conflicting_activity->user?->org_name ?: $activity->conflicting_activity->user?->name }} · {{ $activity->conflicting_activity->date_range_label }}
                                            @else
                                                Another organization's reservation overlaps at this venue.
                                            @endif
                                        </p>
                                    </details>
                                @endif
                            @endforeach
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500">No activities scheduled this month.</p>
                @endif
            </div>
        </section>
    </main>
</x-app-layout>