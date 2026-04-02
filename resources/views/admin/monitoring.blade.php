<x-app-layout>
<div class="mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Activity Monitoring</h2>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.activities.export', ['format'=>'excel']) }}?{{ http_build_query(request()->all()) }}"
               class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">Export CSV</a>
            <button onclick="window.print()" class="px-3 py-1 bg-gray-600 text-white text-xs rounded">Print</button>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.activities') }}" class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <input type="text" name="search" placeholder="Search title/venue..." value="{{ request('search') }}"
                   class="border rounded px-3 py-2 text-sm col-span-2 md:col-span-1"/>
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="pending"  {{ request('status')=='pending'  ? 'selected':'' }}>Pending</option>
                <option value="approved" {{ request('status')=='approved' ? 'selected':'' }}>Approved</option>
                <option value="rejected" {{ request('status')=='rejected' ? 'selected':'' }}>Rejected</option>
            </select>
            <select name="organization" class="border rounded px-3 py-2 text-sm">
                <option value="">All Organizations</option>
                @foreach($organizations as $org)
                <option value="{{ $org->id }}" {{ request('organization')==$org->id ? 'selected':'' }}>{{ $org->org_name ?? $org->name }}</option>
                @endforeach
            </select>
            <select name="category" class="border rounded px-3 py-2 text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ request('category')==$cat ? 'selected':'' }}>{{ $cat }}</option>
                @endforeach
            </select>
            <select name="term" class="border rounded px-3 py-2 text-sm">
                <option value="">All Terms</option>
                @foreach($terms as $t)
                <option value="{{ $t }}" {{ request('term')==$t ? 'selected':'' }}>{{ $t }}</option>
                @endforeach
            </select>
            <select name="school_year" class="border rounded px-3 py-2 text-sm">
                <option value="">All SY</option>
                @foreach($schoolYears as $sy)
                <option value="{{ $sy }}" {{ request('school_year')==$sy ? 'selected':'' }}>{{ $sy }}</option>
                @endforeach
            </select>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded text-sm hover:bg-sky-700">Filter</button>
            <a href="{{ route('admin.activities') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Reset</a>
        </div>
    </form>
</div>

<!-- Stats -->
@if(isset($stats))
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-lg shadow-sm border"><p class="text-xs text-gray-500 uppercase">Total</p><p class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</p></div>
    <div class="bg-white p-4 rounded-lg shadow-sm border"><p class="text-xs text-gray-500 uppercase">Pending</p><p class="text-2xl font-bold text-yellow-500">{{ $stats['pending'] }}</p></div>
    <div class="bg-white p-4 rounded-lg shadow-sm border"><p class="text-xs text-gray-500 uppercase">Approved</p><p class="text-2xl font-bold text-green-500">{{ $stats['approved'] }}</p></div>
    <div class="bg-white p-4 rounded-lg shadow-sm border"><p class="text-xs text-gray-500 uppercase">Rejected</p><p class="text-2xl font-bold text-red-500">{{ $stats['rejected'] }}</p></div>
</div>
@endif

<!-- Table -->
<div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
    <table class="w-full text-sm min-w-[900px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left text-gray-500">Activity</th>
                <th class="p-3 text-left text-gray-500">Organization</th>
                <th class="p-3 text-left text-gray-500">Category</th>
                <th class="p-3 text-left text-gray-500">Term / SY</th>
                <th class="p-3 text-left text-gray-500">Date</th>
                <th class="p-3 text-left text-gray-500">Venue</th>
                <th class="p-3 text-left text-gray-500">Pax</th>
                <th class="p-3 text-left text-gray-500">Basis</th>
                <th class="p-3 text-left text-gray-500">Files</th>
                <th class="p-3 text-left text-gray-500">Status</th>
                <th class="p-3 text-center text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $activity)
            <tr class="border-b last:border-0 hover:bg-gray-50">
                <td class="p-3 font-medium max-w-[160px] truncate" title="{{ $activity->title }}">{{ $activity->title }}</td>
                <td class="p-3">
                    @if($activity->user)
                        {{ $activity->user->org_name ?? $activity->user->name }}
                    @else
                        {{ $activity->organization ?? '—' }}
                    @endif
                </td>
                <td class="p-3">
                    @if($activity->category)
                    <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $activity->category }}</span>
                    @else &mdash; @endif
                </td>
                <td class="p-3 text-xs text-gray-500">
                    {{ $activity->term ?? '—' }}<br>{{ $activity->school_year ?? '—' }}
                </td>
                <td class="p-3">{{ $activity->date->format('M d, Y') }}</td>
                <td class="p-3">{{ $activity->venue }}</td>
                <td class="p-3">{{ $activity->participants_count ?? '—' }}</td>
                <td class="p-3 text-xs">{{ $activity->basis_grading ?? '—' }}</td>
                <td class="p-3">
                    <div class="flex flex-wrap gap-1">
                        @if($activity->communication_letter)
                            <button onclick="viewPDF('{{ route('admin.file.view', [$activity->id, 'communication']) }}', 'Communication Letter')" 
                                    class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 font-semibold">View</button>
                            <a href="{{ route('admin.file.download', [$activity->id, 'communication']) }}" 
                               class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 font-semibold">DL</a>
                        @endif
                        @if($activity->narrative_report)
                            <button onclick="viewPDF('{{ route('admin.file.view', [$activity->id, 'narrative']) }}', 'Narrative Report')" 
                                    class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs hover:bg-green-200 font-semibold">View</button>
                            <a href="{{ route('admin.file.download', [$activity->id, 'narrative']) }}" 
                               class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs hover:bg-green-200 font-semibold">DL</a>
                        @endif
                        @if(!$activity->communication_letter && !$activity->narrative_report) &mdash; @endif
                    </div>
                </td>
                <td class="p-3">
                    <span class="px-2 py-1 rounded-full text-xs font-bold
                        {{ $activity->status == 'pending'  ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $activity->status == 'approved' ? 'bg-green-100 text-green-700'  : '' }}
                        {{ $activity->status == 'rejected' ? 'bg-red-100 text-red-700'      : '' }}">
                        {{ ucfirst($activity->status) }}
                    </span>
                    @if($activity->status == 'rejected' && $activity->reject_reason)
                    <p class="text-xs text-red-500 mt-1" title="{{ $activity->reject_reason }}">
                        {{ Str::limit($activity->reject_reason, 30) }}
                    </p>
                    @endif
                </td>
                <td class="p-3 text-center">
                    @if($activity->status == 'pending')
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.approve', $activity->id) }}"
                           class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs hover:bg-green-200 font-semibold">Approve</a>
                        <button onclick="openRejectModal({{ $activity->id }})"
                                class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200 font-semibold">Reject</button>
                    </div>
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

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-3">Reject Activity</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection</label>
                <textarea name="reject_reason" rows="4" placeholder="Provide a reason for rejection (optional but recommended)..."
                          class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-300 focus:border-red-400"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openRejectModal(id) {
    document.getElementById('rejectForm').action = '/admin/reject/' + id;
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});

function viewPDF(url, title) {
    const modal = document.getElementById('pdfViewerModal');
    const iframe = document.getElementById('pdfFrame');
    const titleEl = document.getElementById('pdfTitle');
    
    titleEl.textContent = title;
    iframe.src = url;
    modal.classList.remove('hidden');
}

function closePDFViewer() {
    document.getElementById('pdfViewerModal').classList.add('hidden');
    document.getElementById('pdfFrame').src = '';
}

document.getElementById('pdfViewerModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePDFViewer();
});
</script>
@endpush

<!-- PDF Viewer Modal -->
<div id="pdfViewerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full h-full max-w-6xl flex flex-col">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800" id="pdfTitle">Document Viewer</h3>
            <button onclick="closePDFViewer()" class="text-gray-500 hover:text-gray-700 text-3xl font-light">&times;</button>
        </div>
        <iframe id="pdfFrame" class="flex-1 w-full" style="border: none; min-height: 600px;"></iframe>
    </div>
</div>
</x-app-layout>
