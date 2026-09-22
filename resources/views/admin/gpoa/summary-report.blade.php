<x-app-layout>
<div class="mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Summary Report</h2>
            <p class="text-sm text-gray-500">Approved and completed activity requests</p>
        </div>
        <a href="{{ route('admin.summary-report.download', request()->query()) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">Generate Report</a>
    </div>
</div>

<form method="GET" action="{{ route('admin.summary-report') }}" class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div>
            <label for="term" class="block text-xs font-semibold text-gray-600 mb-1">Term</label>
            <select id="term" name="term" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">All Terms</option>
                <option value="1st Term" {{ ($filters['term'] ?? '') === '1st Term' ? 'selected' : '' }}>1st Sem</option>
                <option value="2nd Term" {{ ($filters['term'] ?? '') === '2nd Term' ? 'selected' : '' }}>2nd Sem</option>
            </select>
        </div>
        <div>
            <label for="organization" class="block text-xs font-semibold text-gray-600 mb-1">Organization</label>
            <select id="organization" name="organization" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">All Organizations</option>
                @foreach($organizations as $organization)
                    <option value="{{ $organization }}" {{ ($filters['organization'] ?? '') === $organization ? 'selected' : '' }}>{{ $organization }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="category" class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
            <select id="category" name="category" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ ($filters['category'] ?? '') === $category ? 'selected' : '' }}>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date_from" class="block text-xs font-semibold text-gray-600 mb-1">Date from</label>
            <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label for="date_to" class="block text-xs font-semibold text-gray-600 mb-1">Date to</label>
            <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded text-sm hover:bg-sky-700">Filter</button>
    </div>
</form>

<div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
    Showing {{ $activityRequests->count() }} approved or completed activities.
</div>

<div class="mb-6 bg-white rounded-xl shadow-sm border overflow-x-auto">
    <div class="px-4 py-3 border-b bg-gray-50">
        <h3 class="font-semibold text-gray-800">Category Summary</h3>
        <p class="text-xs text-gray-500">Activities, participants, and estimated budgets in the filtered report.</p>
    </div>
    <table class="w-full text-sm min-w-[640px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left">Category</th>
                <th class="p-3 text-right">Activity Count</th>
                <th class="p-3 text-right">Total Participants</th>
                <th class="p-3 text-right">Total Estimated Budget</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categorySummary as $summary)
                <tr class="border-b">
                    <td class="p-3">{{ $summary['category'] }}</td>
                    <td class="p-3 text-right">{{ $summary['activity_count'] }}</td>
                    <td class="p-3 text-right">{{ number_format($summary['participants']) }}</td>
                    <td class="p-3 text-right">PHP {{ number_format($summary['budget'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-5 text-center text-slate-500">No category data for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
    <table class="w-full text-sm min-w-[1200px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left">Organization</th>
                <th class="p-3 text-left">Term / School Year</th>
                <th class="p-3 text-left">Title</th>
                <th class="p-3 text-left">Category</th>
                <th class="p-3 text-left">Date</th>
                <th class="p-3 text-left">Venue</th>
                <th class="p-3 text-left">Status</th>
                <th class="p-3 text-right">Estimated Budget</th>
                <th class="p-3 text-center">Communication Letter</th>
                <th class="p-3 text-center">Narrative Report</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activityRequests as $req)
                @php $gpoa = $req->gpoaActivity?->gpoa ?? $req->gpoa; @endphp
                <tr class="border-b align-top">
                    <td class="p-3">{{ $req->user->org_name ?? $req->user->name ?? '—' }}</td>
                    <td class="p-3">{{ $gpoa?->term ?? '—' }} / SY {{ $gpoa?->school_year ?? '—' }}</td>
                    <td class="p-3 font-medium">{{ $req->title }}</td>
                    <td class="p-3">{{ $req->category ?? '—' }}</td>
                    <td class="p-3">{{ $req->date ? $req->date_range_label : '—' }}</td>
                    <td class="p-3">{{ $req->venue ?? '—' }}</td>
                    <td class="p-3"><span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold bg-green-100 text-green-700">{{ str_replace('_', ' ', ucfirst($req->status)) }}</span></td>
                    <td class="p-3 text-right">PHP {{ number_format((float) ($req->estimated_budget ?? 0), 2) }}</td>
                    <td class="p-3 text-center">
                        @if($req->communication_letter)<a href="{{ asset('storage/'.$req->communication_letter) }}" target="_blank" class="text-sky-600 text-xs font-semibold hover:underline">View</a>@else<span class="text-xs text-slate-400">—</span>@endif
                    </td>
                    <td class="p-3 text-center">
                        @if($req->report)
                            <button type="button"
                                    onclick="openSummaryReport('{{ route('admin.file.view', [$req->id, 'narrative']) }}', 'Narrative Report')"
                                    class="text-sky-600 text-xs font-semibold hover:underline">View Report</button>
                        @else
                            <span class="text-xs text-slate-400">Not yet submitted</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="p-6 text-center text-slate-500">No activities match the selected filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 border-t font-semibold">
                <td class="p-3" colspan="7">Total</td>
                <td class="p-3 text-right">PHP {{ number_format((float) $totalBudget, 2) }}</td>
                <td class="p-3" colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

@push('scripts')
<script>
function openSummaryReport(url, title) {
    document.getElementById('summaryReportTitle').textContent = title;
    document.getElementById('summaryReportFrame').src = url;
    document.getElementById('summaryReportModal').classList.remove('hidden');
}

function closeSummaryReport() {
    document.getElementById('summaryReportModal').classList.add('hidden');
    document.getElementById('summaryReportFrame').src = '';
}
</script>
@endpush

<div id="summaryReportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full h-full max-w-6xl flex flex-col">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="text-lg font-bold" id="summaryReportTitle">Narrative Report</h3>
            <button type="button" onclick="closeSummaryReport()" class="text-gray-500 text-3xl" aria-label="Close report viewer">&times;</button>
        </div>
        <iframe id="summaryReportFrame" class="flex-1 w-full" style="border:none;min-height:600px;" title="Narrative report viewer"></iframe>
    </div>
</div>
</x-app-layout>
