<x-app-layout>
<div class="mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Activity Monitoring</h2>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.gpoa.index') }}" class="px-3 py-1 bg-orange-500 text-white text-xs rounded hover:bg-orange-600">GPOA Review</a>
            <a href="{{ route('admin.activities.export', ['format'=>'excel']) }}?{{ http_build_query(request()->all()) }}"
               class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">Export CSV</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.activities') }}" class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <input type="text" name="search" placeholder="Search title/venue..." value="{{ request('search') }}"
                   class="border rounded px-3 py-2 text-sm col-span-2 md:col-span-1"/>
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">All Status</option>
                @foreach(['pending','approved','in_progress','awaiting_report','report_submitted','closed','rejected'] as $s)
                <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ str_replace('_',' ',ucfirst($s)) }}</option>
                @endforeach
            </select>
            <select name="organization" class="border rounded px-3 py-2 text-sm">
                <option value="">All Organizations</option>
                @foreach($organizations as $org)
                <option value="{{ $org->id }}" {{ request('organization')==$org->id?'selected':'' }}>{{ $org->org_name ?? $org->name }}</option>
                @endforeach
            </select>
            <select name="category" class="border rounded px-3 py-2 text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ request('category')==$cat?'selected':'' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded text-sm hover:bg-sky-700">Filter</button>
            <a href="{{ route('admin.activities') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">Reset</a>
        </div>
    </form>
</div>

@if(isset($stats))
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">Total</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['total'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">All activities</p>
    </div>
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">Pending</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['pending'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">Awaiting review</p>
    </div>
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">Active/Closed</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['approved'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">Completed activities</p>
    </div>
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">Rejected</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['rejected'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">Declined activities</p>
    </div>
</div>
@endif

<div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
    <table class="w-full text-sm min-w-[1000px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left text-gray-500">Activity</th>
                <th class="p-3 text-left text-gray-500">Organization</th>
                <th class="p-3 text-left text-gray-500">Source</th>
                <th class="p-3 text-left text-gray-500">GPOA</th>
                <th class="p-3 text-left text-gray-500">Category</th>
                <th class="p-3 text-left text-gray-500">Date</th>
                <th class="p-3 text-left text-gray-500">Venue</th>
                <th class="p-3 text-left text-gray-500">Files</th>
                <th class="p-3 text-left text-gray-500">Status</th>
                <th class="p-3 text-center text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $activity)
            <tr class="border-b last:border-0 hover:bg-gray-50">
                <td class="p-3 font-medium max-w-[140px] truncate" title="{{ $activity->title }}">{{ $activity->title }}</td>
                <td class="p-3">{{ $activity->user->org_name ?? $activity->user->name ?? '—' }}</td>
                <td class="p-3"><span class="text-xs text-slate-600 font-semibold">Activity Request</span></td>
                <td class="p-3">
                    @if($activity->gpoa)
                    <span class="text-xs font-semibold">{{ $activity->gpoa->term }} / SY {{ $activity->gpoa->school_year }}</span>
                    @if($activity->gpoa->college)
                    <span class="block text-xs text-gray-500">{{ $activity->gpoa->college }}</span>
                    @endif
                    @else
                    <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="p-3">
                    @if($activity->category)
                    <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $activity->category }}</span>
                    @else — @endif
                </td>
                <td class="p-3">{{ $activity->date->format('M d, Y') }}</td>
                <td class="p-3">{{ $activity->venue }}</td>
                <td class="p-3">
                    @php
                        $existingFiles = 0;
                        if ($activity->communication_letter) { $existingFiles++; }
                        if ($activity->report) { $existingFiles++; }

                        $reportBadgeClasses = [
                            'pending' => 'bg-orange-100 text-orange-700',
                            'needs_revision' => 'bg-amber-100 text-amber-700',
                            'approved' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ];

                        $reportBadgeText = [
                            'pending' => 'Pending Review',
                            'needs_revision' => 'Needs Revision',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ];
                    @endphp

                    @if($existingFiles > 0)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-2">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Documents</span>
                                <span class="text-[11px] font-semibold text-blue-700">{{ $existingFiles }} File{{ $existingFiles > 1 ? 's' : '' }}</span>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-3 border-b border-dashed border-gray-200 pb-2">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-800">Communication</div>
                                        @php
                                            $communicationFileName = $activity->communication_letter ? basename($activity->communication_letter) : null;
                                            $communicationDisplayName = $communicationFileName ? (strlen($communicationFileName) > 28 ? substr($communicationFileName, 0, 25) . '...' : $communicationFileName) : '—';
                                        @endphp
                                        <div class="mt-1 text-xs text-gray-500 truncate" title="{{ $communicationFileName ?? '—' }}">
                                            {{ $communicationDisplayName }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        @if($activity->communication_letter)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">Submitted</span>
                                            <button type="button"
                                                    onclick="viewPDF('{{ route('admin.file.view', [$activity->id, 'communication']) }}', 'Communication Letter')"
                                                    class="text-xs font-semibold text-blue-700 hover:underline">View</button>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600">Not Submitted</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-800">Activity Report</div>
                                        @if($activity->report)
                                            @php
                                                $reportFileName = $activity->report->description ? $activity->report->description : 'Narrative Report';
                                                $reportDisplayName = strlen($reportFileName) > 28 ? substr($reportFileName, 0, 25) . '...' : $reportFileName;
                                            @endphp
                                            <button type="button"
                                                    onclick="viewPDF('{{ route('admin.file.view', [$activity->id, 'narrative']) }}', 'Narrative Report')"
                                                    class="mt-1 inline-block text-xs font-semibold text-green-700 hover:underline"
                                                    title="{{ $reportFileName }}">{{ $reportDisplayName }}</button>
                                        @else
                                            <div class="mt-1 text-xs text-gray-500">—</div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        @if($activity->report)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $reportBadgeClasses[$activity->report->status] ?? 'bg-gray-100 text-gray-600' }}">
                                                {{ $reportBadgeText[$activity->report->status] ?? ucfirst($activity->report->status) }}
                                            </span>
                                            <button type="button"
                                                    onclick="viewPDF('{{ route('admin.file.view', [$activity->id, 'narrative']) }}', 'Narrative Report')"
                                                    class="text-xs font-semibold text-blue-700 hover:underline">View</button>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600">Not Submitted</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex h-full min-h-[64px] items-center justify-center text-gray-400">—</div>
                    @endif
                </td>
                <td class="p-3">
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
                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $statusColors[$activity->status] ?? '' }}">
                        {{ str_replace('_', ' ', ucfirst($activity->status)) }}
                    </span>
                    @if($activity->monitoringResult)
                    <p class="text-xs text-gray-500 mt-1">{{ ucfirst(str_replace('_',' ',$activity->monitoringResult->compliance_status)) }}</p>
                    @endif
                </td>
                <td class="p-3 text-center">
                    @php
                        $hasPendingReport = $activity->report && $activity->report->status === 'pending';
                        $canRecordMonitoring = $activity->status === 'report_submitted';
                        $canApprove = $activity->status === 'pending';
                        $canReject = $activity->status === 'pending';
                    @endphp

                    <div class="flex flex-col items-center justify-center gap-2">
                        @if($canApprove)
                            <button type="button" onclick="openApproveModal({{ $activity->id }})"
                                    class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Approve</button>
                        @endif

                        @if($canReject)
                            <button type="button" onclick="openRejectModal({{ $activity->id }})"
                                    class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">Reject</button>
                        @endif

                        @if($activity->status === 'rejected')
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">Awaiting Resubmission</span>
                        @endif

                        @if($hasPendingReport)
                            <button type="button"
                                    data-report-id="{{ $activity->report->id }}"
                                    data-approve-url="{{ route('admin.reports.approve', $activity->report) }}"
                                    data-reject-url="{{ route('admin.reports.reject', $activity->report) }}"
                                    data-return-url="{{ route('admin.reports.return-for-correction', $activity->report) }}"
                                    onclick="openReportReviewModal(this)"
                                    class="px-2 py-1 bg-violet-100 text-violet-700 rounded text-xs font-semibold">Review Report</button>
                        @endif

                        @if($canRecordMonitoring)
                            <button type="button" onclick="openMonitoringModal({{ $activity->id }})"
                                    class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-semibold">Record Monitoring</button>
                        @elseif(! $hasPendingReport && ! $canApprove && ! $canReject && $activity->status !== 'rejected')
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $activities->links() }}</div>

<!-- Report Review Modal -->
<div id="reportReviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">Review Narrative Report</h3>
            <button type="button" onclick="closeReportReviewModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <p class="text-gray-600 text-sm mb-5">Choose the next action for this narrative report.</p>

        <div class="space-y-3">
            <form id="reportApproveForm" method="POST" action="">
                @csrf
                <button type="submit" class="w-full px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-semibold hover:bg-green-200">Approve</button>
            </form>

            <form id="reportRejectForm" method="POST" action="" onsubmit="return promptReportRejection(this)">
                @csrf
                <input type="hidden" name="feedback">
                <button type="submit" class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-semibold hover:bg-red-200">Reject with feedback</button>
            </form>

            <form id="reportReturnForCorrectionForm" method="POST" action="" onsubmit="return promptReturnForCorrection(this)">
                @csrf
                <input type="hidden" name="feedback">
                <button type="submit" class="w-full px-4 py-2 bg-amber-100 text-amber-700 rounded-lg text-sm font-semibold hover:bg-amber-200">Return for Correction</button>
            </form>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-3">Confirm Approval</h3>
        <p class="text-gray-600 text-sm mb-6">Approve only if the activity request details are correct and appropriate for the organization's approved GPOA.</p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeApproveModal()" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
            <a id="approveLink" href="" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">Yes, Approve</a>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-3">Reject Activity Request</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <textarea name="reject_reason" rows="4" placeholder="Reason for rejection..."
                      class="w-full border rounded-lg px-3 py-2 text-sm mb-4"></textarea>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

<!-- Monitoring Modal -->
<div id="monitoringModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-3">Record Monitoring Results</h3>
        <p class="text-gray-600 text-sm mb-4">Evaluate the activity against the organization's approved GPOA submission.</p>
        <form id="monitoringForm" method="POST" action="/admin/monitoring/0/record" data-route-base="/admin/monitoring/">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Compliance Status *</label>
                <select name="compliance_status" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Select status</option>
                    <option value="aligned">Aligned with GPOA</option>
                    <option value="partial">Partially Aligned</option>
                    <option value="not_aligned">Not Aligned</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Notes</label>
                <textarea name="compliance_notes" rows="4" placeholder="Monitoring notes..."
                          class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeMonitoringModal()" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">Save & Close Activity</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openApproveModal(id) {
    document.getElementById('approveLink').href = '/admin/approve/' + id;
    document.getElementById('approveModal').classList.remove('hidden');
}
function closeApproveModal() { document.getElementById('approveModal').classList.add('hidden'); }
function openRejectModal(id) {
    document.getElementById('rejectForm').action = '/admin/reject/' + id;
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() { document.getElementById('rejectModal').classList.add('hidden'); }
function openMonitoringModal(id) {
    const form = document.getElementById('monitoringForm');
    const baseRoute = form.getAttribute('data-route-base');
    form.action = baseRoute + id + '/record';
    document.getElementById('monitoringModal').classList.remove('hidden');
}
function closeMonitoringModal() { document.getElementById('monitoringModal').classList.add('hidden'); }
function openReportReviewModal(button) {
    const modal = document.getElementById('reportReviewModal');
    const approveForm = document.getElementById('reportApproveForm');
    const rejectForm = document.getElementById('reportRejectForm');
    const returnForm = document.getElementById('reportReturnForCorrectionForm');

    approveForm.action = button.dataset.approveUrl;
    rejectForm.action = button.dataset.rejectUrl;
    returnForm.action = button.dataset.returnUrl;
    modal.classList.remove('hidden');
}
function closeReportReviewModal() { document.getElementById('reportReviewModal').classList.add('hidden'); }
function promptReportRejection(form) {
    const feedback = window.prompt('Enter feedback for rejecting this narrative report:');
    if (!feedback || !feedback.trim()) return false;
    form.querySelector('input[name="feedback"]').value = feedback.trim();
    return true;
}
function promptReturnForCorrection(form) {
    const feedback = window.prompt('Enter the corrections needed for this narrative report:');
    if (!feedback || !feedback.trim()) return false;
    form.querySelector('input[name="feedback"]').value = feedback.trim();
    return true;
}
function returnForCorrectionPrompt(reportId) {
    const feedback = window.prompt('Enter the corrections needed for this narrative report:');
    if (!feedback || !feedback.trim()) return false;
    const form = document.getElementById('returnForCorrectionForm-' + reportId);
    if (!form) return false;
    form.querySelector('input[name="feedback"]').value = feedback.trim();
    form.submit();
    return true;
}
function viewPDF(url, title) {
    document.getElementById('pdfTitle').textContent = title;
    document.getElementById('pdfFrame').src = url;
    document.getElementById('pdfViewerModal').classList.remove('hidden');
}
function closePDFViewer() {
    document.getElementById('pdfViewerModal').classList.add('hidden');
    document.getElementById('pdfFrame').src = '';
}
</script>

@endpush

<div id="pdfViewerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full h-full max-w-6xl flex flex-col">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="text-lg font-bold" id="pdfTitle">Document Viewer</h3>
            <button onclick="closePDFViewer()" class="text-gray-500 text-3xl">&times;</button>
        </div>
        <iframe id="pdfFrame" class="flex-1 w-full" style="border:none;min-height:600px;"></iframe>
    </div>
</div>
</x-app-layout>
