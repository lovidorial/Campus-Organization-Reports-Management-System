<x-app-layout>
<div class="mb-6">
    <a href="{{ route('admin.gpoa.show', $gpoa) }}" class="text-sky-600 text-sm hover:underline">← Back to GPOA</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">Summary Report</h2>
    <p class="text-sm text-gray-500">{{ $gpoa->user->org_name ?? $gpoa->user->name }} — {{ $gpoa->term }} / SY {{ $gpoa->school_year }}</p>
</div>

<div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
    Showing approved and completed activities only ({{ $activityRequests->count() }} of {{ $totalRequestCount }} total requests). Pending and rejected activity requests are excluded from this summary.
</div>

<div class="bg-white rounded-xl shadow-sm border overflow-x-auto mb-6">
    <table class="w-full text-sm min-w-[1200px]">
        <thead class="bg-gray-50 border-b">
            <tr>
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
                @php
                    $statusColors = [
                        'approved' => 'bg-blue-100 text-blue-700',
                        'in_progress' => 'bg-sky-100 text-sky-700',
                        'awaiting_report' => 'bg-orange-100 text-orange-700',
                        'report_submitted' => 'bg-purple-100 text-purple-700',
                        'closed' => 'bg-green-100 text-green-700',
                    ];
                @endphp
                <tr class="border-b align-top">
                    <td class="p-3 font-medium">{{ $req->title }}</td>
                    <td class="p-3">{{ $req->category ?? '—' }}</td>
                    <td class="p-3">{{ optional($req->date)->format('M d, Y') ?? '—' }}</td>
                    <td class="p-3">{{ $req->venue ?? '—' }}</td>
                    <td class="p-3">
                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ str_replace('_', ' ', ucfirst($req->status)) }}
                        </span>
                    </td>
                    <td class="p-3 text-right">₱ {{ number_format((float) ($req->estimated_budget ?? 0), 2) }}</td>
                    <td class="p-3 text-center">
                        @if($req->communication_letter)
                            <a href="{{ asset('storage/'.$req->communication_letter) }}" target="_blank" class="text-sky-600 text-xs font-semibold hover:underline">View</a>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        @if($req->report)
                            <a href="{{ asset('storage/'.$req->report->narrative_report) }}" target="_blank" class="text-sky-600 text-xs font-semibold hover:underline">View</a>
                        @else
                            <span class="text-xs text-slate-400">Not yet submitted</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-6 text-center text-slate-500">No approved or completed activities under this GPOA yet.</td>
                </tr>
            @endforelse
        </tbody>
        @if($activityRequests->isNotEmpty())
        <tfoot>
            <tr class="bg-gray-50 border-t font-semibold">
                <td class="p-3" colspan="5">Total</td>
                <td class="p-3 text-right">₱ {{ number_format((float) $totalBudget, 2) }}</td>
                <td class="p-3" colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
</x-app-layout>
