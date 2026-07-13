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
    <div class="bg-white p-4 rounded-lg shadow-sm border"><p class="text-xs text-gray-500 uppercase">Total</p><p class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</p></div>
    <div class="bg-white p-4 rounded-lg shadow-sm border"><p class="text-xs text-gray-500 uppercase">Pending</p><p class="text-2xl font-bold text-yellow-500">{{ $stats['pending'] }}</p></div>
    <div class="bg-white p-4 rounded-lg shadow-sm border"><p class="text-xs text-gray-500 uppercase">Active/Closed</p><p class="text-2xl font-bold text-green-500">{{ $stats['approved'] }}</p></div>
    <div class="bg-white p-4 rounded-lg shadow-sm border"><p class="text-xs text-gray-500 uppercase">Rejected</p><p class="text-2xl font-bold text-red-500">{{ $stats['rejected'] }}</p></div>
</div>
@endif

<div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
    <table class="w-full text-sm min-w-[1000px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left text-gray-500">Activity</th>
                <th class="p-3 text-left text-gray-500">Organization</th>
                <th class="p-3 text-left text-gray-500">GPOA Match</th>
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
                <td class="p-3">
                    @if($activity->matchesGpoaLineItem())
                    <span class="text-green-600 text-xs font-bold">✓ Match</span>
                    @else
                    <span class="text-red-600 text-xs font-bold">✗ Mismatch</span>
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
                    <div class="flex flex-wrap gap-1">
                        @if($activity->communication_letter)
                            <button onclick="viewPDF('{{ route('admin.file.view', [$activity->id, 'communication']) }}', 'Communication Letter')"
                                    class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Comm</button>
                        @endif
                        @if($activity->report)
                            <button onclick="viewPDF('{{ route('admin.file.view', [$activity->id, 'narrative']) }}', 'Narrative Report')"
                                    class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Report</button>
                        @endif
                        @if(!$activity->communication_letter && !$activity->report) — @endif
                    </div>
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
                    @if($activity->status == 'pending')
                    <div class="flex items-center justify-center gap-1 flex-wrap">
                        <button onclick="openApproveModal({{ $activity->id }})"
                                class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Approve</button>
                        <button onclick="openRejectModal({{ $activity->id }})"
                                class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">Reject</button>
                    </div>
                    @elseif($activity->status == 'report_submitted')
                    <button onclick="openMonitoringModal({{ $activity->id }})"
                            class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-semibold">Record Monitoring</button>
                    @elseif(in_array($activity->status, ['approved','in_progress','awaiting_report']))
                    <span class="text-xs text-sky-600">Monitoring</span>
                    @else
                    <span class="text-gray-300 text-xs">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $activities->links() }}</div>

<!-- Approve Modal -->
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-3">Confirm Approval</h3>
        <p class="text-gray-600 text-sm mb-6">Approve only if this request matches the organization's approved GPOA entry.</p>
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
        <p class="text-gray-600 text-sm mb-4">Evaluate the activity against the approved GPOA line item.</p>
        <form id="monitoringForm" method="POST">
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
    document.getElementById('monitoringForm').action = '/admin/monitoring/' + id + '/record';
    document.getElementById('monitoringModal').classList.remove('hidden');
}
function closeMonitoringModal() { document.getElementById('monitoringModal').classList.add('hidden'); }
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
