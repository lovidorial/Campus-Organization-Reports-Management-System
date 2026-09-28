<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Activity Requests</h2>
        <p class="text-sm text-gray-500">Submit detailed activity requests under your approved GPOA.</p>
    </div>
    <a href="{{ route('activity-requests.create') }}"
       class="min-h-10 md:min-h-0 px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">+ Request Activity</a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 mb-4">
    <div class="bg-white p-3 border border-gray-100 shadow-sm">
        <p class="text-[11px] text-sky-700 font-bold uppercase tracking-wide">Total</p>
        <p class="text-2xl font-normal text-sky-800 leading-tight">{{ $grouped->sum(fn($group) => $group->count()) }}</p>
        <p class="text-[11px] text-sky-700 mt-0.5">All activity requests</p>
    </div>
    <div class="bg-white p-3 border border-gray-100 shadow-sm">
        <p class="text-[11px] text-orange-600 font-bold uppercase tracking-wide">Pending</p>
        <p class="text-2xl font-normal text-orange-600 leading-tight">{{ $grouped->sum(fn($group) => $group->where('status','pending')->count()) }}</p>
        <p class="text-[11px] text-orange-700 mt-0.5">Awaiting review</p>
    </div>
    <div class="bg-white p-3 border border-gray-100 shadow-sm">
        <p class="text-[11px] text-green-700 font-bold uppercase tracking-wide">In Progress</p>
        <p class="text-2xl font-normal text-green-700 leading-tight">{{ $grouped->sum(fn($group) => $group->whereIn('status',['approved','in_progress','awaiting_report'])->count()) }}</p>
        <p class="text-[11px] text-green-700 mt-0.5">Approved activities</p>
    </div>
    <div class="bg-white p-3 border border-gray-100 shadow-sm">
        <p class="text-[11px] text-slate-600 font-bold uppercase tracking-wide">Closed</p>
        <p class="text-2xl font-normal text-slate-700 leading-tight">{{ $grouped->sum(fn($group) => $group->where('status','closed')->count()) }}</p>
        <p class="text-[11px] text-slate-600 mt-0.5">Completed activities</p>
    </div>
</div>

@if($grouped->isEmpty())
    <div class="bg-white rounded-xl border p-8 text-center text-slate-500">
        <p class="text-lg font-semibold mb-2">No activity requests yet.</p>
        <p class="text-sm mb-4">Submit your first activity request under an approved GPOA.</p>
        <a href="{{ route('activity-requests.create') }}" class="inline-flex min-h-10 items-center px-4 py-2 bg-sky-600 text-white rounded-lg text-sm">Request your first activity</a>
    </div>
@else
    <div
        x-data="{
            statusInterval: null,
            statusColors: {
                pending: 'bg-amber-100 text-amber-700',
                approved: 'bg-green-100 text-green-700',
                in_progress: 'bg-blue-100 text-blue-700',
                awaiting_report: 'bg-orange-100 text-orange-700',
                report_submitted: 'bg-indigo-100 text-indigo-700',
                closed: 'bg-green-100 text-green-700',
                rejected: 'bg-red-100 text-red-700',
            },
            formatStatus(status) {
                return status.replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase());
            },
            escapeHtml(value) {
                const element = document.createElement('div');
                element.textContent = value || '';
                return element.innerHTML;
            },
            renderActions(request, cell) {
                const reportUrl = cell.dataset.reportUrl;
                const resubmitUrl = cell.dataset.resubmitUrl;
                const feedback = this.escapeHtml(request.report_feedback);

                if (['approved', 'in_progress', 'awaiting_report'].includes(request.status) && !request.report_status) {
                    return `<a href='${reportUrl}' class='px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold hover:bg-green-200'>Submit Report</a>`;
                }

                if (request.report_status === 'needs_revision') {
                    return `<div class='text-left'>
                        <span class='text-xs text-amber-600 font-semibold'>Needs revision</span>
                        <div class='mt-1 text-[11px] text-slate-600'>${feedback}</div>
                        <a href='${reportUrl}' class='mt-2 inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold hover:bg-amber-200'>Fix & Resubmit</a>
                    </div>`;
                }

                if (request.status === 'rejected') {
                    return `<form method='POST' action='${resubmitUrl}'>
                        <input type='hidden' name='_token' value='{{ csrf_token() }}'>
                        <button type='submit' class='px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200'>Resubmit</button>
                    </form>`;
                }

                if (request.status === 'report_submitted' && request.report_status === 'rejected') {
                    return `<span class='text-xs text-red-600'>Report rejected: ${feedback}</span>`;
                }

                if (request.status === 'report_submitted' && request.report_status === 'approved') {
                    return '<span class=\'text-xs text-green-600\'>Report approved</span>';
                }

                if (request.status === 'report_submitted') {
                    return '<span class=\'text-xs text-slate-500\'>Awaiting report review</span>';
                }

                if (request.monitoring_compliance_status) {
                    return `<span class='text-xs text-green-600'>${this.escapeHtml(this.formatStatus(request.monitoring_compliance_status))}</span>`;
                }

                return '<span class=\'text-xs text-slate-400\'>—</span>';
            },
            refreshStatuses() {
                fetch('{{ route('activity-requests.statuses') }}', {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                })
                    .then(response => response.json())
                    .then(data => {
                        data.requests.forEach(request => {
                            const row = this.$root.querySelector(`[data-request-id='${request.id}']`);
                            if (!row) {
                                return;
                            }

                            const badge = row.querySelector('[data-status-badge]');
                            const actions = row.querySelector('[data-actions-cell]');
                            badge.textContent = this.formatStatus(request.status);
                            badge.className = `inline-flex rounded-full px-2 py-1 text-xs font-semibold ${this.statusColors[request.status] || 'bg-gray-100 text-gray-700'}`;
                            actions.innerHTML = this.renderActions(request, actions);
                        });
                    })
                    .catch(() => {});
            },
            init() {
                if (this.$el._statusInterval) {
                    clearInterval(this.$el._statusInterval);
                }

                this.refreshStatuses();
                this.statusInterval = setInterval(() => this.refreshStatuses(), 15000);
                this.$el._statusInterval = this.statusInterval;
            },
            destroy() {
                if (this.statusInterval) {
                    clearInterval(this.statusInterval);
                }

                this.$el._statusInterval = null;
            }
        }"
    >
    <!-- Existing rows only are updated; adding or removing rows needs a separate diffing approach. -->
    @foreach($grouped as $index => $group)
        @php
            $gpoa = optional($group->first()->gpoa ?? $group->first()->gpoaActivity?->gpoa);
            $requestedCount = $group->count();
            $approvedRequestsCount = $group->where('status', 'approved')->count();
        @endphp
        <div class="mb-6 border rounded-3xl bg-white shadow-sm">
            <button type="button" class="w-full px-5 py-4 flex items-center justify-between gap-3 text-left" onclick="this.nextElementSibling.classList.toggle('hidden')">
                <div>
                    <p class="text-sm text-slate-500">{{ $gpoa->college ?? 'Unknown College' }}</p>
                    <h3 class="text-xl font-semibold text-slate-900">{{ $gpoa->term ?? 'Unknown Term' }} / SY {{ $gpoa->school_year ?? '—' }}</h3>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $requestedCount }} requests</span>
                    <span class="ml-3 text-xs text-slate-500">Toggle</span>
                </div>
            </button>

            <div class="min-w-0 max-w-full px-5 pb-5 {{ $index > 0 ? 'hidden' : '' }}">
            <div class="min-w-0 max-w-full overflow-x-auto rounded-3xl border border-slate-200 bg-slate-50">
                <table class="w-full table-fixed text-sm min-w-[1100px]">
                    <thead class="bg-slate-100 text-slate-600 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="w-[20%] px-3 py-2 text-left">Title</th>
                            <th class="w-[11%] px-3 py-2 text-left">Category</th>
                            <th class="w-[11%] px-3 py-2 text-left">Activity Level</th>
                            <th class="w-[13%] px-3 py-2 text-left">Target Participants</th>
                            <th class="w-[11%] px-3 py-2 text-left">Est. Budget</th>
                            <th class="w-[12%] px-3 py-2 text-left">Date</th>
                            <th class="w-[12%] px-3 py-2 text-left">Status</th>
                            <th class="w-[10%] px-3 py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y bg-white">
                        @foreach($group as $req)
                            <tr class="hover:bg-slate-50" data-request-id="{{ $req->id }}">
                                <td class="px-3 py-2.5 font-medium text-slate-900" title="{{ $req->title }}">
                                    <div class="flex flex-wrap items-center gap-1">
                                        <a href="{{ route('activity-requests.show', $req) }}" class="truncate text-sky-800 hover:underline">{{ $req->title }}</a>
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500">
                                        <span>{{ $req->venue ?? '—' }}</span>
                                        <x-venue-status-badge :venue="$req->venueRecord" />
                                    </div>
                                    @if($req->programFlows->isNotEmpty())
                                        <details class="mt-2 font-normal">
                                            <summary class="cursor-pointer text-xs font-semibold text-sky-700">Program Flow ({{ $req->programFlows->count() }})</summary>
                                            <div class="mt-2 overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                                <table class="w-full min-w-[420px] text-xs">
                                                    <thead class="bg-slate-50 text-slate-500">
                                                        <tr>
                                                            <th class="px-2 py-1.5 text-left">Time</th>
                                                            <th class="px-2 py-1.5 text-left">Flow</th>
                                                            <th class="px-2 py-1.5 text-left">Person in Charge</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @foreach($req->programFlows as $programFlow)
                                                            <tr>
                                                                <td class="px-2 py-1.5">{{ $programFlow->time }}</td>
                                                                <td class="px-2 py-1.5">{{ $programFlow->flow }}</td>
                                                                <td class="px-2 py-1.5">{{ $programFlow->person_in_charge }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-slate-700 truncate">{{ $req->category ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-slate-700 truncate">{{ $req->activity_level ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-slate-700 truncate">{{ $req->target_participants ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap">₱ {{ number_format((float) ($req->estimated_budget ?? 0), 2) }}</td>
                                <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap">{{ $req->date ? $req->date_range_label : '—' }}</td>
                                <td class="px-3 py-2.5" data-status-cell>
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-amber-100 text-amber-700',
                                            'approved' => 'bg-green-100 text-green-700',
                                            'in_progress' => 'bg-blue-100 text-blue-700',
                                            'awaiting_report' => 'bg-orange-100 text-orange-700',
                                            'report_submitted' => 'bg-indigo-100 text-indigo-700',
                                            'closed' => 'bg-green-100 text-green-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                        ];
                                    @endphp
                                    <span data-status-badge class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ str_replace('_', ' ', ucfirst($req->status)) }}
                                    </span>
                                </td>
                                <td
                                    class="px-3 py-2.5 text-center"
                                    data-actions-cell
                                    data-report-url="{{ route('activity-reports.create', $req) }}"
                                    data-resubmit-url="{{ route('activity-requests.resubmit', $req) }}"
                                >
                                    @if(in_array($req->status, ['approved','in_progress','awaiting_report']) && !$req->report)
                                        <a href="{{ route('activity-reports.create', $req) }}" class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold hover:bg-green-200">Submit Report</a>
                                    @elseif($req->report?->status === 'needs_revision')
                                        <div class="text-left">
                                            <span class="text-xs text-amber-600 font-semibold" title="{{ $req->report->feedback }}">Needs revision</span>
                                            <div class="mt-1 text-[11px] text-slate-600" title="{{ $req->report->feedback }}">{{ Str::limit($req->report->feedback, 80) }}</div>
                                            <a href="{{ route('activity-reports.create', $req) }}" class="mt-2 inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold hover:bg-amber-200">Fix & Resubmit</a>
                                        </div>
                                    @elseif($req->status === 'rejected')
                                        <form method="POST" action="{{ route('activity-requests.resubmit', $req) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200">Resubmit</button>
                                        </form>
                                    @elseif($req->status === 'report_submitted' && $req->report?->status === 'rejected')
                                        <span class="text-xs text-red-600" title="{{ $req->report->feedback }}">Report rejected: {{ $req->report->feedback }}</span>
                                    @elseif($req->status === 'report_submitted' && $req->report?->status === 'approved')
                                        <span class="text-xs text-green-600">Report approved</span>
                                    @elseif($req->status === 'report_submitted')
                                        <span class="text-xs text-slate-500">Awaiting report review</span>
                                    @elseif($req->monitoringResult)
                                        <span class="text-xs text-green-600">{{ ucfirst(str_replace('_',' ',$req->monitoringResult->compliance_status)) }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach

    </div>
@endif
</x-app-layout>
