<x-app-layout>
    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Narrative Report Tracking</h1>
                <p class="mt-1 text-sm text-slate-600">Organization-wide report status and deadline monitoring.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Monitoring Dashboard</a>
        </header>

        <section class="flex flex-wrap items-center justify-between gap-3 border-y border-slate-200 py-3" aria-label="Narrative report totals">
            <div>
                <p class="text-xs font-semibold uppercase text-slate-500">Outstanding reports</p>
                <p class="text-2xl font-bold text-rose-700">{{ $outstandingCount }}</p>
            </div>
            <p class="text-sm text-slate-600">{{ $reports->count() }} records shown, including submitted history</p>
        </section>

        <form method="GET" action="{{ route('admin.narrative-reports.outstanding') }}" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
            <div>
                <label for="report-organization" class="mb-1 block text-xs font-semibold text-slate-600">Organization</label>
                <select id="report-organization" name="organization" class="w-full rounded border-slate-300 py-2 text-sm">
                    <option value="">All organizations</option>
                    @foreach($organizations as $organization)
                        <option value="{{ $organization['organization_id'] }}" @selected((string) ($filters['organization'] ?? '') === (string) $organization['organization_id'])>{{ $organization['organization'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-college" class="mb-1 block text-xs font-semibold text-slate-600">College</label>
                <select id="report-college" name="college" class="w-full rounded border-slate-300 py-2 text-sm">
                    <option value="">All colleges</option>
                    @foreach($colleges as $college)
                        <option value="{{ $college }}" @selected(($filters['college'] ?? '') === $college)>{{ $college }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-status" class="mb-1 block text-xs font-semibold text-slate-600">Report status</label>
                <select id="report-status" name="status" class="w-full rounded border-slate-300 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach(['Missing', 'Overdue', 'Needs revision', 'Submitted'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-school-year" class="mb-1 block text-xs font-semibold text-slate-600">School year</label>
                <select id="report-school-year" name="school_year" class="w-full rounded border-slate-300 py-2 text-sm">
                    <option value="">All school years</option>
                    @foreach($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear }}" @selected(($filters['school_year'] ?? '') === $schoolYear)>{{ $schoolYear }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-term" class="mb-1 block text-xs font-semibold text-slate-600">Term</label>
                <select id="report-term" name="term" class="w-full rounded border-slate-300 py-2 text-sm">
                    <option value="">All terms</option>
                    @foreach($terms as $term)
                        <option value="{{ $term }}" @selected(($filters['term'] ?? '') === $term)>{{ $term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5 lg:justify-end">
                <button type="submit" class="rounded bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Apply filters</button>
                <a href="{{ route('admin.narrative-reports.outstanding') }}" class="rounded border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto border-y border-slate-200 bg-white">
            <table class="min-w-[1180px] w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Organization</th>
                        <th class="px-3 py-3">College</th>
                        <th class="px-3 py-3">Activity</th>
                        <th class="px-3 py-3">End date</th>
                        <th class="px-3 py-3">Deadline</th>
                        <th class="px-3 py-3 text-right">Days overdue</th>
                        <th class="px-3 py-3">Report status</th>
                        <th class="px-3 py-3">Submitted</th>
                        <th class="px-3 py-3">Organization Head confirmed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reports as $row)
                        @php
                            $activity = $row['activity'];
                            $statusClasses = match ($row['report_status']) {
                                'Overdue' => 'bg-rose-100 text-rose-800',
                                'Needs revision' => 'bg-amber-100 text-amber-800',
                                'Submitted' => 'bg-emerald-100 text-emerald-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-3 font-medium text-slate-900">{{ $row['organization'] }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ $row['college'] }}</td>
                            <td class="px-3 py-3 text-slate-900">
                                @if($row['activity_request_id'])
                                    <a href="{{ route('admin.activity-requests.show', $row['activity_request_id']) }}" class="font-semibold text-sky-700 hover:underline">{{ $activity->title }}</a>
                                @else
                                    {{ $activity->title }}
                                @endif
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $row['term'] ?: '—' }} / {{ $row['school_year'] ?: '—' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-slate-700">{{ $row['end_date']?->format('M j, Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-slate-700">{{ $row['deadline']?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">{{ $row['days_overdue'] ?: '—' }}</td>
                            <td class="px-3 py-3"><span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $row['report_status'] }}</span></td>
                            <td class="whitespace-nowrap px-3 py-3 text-slate-700">{{ $row['submitted_at']?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ $row['signed_by_organization_head'] ? 'Confirmed' : 'Not confirmed' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">No narrative report records match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</x-app-layout>