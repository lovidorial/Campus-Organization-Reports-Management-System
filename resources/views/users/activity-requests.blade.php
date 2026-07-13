<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Activity Requests</h2>
        <p class="text-sm text-gray-500">Request permission to conduct activities from your approved GPOA</p>
    </div>
    <a href="{{ route('activity-requests.create') }}"
       class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">+ Request Activity</a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Total</p>
        <p class="text-2xl font-bold text-blue-600">{{ $requests->total() }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Pending</p>
        <p class="text-2xl font-bold text-yellow-500">{{ $requests->where('status','pending')->count() }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">In Progress</p>
        <p class="text-2xl font-bold text-sky-500">{{ $requests->whereIn('status',['approved','in_progress','awaiting_report'])->count() }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Closed</p>
        <p class="text-2xl font-bold text-green-500">{{ $requests->where('status','closed')->count() }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
    <table class="w-full text-sm min-w-[800px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3">Title</th>
                <th class="text-left px-4 py-3">GPOA Match</th>
                <th class="text-left px-4 py-3">Date</th>
                <th class="text-left px-4 py-3">Venue</th>
                <th class="text-left px-4 py-3">Comm Letter</th>
                <th class="text-left px-4 py-3">Report</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-center px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($requests as $req)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $req->title }}</td>
                <td class="px-4 py-3 text-xs">
                    @if($req->matchesGpoaLineItem())
                    <span class="text-green-600 font-semibold">✓ Matches GPOA</span>
                    @else
                    <span class="text-red-600 font-semibold">✗ Mismatch</span>
                    @endif
                </td>
                <td class="px-4 py-3">{{ $req->date->format('M d, Y') }}</td>
                <td class="px-4 py-3">{{ $req->venue }}</td>
                <td class="px-4 py-3">
                    @if($req->communication_letter)
                    <a href="{{ asset('storage/'.$req->communication_letter) }}" target="_blank" class="text-sky-600 text-xs hover:underline">View</a>
                    @else — @endif
                </td>
                <td class="px-4 py-3">
                    @if($req->report)
                    <a href="{{ asset('storage/'.$req->report->narrative_report) }}" target="_blank" class="text-green-600 text-xs hover:underline">View</a>
                    @else — @endif
                </td>
                <td class="px-4 py-3">
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
                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $statusColors[$req->status] ?? 'bg-gray-100' }}">
                        {{ str_replace('_', ' ', ucfirst($req->status)) }}
                    </span>
                    @if($req->reject_reason)
                    <p class="text-xs text-red-500 mt-1">{{ Str::limit($req->reject_reason, 40) }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if(in_array($req->status, ['approved','in_progress','awaiting_report']) && !$req->report)
                    <a href="{{ route('activity-reports.create', $req) }}"
                       class="px-3 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold hover:bg-green-200">Submit Report</a>
                    @elseif($req->status === 'report_submitted')
                    <span class="text-xs text-gray-500">Awaiting admin review</span>
                    @elseif($req->monitoringResult)
                    <span class="text-xs text-green-600">{{ ucfirst(str_replace('_',' ',$req->monitoringResult->compliance_status)) }}</span>
                    @else
                    <span class="text-gray-300">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                    No activity requests yet.
                    <a href="{{ route('activity-requests.create') }}" class="text-sky-600 hover:underline ml-1">Request your first activity →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $requests->links() }}</div>
</x-app-layout>
