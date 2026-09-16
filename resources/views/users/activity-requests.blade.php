<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Activity Requests</h2>
        <p class="text-sm text-gray-500">Submit detailed activity requests under your approved GPOA.</p>
    </div>
    <a href="{{ route('activity-requests.create') }}"
       class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">+ Request Activity</a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Total</p>
        <p class="text-2xl font-bold text-slate-500">{{ $grouped->sum(fn($group) => $group->count()) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Pending</p>
        <p class="text-2xl font-bold text-slate-500">{{ $grouped->sum(fn($group) => $group->where('status','pending')->count()) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">In Progress</p>
        <p class="text-2xl font-bold text-slate-500">{{ $grouped->sum(fn($group) => $group->whereIn('status',['approved','in_progress','awaiting_report'])->count()) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Closed</p>
        <p class="text-2xl font-bold text-slate-500">{{ $grouped->sum(fn($group) => $group->where('status','closed')->count()) }}</p>
    </div>
</div>

@if($grouped->isEmpty())
    <div class="bg-white rounded-xl border p-8 text-center text-slate-500">
        <p class="text-lg font-semibold mb-2">No activity requests yet.</p>
        <p class="text-sm mb-4">Submit your first activity request under an approved GPOA.</p>
        <a href="{{ route('activity-requests.create') }}" class="inline-flex px-4 py-2 bg-sky-600 text-white rounded-lg text-sm">Request your first activity</a>
    </div>
@else
    <div
        x-data="{
            statusInterval: null,
            statusColors: {
                pending: 'bg-yellow-100 text-yellow-700',
                approved: 'bg-blue-100 text-blue-700',
                in_progress: 'bg-sky-100 text-sky-700',
                awaiting_report: 'bg-orange-100 text-orange-700',
                report_submitted: 'bg-purple-100 text-purple-700',
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

            <div class="px-5 pb-5 {{ $index > 0 ? 'hidden' : '' }}">
            <div class="overflow-x-auto rounded-3xl border border-slate-200 bg-slate-50">
                <table class="w-full text-sm min-w-[900px]">
                    <thead class="bg-slate-100 text-slate-600 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-left">Activity Level</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y bg-white">
                        @foreach($group as $req)
                            <tr class="hover:bg-slate-50" data-request-id="{{ $req->id }}">
                                <td class="px-4 py-4 font-medium text-slate-900">{{ $req->title }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $req->category ?? '—' }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $req->activity_level ?? '—' }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $req->date ? $req->date_range_label : '—' }}</td>
                                <td class="px-4 py-4" data-status-cell>
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            'approved' => 'bg-blue-100 text-blue-700',
                                            'in_progress' => 'bg-sky-100 text-sky-700',
                                            'awaiting_report' => 'bg-orange-100 text-orange-700',
                                            'report_submitted' => 'bg-purple-100 text-purple-700',
                                            'closed' => 'bg-green-100 text-green-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                        ];
                                    @endphp
                                    <span data-status-badge class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ str_replace('_', ' ', ucfirst($req->status)) }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-4 text-center"
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
