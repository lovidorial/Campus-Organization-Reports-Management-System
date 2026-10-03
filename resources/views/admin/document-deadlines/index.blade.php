<x-app-layout>
    <div class="space-y-5">
        <header>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-sky-700 hover:underline">&larr; Back to Dashboard</a>
            <h1 class="mt-3 text-xl font-bold text-gray-900">Document Deadlines</h1>
            <p class="mt-1 text-sm text-gray-500">Set when narrative reports are due, by term and school year.</p>
        </header>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.document-deadlines.index') }}" class="grid gap-4 sm:grid-cols-3 sm:items-end">
                <div>
                    <label for="term" class="mb-1 block text-sm font-semibold text-gray-700">Term</label>
                    <select id="term" name="term" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach($terms->prepend($term)->unique() as $option)
                            <option value="{{ $option }}" @selected($option === $term)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="school_year" class="mb-1 block text-sm font-semibold text-gray-700">School year</label>
                    <select id="school_year" name="school_year" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach($schoolYears->prepend($schoolYear)->unique() as $option)
                            <option value="{{ $option }}" @selected($option === $schoolYear)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Load period</button>
            </form>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('admin.document-deadlines.update') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="term" value="{{ $term }}">
                <input type="hidden" name="school_year" value="{{ $schoolYear }}">

                @php
                    $selectedGraceDays = old('grace_days', $graceDays);
                    $selectedDeadlineDate = old('deadlines.activity_report', $deadlineDate?->format('Y-m-d'));
                    $cutoffStatus = ! $deadlineDate
                        ? 'Not set'
                        : ($deadlineDate->lt(now()->startOfDay()) ? 'Passed' : 'Upcoming');
                @endphp

                <h2 class="text-sm font-semibold text-gray-900">Narrative Report Deadline</h2>
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-md border border-gray-200 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label for="grace-days" class="text-sm font-semibold text-gray-800">Days allowed after the activity</label>
                            @if($graceDays !== null)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Active</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">Not set</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Narrative report is due this many days after the activity. Leave blank to disable.</p>
                        <input id="grace-days" type="number" name="grace_days" min="0" max="60" placeholder="e.g. 7" value="{{ $selectedGraceDays }}" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="rounded-md border border-gray-200 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label for="deadline-activity-report" class="text-sm font-semibold text-gray-800">Term cutoff date</label>
                            @if($cutoffStatus === 'Not set')
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">Not set</span>
                            @elseif($cutoffStatus === 'Upcoming')
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Upcoming</span>
                            @else
                                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">Passed</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Final due date for all narrative reports this term. Leave blank to disable.</p>
                        <input id="deadline-activity-report" type="date" name="deadlines[activity_report]" value="{{ $selectedDeadlineDate }}" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @if($selectedDeadlineDate && $selectedDeadlineDate < now()->toDateString())
                            <p class="mt-1 rounded-md bg-amber-50 px-2 py-1 text-[11px] text-amber-800">This date is in the past. Saving is still allowed.</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">An activity is marked Late when its narrative report is still missing after the days allowed, or after the term cutoff. Submitted reports are never Late. If neither setting is set, no activity is marked Late.</div>
                <button type="submit" class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Save deadline</button>
            </form>
        </section>
    </div>
</x-app-layout>
