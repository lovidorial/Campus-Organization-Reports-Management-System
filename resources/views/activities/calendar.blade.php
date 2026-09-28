<x-app-layout>
    @php
        $statusClasses = [
            'approved' => 'border-l-green-600 bg-green-50 text-green-900',
            'in_progress' => 'border-l-blue-600 bg-blue-50 text-blue-900',
            'closed' => 'border-l-slate-500 bg-slate-100 text-slate-700',
        ];
        $selectedVenue = $selectedVenue ? (int) $selectedVenue : null;
    @endphp

    <main class="mx-auto max-w-7xl space-y-4 p-3 sm:p-5">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Activity Calendar</h1>
                <p class="mt-1 text-sm text-slate-500">Approved, in-progress, and closed activity schedules.</p>
            </div>
            <form method="GET" action="{{ route('activities.calendar') }}" class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <div class="w-full sm:w-auto">
                    <label for="venue-filter" class="mb-1 block text-xs font-semibold text-slate-600">Venue</label>
                    <select id="venue-filter" name="venue" class="w-full rounded border-slate-300 py-2 text-sm sm:w-auto" onchange="this.form.submit()">
                        <option value="">All venues</option>
                        @foreach($venues as $venue)
                            <option value="{{ $venue->id }}" @selected($selectedVenue === $venue->id)>{{ $venue->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </header>

        <section class="overflow-hidden border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col items-center justify-between gap-3 border-b border-slate-200 px-3 py-3 sm:flex-row sm:flex-wrap sm:px-4">
                <div class="flex w-full min-w-0 items-center gap-2 sm:w-auto">
                    <a aria-label="Previous month" title="Previous month" href="{{ route('activities.calendar', array_filter(['month' => $month->copy()->subMonth()->format('Y-m'), 'venue' => $selectedVenue])) }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded border border-slate-300 text-lg text-slate-700 hover:bg-slate-50">&lsaquo;</a>
                    <h2 class="min-w-0 flex-1 truncate text-center text-base font-bold text-slate-900 sm:min-w-36 sm:flex-initial">{{ $month->format('F Y') }}</h2>
                    <a aria-label="Next month" title="Next month" href="{{ route('activities.calendar', array_filter(['month' => $month->copy()->addMonth()->format('Y-m'), 'venue' => $selectedVenue])) }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded border border-slate-300 text-lg text-slate-700 hover:bg-slate-50">&rsaquo;</a>
                    <a href="{{ route('activities.calendar', array_filter(['month' => now()->format('Y-m'), 'venue' => $selectedVenue])) }}" class="ml-1 shrink-0 rounded border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Today</a>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px] font-medium text-slate-600">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-green-600"></span>Approved</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-blue-600"></span>In progress</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-slate-500"></span>Closed</span>
                </div>
            </div>

            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-[10px] font-bold uppercase text-slate-500 sm:text-xs">
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
                            <div class="min-h-14 border-b border-r border-slate-200 p-1 sm:min-h-32 sm:p-2 {{ $isToday ? 'bg-amber-50/70' : 'bg-white' }}">
                                <div class="mb-1 flex justify-center sm:justify-end">
                                    <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full px-1 text-xs {{ $isToday ? 'bg-amber-500 font-bold text-white' : 'text-slate-600' }}">{{ $day }}</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach($eventsByDay[$day] ?? [] as $activity)
                                        <span title="{{ $activity->title }}" aria-label="{{ $activity->status }} activity: {{ $activity->title }}" @class([
                                            'mx-auto block h-2 w-2 rounded-full sm:hidden',
                                            'bg-green-600' => $activity->status === 'approved',
                                            'bg-blue-600' => $activity->status === 'in_progress',
                                            'bg-slate-500' => $activity->status === 'closed' || ! isset($statusClasses[$activity->status]),
                                        ])></span>
                                        <a href="{{ route('activity-requests.show', $activity) }}" title="{{ $activity->title }} · {{ $activity->venue }} · {{ $activity->start_time ? substr((string) $activity->start_time, 0, 5) : 'Time not set' }}{{ $activity->end_time ? '–' . substr((string) $activity->end_time, 0, 5) : '' }}" class="hidden overflow-hidden rounded-sm border-l-2 px-1.5 py-1 text-[10px] leading-tight sm:block sm:text-[11px] {{ $statusClasses[$activity->status] ?? 'border-l-slate-400 bg-slate-50 text-slate-800' }}">
                                            <span class="block truncate font-semibold">{{ $activity->title }}</span>
                                            <span class="hidden truncate sm:block">{{ $activity->venue }}</span>
                                            <span class="block truncate">{{ $activity->start_time ? substr((string) $activity->start_time, 0, 5) : 'Time not set' }}{{ $activity->end_time ? '–' . substr((string) $activity->end_time, 0, 5) : '' }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div aria-hidden="true" class="min-h-14 border-b border-r border-slate-200 bg-slate-50/60 sm:min-h-32"></div>
                        @endif
                    @endforeach
                @endforeach
            </div>

            <div class="border-t border-slate-200 p-3 sm:hidden">
                <h2 class="mb-3 text-base font-bold text-slate-900">Activities this month</h2>
                @if(count($eventsByDay))
                    <div class="space-y-2">
                        @foreach(collect($eventsByDay)->sortKeys() as $dayNumber => $dayActivities)
                            @php($dayDate = $month->copy()->day($dayNumber))
                            @foreach($dayActivities as $activity)
                                <a href="{{ route('activity-requests.show', $activity) }}" class="block rounded border border-l-4 border-slate-200 p-3 {{ $statusClasses[$activity->status] ?? 'border-l-slate-400 bg-slate-50 text-slate-800' }}">
                                    <div class="mb-1 flex items-baseline gap-2 text-xs font-semibold">
                                        <span>{{ $dayDate->format('j') }}</span>
                                        <span>{{ $dayDate->format('D') }}</span>
                                    </div>
                                    <p class="break-words text-sm font-bold">{{ $activity->title }}</p>
                                    <p class="mt-1 break-words text-xs">{{ $activity->venue ?? 'Venue not set' }}</p>
                                    <p class="mt-1 text-xs">{{ $activity->start_time ? substr((string) $activity->start_time, 0, 5) . ($activity->end_time ? '–' . substr((string) $activity->end_time, 0, 5) : '') : 'Time not set' }}</p>
                                </a>
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